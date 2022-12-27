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
 *  @author    paywithiyzico <info@iyzico.com>
 *  @copyright 2018 paywithiyzico
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of paywithiyzico
 */

require_once _PS_MODULE_DIR_.'iyzipay/classes/IyzipayModel.php';
require_once _PS_MODULE_DIR_.'paywithiyzico/classes/PaywithiyzicoObject.php';
require_once _PS_MODULE_DIR_.'paywithiyzico/classes/PaywithiyzicoPkiStringBuilder.php';
require_once _PS_MODULE_DIR_.'paywithiyzico/classes/PaywithiyzicoRequest.php';
require_once _PS_MODULE_DIR_.'paywithiyzico/classes/PaywithiyzicoModel.php';

class PaywithiyzicoCallBackModuleFrontController extends ModuleFrontController
{

    public function __construct()
    {
        parent::__construct();
        $this->display_column_left  = false;
        $this->display_column_right = false;
        $this->context              = Context::getContext();
    }

    public function init()
    {
        parent::init();

        try {
            if (!Tools::getValue('token')) {
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
            $iyziTotalPrice                 = (float) $this->context->cookie->PwiTotalPrice;
            $token                          = Tools::getValue('token');

            $extraVars = array();
            $installmentMessage = false;

            $apiKey          = Configuration::get('iyzipay_api_key');
            $secretKey       = Configuration::get('iyzipay_secret_key');
            $rand            = rand(100000, 99999999);
            $endpoint        = Configuration::get('iyzipay_api_type');
            $responseObject  = PaywithiyzicoObject::responseObject($orderId, $token, $locale);

            $pkiString       = PaywithiyzicoPkiStringBuilder::pkiStringGenerate($responseObject);
            $authorization   = PaywithiyzicoPkiStringBuilder::authorization($pkiString, $apiKey, $secretKey, $rand);
            $responseObject  = json_encode($responseObject, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
            $requestResponse = PaywithiyzicoRequest::checkoutFormRequestDetail($endpoint, $responseObject, $authorization);

            $requestResponse->installment     = (int)   $requestResponse->installment;
            $requestResponse->paidPrice       = (float) $requestResponse->paidPrice;
            $requestResponse->paymentId       = (int)   $requestResponse->paymentId;
            $requestResponse->conversationId  = (int)   $requestResponse->conversationId;

            if($requestResponse->paymentStatus == 'INIT_BANK_TRANSFER' && $requestResponse->status == 'success') {
                $orderMessage = 'iyzico Banka havalesi/EFT ödemesi bekleniyor.';
                $this->module->validateOrder($orderId, Configuration::get('PS_OS_BANKWIRE'), $cartTotal, $this->module->displayName, $orderMessage, $extraVars, NULL, false, $customerSecureKey);

                Tools::redirect('index.php?controller=order-confirmation&id_cart='.$orderId.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
            }

            if($requestResponse->paymentStatus == 'PENDING_CREDIT' && $requestResponse->status == 'success') {
              $orderMessage = 'Alışveriş kredisi başvurusu sürecindedir.';
              Configuration::updateValue('thankyou_page_text',1);
              $this->module->validateOrder($orderId, Configuration::get('PS_OS_PREPARATION'), $cartTotal, $this->module->displayName, $orderMessage, $extraVars, NULL, false, $customerSecureKey);
              Tools::redirect('index.php?controller=order-confirmation&id_cart='.$orderId.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
          }
          Configuration::updateValue('thankyou_page_text',0);

            if (empty($orderId)) {
                if ($token) {
                    $this->cancelPayment($locale, $requestResponse->paymentId, $remoteIpAddr, $apiKey, $secretKey, $rand, $endpoint);
                } else {
                    $errorMessage = $this->l('orderNotFound');
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
                    $errorMessage = $this->l('basketItemsNotMatch');
                    throw new \Exception($errorMessage);
                }
            }


            $paywithiyzicoLocalOrder = new stdClass;
            $paywithiyzicoLocalOrder->paymentId     = !empty($requestResponse->paymentId) ? (int) $requestResponse->paymentId : '';
            $paywithiyzicoLocalOrder->orderId       = $orderId;
            $paywithiyzicoLocalOrder->totalAmount   = !empty($requestResponse->paidPrice) ? (float) $requestResponse->paidPrice : '';
            $paywithiyzicoLocalOrder->status        = $requestResponse->paymentStatus;


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


            if (isset($requestResponse->installment) && !empty($requestResponse->installment) && $requestResponse->installment > 1) {
                /* Installment Calc and DB Update */

                $PwiInstallmentFee                         = $requestResponse->paidPrice - $iyziTotalPrice;
                $this->context->cookie->PwiInstallmentFee  = $PwiInstallmentFee;


                $installmentMessage = '<br><br><strong style="color:#000;">Taksitli Alışveriş: </strong>Toplam ödeme tutarınıza <strong style="color:#000">'.$requestResponse->installment.' Taksit </strong> için <strong style="color:red">'.Tools::displayPrice($PwiInstallmentFee, $currency, false).'</strong> yansıtılmıştır.<br>';

                $installmentMessageEmail = '<br><br><strong style="color:#000;">'.$this->l('installmentShopping').'</strong><br> '.$this->l('installmentOption').'<strong style="color:#000"> '.$requestResponse->installment.' '.$this->l('InstallmentKey').'<br></strong>'.$this->l('commissionAmount').'<strong style="color:red">
             '.Tools::displayPrice($PwiInstallmentFee, $currency, false).'</strong><br>';

                $extraVars['{total_paid}']            = Tools::displayPrice($requestResponse->paidPrice, $currency, false);
                $extraVars['{date}']                  = Tools::displayDate(date('Y-m-d H:i:s'), null, 1).$installmentMessageEmail;

                /* Invoice false */
                //Configuration::updateValue('PS_INVOICE', false);
            }

            $test = $this->module->validateOrder($orderId, Configuration::get('PS_OS_PAYMENT'), $cartTotal, $this->module->displayName, $installmentMessage, $extraVars, $currenyId, false, $customerSecureKey);

            if (isset($requestResponse->installment) && !empty($requestResponse->installment) && $requestResponse->installment > 1) {
                /* Invoice true */
                Configuration::updateValue('PS_INVOICE', $orderId);

                $currentOrderId = (int) $this->module->currentOrder;
                $order = new Order($currentOrderId);

                /* Update Total Price and Installment Calc and DB Update  */
                IyzipayModel::updateOrderTotal($requestResponse->paidPrice, $currentOrderId);

                IyzipayModel::updateOrderPayment($requestResponse->paidPrice, $order->reference);

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


            Tools::redirect('index.php?controller=order-confirmation&id_cart='.$orderId.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();

            $this->context->smarty->assign(array(
                'errorMessage' => $errorMessage,
            ));


            $this->setTemplate('module:paywithiyzico/views/templates/front/iyzi_error.tpl');
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
            $errorMessage = $this->l('basketItemsNotMatch');
            throw new \Exception($errorMessage);
        }

        $errorMessage = $this->l('uniqError');
        throw new \Exception($errorMessage);
    }
}
