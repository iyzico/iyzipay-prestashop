<?php

require_once rtrim(_PS_MODULE_DIR_, '/') . '/iyzipay/vendor/autoload.php';
require_once rtrim(_PS_MODULE_DIR_, '/') . '/iyzipay/classes/IyzipayModel.php';
require_once rtrim(_PS_MODULE_DIR_, '/') . '/iyzipay/classes/IyzipayCheckoutFormObject.php';

class IyzipayCallBackModuleFrontController extends ModuleFrontController
{

    public function __construct()
    {
        parent::__construct();
        $this->display_column_left  = false;
        $this->display_column_right = false;
        $this->context              = Context::getContext();
    }

    public function init($webhook = null, $webhookPaymentConversationId = null, $webhookToken = null, $webhookIyziEventType = null)
    {
        parent::init();

        try {

            if (!Tools::getValue('token') && $webhook != "webhook") {
                $errorMessage = $this->l('tokenNotFound');
                throw new \Exception("Token not found");
            }

            $customerId         = (int) $this->context->cookie->id_customer;
            $orderId            = (int) $this->context->cookie->id_cart;
            $locale             = $this->context->language->iso_code;
            $remoteIpAddr       = Tools::getRemoteAddr();
            $cart               = $this->context->cart;
            $cartTotal          = (float) $cart->getOrderTotal(true, Cart::BOTH);
            $customer           = new Customer($cart->id_customer);
            $currency           = $this->context->currency;
            $shopId             = (int) $this->context->shop->id;
            $languageId         = (int) $this->context->language->id;
            $customerSecureKey  = $customer->secure_key;
            $iyziTotalPrice     = (float) $this->context->cookie->totalPrice;
            $token              = Tools::getValue('token');

            if ($webhook == 'webhook') {
                $token = $webhookToken;
                $orderId =  $webhookPaymentConversationId;
            }

            $extraVars = array();
            $installmentMessage = false;

            $apiKey          = Configuration::get('iyzipay_api_key');
            $secretKey       = Configuration::get('iyzipay_secret_key');
            $rand            = rand(100000, 99999999);
            $endpoint        = Configuration::get('iyzipay_api_type');
            $thankYouPage    = Configuration::get('thankyou_page_text', 0);

            if (!$thankYouPage) {
                Configuration::updateValue('thankyou_page_text', 0);
            }

            $options = new \Iyzipay\Options();
            $options->setApiKey($apiKey);
            $options->setSecretKey($secretKey);
            $options->setBaseUrl($endpoint);

            $formRequest = new \Iyzipay\Request\RetrieveCheckoutFormRequest();
            $formRequest->setLocale($locale);
            $formRequest->setConversationId($orderId);
            $formRequest->setToken($token);

            $paymentType = isset($this->context->cookie->iyziPaymentType) ? $this->context->cookie->iyziPaymentType : '';

            if ($paymentType == 'pwi') {
                $request = new \Iyzipay\Request\RetrievePayWithIyzicoRequest();
                $request->setLocale($locale);
                $request->setConversationId($orderId);
                $request->setToken($token);
                $requestResponse = \Iyzipay\Model\PayWithIyzico::retrieve($request, $options);
            } else {
                $requestResponse = \Iyzipay\Model\CheckoutForm::retrieve($formRequest, $options);
            }

            $this->context->cookie->iyziPaymentType = null;

            if ($webhook == "webhook" && $webhookIyziEventType != 'CREDIT_PAYMENT_AUTH' && $requestResponse->getStatus() == 'failure') {
                return IyzipayWebhookModuleFrontController::webhookHttpResponse("errorCode: " . $requestResponse->getErrorCode() . " - " . $requestResponse->getErrorMessage(), 404);
            }

            if ($webhook == "webhook") {
                $orderId            = $requestResponse->getBasketId();
                $cartId             = $requestResponse->getBasketId();
                $cart               = new Cart($cartId);
                $cartTotal          = (float) $cart->getOrderTotal(true, Cart::BOTH);
                $customer           = new Customer($cart->id_customer);
                $customerSecureKey  = $customer->secure_key;
                $order              = Order::getByCartId($cart->id);

                if ($webhookIyziEventType == 'CREDIT_PAYMENT_PENDING' && $requestResponse->getPaymentStatus() == 'PENDING_CREDIT') {
                    $orderMessage = 'Alışveriş kredisi başvurusu sürecindedir.';
                    IyzipayWebhookModuleFrontController::webhookResponseOrderNote($orderMessage, 3, $requestResponse->getBasketId());

                    return IyzipayWebhookModuleFrontController::webhookHttpResponse("Order Status Updated - Alışveriş kredisi başvurusu sürecindedir.", 200);
                }

                if ($webhookIyziEventType == 'CREDIT_PAYMENT_AUTH' && $requestResponse->getStatus() == 'success') {
                    $orderMessage = 'Alışveriş kredisi işlemi başarıyla tamamlandı.';
                    IyzipayWebhookModuleFrontController::webhookResponseOrderNote($orderMessage, 2, $requestResponse->getBasketId());

                    return IyzipayWebhookModuleFrontController::webhookHttpResponse("Order Status Updated - Alışveriş kredisi işlemi başarıyla tamamlandı.", 200);
                }

                if ($webhookIyziEventType == 'CREDIT_PAYMENT_INIT' && $requestResponse->getStatus() == 'INIT_CREDIT') {
                    $orderMessage = 'Alışveriş kredisi işlemi başlatıldı.';
                    IyzipayWebhookModuleFrontController::webhookResponseOrderNote($orderMessage, 3, $requestResponse->getBasketId());

                    return IyzipayWebhookModuleFrontController::webhookHttpResponse("Order Status Updated - Alışveriş kredisi işlemi başlatıldı.", 200);
                }

                if ($webhookIyziEventType == 'CREDIT_PAYMENT_AUTH' && $requestResponse->getStatus() == 'FAILURE') {
                    $orderMessage = 'Alışveriş kredisi işlemi başarısız sonuçlandı.';
                    IyzipayWebhookModuleFrontController::webhookResponseOrderNote($orderMessage, 6, $requestResponse->getBasketId());

                    return IyzipayWebhookModuleFrontController::webhookHttpResponse("Order Status Updated - Alışveriş kredisi işlemi başarısız sonuçlandı.", 200);
                }

                if ($order && $order->getCurrentState() == (int)Configuration::get('PS_OS_PAYMENT')) {
                    return IyzipayWebhookModuleFrontController::webhookHttpResponse("Order Exist - Sipariş zaten var.", 200);
                }

                if ($order && $order->getCurrentState() != (int)Configuration::get('PS_OS_PAYMENT')) {
                    $order->setCurrentState((int)Configuration::get('PS_OS_PAYMENT'));

                    return IyzipayWebhookModuleFrontController::webhookHttpResponse("Order Status Updated - Sipariş Durumu Ödendi yapıldı.", 200);
                }
            }

            if ($requestResponse->getPaymentStatus() == 'INIT_BANK_TRANSFER' && $requestResponse->getStatus() == 'success') {
                $orderMessage = 'iyzico Banka havalesi/EFT ödemesi bekleniyor.';
                Configuration::updateValue('thankyou_page_text', 0);
                $this->module->validateOrder($orderId, Configuration::get('PS_OS_BANKWIRE'), $cartTotal, $this->module->displayName, $orderMessage, $extraVars, NULL, false, $customerSecureKey);
                Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $orderId . '&id_module=' . (int)$this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);
            }

            if ($webhook != 'webhook' && $requestResponse->getPaymentStatus() == 'PENDING_CREDIT' && $requestResponse->getStatus() == 'success') {
                Configuration::updateValue('thankyou_page_text', 1);
                $orderMessage = 'Alışveriş kredisi başvurusu sürecindedir.';
                $this->module->validateOrder($orderId, Configuration::get('PS_OS_PREPARATION'), $cartTotal, $this->module->displayName, $orderMessage, $extraVars, NULL, false, $customerSecureKey);
                Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $orderId . '&id_module=' . (int)$this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);
            }

