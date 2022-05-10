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
<style type="text/css">
	.accordionBox {
		background-color:#fff;
		color:#444;
		cursor:pointer;
		padding:18px;
		width:100%;
		border:none;
		text-align:left;
		outline:0;
		transition:.4s;
		border-bottom:1px solid #5cb7e7;
		margin:5px;
		font-size:16px;
		font-weight:700
	}

	.panelBox {
		display:none;
		padding:15px;
	}
</style>
<div class="panel">
	<p class="text-muted">


				{if $languageIsoCode == 'tr'}

					<div class="accordionBox">Cookie SameSite nedir ?</div>
					<div class="panelBox">

							</ul>
							<p>Google Chrome tarayıcılarında yapılan güncelleme ile sameSite=None ve secure olarak tanımlanmamış cookiler varsayılan olarak sameSite=Lax(first-party cookie) kabul edilecektir. Bu da first-party cookielere sadece tanımlanan domain/host üzerinden ulaşılabileceği anlamına gelmektedir. Sitenize cross-site request olması durumunda(örn:farklı bir domain üzerinden yapılan post isteği) browser üzerinde cookie erişimi güvenlik sebebiyle kısıtlanmaktadır.</p>

							<p>Sorunsuz ödeme almak için SameSite = None olarak kalması gerekiyor.Bunun için aşağıdaki yöntemlerden <b>birini</b> kullanmanız yeterli olacaktır.;</p>
							<ul>

									<li><strong>PrestaShop admin paneli içnde Yapılandır > Gelişmiş Parametler > Genel > Cookie SameSite Hiçbiri(None) olarak değiştirin.</strong></li>
									<li><strong>PrestaShop İyzico eklenti içinde bulunan Cookie SameSite alanı Hiçbiri(None) olarak seçiniz.</strong></li>
							</ul>
							<p> <b>* Not</b> : SSL aktif etmeyi unutmayınız.</p>

					</div>
					<div class="accordionBox">Live  ve Sandbox  Nedir ?</div>
					<div class="panelBox">
					    <p><stong>Live</stong> ve <strong>Sandbox</strong> kullanacağınız API türünü yansıtmaktadır.</p>
					    <ul>
					        <li><strong>Live API</strong></li>
					    </ul>
					    <p>Müşterilerinizden gerçek ödeme almak için Live Api kullanılır.  Müşterilerinizin kartları aracılığıyla İyzico üzerinden ödeme alabilmeniz için kullanılır.</p>
					    <ul>
					        <li><strong>Sandbox API</strong></li>
					    </ul>
					    <p>Web sayfanızı müşterilerinize açmadan önce İyzico Api ile test yapmak için kullanılır. Yapılan istekler gerçek istekler değildir, sadece geliştirme amaçlı kullanılır.</p>
					</div>

					<div class="accordionBox">Responsive ve Popup Nedir ?</div>
					<div class="panelBox">
					    <ul>
					        <li><strong>Responsive</strong></li>
					    </ul>
					    <p>Müşterileriniz ödeme adımına geldiği zaman  ödeme formunun Mobil ve Web uyumlu olarak görünmesini sağlar.</p>
					    <ul>
					        <li><strong>Popup</strong></li>
					    </ul>
					    <p>Müşterileriniz ödeme adımına geldiği zaman tüm ekranı şeffaf olarak kaplayan Mobil ve Web uyumlu İyzico ödeme formunun görünmesini sağlar.</p>
					</div>

					<div class="accordionBox">Not Connection hatası nedir ?</div>
					<div class="panelBox">
					    <ul>
					        <li><strong>API bilgilerinizi kontrol ettiniz mi ?</strong></li>
					    </ul>
					    <p>İyzico üzerinden aldığınız "<strong>API Anahtarı</strong>” ve “<strong>Güvenlik Anahtarı</strong>” bilgilerinin  doğru olduğundan emin olunuz.</p>
					    <ul>
					        <li><strong>TLS versiyonunuzu kontrol ettiniz mi ?</strong></li>
					    </ul>
					    <p>Sunucu sağlayıcınızla görüşerek OpenSSL versiyonunu minimum 1.0.1’e, curl versiyonunu 7.30.4 yukseltebilirsiniz.</p>
					    <ul>
					        <li><strong>Sorununuz hala devam ediyor mu ?</strong></li>
					    </ul>
					    <p>Bizimle iletişime geçebilirsiniz. Destek için: <a href="mailto:destek@iyzico.com" target="_top">destek@iyzico.com</a>
					</div>

				</p>
			</div>




				{else}



					<div class="accordionBox">What is Cookie SameSite  ?</div>
					<div class="panelBox">

							</ul>
							<p>With the update made in Google Chrome browsers, cookies not defined as sameSite=None and secure will be accepted as sameSite=Lax(first-party cookie) by default. This means that first-party cookies can only be accessed through the defined domain/host. In case of a cross-site request to your site (eg post request from a different domain), cookie access on the browser is restricted for security reasons.</p>

							<p>Needs to remain SameSite = None to get paid seamlessly.For this, it will be sufficient to use <b>one</b> of the following methods;</p>
							<ul>

									<li><strong>In PrestaShop admin panel Configure > Advanced Parameters > General > Change Cookie SameSite to None</strong></li>
									<li><strong>Select the Cookie SameSite field in PrestaShop İyzico plugin as None</strong></li>
							</ul>
							<p> <b>* Note</b> : Don't forget to enable SSL.</p>

					</div>
					<div class="accordionBox">What is Live  and Sandbox   ?</div>
					<div class="panelBox">
					    <p><stong>Live</stong> and <strong>Sandbox</strong> reflects the type of API you will use.</p>
					    <ul>
					        <li><strong>Live API</strong></li>
					    </ul>
					    <p>Live Api is used to get real payment from your customers. It is used to receive payments via Iyzico through the cards of your customers.</p>
					    <ul>
					        <li><strong>Sandbox API</strong></li>
					    </ul>
					    <p>It is used to test with Iyzico Api before opening your web page to your customers. Requests made are not real requests, they are used for development purposes only.</p>
					</div>

					<div class="accordionBox">What is Responsive and Popup  ?</div>
					<div class="panelBox">
					    <ul>
					        <li><strong>Responsive</strong></li>
					    </ul>
					    <p>It ensures that the payment form appears as Mobile and Web compatible when your customers come to the payment step.</p>
					    <ul>
					        <li><strong>Popup</strong></li>
					    </ul>
					    <p>When your customers come to the payment step, it provides the mobile and web compatible Iyzico payment form, which covers the entire screen transparently.</p>
					</div>

					<div class="accordionBox">What is the Note Connection error ?</div>
					<div class="panelBox">
					    <ul>
					        <li><strong>Have you checked your API information??</strong></li>
					    </ul>
					    <p>Make sure that the "<strong>API Key</strong>" and "<strong>Security Key</strong>" information you received from iyzico are correct.</p>
					    <ul>
					        <li><strong>Did you check the TLS version?</strong></li>
					    </ul>
					    <p>You can upgrade the OpenSSL version to a minimum of 1.0.1 and the curl version to 7.30.4 by contacting your server provider.</p>
					    <ul>
					        <li><strong>Does your problem still persist?</strong></li>
					    </ul>
					    <p>You can contact us. Support: <a href="mailto:destek@iyzico.com" target="_top">destek@iyzico.com</a>
					</div>

				</p>
			</div>




				{/if}




<script>
var acc = document.getElementsByClassName("accordionBox");
var i;

for (i = 0; i < acc.length; i++) {
    acc[i].addEventListener("click", function() {
        this.classList.toggle("active");
        var panel = this.nextElementSibling;
        if (panel.style.display === "block") {
            panel.style.display = "none";
        } else {
            panel.style.display = "block";
        }
    });
}
</script>
