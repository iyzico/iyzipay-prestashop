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

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once 'classes/IyzipayOverlayScript.php';
include_once 'classes/IyzipayPkiStringBuilder.php';
include_once 'classes/IyzipayRequest.php';
include_once 'classes/IyzipayCheckoutFormObject.php';

class Iyzipay extends PaymentModule
{
    protected $config_form = false;
    public $extra_mail_vars;

    public function __construct()
    {
        $this->name = 'iyzipay';
        $this->tab = 'payments_gateways';
        $this->version = '1.2.0';
        $this->author = 'iyzico';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();


        $this->displayName              = $this->l('iyzico Checkout Form Module');
        $this->description              = $this->l('iyzico Checkout Form Module for PrestaShop');
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


        $this->limited_currencies = array('TRY','EUR','USD' , 'GBP' , 'RUB' , 'CHF' , 'NOK');


        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);

        $this->extra_mail_vars = array(
            '{instalmentFee}' => '',
        );
        $versionControl = Configuration::get('PS_INSTALL_VERSION');
        $versionControlCheck = str_replace(".", "", $versionControl);
        if ($versionControlCheck < 1775 )
        {
          $this->checkAndSetCookieSameSite();
        }
         Configuration::updateValue('PS_CONDITIONS_CMS_ID',0);





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

