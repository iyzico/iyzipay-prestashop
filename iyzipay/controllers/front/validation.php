<?php
/**
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2023 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

class IyzipayValidationModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;

        if (!$this->module->active || $cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0) {
            Tools::redirect('index.php?controller=order&step=1');
            return;
        }

        // Check if cart exists and cart has products
        if (!Validate::isLoadedObject($cart) || $cart->nbProducts() <= 0) {
            Tools::redirect('index.php?controller=order&step=1');
            return;
        }

        // Check if this payment option is still available
        if (!Validate::isLoadedObject($this->context->customer) || !$this->module->checkCurrency($cart)) {
            Tools::redirect('index.php?controller=order');
            return;
        }

        // Initialize checkout form
        $paymentResponse = $this->module->checkoutFormGenerate(['cart' => $cart, 'cookie' => $this->context->cookie]);

        if (is_string($paymentResponse)) {
            // Error occurred
            $this->errors[] = $paymentResponse;
            $this->setTemplate('module:iyzipay/views/templates/front/error.tpl');
            return;
        }

        // Redirect to iyzico checkout form
        if (isset($paymentResponse) && $paymentResponse->getStatus() === 'success') {
            $this->context->cookie->iyziPaymentType = 'form'; // Regular checkout form tipini belirt
            Tools::redirect($paymentResponse->getPaymentPageUrl());
            exit;
        } else {
            $this->errors[] = $this->module->l('An error occurred during the payment process. Please try again.');
            $this->setTemplate('module:iyzipay/views/templates/front/error.tpl');
        }
    }
} 