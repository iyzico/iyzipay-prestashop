<?php

class IyzicocheckoutformResultModuleFrontController extends ModuleFrontController {

    public $ssl = true;
    public $display_column_left = false;

    public function initContent() {
        parent::initContent();

        $module_action = Tools::getValue('module_action');
        $action_list = array('result' => 'initResult', 'payment' => 'initPayment');

        if (isset($action_list[$module_action])) {
            $this->{$action_list[$module_action]}();
        }
    }

    public function initResult($webhook = null, $webhookToken = null) {

        IyzipayBootstrap::init();

        $iyzico = new Iyzicocheckoutform();

        $context = Context::getContext();
        $language_iso_code = $context->language->iso_code;
        $locale = ($language_iso_code == "tr") ? Iyzipay\Model\Locale::TR : Iyzipay\Model\Locale::EN;
        $cart = $context->cart;
        $error_msg = '';

        try {

            $token = Tools::getValue('token');
            if (empty($token)) {
                $error_msg = ($language_iso_code == "tr") ? 'Güvenlik token bulunamadı' : 'Token not found.';
            }

            $cart_total = 0;

            $options = new \Iyzipay\Options();
            $options->setApiKey(Configuration::get('IYZICO_FORM_LIVE_API_ID'));
            $options->setSecretKey(Configuration::get('IYZICO_FORM_LIVE_SECRET'));
            $options->setBaseUrl("https://sandbox-api.iyzipay.com");

            $request = new \Iyzipay\Request\RetrieveCheckoutFormRequest();
            $request->setLocale($locale);
            $request->setToken($token);

            if ($webhook == 'webhook'){
                $request->setToken($webhookToken);
            }


            $response = \Iyzipay\Model\CheckoutForm::retrieve($request, $options);

            if ($webhook == "webhook" && $response->getStatus() == 'failure'){
                return IyzicocheckoutformWebhookModuleFrontController::webhookHttpResponse("errorCode: ".$response->getErrorCode() ." - " . $response->getErrorMessage(), 404);
            }

            if ($webhook == "webhook"){
                $cart = new Cart($response->getBasketId());
                $order = new Order(Order::getOrderByCartId($response->getBasketId()));

                if (!empty($order->getCurrentState()) && $order->getCurrentState() == (int)Configuration::get('PS_OS_PAYMENT')){
                    return IyzicocheckoutformWebhookModuleFrontController::webhookHttpResponse("Order Exist - Sipariş zaten var.", 200);
                }

                if (!empty($order->getCurrentState()) && $order->getCurrentState() != (int)Configuration::get('PS_OS_PAYMENT')){
                    $order->setCurrentState((int)Configuration::get('PS_OS_PAYMENT'));
                    return IyzicocheckoutformWebhookModuleFrontController::webhookHttpResponse("Order Status Updated - Sipariş Durumu Ödendi yapıldı.", 200);
                }
            }

            Db::getInstance()->insert("iyzico_api_log", array(
                'id' => Tools::getValue('id'),
                'order_id' => (int) $response->getBasketId(),
                'item_id' => 0,
                'transaction_status' => '',
                'api_request' => pSQL($request->toJsonString()),
                'api_response' => '',
                'request_type' => 'get_auth',
                'note' => '',
                'created' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s'),
            ));

            $last_insert_id = Db::getInstance()->Insert_ID();

            $status = $response->getStatus();
            if (empty($status) || (!empty($status) && 'failure' == $status)) {
                throw new \Exception($response->getErrorMessage());
            }

            if ($response->getPaymentStatus() == "FAILURE") {
                throw new \Exception($response->getErrorMessage());
            }

            $basket_id = pSQL($response->getBasketId());

            if ((int) $cart->id != $basket_id) {
                $error_msg = ($language_iso_code == "tr") ? "Geçersiz istek" : "Invalid request";
            }

            $query = 'SELECT * FROM `' . _DB_PREFIX_ . 'iyzico_order_form` WHERE `order_id`= "' . $basket_id . '"';
            $order = Db::getInstance()->ExecuteS($query);

            if (!empty($order)) {
                $error_msg = ($language_iso_code == "tr") ? "Sipariş zaten var." : "Order already exists.";
            }

            $cart_total = (float) $cart->getOrderTotal(true, Cart::BOTH);
            $total = pSQL($response->getPaidPrice());
            $payment_currency = pSQL($response->getCurrency());
            $currency = new Currency((int) ($cart->id_currency));
            $iso_code = ($currency->iso_code) ? $currency->iso_code : '';

            $iyzico->validateOrder((int) $cart->id, Configuration::get('PS_OS_PAYMENT'), $cart_total, $iyzico->displayName, null, $total, (int) $currency->id, false, $cart->secure_key);

            $cart->id_customer = (int) $cart->id_customer;

            if ($cart->is_guest !== 1) {
                $cardcustomer = 'SELECT * FROM `' . _DB_PREFIX_ . 'iyzico_cart_save` WHERE `customer_id`= "' . $cart->id_customer . '"';
                if ($row = Db::getInstance()->getRow($cardcustomer)) {

                    $card_user_key = pSQL($response->GetcardUserKey());
                    $merchant_api_id = Configuration::get('IYZICO_FORM_LIVE_API_ID');
                    $customer_id = $cart->id_customer;

                    $card_update_array = array(
                        'customer_id' => $customer_id,
                        'card_key' => $card_user_key,
                        'api_key' => $merchant_api_id,
                    );
                    Db::getInstance()->update('iyzico_cart_save', $card_update_array, 'customer_id = ' . (int) $customer_id);
                } else {

                    $card_user_key = pSQL($response->GetcardUserKey());
                    $merchant_api_id = Configuration::get('IYZICO_FORM_LIVE_API_ID');
                    $customer_id = $cart->id_customer;

                    $card_update_array = array(
                        'customer_id' => $customer_id,
                        'card_key' => $card_user_key,
                        'api_key' => $merchant_api_id,
                    );

                    $cardfields = '`' . implode('`,`', array_keys($card_update_array)) . '`';
                    $cardparams = "'" . implode("','", array_values($card_update_array)) . "'";
                    $card_query = "INSERT INTO `" . _DB_PREFIX_ . "iyzico_cart_save` ({$cardfields}) VALUES ({$cardparams})";
                    Db::getInstance()->execute($card_query);
                }
            }

            if ($response->getInstallment()) {

                $order = new Order($iyzico->currentOrder);
                $current_order_id = $order->id;
                $installment_fee = abs($response->getPaidPrice() - $response->getPrice());

                $response_arr = array(
                    'order_id' => (int) $current_order_id,
                    'transaction_id' => $cart->id,
                    'installment_fee' => pSQL($installment_fee),
                    'installment_amount' => (double) $total,
                    'installment_no' => (int) pSQL($response->getInstallment()),
                    'installment_brand' => pSQL($response->getCardAssociation()),
                    'response_data' => pSQL($response->getRawResult()),
                    'created' => date('Y-m-d H:i:s'),
                    'processing_time' => pSQL($response->getSystemTime())
                );

                IyzicocheckoutformOrder::insertOrder($response_arr);

                IyzicocheckoutformOrder::updateOrderTotal($total, $current_order_id);

                IyzicocheckoutformOrder::updateOrderPayment($total, $order->reference);

                IyzicocheckoutformOrder::updateOrderInvoiceTotal($total, $current_order_id);


                $order_detail = $response->getPaymentItems();

                foreach ($order_detail as $detail) {
                    $detail_arr = array(
                        'order_id' => (int) $current_order_id,
                        'item_id' => pSQL($detail->getItemId()),
                        'payment_transaction_id' => pSQL($detail->getPaymentTransactionId()),
                        'paid_price' => pSQL($detail->getPaidPrice()),
                        'currency' => $payment_currency,
                        'total_refunded_amount' => 0,
                        'created' => date('Y-m-d H:i:s'),
                        'updated' => date('Y-m-d H:i:s'),
                    );

                    $dbFields = '`' . implode('`,`', array_keys($detail_arr)) . '`';
                    $dbParams = "'" . implode("','", array_values($detail_arr)) . "'";

                    $detail_query = "INSERT INTO `" . _DB_PREFIX_ . "iyzico_cart_detail` ({$dbFields}) VALUES ({$dbParams})";
                    $test = Db::getInstance()->execute($detail_query);


                    $update_id_array = array(
                        'order_id' => (int) $current_order_id,
                        'updated' => date('Y-m-d H:i:s'),
                    );

                    Db::getInstance()->update('iyzico_api_log', $update_id_array, 'order_id = ' . (int) $cart->id);

                    $update_array = array(
                        'order_id' => (int) $current_order_id,
                        'transaction_status' => 'success',
                        'api_response' => pSQL($response->getRawResult()),
                        'updated' => date('Y-m-d H:i:s'),
                    );


                    Db::getInstance()->update('iyzico_api_log', $update_array, 'id = ' . (int) $last_insert_id);
                }
            }

            if ($webhook == 'webhook'){
                return IyzicocheckoutformWebhookModuleFrontController::webhookHttpResponse("Order Created by Webhook - Sipariş webhook tarafından oluşturuldu.", 200);
            }

            $this->context->smarty->assign(array(
                'error' => $error_msg,
                'total' => $total,
                'currency' => $iso_code,
                'locale' => $locale,
            ));
            $this->setTemplate('order_result.tpl');
        } catch (\Exception $ex) {
            $error_msg = $ex->getMessage();
            if (!empty($error_msg)) {
                if ($language_iso_code == 'tr') {
                    $error_msg = "Bir hata oluştu, lütfen tekrar deneyin.";
                } else {
                    $error_msg = "Unknown Error, please try again";
                }
            }
            $this->context->smarty->assign(array(
                'error' => $error_msg,
            ));
            $this->setTemplate('order_result.tpl');
        }
    }

    public function initPayment() {
        $context = Context::getContext();
        $cart = $context->cart;
        $zero_total = $context->cookie->zero_total;

        $iyzico = new Iyzicocheckoutform();
        $currency = new Currency((int) ($cart->id_currency));
        $iso_code = ($currency->iso_code) ? $currency->iso_code : '';
        $cart_total = (float) $cart->getOrderTotal(true, Cart::BOTH);
        $shipping_toal = (float) $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
        $language_iso_code = $context->language->iso_code;
        if ($cart_total == $shipping_toal && $zero_total) {
            $total = 0;
            $cart_total = 0;
            $error_msg = ($language_iso_code == "tr") ? 'Alışveriş tutarı indirim tutarına eşit olamaz.' : 'Cart total cannot be equal to discount amount.';
            $this->context->smarty->assign(array(
                'error' => $error_msg,
                'total' => $total,
                'currency' => $iso_code,
            ));
            $iyzico->validateOrder((int) $cart->id, Configuration::get('PS_OS_PAYMENT'), $cart_total, $iyzico->displayName, null, $total, (int) $currency->id, false, $cart->secure_key);
            $this->setTemplate('order_result.tpl');
        }
    }

}