            Configuration::updateValue('thankyou_page_text', 0);

            $requestResponseInstallment     = (int) $requestResponse->getInstallment();
            $requestResponsePaidPrice       = (float) $requestResponse->getPaidPrice();
            $requestResponsePaymentId       = (int) $requestResponse->getPaymentId();

            if (empty($orderId)) {
                if ($token) {
                    $this->cancelPayment($locale, $requestResponsePaymentId, $remoteIpAddr, $apiKey, $secretKey, $rand, $endpoint);
                } else {
                    $errorMessage = $this->l('orderNotFound');
                    throw new \Exception($errorMessage);
                }
            }

            if ($requestResponse->getPaymentStatus() == 'SUCCESS' && $webhook != "webhook") {
                if ($this->context->cookie->iyziToken == $token) {
                    $iyziTotalPriceFraud  = $iyziTotalPrice;

                    if (isset($requestResponseInstallment) && !empty($requestResponseInstallment) && $requestResponseInstallment > 1) {
                        $installmentFee       = $requestResponsePaidPrice - $iyziTotalPrice;
                        $iyziTotalPriceFraud  = $iyziTotalPrice + $installmentFee;
                    }

                    if ($iyziTotalPriceFraud < $cartTotal) {
                        $this->cancelPayment($locale, $requestResponsePaymentId, $remoteIpAddr, $apiKey, $secretKey, $rand, $endpoint);
                    }
                } else {
                    $errorMessage = $this->l('basketItemsNotMatch');
                    throw new \Exception($errorMessage);
                }
            }

