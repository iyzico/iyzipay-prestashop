<?php

require_once _PS_MODULE_DIR_.'paywithiyzico/classes/PaywithiyzicoObject.php';
require_once _PS_MODULE_DIR_.'paywithiyzico/classes/PaywithiyzicoPkiStringBuilder.php';
require_once _PS_MODULE_DIR_.'paywithiyzico/classes/PaywithiyzicoRequest.php';
require_once _PS_MODULE_DIR_.'paywithiyzico/classes/PaywithiyzicoModel.php';

class PaywithiyzicoCallBackModuleFrontController extends ModuleFrontController{

    public function __construct()
    {
        parent::__construct();
        $this->context              = Context::getContext();
    }

    public function init(){
        parent::init();

        try {

            if (!Tools::getValue('token')) {
                $errorMessage = $this->module->l('tokenNotFound','callback');
                throw new \Exception($errorMessage);
            }

            $orderCartId     = (int) $this->context->cookie->id_cart;
            $locale          = $this->context->language->iso_code;
            $remoteIpAddr    = Tools::getRemoteAddr();

            $cart             = $this->context->cart;
            $cartTotal        = (float) $cart->getOrderTotal(true, Cart::BOTH);
            $customer         = new Customer($cart->id_customer);

            $currency                       = $this->context->currency;
            $shopId                         = (int) $this->context->shop->id;
            $currenyId                      = (int) $currency->id;
            $languageId                     = (int) $this->context->language->id;
            $customerSecureKey              = $customer->secure_key;
            $iyziTotalPrice                 = (float) $this->context->cookie->PwiTotalPrice;
            $token                          = Tools::getValue('token');

            $extraVars = array();
            $installmentMessage = false;

            $apiKey          = Configuration::get('IYZICO_FORM_LIVE_API_ID');
            $secretKey       = Configuration::get('IYZICO_FORM_LIVE_SECRET');
            $rand            = rand(100000, 99999999);
            $endpoint        = Configuration::get('IYZICO_FORM_BASEURL');
            $responseObject  = PaywithiyzicoObject::responseObject($orderCartId, $token, $locale);

            $pkiString       = PaywithiyzicoPkiStringBuilder::pkiStringGenerate($responseObject);
            $authorization   = PaywithiyzicoPkiStringBuilder::authorization($pkiString, $apiKey, $secretKey, $rand);
            $responseObject  = json_encode($responseObject, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
            $requestResponse = PaywithiyzicoRequest::checkoutFormRequestDetail($endpoint, $responseObject, $authorization);

            if($requestResponse->paymentStatus == 'INIT_BANK_TRANSFER' && $requestResponse->status == 'success') {
                $orderMessage = 'iyzico Banka havalesi/EFT ödemesi bekleniyor.';
                $this->module->validateOrder($orderCartId, Configuration::get('PS_OS_BANKWIRE'), $cartTotal, $this->module->displayName, $orderMessage, $extraVars, NULL, false, $customerSecureKey);

                $this->insertIyzicoOrder($requestResponse->paymentId, $this->module->currentOrder, $requestResponse->paidPrice, $requestResponse->paymentStatus);

                Tools::redirect('index.php?controller=order-confirmation&id_cart='.$orderCartId.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
            }

            /**
             * If bank transfer is success, change order status to success.
             */
            if ($order = new Order(Order::getOrderByCartId($requestResponse->basketId)) && Tools::getValue('iyziEventType') == 'BANK_TRANSFER_AUTH' && $requestResponse->paymentStatus == 'SUCCESS'){
                $order = new Order(Order::getOrderByCartId($requestResponse->basketId));
                $order->setCurrentState( (int) Configuration::get('PS_OS_PAYMENT'));
                exit;
            }

            if (empty($orderCartId)) {
                if ($token) {
                    $this->cancelPayment($locale, $requestResponse->paymentId, $remoteIpAddr, $apiKey, $secretKey, $rand, $endpoint);
                } else {
                    $errorMessage = $this->module->l('orderNotFound','callback');
                    throw new \Exception($errorMessage);
                }
            }

            if ($requestResponse->paymentStatus == 'SUCCESS') {
                if ($this->context->cookie->PwiIyziToken == $token) {
                    $iyziTotalPriceFraud  = $iyziTotalPrice;

                    if (isset($requestResponse->installment) && !empty($requestResponse->installment) && $requestResponse->installment > 1) {
                        $installmentFee       = $requestResponse->paidPrice - $iyziTotalPrice;
                        $iyziTotalPriceFraud  = $iyziTotalPrice + $installmentFee;
                    }

                    if ($iyziTotalPriceFraud < $cartTotal) {
                        $this->cancelPayment($locale, $requestResponse->paymentId, $remoteIpAddr, $apiKey, $secretKey, $rand, $endpoint);
                    }
                } else {
                    $errorMessage = $this->module->l('basketItemsNotMatch','callback');
                    throw new \Exception($errorMessage);
                }
            }

            $paywithiyzicoLocalOrder = new stdClass;
            $paywithiyzicoLocalOrder->paymentId     = !empty($requestResponse->paymentId) ? (int) $requestResponse->paymentId : '';
            $paywithiyzicoLocalOrder->orderId       = $orderCartId;
            $paywithiyzicoLocalOrder->totalAmount   = !empty($requestResponse->paidPrice) ? (float) $requestResponse->paidPrice : '';
            $paywithiyzicoLocalOrder->status        = $requestResponse->paymentStatus;


            if ($requestResponse->paymentStatus != 'SUCCESS' || $requestResponse->status != 'success' || $orderCartId != $requestResponse->basketId) {
                if ($requestResponse->status == 'success' && $requestResponse->paymentStatus == 'FAILURE') {
                    $errorMessage = $this->module->l('error3D','callback');
                    throw new Exception($errorMessage);
                }

                /* Redirect Error */
                $errorMessage = $this->module->l('generalError','callback');
                $errorMessage = isset($requestResponse->errorMessage) ? $requestResponse->errorMessage : $errorMessage;
                throw new \Exception($errorMessage);
            }

            if (isset($requestResponse->installment) && !empty($requestResponse->installment) && $requestResponse->installment > 1) {
                /* Installment Calc and DB Update */

                $PwiInstallmentFee                         = $requestResponse->paidPrice - $iyziTotalPrice;
                $this->context->cookie->PwiInstallmentFee  = $PwiInstallmentFee;


                $installmentMessage = '<br><br><strong style="color:#000;">'.$this->module->l('installmentShopping','callback').': '.$requestResponse->installment.' '.$this->module->l('installmentOption','callback').', </strong>'.$this->module->l('commissionAmount','callback').': <strong style="color:red">'.Tools::displayPrice($PwiInstallmentFee, $currency, false).'</strong><br>';

                $extraVars['{total_paid}']            = Tools::displayPrice($requestResponse->paidPrice, $currency, false);
                $extraVars['{date}']                  = Tools::displayDate(date('Y-m-d H:i:s'), null, 1).$installmentMessage;

            }


            $this->module->validateOrder($orderCartId, Configuration::get('PS_OS_PAYMENT'), $cartTotal, $this->module->displayName, $installmentMessage, $extraVars, $currenyId, false, $customerSecureKey);

            if (isset($requestResponse->installment) && !empty($requestResponse->installment) && $requestResponse->installment > 1) {
                /* Invoice true */

                Configuration::updateValue('PS_INVOICE', $orderCartId);

                $currentOrderId = $this->module->currentOrder;
                $order = new Order($currentOrderId);

                /* Update Total Price and Installment Calc and DB Update  */
                PaywithiyzicoModel::updateOrderTotal($requestResponse->paidPrice, $currentOrderId);

                PaywithiyzicoModel::updateOrderPayment($requestResponse->paidPrice, $order->reference);

                PaywithiyzicoModel::updateOrderInvoiceTotal($requestResponse->paidPrice, $currentOrderId);


                /* Open Thread */
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

                /* Add Info Message */
                $customer_message = new CustomerMessage();
                $customer_message->id_customer_thread  = $customer_thread->id;
                $customer_message->id_employee         = 1;
                $customer_message->message             = $installmentMessage;
                $customer_message->private             = 0;
                $customer_message->add();
            }

            $this->insertIyzicoOrder($requestResponse->paymentId, $this->module->currentOrder, $requestResponse->paidPrice, $requestResponse->paymentStatus);

            Tools::redirect('index.php?controller=order-confirmation&id_cart='.$orderCartId.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);

        }
        catch (Exception $e) {
            $errorMessage = $e->getMessage();

            $this->context->smarty->assign(array(
                'errorMessage' => $errorMessage,
            ));

            $this->setTemplate('iyzi_error.tpl');

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
        $responseObject = PaywithiyzicoObject::cancelObject($locale, $paymentId, $remoteIpAddr);
        $pkiString = PaywithiyzicoPkiStringBuilder::pkiStringGenerate($responseObject);
        $authorization = PaywithiyzicoPkiStringBuilder::authorization($pkiString, $apiKey, $secretKey, $rand);

        $responseObject = json_encode($responseObject, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $cancelResponse = PaywithiyzicoRequest::paymentCancel($endpoint, $responseObject, $authorization);

        if ($cancelResponse->status == 'success') {
            $errorMessage = $this->module->l('basketItemsNotMatch','callback');
            throw new \Exception($errorMessage);
        }

        $errorMessage = $this->module->l('uniqError','callback');
        throw new \Exception($errorMessage);
    }

    /**
     * @param $paymentId
     * @param $orderId
     * @param $total_amount
     * @param $status
     * @return bool
     */
    private function insertIyzicoOrder($paymentId, $orderId, $total_amount, $status) {

        Db::getInstance()->Execute(
            'INSERT INTO `'._DB_PREFIX_.'paywithiyzico_order` (`payment_id`, `order_id`,`total_amount`,`status`)
                VALUES('.$paymentId.', '. $orderId.', '. $total_amount.', "'.pSQL($status).'")');

        return true;

    }


}
