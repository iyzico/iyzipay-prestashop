<?php

if (!defined('_PS_VERSION_'))
    exit;

include_once 'classes/PaywithiyzicoPkiStringBuilder.php';
include_once 'classes/PaywithiyzicoRequest.php';
include_once 'classes/PaywithiyzicoObject.php';

class Paywithiyzico extends PaymentModule{

    public static $baseUrl = "https://sandbox-api.iyzipay.com";

    public function __construct()
    {
        $this->name = "paywithiyzico";
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'iyzico';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Pay with iyzico Payment Module');
        $this->description = $this->l('Pay with iyzico Payment Gateway for PrestaShop');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6.99.99');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        $this->checkAndSetCookieSameSite();
    }
    
    public function install(){

        if (!parent::install()
            || !$this->registerHook('payment')
            || !$this->registerHook('paymentReturn')
        )
            return false;

        $this->createPwiOrderTable();

        return true;
    }

    public function uninstall()
    {
        if (parent::uninstall()) {
            foreach ($this->hooks as $hook) {
                if (!$this->unregisterHook($hook))
                    return false;
            }
        }
        return true;
    }


    /**
     * This hook is used to redirect the paywithiyzico page
     * @param $params
     * @return mixed|void
     */
    public function hookPayment($params)
    {

        if (!$this->active)
            return;

        $pwiResponse = $this->PwiGenerate($params);

        $phpVersionCheck = $this->phpVersionCheck();

        if ($phpVersionCheck) {
            return $this->errorAssign($phpVersionCheck);
        }

        if (!is_object($pwiResponse)) {
            return $this->errorAssign($pwiResponse);
        }

        return $this->successAssign($pwiResponse);
    }

    /**
     * @param $params
     * @return void This hook is used to display the order confirmation page.
     */
    public function hookPaymentReturn($params)
    {
        if (!$this->active)
            return;

        $order = $params['objOrder'];

        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')) {
            $this->smarty->assign('status', 'ok');
        }

        $this->smarty->assign(array(
            'id_order' => $order->id,
            'reference' => $order->reference,
            'total' => Tools::displayPrice($this->context->cookie->PwiTotalPrice, $this->context->currency, false),
            'PwiInstallmentFee' => Tools::displayPrice($this->context->cookie->PwiInstallmentFee, $this->context->currency, false),
        ));

        return $this->display(__FILE__, 'views/templates/front/confirmationpwi.tpl');
    }

    /**
     * Initialize PWI Request
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
        $apiKey = Configuration::get('IYZICO_FORM_LIVE_API_ID');
        $secretKey = Configuration::get('IYZICO_FORM_LIVE_SECRET');
        $rand = rand(100000, 99999999);
        $endpoint = self::$baseUrl;

        $paywithiyzico = PaywithiyzicoObject::option($params, $context);
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
     * @param $errorMessage
     * @return mixed
     */
    private function errorAssign($errorMessage)
    {
        $this->context->smarty->assign('error', $errorMessage);

        return $this->pwiPaymentOptionDisplay();
    }

    /**
     * @param $pwiResponse
     * @return mixed
     */
    public function successAssign($pwiResponse)
    {
        $this->context->smarty->assign('pwi_page_url', $pwiResponse->payWithIyzicoPageUrl);

        return $this->pwiPaymentOptionDisplay();
    }

    /**
     * @return mixed
     */
    public function pwiPaymentOptionDisplay()
    {
        $this->smarty->assign(array(
            'this_description' => $this->getPwiDescriptionForLanguage(),
            'this_logo' => $this->_path . 'views/img/'. $this->getPwiLogoSvgForLanguage(),
            'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
        ));

        return $this->display(__FILE__, 'payment.tpl');
    }

    /**
     * @return mixed
     */
    public function getLanguageIsoCode()
    {
        $languageId = $this->context->cookie->id_lang;
        $languages = Language::getLanguage($languageId);
        return $languageIsoCode = $languages['iso_code'];
    }

    /**
     * @return string
     */
    public function getPwiLogoSvgForLanguage()
    {
        return ($this->getLanguageIsoCode() != 'tr') ? 'pay-with-iyzico.svg' : 'pay-with-iyzico-tr.svg';

    }


    /**
     * @return string
     */
    public function getPwiDescriptionForLanguage()
    {
        return ($this->getLanguageIsoCode() != 'tr')
            ?
            'You can easily pay for your shopping
            with your iyzico balance, with your stored card or money transfer method;
            get 24/7 live support with the advantage of iyzico Buyer Protection on any matter.'
            :
            'Alışverişini ister iyzico bakiyenle, ister saklı kartınla,
             ister havale/EFT yöntemi ile kolayca öde; aklına takılan herhangi bir konuda iyzico Korumalı Alışveriş avantajıyla
             7/24 canlı destek al.';
    }

    /**
     * @param $name
     * @param $value
     * @param $expire
     * @param $path
     * @param $domain
     * @param $secure
     * @param $httponly
     * @return void
     */
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

    /**
     * @return void
     */
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

    /**
     * @return bool|string
     */
    private function phpVersionCheck()
    {
        $requiredVersion = 5.4;
        $phpVersion = phpversion();

        if (version_compare($phpVersion, $requiredVersion, '<')) {
            return ($this->getLanguageIsoCode() != 'tr')
                ?
                'Required PHP '.$requiredVersion.' and greater for paywithiyzico PrestaShop Payment Gateway.
                Please contact your hosting provider to upgrade PHP version. Your PHP version: '
                .$phpVersion.''
                :
                'iyzico ile Öde modülünün çalışması için minimum PHP versiyonunuz '.$requiredVersion. ' olmalıdır.
                PHP versiyonunuzu yükseltmek için hosting firmanızla iletişime geçebilirsiniz. Sunucu PHP 
                versiyonunuz: '.$phpVersion.'';
        }
        return false;
    }

    /**
     * Create PWI Order Table
     * @return bool
     */
    private function createPwiOrderTable(){

        if (!Db::getInstance()->Execute('CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'paywithiyzico_order` (
                       `paywithiyzico_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                        `payment_id` int(11) NOT NULL,
                        `order_id` int(11) NOT NULL,
                        `total_amount` DECIMAL( 10, 2 ) NOT NULL,
                        `status` varchar(25) NOT NULL,
                        `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                         PRIMARY KEY (`paywithiyzico_id`)
                    ) ENGINE= ' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8'))
            return false;
        return true;
    }
}