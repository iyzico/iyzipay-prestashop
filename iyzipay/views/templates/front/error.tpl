{*
* 2007-2023 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2023 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{extends file='page.tpl'}

{block name='page_title'}
  {l s='Payment Error' mod='iyzipay'}
{/block}

{block name='page_content'}
  <div class="card card-block">
    <h1 class="h1">{l s='An error occurred during the payment process' mod='iyzipay'}</h1>
    
    {if isset($errors) && $errors}
      <div class="alert alert-danger">
        <ul>
          {foreach from=$errors item='error'}
            <li>{$error|escape:'html':'UTF-8'}</li>
          {/foreach}
        </ul>
      </div>
    {else}
      <div class="alert alert-danger">
        {l s='Please try again or contact the store owner.' mod='iyzipay'}
      </div>
    {/if}
    
    <p>
      <a href="{$link->getPageLink('order', true)}" class="btn btn-primary">
        {l s='Try again' mod='iyzipay'}
      </a>
    </p>
  </div>
{/block}