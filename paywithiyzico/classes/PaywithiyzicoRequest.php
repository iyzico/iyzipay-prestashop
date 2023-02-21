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

class PaywithiyzicoRequest
{
    /**
     * @param $endpoint
     * @param $json
     * @param $authorization
     * @return mixed
     */
    public static function checkoutFormRequest($endpoint, $json, $authorization)
    {
        $endpoint .= '/payment/pay-with-iyzico/initialize';

        return PaywithiyzicoRequest::curlPost($json, $authorization, $endpoint);
    }

    /**
     * @param $endpoint
     * @param $json
     * @param $authorization
     * @return mixed
     */
    public static function checkoutFormRequestDetail($endpoint, $json, $authorization)
    {
        $endpoint .= '/payment/iyzipos/checkoutform/auth/ecom/detail';

        return PaywithiyzicoRequest::curlPost($json, $authorization, $endpoint);
    }

    /**
     * @param $endpoint
     * @param $json
     * @param $authorization
     * @return mixed
     */
    public static function paymentCancel($endpoint, $authorization, $json)
    {
        $endpoint .= '/payment/cancel';

        return PaywithiyzicoRequest::curlPost($json, $authorization, $endpoint);
    }

    /**
     * @param $json
     * @param $authorization
     * @param $endpoint
     * @return mixed
     */
    public static function curlPost($json, $authorization, $endpoint)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $endpoint);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_NONE);
        curl_setopt($curl, CURLOPT_TIMEOUT, 150);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                "Authorization:".$authorization['authorization'],
                "x-iyzi-rnd:".$authorization['randValue'],
                "Content-Type: application/json",
        ));
        $result = json_decode(curl_exec($curl));
        curl_close($curl);

        return $result;
    }
}