        $this->setIyziWebhookUrlKey();


        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('footer') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('PaymentOptions') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('ModuleRoutes');
    }

    public function hookModuleRoutes()
    {

        return [
            'module-iyzipay-webhook' => [
                'rule' => 'iyzico/api/webhook/'. $this->getIyziWebhookUrlKey(),
                'controller' => 'webhook',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => 'iyzipay'
                ]
            ]
        ];
    }

    public function uninstall()
    {

        include(dirname(__FILE__).'/sql/uninstall.php');

        return $this->unregisterHook('footer')
            && $this->unregisterHook('backOfficeHeader')
            && $this->unregisterHook('PaymentOptions')
            && $this->unregisterHook('paymentReturn')
            && Configuration::deleteByName('iyzipay_api_type')
            && Configuration::deleteByName('iyzipay_api_key')
            && Configuration::deleteByName('iyzipay_secret_key')
            && Configuration::deleteByName('iyzipay_module_status')
            && Configuration::deleteByName('iyzipay_option_text')
            && Configuration::deleteByName('iyzipay_display')
            && Configuration::deleteByName('iyzipay_overlay_position')
            && Configuration::deleteByName('iyzipay_overlay_token')
            && Configuration::deleteByName('iyzipay_pwi_first_enabled_status')
            && Configuration::deleteByName('iyzipay_language')
            && Configuration::updateValue('PS_CONDITIONS_CMS_ID',3)
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
        if (((bool)Tools::isSubmit('submitIyzipayModule')) == true) {
            $this->postProcess();
        }
        $sslControl = $_SERVER['HTTPS'] ;


        $this->registerHook('ModuleRoutes');

        $this->setIyziWebhookUrlKey();

        $this->context->smarty->assign('module_dir', $this->_path);

        $this->context->smarty->assign('webhookUrlKey', $this->getIyziWebhookUrlKey());

        $this->context->smarty->assign('websiteBaseUrl',Tools::getHttpHost(true) . __PS_BASE_URI__);

        $this->context->smarty->assign('iyziVersion', $this->version);

        $this->context->smarty->assign('languageIsoCode', $this->context->language->iso_code);

        $this->context->smarty->assign('sslEnabled', $sslControl);

        $this->context->smarty->assign('iyziApiType', Configuration::get('iyzipay_api_type'));

        $this->context->smarty->assign('cookieSamesite', Configuration::get('PS_COOKIE_SAMESITE'));

        $pwi_status_after_enabled_pwi = Configuration::get('iyzipay_pwi_first_enabled_status', true);
        if (!Module::isEnabled('paywithiyzico') && $pwi_status_after_enabled_pwi != 1){
            $this->context->smarty->assign('iyzipay_pwi_first_enabled_status', 0);

        }
        else{
            Configuration::updateValue('iyzipay_pwi_first_enabled_status',1);
            $this->context->smarty->assign('iyzipay_pwi_first_enabled_status', 1);
        }

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        /* Set iyziTitle */
        $this->setIyziTitle();

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
        $helper->id = 'iyzipay';
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitIyzipayModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );


        $pwi_status_after_enabled_pwi = Configuration::get('iyzipay_pwi_first_enabled_status', true);
        if (!Module::isEnabled('paywithiyzico') && $pwi_status_after_enabled_pwi != 1){
            return $helper->generateForm(array());
        }
        else{
            return $helper->generateForm(array($this->getConfigForm()));
        }
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
                'input' => array(

                    array(
                        'type' => 'select',
                        'label' => $this->l('API Type'),
                        'name' => 'iyzipay_api_type',
                        'desc' => $this->l('API Type Live or Sandbox'),
                        'required' => true,
                        'options' => array(
                            'query' => array(
                                array('id' => 'https://api.iyzipay.com', 'name' => 'Live'),
                                array('id' => 'https://sandbox-api.iyzipay.com', 'name' => 'Sandbox / Test'),
                            ),
                            'id' => 'id',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'col' => 4,
                        'type' => 'text',
                        'name' => 'iyzipay_api_key',
                        'desc'=> $this->l('Your API key with including 32 digit letter and number'),
                        'required' => true,
                        'label' => $this->l('Api Key'),
                    ),
                    array(
                        'col' => 4,
                        'type' => 'text',
                        'name' => 'iyzipay_secret_key',
                        'desc'=> $this->l('Your Secret Key with including 32 digit letter and number.'),
                        'required' => true,
                        'label' => $this->l('Secret Key'),
                    ),
                    array(
                        'col' => 8,
                        'type' => 'text',
                        'name' => 'iyzipay_option_text',
                        'desc'=> $this->l('Payment option text / Provides multi-language support.Example :tr=iyzico|en=Credit Cart'),
                        'label' => $this->l('Payment Text'),
                    ),

                    array(
                        'type' => 'select',
                        'label' => $this->l('Display Form'),
                        'name' => 'iyzipay_display',
                        'desc'=> $this->l('The appearance of your payment form'),
                        'required' => true,
                        'is_bool' => true,
                        'options' => array(
                            'query' => array(
                                array('id' => 'responsive', 'name' => 'Responsive'),
                                array('id' => 'popup', 'name' => 'Popup'),
                            ),
                            'id' => 'id',
                            'name' => 'name',
                        ),
                    ),

                    array(
                        'type' => 'select',
                        'label' => $this->l('Checkout language'),
                        'name' => 'iyzipay_language',
                        'required' => true,
                        'is_bool' => true,
                        'options' => array(
                            'query' => array(
                                array('id' => '', 'name' => $this->l('Automatic')),
                                array('id' => 'TR', 'name' => $this->l('Turkish')),
                                array('id' => 'EN', 'name' => $this->l('English')),
                            ),
                            'id' => 'id',
                            'name' => 'name',
                        ),
                    ),

                    array(
                        'type' => 'select',
                        'label' => $this->l('Overlay Script Position'),
                        'name' => 'iyzipay_overlay_position',
                        'required' => true,
                        'is_bool' => true,
                        'options' => array(
                            'query' => array(
                                array('id' => 'bottomLeft', 'name' => $this->l('Overlay Bottom Left')),
                                array('id' => 'bottomRight', 'name' => $this->l('Overlay Bottom Right')),
                                array('id' => 'hidden', 'name' => $this->l('Overlay Hidden')),
                            ),
                            'id' => 'id',
                            'name' => 'name',
                        ),
                    ),
                    //SameSite Selectbox
                    array(
                          'type' => 'select',
                          'label' => $this->l('Cookie SameSite'),
                          'name' => 'PS_COOKIE_SAMESITE',
                          'required' => true,
                          'is_bool' => true,
                          'desc'=> $this->l('Recommended should be selected as none.'),
                          'options' => array(
                              'query' => array(
                                  array('id' => $this->sslEnabledSamesite(), 'name' => $this->l('None')),
                                  array('id' => 'Lax', 'name' => $this->l('Lax')),
                                  array('id' => 'Strict', 'name' => $this->l('Strict')),
                              ),
                              'id' => 'id',
                              'name' => 'name',
                          ),
                      ),

                    array(
                        'type' => 'hidden',
                        'name' => 'iyzipay_overlay_token',
                    ),
                    array(
                        'type' => 'hidden',
                        'name' => 'iyzipay_webhook_url_key',
                    ),
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
            'iyzipay_api_type' => Configuration::get('iyzipay_api_type', true),
            'iyzipay_api_key' => Configuration::get('iyzipay_api_key', true),
            'iyzipay_secret_key' => Configuration::get('iyzipay_secret_key', true),
            'iyzipay_webhook_url_key' => Configuration::get('iyzipay_webhook_url_key', true),
            'iyzipay_module_status' => Configuration::get('iyzipay_module_status', true),
            'iyzipay_option_text' => Configuration::get('iyzipay_option_text', true),
            'iyzipay_display' => Configuration::get('iyzipay_display', true),
            'iyzipay_overlay_position' => Configuration::get('iyzipay_overlay_position', true),
            'iyzipay_overlay_token' => Configuration::get('iyzipay_overlay_token', true),
            'iyzipay_language' => Configuration::get('iyzipay_language', true),
            'PS_COOKIE_SAMESITE' => Configuration::get('PS_COOKIE_SAMESITE' , true),



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

        /*  Set iyziTitle */
        $this->setIyziTitle();

        /* Call Overlay Script */
        $isoCode = $this->context->language->iso_code;
        $apiKey = Tools::getValue('iyzipay_api_key');
        $secretKey = Tools::getValue('iyzipay_secret_key');
        $randNumer = rand(100000, 99999999);

        $overlayScriptObject = IyzipayOverlayScript::generateOverlayScriptObject($isoCode, $randNumer);
        $pkiString = IyzipayPkiStringBuilder::pkiStringGenerate($overlayScriptObject);
        $authorization = IyzipayPkiStringBuilder::authorization($pkiString, $apiKey, $secretKey, $randNumer);
        $overlayScriptJson = json_encode($overlayScriptObject, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $requestResponse = IyzipayRequest::callOverlayScript($overlayScriptJson, $authorization, false);

        if (isset($requestResponse->protectedShopId)) {
            Configuration::updateValue('iyzipay_overlay_token', $requestResponse->protectedShopId);
        } else {
            Configuration::updateValue('iyzipay_overlay_token', false);
        }
    }

    /**
     * @return bool
     */
    private function setIyziTitle()
    {
        $title = Configuration::get('iyzipay_option_text');

        if (!$title) {
            Configuration::updateValue('iyzipay_option_text', 'tr=Kredi ve Banka Kartı ile Ödeme |en=Credit and Debit Card |fr=Credit and Debit Card');
        }

        return true;
    }

    /**
     * @return bool
     */
    private function setIyziWebhookUrlKey()
    {
        $webhookUrl = Configuration::get('iyzipay_webhook_url_key');

        $uniqueUrlId = substr(base64_encode(time() . mt_rand()),15,6);

        if (!$webhookUrl) {
            Configuration::updateValue('iyzipay_webhook_url_key', $uniqueUrlId);
        }

        return true;
    }

    public function getIyziWebhookUrlKey(){
        if (!Configuration::get('iyzipay_webhook_url_key')){
            $output = null;
            $lanugage = $this->context->language->iso_code;
            $output .= ($lanugage == 'tr' ) ? $this->displayError('Webhook URL üretilemedi!') : $this->displayError('Webhook URL did not create!');
            return $output;
        }
        else{
            return Configuration::get('iyzipay_webhook_url_key');
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
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookFooter($params)
    {
        $this->context->smarty->assign(
            array(
                'token' => Configuration::get('iyzipay_overlay_token'),
                'position' => Configuration::get('iyzipay_overlay_position'),
            )
        );

        return $this->display(__FILE__, 'footer.tpl');
    }

    /**
     * This method is used to render the payment button,
     * Take care if the button should be displayed or not.
     */

    public function hookPaymentOptions($params)
    {

        if(!$params['cart']->id_carrier)
            return $this->paymentOptionResult();

        $iyzicoCheckoutFormResponse = $this->checkoutFormGenerate($params);

        $phpCheckVersion = $this->versionCheck();

        if ($phpCheckVersion) {
            return $this->errorAssign($phpCheckVersion);
        }

        if (!is_object($iyzicoCheckoutFormResponse)) {
            return $this->errorAssign($iyzicoCheckoutFormResponse);
        }

        return $this->successAssign($iyzicoCheckoutFormResponse);
    }

    /**
     * @param $params
     * @return mixed|string
     */
    public function checkoutFormGenerate($params)
    {
        $this->context->cookie->totalPrice = false;
        $this->context->cookie->installmentFee = false;
        $this->context->cookie->iyziToken = false;

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

        $iyzico = IyzipayCheckoutFormObject::option($params, $currency, $context, $apiKey, $this->version);
        $iyzico->buyer = IyzipayCheckoutFormObject::buyer($billingAddress);
        $iyzico->shippingAddress = IyzipayCheckoutFormObject::shippingAddress($shippingAddress);
        $iyzico->billingAddress = IyzipayCheckoutFormObject::billingAddress($billingAddress);
        $iyzico->basketItems = IyzipayCheckoutFormObject::basketItems($basketItems, $shipping);
        $iyzico = IyzipayCheckoutFormObject::checkoutFormObjectSort($iyzico);

        $pkiString = IyzipayPkiStringBuilder::pkiStringGenerate($iyzico);

        $authorization = IyzipayPkiStringBuilder::authorization($pkiString, $apiKey, $secretKey, $rand);

        $iyzico = json_encode($iyzico, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $this->context->cookie->totalPrice = $params['cart']->getOrderTotal();

        $requestResponse = IyzipayRequest::checkoutFormRequest($endpoint, $iyzico, $authorization);

        if (isset($requestResponse->status)) {
            if ($requestResponse->status != 'success') {
                return $requestResponse->errorMessage;
            }
        } else {
            return 'Not Connection...';
        }

        $this->context->cookie->iyziToken = $requestResponse->token;

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
            'total' => Tools::displayPrice($this->context->cookie->totalPrice, $this->context->currency, false),
            'installmentFee' => Tools::displayPrice($this->context->cookie->installmentFee, $this->context->currency, false),
        ));

        return $this->display(__FILE__, 'views/templates/front/confirmation.tpl');
    }


    private function setcookieSameSite($name, $value, $expire, $path, $domain, $secure, $httponly) {

        if (PHP_VERSION_ID < 70300) {

            setcookie($name, $value, $expire, "$path; samesite=None", $domain, $secure, $httponly);
        }
        else {
            setcookie($name, $value, [
                'expires' => $expire,
                'path' => $path,
                'domain' => $domain,
                'samesite' => 'None',
                'secure' => $secure,
                'httponly' => $httponly
            ]);


        }
    }

    private function checkAndSetCookieSameSite(){

        $checkCookieNames = array('PHPSESSID','OCSESSID','default','PrestaShop-','wp_woocommerce_session_');

        foreach ($_COOKIE as $cookieName => $value) {
            foreach ($checkCookieNames as $checkCookieName){
                if (stripos($cookieName,$checkCookieName) === 0) {
                    $this->setcookieSameSite($cookieName,$_COOKIE[$cookieName], time() + 86400, "/", $_SERVER['SERVER_NAME'],true, true);
                }
            }
        }
    }



    private function sslEnabledSamesite ()
    {
      $iyzipayApiType = Configuration::get('iyzipay_api_type');
      $versionControl = Configuration::get('PS_INSTALL_VERSION');
      $versionControlCheck = str_replace(".", "", $versionControl);
      if ($versionControlCheck >= 1775 && empty($_SERVER['HTTPS']) && $iyzipayApiType != 'https://api.iyzipay.com')
        {
        $sslEnabledSamesite = 'SameSite=None' ;
      }
      else {
          $sslEnabledSamesite = 'None' ;
      }
      return $sslEnabledSamesite;
    }

    /**
     * @return mixed
     */
    private function getOptionText()
    {
        $title = Configuration::get('iyzipay_option_text');

        $language=Configuration::get('iyzipay_language');

        empty($language)? $isoCode = $this->context->language->iso_code : $isoCode  = strtolower(Configuration::get('iyzipay_language'));

        $title = $this->iyziMultipLangTitle($title, $isoCode);

        return $title;
    }

    /**
     * @return array
     */
    private function paymentOptionResult()
    {
        $title = $this->getOptionText();
        $newOptions = array();

        $cards_logo = Media::getMediaPath(_PS_MODULE_DIR_.$this->name."/views/img/cards.png");

        $newOption = new PaymentOption();
        $newOption->setModuleName($this->name)
            ->setLogo($cards_logo)
            ->setCallToActionText($this->trans($title, array(), 'Modules.Iyzipay'))
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
            ->setAdditionalInformation($this->fetch('module:iyzipay/views/templates/front/iyzico.tpl'));

        $newOptions[] = $newOption;

        return $newOptions;
    }

    /**x
     * @param $iyzicoCheckoutFormResponse
     * @return array
     */
    private function successAssign($iyzicoCheckoutFormResponse)
    {
        $logo = Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/img/cards.png');
        $iyzipay_language = Configuration::get('iyzipay_language');

        $title = $this->getOptionText();


        $this->context->smarty->assign('response', $iyzicoCheckoutFormResponse->checkoutFormContent);
        $this->context->smarty->assign('form_class', Configuration::get('iyzipay_display'));
        $this->context->smarty->assign('credit_card', $title);
        if(empty($iyzipay_language))
        {
          $this->context->smarty->assign('contract_text', $this->l('Contract approval is required for the payment form to be active.'));
        }
        elseif ($iyzipay_language == 'TR') {
          $this->context->smarty->assign('contract_text', 'Ödeme formunun aktif olması için sözleşme onayı gereklidir.');
        }
        else {
          $this->context->smarty->assign('contract_text', 'Contract approval is required for the payment form to be active.');

        }

        $this->context->smarty->assign('cards', $logo);
        $this->context->smarty->assign('module_dir', __PS_BASE_URI__);
        Configuration::updateValue('PS_CONDITIONS_CMS_ID',3);
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
            return 'Required PHP '.$requiredVersion.' and greater for iyzico PrestaShop Payment Gateway';
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
