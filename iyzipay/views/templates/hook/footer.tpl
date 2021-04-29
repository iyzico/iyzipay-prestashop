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

{if ($position != 'hidden') || ($position != '') }
    <style>
        @media screen and (max-width: 380px) {
            ._1xrVL7npYN5CKybp32heXk {
                position: fixed;
                bottom: 0!important;
                top: unset;
                left: 0;
                width: 100%;
            }
        }
    </style>
<script> window.iyz = { token:'{$token}', position:'{$position}',ideaSoft: false,pwi:true};</script>
<script src='https://static.iyzipay.com/buyer-protection/buyer-protection.js' type='text/javascript'></script>
{/if}