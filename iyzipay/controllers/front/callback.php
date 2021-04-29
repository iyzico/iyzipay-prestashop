<?php
/**
 * 2007-2018 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    iyzico <info@iyzico.com>
 *  @copyright 2018 iyzico
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of iyzico
 */

require_once _PS_MODULE_DIR_.'iyzipay/classes/IyzipayModel.php';
require_once _PS_MODULE_DIR_.'iyzipay/classes/IyzipayCheckoutFormObject.php';
require_once _PS_MODULE_DIR_.'iyzipay/classes/IyzipayPkiStringBuilder.php';
require_once _PS_MODULE_DIR_.'iyzipay/classes/IyzipayRequest.php';

class IyzipayCallBackModuleFrontController extends ModuleFrontController
{

    public function __construct()
    {
        parent::__construct();
        $this->display_column_left  = false;
        $this->display_column_right = false;
        $this->context              = Context::getContext();
    }

    public function init($webhook = null, $webhookPaymentConversationId = null ,$webhookToken = null)
    {
        parent::init();

        try {

            if (!Tools::getValue('token') && $webhook != "webhook") {
                $errorMessage = $this->l('tokenNotFound');
                throw new \Exception("Token not found");
            }

            $customerId      = (int) $this->context->cookie->id_customer;
            $orderId         = (int) $this->context->cookie->id_cart;
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
            $iyziTotalPrice                 = (float) $this->context->cookie->totalPrice;
            $token                          = Tools::getValue('token');

            if ($webhook == 'webhook'){
                $token = $webhookToken;
                $orderId =  $webhookPaymentConversationId;
            }

            $extraVars = array();
            $installmentMessage = false;

            $apiKey          = Configuration::get('iyzipay_api_key');
            $secretKey       = Configuration::get('iyzipay_secret_key');
            $rand            = rand(100000, 99999999);
            $endpoint        = Configuration::get('iyzipay_api_type');
            $responseObject  = IyzipayCheckoutFormObject::responseObject($orderId, $token, $locale);

            $pkiString       = IyzipayPkiStringBuilder::pkiStringGenerate($responseObject);
            $authorization   = IyzipayPkiStringBuilder::authorization($pkiString, $apiKey, $secretKey, $rand);
            $responseObject  = json_encode($responseObject, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
            $requestResponse = IyzipayRequest::checkoutFormRequestDetail($endpoint, $responseObject, $authorization);

            if ($webhook == "webhook" && $requestResponse->status == 'failure'){
                return IyzipayWebhookModuleFrontController::webhookHttpResponse("errorCode: ".$requestResponse->errorCode ." - " . $requestResponse->errorMessage, 404);
            }

            if ($webhook == "webhook"){
                $orderId = $requestResponse->basketId;
                $cartId = $requestResponse->basketId;
                $cart = new Cart($cartId);
                $cartTotal = (float) $cart->getOrderTotal(true, Cart::BOTH);
                $customer = new Customer($cart->id_customer);
                $customerSecureKey = $customer->secure_key;

                $order = Order::getByCartId($cart->id);

                if ($order && $order->getCurrentState() == (int)Configuration::get('PS_OS_PAYMENT')){
                    return IyzipayWebhookModuleFrontController::webhookHttpResponse("Order Exist - Sipariş zaten var.", 200);
                }

                if ($order && $order->getCurrentState() != (int)Configuration::get('PS_OS_PAYMENT')){
                    $order->setCurrentState((int)Configuration::get('PS_OS_PAYMENT'));
                    return IyzipayWebhookModuleFrontController::webhookHttpResponse("Order Status Updated - Sipariş Durumu Ödendi yapıldı.", 200);
                }
            }

            if($requestResponse->paymentStatus == 'INIT_BANK_TRANSFER' && $requestResponse->status == 'success') {
                $orderMessage = 'iyzico Banka havalesi/EFT ödemesi bekleniyor.';
                $this->module->validateOrder($orderId, Configuration::get('PS_OS_BANKWIRE'), $cartTotal, $this->module->displayName, $orderMessage, $extraVars, NULL, false, $customerSecureKey);

                Tools::redirect('index.php?controller=order-confirmation&id_cart='.$orderId.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
            }


            $requestResponse->installment     = (int)   $requestResponse->installment;
            $requestResponse->paidPrice       = (float) $requestResponse->paidPrice;
            $requestResponse->paymentId       = (int)   $requestResponse->paymentId;
            $requestResponse->conversationId  = (int)   $requestResponse->conversationId;

            if (empty($orderId)) {
                if ($token) {
                    $this->cancelPayment($locale, $requestResponse->paymentId, $remoteIpAddr, $apiKey, $secretKey, $rand, $endpoint);
                } else  {
                    $errorMessage = $this->l('orderNotFound');
                    throw new \Exception($errorMessage);
                }
            }

            if ($requestResponse->paymentStatus == 'SUCCESS' && $webhook != "webhook") {
                if ($this->context->cookie->iyziToken == $token)  {
                    $iyziTotalPriceFraud  = $iyziTotalPrice;

                    if (isset($requestResponse->installment) && !empty($requestResponse->installment) && $requestResponse->installment > 1) {
                        $installmentFee       = $requestResponse->paidPrice - $iyziTotalPrice;
                        $iyziTotalPriceFraud  = $iyziTotalPrice + $installmentFee;
                    }

                    if ($iyziTotalPriceFraud < $cartTotal) {
                        $this->cancelPayment($locale, $requestResponse->paymentId, $remoteIpAddr, $apiKey, $secretKey, $rand, $endpoint);
                    }
                } else {
                    $errorMessage = $this->l('basketItemsNotMatch');
                    throw new \Exception($errorMessage);
                }
            }


            $iyzicoLocalOrder = new stdClass;
            $iyzicoLocalOrder->paymentId     = !empty($requestResponse->paymentId) ? (int) $requestResponse->paymentId : '';
            $iyzicoLocalOrder->orderId       = $orderId;
            $iyzicoLocalOrder->totalAmount   = !empty($requestResponse->paidPrice) ? (float) $requestResponse->paidPrice : '';
            $iyzicoLocalOrder->status        = $requestResponse->paymentStatus;

            IyzipayModel::insertIyzicoOrder($iyzicoLocalOrder);



            if ($requestResponse->paymentStatus != 'SUCCESS' || $requestResponse->status != 'success' || $orderId != $requestResponse->basketId) {
                if ($requestResponse->status == 'success' && $requestResponse->paymentStatus == 'FAILURE') {
                    $errorMessage = $this->l('error3D');
                    throw new Exception($errorMessage);
                }

                /* Redirect Error */
                $errorMessage = $this->l('generalError');
                $errorMessage = isset($requestResponse->errorMessage) ? $requestResponse->errorMessage : $errorMessage;
                throw new \Exception($errorMessage);
            }

            /* Save Card */
            if (isset($requestResponse->cardUserKey)) {
                if ($customerId) {
                    $cardUserKey = IyzipayModel::findUserCardKey($customerId, $apiKey);

                    if ($requestResponse->cardUserKey != $cardUserKey) {
                        IyzipayModel::insertCardUserKey($customerId, $requestResponse->cardUserKey, $apiKey);
                    }
                }
            }


            if (isset($requestResponse->installment) && !empty($requestResponse->installment) && $requestResponse->installment > 1) {
                /* Installment Calc and DB Update */

                $cartId = $requestResponse->basketId;
                $cart = new Cart($cartId);
                $cartTotal = (float) $cart->getOrderTotal(true, Cart::BOTH);

                $installmentFee                         = $requestResponse->paidPrice - $cartTotal;
                $this->context->cookie->installmentFee  = $installmentFee;

                $installmentMessage = '<br><br><strong style="color:#000;">Taksitli Alışveriş: </strong>Toplam ödeme tutarınıza <strong style="color:#000">'.$requestResponse->installment.' Taksit </strong> için <strong style="color:red">'.Tools::displayPrice($installmentFee, $currency, false).'</strong> yansıtılmıştır.<br>';

                $installmentMessageEmail = '<br><br><strong style="color:#000;">'.$this->l('installmentShopping').'</strong><br> '.$this->l('installmentOption').'<strong style="color:#000"> '.$requestResponse->installment.' '.$this->l('InstallmentKey').'<br></strong>'.$this->l('commissionAmount').'<strong style="color:red">
                '.Tools::displayPrice($installmentFee, $currency, false).'</strong><br>';

                $extraVars['{total_paid}']            = Tools::displayPrice($requestResponse->paidPrice, $currency, false);
                $extraVars['{date}']                  = Tools::displayDate(date('Y-m-d H:i:s'), null, 1).$installmentMessageEmail;

                /* Invoice false */
                //Configuration::updateValue('PS_INVOICE', false);
            }

                $this->module->validateOrder($orderId, Configuration::get('PS_OS_PAYMENT'), $cartTotal, $this->module->displayName, $installmentMessage, $extraVars, NULL, false, $customerSecureKey);

            if (isset($requestResponse->installment) && !empty($requestResponse->installment) && $requestResponse->installment > 1) {
                /* Invoice true */

                Configuration::updateValue('PS_INVOICE', $orderId);

                $currentOrderId = (int) $this->module->currentOrder;
                $order = new Order($currentOrderId);

                /* Update Total Price and Installment Calc and DB Update  */
                IyzipayModel::updateOrderTotal($requestResponse->paidPrice, $currentOrderId);

                IyzipayModel::updateOrderPayment($requestResponse->paidPrice, $order->reference);

                IyzipayModel::updateOrderInvoiceTotal($requestResponse->paidPrice, $currentOrderId);

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

            if ($webhook == 'webhook'){
                return IyzipayWebhookModuleFrontController::webhookHttpResponse("Order Created by Webhook - Sipariş webhook tarafından oluşturuldu.", 200);
            }

            Tools::redirect('index.php?controller=order-confirmation&id_cart='.$orderId.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
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
        $responseObject = IyzipayCheckoutFormObject::cancelObject($locale, $paymentId, $remoteIpAddr);
        $pkiString = IyzipayPkiStringBuilder::pkiStringGenerate($responseObject);
        $authorization = IyzipayPkiStringBuilder::authorization($pkiString, $apiKey, $secretKey, $rand);

        $responseObject = json_encode($responseObject, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $cancelResponse = IyzipayRequest::paymentCancel($endpoint, $responseObject, $authorization);

        if ($cancelResponse->status == 'success') {
            $errorMessage = $this->l('basketItemsNotMatch');
            throw new \Exception($errorMessage);
        }

        $errorMessage = $this->l('uniqError');
        throw new \Exception($errorMessage);
    }
}