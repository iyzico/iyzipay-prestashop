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

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once 'classes/PaywithiyzicoPkiStringBuilder.php';
include_once 'classes/PaywithiyzicoRequest.php';
include_once 'classes/PaywithiyzicoObject.php';

class Paywithiyzico extends PaymentModule
{
    protected $config_form = false;
    public $extra_mail_vars;

    public function __construct()
    {
        $this->name = 'paywithiyzico';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'iyzico';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName              = $this->l('Pay with iyzico Payment Module');
        $this->description              = $this->l('Pay with iyzico Payment Gateway for PrestaShop');
        $this->basketItemsNotMatch      = $this->l('basketItemsNotMatch');
        $this->uniqError                = $this->l('uniqError');
        $this->error3D                  = $this->l('error3D');
        $this->tokenNotFound            = $this->l('tokenNotFound');
        $this->orderNotFound            = $this->l('orderNotFound');
        $this->generalError             = $this->l('generalError');
        $this->CardFamilyName           = $this->l('CardFamilyName');
        $this->InstallmentKey           = $this->l('InstallmentKey');
        $this->installmentShopping      = $this->l('installmentShopping');
        $this->installmentOption        = $this->l('installmentOption');
        $this->commissionAmount           = $this->l('commissionAmount');


        
        $this->confirmUninstall = $this->l('are you sure ?');

        $this->limited_countries = array('TR','FR','EN');

        $this->limited_currencies = array('TRY','EUR','USD');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);

        $this->extra_mail_vars = array(
             '{instalmentFee}' => '',
            );
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        $iso_code = Country::getIsoById(Configuration::get('PS_COUNTRY_DEFAULT'));

        if (in_array($iso_code, $this->limited_countries) == false) {
            $this->_errors[] = $this->l('This module is not available in your country');
            return false;
        }

