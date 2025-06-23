<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once rtrim(_PS_MODULE_DIR_, '/') . '/iyzipay/vendor/autoload.php';
require_once rtrim(_PS_MODULE_DIR_, '/') . '/iyzipay/classes/IyzipayCheckoutFormObject.php';
require_once rtrim(_PS_MODULE_DIR_, '/') . '/iyzipay/classes/IyzipayHelper.php';
require_once rtrim(_PS_MODULE_DIR_, '/') . '/iyzipay/classes/IyzipayModel.php';

class Iyzipay extends PaymentModule
{
    protected $config_form = false;
    public $extra_mail_vars;

    public function __construct()
    {
        $this->name                     = 'iyzipay';
        $this->tab                      = 'payments_gateways';
        $this->version                  = '2.1.4';
        $this->author                   = 'iyzico';
        $this->need_instance            = 1;
        $this->bootstrap                = true;

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
        $this->commissionAmount         = $this->l('commissionAmount');
        $this->confirmUninstall         = $this->l('are you sure ?');
        $this->limited_currencies       = array('TRY', 'EUR', 'USD', 'GBP', 'RUB', 'CHF', 'NOK');
        $this->ps_versions_compliancy   = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->extra_mail_vars          = array('{instalmentFee}' => '');
        $versionControlCheck            = str_replace(".", "", Configuration::get('PS_INSTALL_VERSION'));

        if ($versionControlCheck < 1775) {
            $this->checkAndSetCookieSameSite();
        }

        Configuration::updateValue('PS_CONDITIONS_CMS_ID', 0);

        // Cookie değişkenini oluştur
        if (isset($this->context->cookie) && !isset($this->context->cookie->iyziPaymentType)) {
            $this->context->cookie->iyziPaymentType = null;
        }
    }

    public function install()
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        $this->setIyziWebhookUrlKey();
        $this->iyzipaySetWebhookUrlKey();
        $this->setIyziTitle();
        $this->setIyziPwiTitle();

