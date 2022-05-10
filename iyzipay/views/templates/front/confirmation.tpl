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

{if (isset($status) == true) && ($status == 'ok')}
<h3>{l s='Your order is complete.'  mod='iyzipay'}</h3>
<p>
	<br />- {l s='Amount' mod='iyzipay'} : <span class="price"><strong>{$total|escape:'htmlall':'UTF-8'}</strong></span>
{if $installmentFee != ''}
	<br />- {l s='installment Fee' mod='iyzipay'} : <span class="installmentFee"><strong>{$installmentFee|escape:'html':'UTF-8'}</strong></span>
{/if}
	<br />- {l s='Reference' mod='iyzipay'} : <span class="reference"><strong>{$reference|escape:'html':'UTF-8'}</strong></span>
	<br /><br />{l s='An email has been sent with this information.' mod='iyzipay'}
	<br /><br />{l s='If you have questions, comments or concerns, please contact our' mod='iyzipay'} <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='expert customer support team.' mod='iyzipay'}</a>
</p>
{else}
<h3>{l s='Your order on %s has not been accepted.'  mod='iyzipay'}</h3>
<p>
	<br />- {l s='Reference' mod='iyzipay'} <span class="reference"> <strong>{$reference|escape:'html':'UTF-8'}</strong></span>
	<br /><br />{l s='Please, try to order again.' mod='iyzipay'}
	<br /><br />{l s='If you have questions, comments or concerns, please contact our' mod='iyzipay'} <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='expert customer support team.' mod='iyzipay'}</a>
</p>
{/if}
<hr />