        return parent::install() &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('PaymentOptions') &&
            $this->registerHook('paymentReturn');
    }

    public function uninstall()
    {

            return $this->unregisterHook('backOfficeHeader')
        && $this->unregisterHook('PaymentOptions')
        && $this->unregisterHook('paymentReturn')
        && Configuration::deleteByName('paywithiyzico_module_status')
        && parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitPaywithiyzicoModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');


        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->id = 'paywithiyzico';
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitPaywithiyzicoModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */

    protected function getConfigFormValues()
    {
        return array(
            'paywithiyzico_module_status' => Configuration::get('paywithiyzico_module_status', true)
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {

        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }

    }


    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }


    /**
     * This method is used to render the payment button,
     * Take care if the button should be displayed or not.
     */

    public function hookPaymentOptions($params)
    {

        if(!$params['cart']->id_carrier)
            return $this->paymentOptionResult();

        $paywithiyzicoResponse = $this->PwiGenerate($params);

        $phpCheckVersion = $this->versionCheck();

        if ($phpCheckVersion) {
            return $this->errorAssign($phpCheckVersion);
        }

        if (!is_object($paywithiyzicoResponse)) {
            return $this->errorAssign($paywithiyzicoResponse);
        }

        return $this->successAssign($paywithiyzicoResponse);
    }

    /**
     * @param $params
     * @return mixed|string
     */
    public function PwiGenerate($params)
    {
        
        $this->context->cookie->PwiTotalPrice = false;
        $this->context->cookie->PwiInstallmentFee = false;
        $this->context->cookie->PwiIyziToken = false;

        $currency = $this->getCurrency($params['cart']->id_currency);
        $shipping = $params['cart']->getOrderTotal(true, Cart::ONLY_SHIPPING);
        $basketItems = $params['cart']->getProducts();

        $context = $this->context;
        $billingAddress = new Address($params['cart']->id_address_invoice);
        $shippingAddress = new Address($params['cart']->id_address_delivery);
        $billingAddress->email = $params['cookie']->email;
        $shippingAddress->email = $params['cookie']->email;
        $apiKey = Configuration::get('iyzipay_api_key');
        $secretKey = Configuration::get('iyzipay_secret_key');
        $rand = rand(100000, 99999999);
        $endpoint = Configuration::get('iyzipay_api_type');

        $paywithiyzico = PaywithiyzicoObject::option($params, $currency, $context, $apiKey);
        $paywithiyzico->buyer = PaywithiyzicoObject::buyer($billingAddress);
        $paywithiyzico->shippingAddress = PaywithiyzicoObject::shippingAddress($shippingAddress);
        $paywithiyzico->billingAddress = PaywithiyzicoObject::billingAddress($billingAddress);
        $paywithiyzico->basketItems = PaywithiyzicoObject::basketItems($basketItems, $shipping);
        $paywithiyzico = PaywithiyzicoObject::checkoutFormObjectSort($paywithiyzico);

        $pkiString = PaywithiyzicoPkiStringBuilder::pkiStringGenerate($paywithiyzico);

        $authorization = PaywithiyzicoPkiStringBuilder::authorization($pkiString, $apiKey, $secretKey, $rand);

        $paywithiyzico = json_encode($paywithiyzico, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);


        $this->context->cookie->PwiTotalPrice = $params['cart']->getOrderTotal();

        $requestResponse = PaywithiyzicoRequest::checkoutFormRequest($endpoint, $paywithiyzico, $authorization);

        if (isset($requestResponse->status)) {
            if ($requestResponse->status != 'success') {
                return $requestResponse->errorMessage;
            }
        } else {
            return 'Not Connection...';
        }

        $this->context->cookie->PwiIyziToken = $requestResponse->token;

        return $requestResponse;
    }

    /**
     * This hook is used to display the order confirmation page.
     */
    public function hookPaymentReturn($params)
    {

        if ($this->active == false) {
            return;
        }

        $order = $params['order'];

        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')) {
            $this->smarty->assign('status', 'ok');
        }

        $this->smarty->assign(array(
            'id_order' => $order->id,
            'reference' => $order->reference,
            'params' => $params,
            'total' => Tools::displayPrice($this->context->cookie->PwiTotalPrice, $this->context->currency, false),
            'PwiInstallmentFee' => Tools::displayPrice($this->context->cookie->PwiInstallmentFee, $this->context->currency, false),
        ));

        return $this->display(__FILE__, 'views/templates/front/confirmationpwi.tpl');
    }

    /**
     * @return mixed
     */

    private function setcookieSameSite($name, $value, $expire, $path, $domain, $secure, $httponly) {

        if (PHP_VERSION_ID < 70300) {

            setcookie($name, $value, $expire, "$path; samesite=None", $domain, $secure, $httponly);
        }
        else {
                return setcookie($name, $value, [
                'expires' => $expire,
                'path' => $path,
                'domain' => $domain,
                'samesite' => 'None',
                'secure' => $secure,
                'httponly' => $httponly,
            ]);

      
        }
    }

    /**
     * @return mixed
     */
    private function getOptionText()
    {
        /* TR - EN */
        
        $title = "iyzico ile Öde";

        if($this->context->language->iso_code != "tr") {
            $title = "Pay with iyzico";
        }


        return $title;
    }

    /**
     * @return array
     */
    private function paymentOptionResult()
    {
        $title = $this->getOptionText();
        $newOptions = array();

        $newOption = new PaymentOption();
        $newOption->setModuleName($this->name)
            ->setCallToActionText($this->trans($title, array(), 'Modules.Paywithiyzico'))
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
            ->setAdditionalInformation($this->fetch('module:paywithiyzico/views/templates/front/paywithiyzico.tpl'));

        $newOptions[] = $newOption;

        return $newOptions;
    }

    /**
     * @param $paywithiyzicoResponse
     * @return array
     */
    private function successAssign($paywithiyzicoResponse)
    {

        $logo_pwi = Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/img/cards.png');
        
        $this->context->smarty->assign('pwi', $paywithiyzicoResponse->payWithIyzicoPageUrl);
        $this->context->smarty->assign('cards_pwi', $logo_pwi);

        return $this->paymentOptionResult();

    }

    /**
     * @param $errorMessage
     * @return array
     */
    private function errorAssign($errorMessage)
    {
        $this->context->smarty->assign('error', $errorMessage);

        return $this->paymentOptionResult();
    }

    /**
     * @return bool|string
     */
    private function versionCheck()
    {
        $phpVersion = phpversion();
        $requiredVersion = 5.4;

        if ($phpVersion < $requiredVersion) {
            return 'Required PHP '.$requiredVersion.' and greater for paywithiyzico PrestaShop Payment Gateway';
        }

        return false;
    }

    /**
     * @param $title
     * @param $isoCode
     * @return mixed
     */
    private function iyziMultipLangTitle($title, $isoCode)
    {
        if ($title) {
            $parser = explode('|', $title);

            if (is_array($parser) && count($parser)) {
                foreach ($parser as $parse) {
                    $result = explode('=', $parse);
                    if ($isoCode == $result[0]) {
                        $title = $result[1];
                        break;
                    }
                }
            }
        }

        return $title;
    }
}