            $iyzicoLocalOrder = new stdClass;
            $iyzicoLocalOrder->paymentId     = !empty($requestResponsePaymentId) ? $requestResponsePaymentId : '';
            $iyzicoLocalOrder->orderId       = $orderId;
            $iyzicoLocalOrder->totalAmount   = !empty($requestResponsePaidPrice) ? $requestResponsePaidPrice : '';
            $iyzicoLocalOrder->status        = $requestResponse->getPaymentStatus();

            IyzipayModel::insertIyzicoOrder($iyzicoLocalOrder);

            if ($requestResponse->getPaymentStatus() != 'SUCCESS' || $requestResponse->getStatus() != 'success' || $orderId != $requestResponse->getBasketId()) {
                if ($requestResponse->getStatus() == 'success' && $requestResponse->getPaymentStatus() == 'FAILURE') {
                    $errorMessage = $this->l('error3D');
                    throw new Exception($errorMessage);
                }
                $errorMessage = $this->l('generalError');
                $errorMessage = empty($requestResponse->getErrorMessage()) ? $requestResponse->getErrorMessage() : $errorMessage;
                throw new \Exception($errorMessage);
            }

            if (empty($requestResponse->getCardUserKey())) {
                if ($customerId) {
                    $cardUserKey = IyzipayModel::findUserCardKey($customerId, $apiKey);

                    if ($requestResponse->getCardUserKey() != $cardUserKey) {
                        IyzipayModel::insertCardUserKey($customerId, $requestResponse->getCardUserKey(), $apiKey);
                    }
                }
            }


            if (isset($requestResponseInstallment) && !empty($requestResponseInstallment) && $requestResponseInstallment > 1) {
                $cartId     = $requestResponse->getBasketId();
                $cart       = new Cart($cartId);
                $cartTotal  = (float) $cart->getOrderTotal(true, Cart::BOTH);

                $installmentFee                         = $requestResponsePaidPrice - $cartTotal;
                $this->context->cookie->installmentFee  = $installmentFee;

                $installmentMessage = '<br><br><strong style="color:#000;">Taksitli Alışveriş: </strong>Toplam ödeme tutarınıza <strong style="color:#000">' . $requestResponseInstallment . ' Taksit </strong> için <strong style="color:red">' . Context::getContext()->currentLocale->formatPrice($installmentFee, $currency->iso_code) . '</strong> yansıtılmıştır.<br>';

                $installmentMessageEmail = '<br><br><strong style="color:#000;">' . $this->l('installmentShopping') . '</strong><br> ' . $this->l('installmentOption') . '<strong style="color:#000"> ' . $requestResponseInstallment . ' ' . $this->l('InstallmentKey') . '<br></strong>' . $this->l('commissionAmount') . '<strong style="color:red">
                ' . Context::getContext()->currentLocale->formatPrice($installmentFee, $currency->iso_code) . '</strong><br>';

                $extraVars['{total_paid}']            = Context::getContext()->currentLocale->formatPrice($requestResponsePaidPrice, $currency->iso_code);


                $dateFormat = $context->language->date_format_lite ?? 'd/m/Y';
                $timeFormat = $context->language->time_format ?? 'H:i:s';
                $extraVars['{date}'] = date($dateFormat . ' ' . $timeFormat) . $installmentMessageEmail;
            }

