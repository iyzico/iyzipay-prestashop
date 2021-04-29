{*
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
*}

<div class="panel">

    <div class="iyzico-content">
        <div class="row">
            <div class="col-md-12">
                <div class="tab-pane" id="tab-buyer-protection">
                    <div class="form-group" style="width:300px;margin:auto;">
                    </div>
                    <div class="col-sm-12">
                        <h1>iyzico Prestashop Webhooks</h1>

                        {if $languageIsoCode == 'tr'}
                            <p> <strong>iyzico webhooks yapısının kullanılması ile, müşterilerinizin ödeme sonrasında yaşayabileceği internet, tarayıcı kaynaklı problemlerde siparişlerin prestashop panel tarafına doğru bir şekilde iletilmesini sağlayabilirsiniz.</strong></p>

                            <p>Prestashop'ta webhooks yapısını aktif edebilmek için aşağıdaki adımları uygulamanız gerekmektedir.</p>
                            <h1>Webhook Kurulum Adımları</h1>
                            <ol>
                                <li>Aşağıdaki Webhook URL adresini kopyalayın.</li>
                                <li>iyzico üye işyeri paneline <a href="https://merchant.iyzipay.com/" target="_blank">(https://merchant.iyzipay.com/)</a> giriş yaptıktan sonra, Sol menüden Ayarlar->Firma Ayarları tıklayın.</li>
                                <li>Açılan sayfada İşyeri Bildirimleri bölümündeki URL alanına webhook URL adresinizi yapıştırın.</li>
                                <li>İşyeri Bildirimleri bölümündeki “Ödeme bildirimlerini gönder” seçeneğini aktif edin.</li>
                                <li>Kaydet’e tıklayın.</li>
                            </ol>
                        {else}
                            <p><strong>When a payment attemt is made, it is possible to receive the transaction result via notification.</strong></p>
                            <p>In order to activate the webhooks in Prestashop, you need to follow the steps below.</p>

                            <h1>Webhook Integration Steps</h1>
                            <ol>
                                <li>Copy webhook URL below.</li>
                                <li>Sing in to  <a href="https://merchant.iyzipay.com/" target="_blank">(https://merchant.iyzipay.com/)</a> and click  Settings->Merchant Settings on left panel.</li>
                                <li>Find merchant notifications area in the page, paste webhook URL to merchant notification url.</li>
                                <li>Turn on Receive notifications for payments button.</li>
                                <li>Save Settings.</li>
                            </ol>
                        {/if}

                        <h2>Webhook URL</h2>
                        <p>{$websiteBaseUrl}iyzico/api/webhook/{$webhookUrlKey}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    li{
        font-size: 14px;
    }
    p{
        font-size: 14px;
    }
</style>