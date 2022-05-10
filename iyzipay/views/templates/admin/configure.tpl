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

{if $iyzipay_pwi_first_enabled_status == 0}
	{if $languageIsoCode == 'tr'}
		<div class="alert alert-danger" role="alert">
			<h1>iyzico İle Öde modülü aktif değil!</h1>
		</div>
		<p>iyzico ile Öde modülünü kurmadan iyzico Ödeme Formu ayarlarına erişemezsiniz.</p>
		<p><strong>Prestashop 1.7 - iyzico ile Öde</strong> modülünün kurulumunu tamamlayınız: <a href="https://dev.iyzipay.com/tr/acik-kaynak/prestashop" target="_blank"> https://dev.iyzipay.com/tr/acik-kaynak/prestashop </a></p>
	{else}
		<div class="alert alert-danger" role="alert">
			<h1>Pay with iyzico module is not enable!</h1>
		</div>
		<p>You can not access Settings of iyzico Checkout Form Module without installing the pay with iyzico module.</p>
		<p>Complete the installation of the <strong>Prestashop 1.7 - Pay with iyzico</strong> module via dev.iyzipay: <a href="https://dev.iyzipay.com/tr/acik-kaynak/prestashop" target="_blank"> https://dev.iyzipay.com/tr/acik-kaynak/prestashop </a></p>

	{/if}
{else}
	<!-- Nav tabs -->
	<ul class="nav nav-tabs" role="tablist">
		<li class="active"><a href="#template_1" role="tab" data-toggle="tab">iyzico</a></li>
		<li><a href="#template_3" role="tab" data-toggle="tab">S.S.S</a></li>

	</ul>
	<!-- Tab panes -->
	<div class="tab-content">
		<div class="tab-pane active" id="template_1">{include file='./template_1.tpl'}</div>
		<div class="tab-pane" id="template_3">{include file='./template_3.tpl'}</div>
	</div>
{/if}
