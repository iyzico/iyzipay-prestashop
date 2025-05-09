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
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/


$(document).ready(function() {
    $("#module_form").submit(function(a) {
        var e, i, r;
        if (e = $("#iyzipay_api_type").val(), i = $("#iyzipay_api_key").val(), r = $("#iyzipay_api_secret_key").val(), "" != i && "" != r || alert("Api Key ve Secret Key Boş Bırakılamaz !"), "https://sandbox-api.iyzipay.com" == e) {
            if ("sandbox-" == i.substring(0, 8) && "sandbox-" == r.substring(0, 8)) return;
            alert("Sandbox / Test API için Live API Anahtarları kullanılamaz !")
        } else if ("https://api.iyzipay.com" == e) {
            if ("sandbox-" != i.substring(0, 8) && "sandbox-" != r.substring(0, 8)) return;
            alert("Live API için Sandbox / Test API Anahtarları kullanılamaz !")
        }
        a.preventDefault()
    })
});