            $this->module->validateOrder($orderId, Configuration::get('PS_OS_PAYMENT'), $cartTotal, $this->module->displayName, $installmentMessage, $extraVars, NULL, false, $customerSecureKey);


            if (isset($requestResponseInstallment) && !empty($requestResponseInstallment) && $requestResponseInstallment > 1) {
                Configuration::updateValue('PS_INVOICE', $orderId);

                $currentOrderId = (int) $this->module->currentOrder;
                $order = new Order($currentOrderId);

                IyzipayModel::updateOrderTotal($requestResponsePaidPrice, $currentOrderId);
                IyzipayModel::updateOrderPayment($requestResponsePaidPrice, $order->reference);
                IyzipayModel::updateOrderInvoiceTotal($requestResponsePaidPrice, $currentOrderId);

                $customer_thread = new CustomerThread();
                $customer_thread->id_contact  = 0;
                $customer_thread->id_customer = $customer->id;
                $customer_thread->id_shop     = $shopId;
                $customer_thread->id_order    = $currentOrderId;
                $customer_thread->id_lang     = $languageId;
                $customer_thread->email       = $customer->email;
                $customer_thread->status      = 'open';
                $customer_thread->token       = Tools::passwdGen(12);
                $customer_thread->add();

                $customer_message = new CustomerMessage();
                $customer_message->id_customer_thread  = $customer_thread->id;
                $customer_message->id_employee         = 1;
                $customer_message->message             = $installmentMessage;
                $customer_message->private             = 0;
                $customer_message->is_html             = 1;
                $customer_message->add();
            }

            if ($webhook == 'webhook') {
                return IyzipayWebhookModuleFrontController::webhookHttpResponse("Order Created by Webhook - Sipariş webhook tarafından oluşturuldu.", 200);
            }


            Tools::redirect($this->context->link->getPageLink(
                'order-confirmation',
                true,
                (int) $this->context->language->id,
                [
                    'id_cart' => (int) $orderId,
                    'id_module' => (int) $this->module->id,
                    'id_order' => (int) $this->module->currentOrder,
                    'key' => $customer->secure_key,
                ]
            ));
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();

            $this->context->smarty->assign(array(
                'errorMessage' => $errorMessage,
            ));


            $this->setTemplate('module:iyzipay/views/templates/front/iyzi_error.tpl');
        }
    }

    /**
     * @param $locale
     * @param $paymentId
     * @param $remoteIpAddr
     * @param $apiKey
     * @param $secretKey
     * @param $rand
     * @param $endpoint
     * @throws Exception
     */
    private function cancelPayment($locale, $paymentId, $remoteIpAddr, $apiKey, $secretKey, $rand, $endpoint)
    {
        $options = new \Iyzipay\Options();
        $options->setApiKey($apiKey);
        $options->setSecretKey($secretKey);
        $options->setBaseUrl($endpoint);

        $request = new \Iyzipay\Request\CreateCancelRequest();
        $request->setLocale($locale);
        $request->setConversationId($rand);
        $request->setPaymentId($paymentId);
        $request->setIp($remoteIpAddr);

        $cancelResponse = \Iyzipay\Model\Cancel::create($request, $options);


        if ($cancelResponse->getStatus() == 'success') {
            $errorMessage = $this->l('basketItemsNotMatch');
            throw new \Exception($errorMessage);
        }

        $errorMessage = $this->l('uniqError');
        throw new \Exception($errorMessage);
    }
}