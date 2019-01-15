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
		<div class="accordionBox">API bilgileri nedir ?</div>
		<div class="panelBox">
		    <p>API bilgileri şifrelenerek size özel olarak tanımlanmış anahtar bilgileridir.  Bu anahtarlar siteniz üzerinden İyzico servisleri ile iletişim kurmanızı sağlar.
		    </p>
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
		<div class="accordionBox">API bilgilerime nereden ulaşabilirim ?</div>
		<div class="panelBox">
		    <ul>
		        <li><strong>Live API</strong></li>
		    </ul>
		    <p><a href="https://merchant.iyzipay.com">https://merchant.iyzipay.com</a> adresi üzerinden müşteri bilgileriniz ile giriş yapınız. Panele eriştiğiniz sırada sağ üst köşede profil bilgilerinizi göreceksiniz. Profil bilgilerinizin üzerine tıkladıktan sonra “<strong>Ayarlar</strong>” menüsüne tıklayınız. “<strong>API Anahtları</strong>" alanından “<strong>API Anahtarı ve Güvenlik Anahtarı</strong>" bilgilerinizi kopyalayıp Opencart iyzico modülü panelinde bulunan “<strong>API Anahtarı</strong>” ve “<strong>Güvenlik Anahtarı</strong>” alanlarına yapıştırınız..</p>
		    <ul>
		        <li><strong>Sandbox API</strong></li>
		    </ul>
		    <p><a href="https://sandbox-merchant.iyzipay.com">https://sandbox-merchant.iyzipay.com</a> adresi üzerinden müşteri bilgileriniz ile giriş yapınız. Panele eriştiğiniz sırada sağ üst köşede profil bilgilerinizi göreceksiniz. Profil bilgilerinizin üzerine tıkladıktan sonra “<strong>Ayarlar</strong>” menüsüne tıklayınız. “<strong>API Anahtları</strong>" alanından “<strong>API Anahtarı ve Güvenlik Anahtarı</strong>" bilgilerinizi kopyalayıp Opencart iyzico modülü panelinde bulunan “<strong>API Anahtarı</strong>” ve “<strong>Güvenlik Anahtarı</strong>” alanlarına yapıştırınız..</p>
		    <p>Test ortamımız için <a href="https://sandbox-merchant.iyzipay.com/login">https://sandbox-merchant.iyzipay.com/login</a>  adresinden kayıt olup hemen sonrasında(mail onaysız) login olabiliyorsunuz.  Sandbox’ı test ederken <a href="https://dev.iyzipay.com/tr/test-kartlari">https://dev.iyzipay.com/tr/test-kartlari</a> adresindeki test kartlarını kullanabilirsiniz.</p>
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
		<div class="accordionBox">Korumalı Alışveriş Nedir ?</div>
		<div class="panelBox">
		    <p>Korumalı Alışveriş müşterilerinizin ve sizin İyzico güvencesi altında olduğunuzu belirten bir servistir.</p>
		    <p>Müşterilerinize <strong>İyzico Korumalı Alışveriş</strong>  güvencesi altında olduğunu göstermek için “Korumalı Alışveriş” sekmesinden Korumalı Alışveriş <strong>logosunun</strong> sayfanızın neresinde gözükmesini istediğinizi seçmeniz yeterlidir.</p>
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