<div class="panel">

    {if $iyzipay_pwi_first_enabled_status == 0}

    {if $languageIsoCode == 'tr'}
        <div class="alert alert-danger" role="alert">
            <h1>iyzico İle Öde modülü aktif değil!</h1>
        </div>
        <p>iyzico ile Öde modülünü kurmadan iyzico Ödeme Formu ayarlarına erişemezsiniz.</p>
        <p><strong>Prestashop 1.6 - iyzico ile Öde</strong> modülünün kurulumunu tamamlayınız: <a href="https://dev.iyzipay.com/tr/acik-kaynak/prestashop" target="_blank"> https://dev.iyzipay.com/tr/acik-kaynak/prestashop </a></p>
    {else}
        <div class="alert alert-danger" role="alert">
            <h1>Pay with iyzico module is not enable!</h1>
        </div>
        <p>You can not access Settings of iyzico Checkout Form Module without installing the pay with iyzico module.</p>
        <p>Complete the installation of the <strong>Prestashop 1.6 - Pay with iyzico</strong> module via dev.iyzipay: <a href="https://dev.iyzipay.com/tr/acik-kaynak/prestashop" target="_blank"> https://dev.iyzipay.com/tr/acik-kaynak/prestashop </a></p>

    {/if}

    {else}

    <div class="iyzico-content">
        <div class="row">
            <div class="col-md-12">
                <div class="tab-pane">
                    <div class="form-group" style="width:300px;margin:auto;">
                    </div>
                    <img width="10%" src="../modules/iyzicocheckoutform/logo.png" style="float:left; margin-right:15px;">
                    v:{$moduleVersion}
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
                        <p>{$websiteBaseUrl}iyzicoform/api/webhook/{$webhookUrlKey}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{if $version.version_status == '1'}

<div class="alert alert-danger">
<img src="../modules/iyzicocheckoutform/iyzicocheckoutform.png" style="float:left; margin-right:15px;">


Yeni bir versiyon mevcut güncellemek için <a href='{$link}&updated_iyzico={$version.new_version_id}'>tıklayınız</a>. <br/>Veya iyzico entegrasyon sayfasına giderek indirebilirsiniz. <a href="https://dev.iyzipay.com/tr/acik-kaynak/prestashop" target="_blank">Entegrasyon sayfası</a>


</div>{/if}
{/if}