        require_once rtrim(_PS_MODULE_DIR_, '/') . '/iyzipay/sql/install.php';

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
                'rule' => 'iyzico/api/webhook/' . $this->getIyziWebhookUrlKey(),
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
        require_once rtrim(_PS_MODULE_DIR_, '/') . '/iyzipay/sql/uninstall.php';

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
            && Configuration::deleteByName('iyzipay_language')
            && Configuration::deleteByName('thankyou_page_text')
            && Configuration::deleteByName('iyzipay_active_webhook_url')
            && Configuration::deleteByName('iyzipay_pwi_enabled')
            && Configuration::deleteByName('iyzipay_pwi_option_text')
            && Configuration::updateValue('PS_CONDITIONS_CMS_ID', 3)
            && parent::uninstall();
    }

    public function getContent()
    {
        if (((bool)Tools::isSubmit('submitIyzipayModule')) == true) {
            $this->postProcess();
        }

        $this->registerHook('ModuleRoutes');
        $this->setIyziWebhookUrlKey();
        $this->setIyziTitle();

        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->smarty->assign('webhookUrlKey', $this->getIyziWebhookUrlKey());
        $this->context->smarty->assign('websiteBaseUrl', Tools::getHttpHost(true) . __PS_BASE_URI__);
        $this->context->smarty->assign('iyziVersion', $this->version);
        $this->context->smarty->assign('languageIsoCode', $this->context->language->iso_code);
        $this->context->smarty->assign('sslEnabled', empty($_SERVER['HTTPS']));
        $this->context->smarty->assign('iyziApiType', Configuration::get('iyzipay_api_type'));
        $this->context->smarty->assign('cookieSamesite', Configuration::get('PS_COOKIE_SAMESITE'));
        $this->context->smarty->assign('webhookActiveButton', $this->iyzicoWebhookSubmitbutton());

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
    }

    protected function renderForm()
    {
        $helper                             = new HelperForm();
        $helper->show_toolbar               = false;
        $helper->table                      = $this->table;
        $helper->module                     = $this;
        $helper->default_form_language      = $this->context->language->id;
        $helper->allow_employee_form_lang   = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->id                         = 'iyzipay';
        $helper->identifier                 = $this->identifier;
        $helper->submit_action              = 'submitIyzipayModule';
        $helper->currentIndex               = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token                      = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars                   = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

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
                        'desc' => $this->l('Your API key with including 32 digit letter and number'),
                        'required' => true,
                        'label' => $this->l('Api Key'),
                    ),
                    array(
                        'col' => 4,
                        'type' => 'text',
                        'name' => 'iyzipay_secret_key',
                        'desc' => $this->l('Your Secret Key with including 32 digit letter and number.'),
                        'required' => true,
                        'label' => $this->l('Secret Key'),
                    ),
                    array(
                        'col' => 8,
                        'type' => 'text',
                        'name' => 'iyzipay_option_text',
                        'desc' => $this->l('Payment option text / Provides multi-language support.Example :tr=iyzico|en=Credit Cart'),
                        'label' => $this->l('Payment Text'),
                    ),

                    array(
                        'type' => 'select',
                        'label' => $this->l('Display Form'),
                        'name' => 'iyzipay_display',
                        'desc' => $this->l('The appearance of your payment form'),
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
                    array(
                        'type' => 'select',
                        'label' => $this->l('Enable Pay with iyzico'),
                        'name' => 'iyzipay_pwi_enabled',
                        'required' => true,
                        'is_bool' => true,
                        'desc' => $this->l('Add "Pay with iyzico" as a separate payment option'),
                        'options' => array(
                            'query' => array(
                                array('id' => '1', 'name' => $this->l('Enabled')),
                                array('id' => '0', 'name' => $this->l('Disabled')),
                            ),
                            'id' => 'id',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'col' => 8,
                        'type' => 'text',
                        'name' => 'iyzipay_pwi_option_text',
                        'desc' => $this->l('Pay with iyzico option text / Provides multi-language support.Example :tr=iyzico ile Öde|en=Pay with iyzico'),
                        'label' => $this->l('Pay with iyzico Text'),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Cookie SameSite'),
                        'name' => 'PS_COOKIE_SAMESITE',
                        'required' => true,
                        'is_bool' => true,
                        'desc' => $this->l('Recommended should be selected as none.'),
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
            'iyzipay_active_webhook_url' => Configuration::get('iyzipay_active_webhook_url', true),
            'iyzipay_language' => Configuration::get('iyzipay_language', true),
            'PS_COOKIE_SAMESITE' => Configuration::get('PS_COOKIE_SAMESITE', true),
            'iyzipay_pwi_enabled' => Configuration::get('iyzipay_pwi_enabled', true),
            'iyzipay_pwi_option_text' => Configuration::get('iyzipay_pwi_option_text', true),
        );
    }

    protected function postProcess()
    {

        $this->setIyziTitle();
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }

        $options = new \Iyzipay\Options();
        $options->setApiKey(Tools::getValue('iyzipay_api_key'));
        $options->setSecretKey(Tools::getValue('iyzipay_secret_key'));
        $options->setBaseUrl(Configuration::get('iyzipay_api_type'));

        $request = new \Iyzipay\Request\RetrieveProtectedOverleyScriptRequest();
        $request->setPosition(Tools::getValue('iyzipay_overlay_position'));

        $response = new  \Iyzipay\Model\ProtectedOverleyScript($options, $request);

        if ($response->getStatus() == 'success') {
            Configuration::updateValue('iyzipay_overlay_token', $response->getProtectedShopId());
        } else {
            Configuration::updateValue('iyzipay_overlay_token', false);
        }
    }

    private function setIyziTitle()
    {
        $title = Configuration::get('iyzipay_option_text');

        if (!$title) {
            Configuration::updateValue('iyzipay_option_text', 'tr=Kredi ve Banka Kartı ile Ödeme |en=Credit and Debit Card |fr=Credit and Debit Card');
        }

        return true;
    }

    private function setIyziPwiTitle()
    {
        $title = Configuration::get('iyzipay_pwi_option_text');

        if (!$title) {
            Configuration::updateValue('iyzipay_pwi_option_text', 'tr=iyzico ile Öde|en=Pay with iyzico|fr=Pay with iyzico');
        }

        return true;
    }

    private function setIyziWebhookUrlKey()
    {
        $webhookUrl = Configuration::get('iyzipay_webhook_url_key');
        $uniqueUrlId = substr(base64_encode(time() . mt_rand()), 15, 6);

        if (!$webhookUrl) {
            Configuration::updateValue('iyzipay_webhook_url_key', $uniqueUrlId);
        }

        return true;
    }

    public function getIyziWebhookUrlKey()
    {
        if (!Configuration::get('iyzipay_webhook_url_key')) {
            $output = null;
            $lanugage = $this->context->language->iso_code;
            $output .= ($lanugage == 'tr') ? $this->displayError('Webhook URL üretilemedi!') : $this->displayError('Webhook URL did not create!');
            return $output;
        } else {
            return Configuration::get('iyzipay_webhook_url_key');
        }
    }

    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    public function hookFooter()
    {
        $this->context->smarty->assign(array(
            'token' => Configuration::get('iyzipay_overlay_token'),
            'position' => Configuration::get('iyzipay_overlay_position')
        ));

        return $this->display(__FILE__, 'footer.tpl');
    }

    public function hookPaymentOptions($params)
    {
        if (!$params['cart']->id_carrier) {
            return $this->paymentOptionResult();
        }

        $iyzicoCheckoutFormResponse = $this->checkoutFormGenerate($params);
        $phpCheckVersion            = $this->versionCheck();

        if ($phpCheckVersion) {
            return $this->errorAssign($phpCheckVersion);
        }

        $paymentOptions = $this->successAssign($iyzicoCheckoutFormResponse);

        // Pay with iyzico option if enabled
        if (Configuration::get('iyzipay_pwi_enabled')) {
            $pwi_option = $this->payWithIyzicoOptionResult();
            $paymentOptions = array_merge($paymentOptions, $pwi_option);
        }

        return $paymentOptions;
    }

    public function checkoutFormGenerate($params)
    {
        $this->context->cookie->installmentFee  = false;
        $this->context->cookie->iyziToken       = false;
        $this->context->cookie->totalPrice      = false;
        $apiKey                                 = Configuration::get('iyzipay_api_key');
        $secretKey                              = Configuration::get('iyzipay_secret_key');
        $apiType                                = Configuration::get('iyzipay_api_type');

        $customerEmail                          = $params['cookie']->email;
        $shipping                               = $params['cart']->getOrderTotal(true, Cart::ONLY_SHIPPING);
        $basketItems                            = $params['cart']->getProducts();
        $billingAddress                         = new Address($params['cart']->id_address_invoice);
        $shippingAddress                        = new Address($params['cart']->id_address_delivery);
        $billingAddress->email                  = $customerEmail;
        $shippingAddress->email                 = $customerEmail;

        $currency                               = new Currency($params['cart']->id_currency);
        $language                               = Configuration::get('iyzipay_language') ? Configuration::get('iyzipay_language') : $this->context->language->iso_code;
        $callBackUrl                            = ((Configuration::get('PS_SSL_ENABLED') || !empty($_SERVER['HTTPS'])) ? 'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'index.php?module_action=init&fc=module&module=iyzipay&controller=callback';
        $conversationId                         = $params['cookie']->id_cart;
        $price                                  = IyzipayHelper::orderProductCalc($basketItems, $shipping);
        $paidPrice                              = IyzipayHelper::priceParser($params['cart']->getOrderTotal());
        $basketId                               = $params['cookie']->id_cart;
        $paymentGroup                           = 'PRODUCT';
        $cardUserKey                            = IyzipayModel::findUserCardKey($params['cookie']->id_customer, $apiKey);
        $paymentSource                          = 'PRESTASHOP|' . _PS_VERSION_ . '|PIE|' . $this->version;

        $options = new \Iyzipay\Options();
        $options->setApiKey($apiKey);
        $options->setSecretKey($secretKey);
        $options->setBaseUrl($apiType);

        $paymentRequest = new \Iyzipay\Request\CreateCheckoutFormInitializeRequest();
        $paymentRequest->setBuyer(IyzipayCheckoutFormObject::buyer($billingAddress));
        $paymentRequest->setShippingAddress(IyzipayCheckoutFormObject::shippingAddress($shippingAddress));
        $paymentRequest->setBillingAddress(IyzipayCheckoutFormObject::billingAddress($billingAddress));
        $paymentRequest->setBasketItems(IyzipayCheckoutFormObject::basketItems($basketItems, $shipping));
        $paymentRequest->setCurrency($currency->iso_code);
        $paymentRequest->setLocale($language);
        $paymentRequest->setCallbackUrl($callBackUrl);
        $paymentRequest->setConversationId($conversationId);
        $paymentRequest->setPrice($price);
        $paymentRequest->setPaidPrice($paidPrice);
        $paymentRequest->setBasketId($basketId);
        $paymentRequest->setPaymentGroup($paymentGroup);
        $paymentRequest->setCardUserKey($cardUserKey);
        $paymentRequest->setPaymentSource($paymentSource);

        $paymentResponse = \Iyzipay\Model\CheckoutFormInitialize::create($paymentRequest, $options);

        if ($paymentResponse->getStatus() != 'success') {
            return $paymentResponse->getErrorMessage();
        }

        $this->context->cookie->iyziToken       = $paymentResponse->getToken();
        $this->context->cookie->totalPrice      = $params['cart']->getOrderTotal();

        return $paymentResponse;
    }

    public function iyzipaySetWebhookUrlKey()
    {
        $webhookActive = Configuration::get('iyzipay_active_webhook_url');

        if (empty($webhookActive)) {
            Configuration::updateValue('iyzipay_active_webhook_url', 0);
        }
    }

    public function payWithIyzicoInitialize($params)
    {
        $this->context->cookie->installmentFee  = false;
        $this->context->cookie->iyziToken       = false;
        $this->context->cookie->totalPrice      = false;
        $apiKey                                 = Configuration::get('iyzipay_api_key');
        $secretKey                              = Configuration::get('iyzipay_secret_key');
        $apiType                                = Configuration::get('iyzipay_api_type');

        $customerEmail                          = $params['cookie']->email;
        $shipping                               = $params['cart']->getOrderTotal(true, Cart::ONLY_SHIPPING);
        $basketItems                            = $params['cart']->getProducts();
        $billingAddress                         = new Address($params['cart']->id_address_invoice);
        $shippingAddress                        = new Address($params['cart']->id_address_delivery);
        $billingAddress->email                  = $customerEmail;
        $shippingAddress->email                 = $customerEmail;

        $currency                               = new Currency($params['cart']->id_currency);
        $language                               = Configuration::get('iyzipay_language') ? Configuration::get('iyzipay_language') : $this->context->language->iso_code;
        $callBackUrl                            = ((Configuration::get('PS_SSL_ENABLED') || !empty($_SERVER['HTTPS'])) ? 'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'index.php?module_action=init&fc=module&module=iyzipay&controller=pwicallback';
        $conversationId                         = $params['cookie']->id_cart;
        $price                                  = IyzipayHelper::orderProductCalc($basketItems, $shipping);
        $paidPrice                              = IyzipayHelper::priceParser($params['cart']->getOrderTotal());
        $basketId                               = $params['cookie']->id_cart;
        $paymentGroup                           = 'PRODUCT';
        $paymentSource                          = 'PRESTASHOP|' . _PS_VERSION_ . '|PIE|' . $this->version;

        $options = new \Iyzipay\Options();
        $options->setApiKey($apiKey);
        $options->setSecretKey($secretKey);
        $options->setBaseUrl($apiType);

        $paymentRequest = new \Iyzipay\Request\CreatePayWithIyzicoInitializeRequest();
        $paymentRequest->setBuyer(IyzipayCheckoutFormObject::buyer($billingAddress));
        $paymentRequest->setShippingAddress(IyzipayCheckoutFormObject::shippingAddress($shippingAddress));
        $paymentRequest->setBillingAddress(IyzipayCheckoutFormObject::billingAddress($billingAddress));
        $paymentRequest->setBasketItems(IyzipayCheckoutFormObject::basketItems($basketItems, $shipping));
        $paymentRequest->setCurrency($currency->iso_code);
        $paymentRequest->setLocale($language);
        $paymentRequest->setCallbackUrl($callBackUrl);
        $paymentRequest->setConversationId($conversationId);
        $paymentRequest->setPrice($price);
        $paymentRequest->setPaidPrice($paidPrice);
        $paymentRequest->setBasketId($basketId);
        $paymentRequest->setPaymentGroup($paymentGroup);
        $paymentRequest->setPaymentSource($paymentSource);

        $paymentResponse = \Iyzipay\Model\PayWithIyzicoInitialize::create($paymentRequest, $options);

        if ($paymentResponse->getStatus() != 'success') {
            return $paymentResponse->getErrorMessage();
        }

        $this->context->cookie->iyziToken       = $paymentResponse->getToken();
        $this->context->cookie->totalPrice      = $params['cart']->getOrderTotal();

        return $paymentResponse;
    }

    public static function iyzicoWebhookSubmitbutton()
    {
        $webhookActiveButton = Configuration::get('iyzipay_active_webhook_url');
        if ($webhookActiveButton == 2) {
            $htmlButton = '<form action="" method="post">
                       <button class="btn btn-primary" type="submit" name="button">Aktifleştir</button> *Problem yaşıyorsanız, iyzico webhook sistemini aktif etmek için iletişime geçiniz.
                        <a href="mailto:entegrasyon@iyzico.com">entegrasyon@iyzico.com</a></form>';
            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['button'])) {
                Configuration::updateValue('iyzipay_active_webhook_url', 0);
            }
            return $htmlButton;
        }
    }

    public function hookPaymentReturn($params)
    {

        if ($this->active == false) {
            return;
        }

        $order = $params['order'];

        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')) {
            $this->smarty->assign('status', 'ok');
        }

        $thankYouPage = Configuration::get('thankyou_page_text');

        $smartyAssignArr = array(
            'id_order' => $order->id,
            'reference' => $order->reference,
            'params' => $params,
            'thankYouPage' => $thankYouPage,
            'total' => Context::getContext()->currentLocale->formatPrice($this->context->cookie->totalPrice, $this->context->currency->iso_code),
        );

        if ($this->context->cookie->installmentFee != "") {
            $smartyAssignArr['installmentFee'] = Context::getContext()->currentLocale->formatPrice(floatval($this->context->cookie->installmentFee), $this->context->currency->iso_code);
        }else {
            $smartyAssignArr['installmentFee'] = '';
        }

        $this->smarty->assign($smartyAssignArr);

        return $this->display(__FILE__, 'views/templates/front/confirmation.tpl');
    }

    private function setcookieSameSite($name, $value, $expire, $path, $domain, $secure, $httponly)
    {
        if (PHP_VERSION_ID < 70300) {
            setcookie($name, $value, $expire, "$path; samesite=None", $domain, $secure, $httponly);
        } else {
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

    private function checkAndSetCookieSameSite()
    {
        $checkCookieNames = array('PHPSESSID', 'OCSESSID', 'default', 'PrestaShop-', 'wp_woocommerce_session_');
        foreach ($_COOKIE as $cookieName => $value) {
            foreach ($checkCookieNames as $checkCookieName) {
                if (stripos($cookieName, $checkCookieName) === 0) {
                    $this->setcookieSameSite($cookieName, $_COOKIE[$cookieName], time() + 86400, "/", $_SERVER['SERVER_NAME'], true, true);
                }
            }
        }
    }

    private function sslEnabledSamesite()
    {
        $iyzipayApiType = Configuration::get('iyzipay_api_type');
        $versionControl = Configuration::get('PS_INSTALL_VERSION');
        $versionControlCheck = str_replace(".", "", $versionControl);
        if ($versionControlCheck >= 1775 && empty($_SERVER['HTTPS']) && $iyzipayApiType != 'https://api.iyzipay.com') {
            $sslEnabledSamesite = 'SameSite=None';
        } else {
            $sslEnabledSamesite = 'None';
        }
        return $sslEnabledSamesite;
    }

    private function getOptionText()
    {
        $title = Configuration::get('iyzipay_option_text');
        $language = Configuration::get('iyzipay_language');

        empty($language) ? $isoCode = $this->context->language->iso_code : $isoCode  = strtolower(Configuration::get('iyzipay_language'));

        $title = $this->iyziMultipLangTitle($title, $isoCode);
        return $title;
    }

    private function getPwiOptionText()
    {
        $title = Configuration::get('iyzipay_pwi_option_text');
        $language = Configuration::get('iyzipay_language');

        empty($language) ? $isoCode = $this->context->language->iso_code : $isoCode  = strtolower(Configuration::get('iyzipay_language'));

        $title = $this->iyziMultipLangTitle($title, $isoCode);
        return $title;
    }

    private function paymentOptionResult()
    {
        $title = $this->getOptionText();
        $newOptions = array();

        $cards_logo = Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . "/views/img/cards.png");

        $newOption = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $newOption->setModuleName($this->name)
            ->setLogo($cards_logo)
            ->setCallToActionText($this->trans($title, array(), 'Modules.Iyzipay'))
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
            ->setAdditionalInformation($this->fetch('module:iyzipay/views/templates/front/iyzico.tpl'));

        $newOptions[] = $newOption;

        return $newOptions;
    }

    private function payWithIyzicoOptionResult()
    {
        $title = $this->getPwiOptionText();
        $newOptions = array();

        $language = Configuration::get('iyzipay_language');
        if ($language == 'EN') {
            $pwi_logo = Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . "/views/img/pay_with_iyzico_en.png");
        } else {
            $pwi_logo = Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . "/views/img/pay_with_iyzico_tr.png");
        }

        $newOption = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $newOption->setModuleName($this->name . '_pwi')
            ->setLogo($pwi_logo)
            ->setCallToActionText($this->trans($title, array(), 'Modules.Iyzipay'))
            ->setAction($this->context->link->getModuleLink($this->name, 'pwivalidation', array(), true))
            ->setAdditionalInformation($this->fetch('module:iyzipay/views/templates/front/pwi.tpl'));

        $newOptions[] = $newOption;

        return $newOptions;
    }

    private function successAssign($iyzicoCheckoutFormResponse)
    {
        $logo               = Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/cards.png');
        $iyzipay_language   = Configuration::get('iyzipay_language');
        $title              = $this->getOptionText();

        $this->context->smarty->assign('response', $iyzicoCheckoutFormResponse->getCheckoutFormContent());
        $this->context->smarty->assign('form_class', Configuration::get('iyzipay_display'));
        $this->context->smarty->assign('credit_card', $title);

        if (empty($iyzipay_language)) {
            $this->context->smarty->assign('contract_text', $this->l('Contract approval is required for the payment form to be active.'));
        } elseif ($iyzipay_language == 'TR') {
            $this->context->smarty->assign('contract_text', 'Ödeme formunun aktif olması için sözleşme onayı gereklidir.');
        } else {
            $this->context->smarty->assign('contract_text', 'Contract approval is required for the payment form to be active.');
        }

        $this->context->smarty->assign('cards', $logo);
        $this->context->smarty->assign('module_dir', __PS_BASE_URI__);
        Configuration::updateValue('PS_CONDITIONS_CMS_ID', 3);

        return $this->paymentOptionResult();
    }

    private function errorAssign($errorMessage)
    {
        $this->context->smarty->assign('error', $errorMessage);
        return $this->paymentOptionResult();
    }

    private function versionCheck()
    {
        $phpVersion = phpversion();
        $requiredVersion = 7.4;

        if ($phpVersion < $requiredVersion) {
            return 'Required PHP ' . $requiredVersion . ' and greater for iyzico PrestaShop Payment Gateway';
        }

        return false;
    }

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

    /**
     * Check if the currency is supported by the module
     * @param Cart $cart
     * @return boolean
     */
    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }
}