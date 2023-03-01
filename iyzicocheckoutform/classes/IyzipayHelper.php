<?php
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
 */

class IyzipayHelper
{

    /**
     * @param $basketItems
     * @param $shipping
     * @return int|string
     */
    public static function orderProductCalc($basketItems, $shipping)
    {

        $price = 0;
        foreach ($basketItems as $item) {
            $price  += $item['total_wt'];
        }

        if (!empty($shipping)) {
            $price += $shipping;
        }


        $price = IyzipayHelper::priceParser($price);

        return $price;
    }

    /**
     * @param $price
     * @return string
     */
    public static function priceParser($price)
    {

        if (strpos($price, ".") === false) {
            return $price . ".0";
        }

        $subStrIndex = 0;
        $priceReversed = strrev($price);
        for ($i = 0; $i < strlen($priceReversed); $i++) {
            if (strcmp($priceReversed[$i], "0") == 0) {
                $subStrIndex = $i + 1;
            } else if (strcmp($priceReversed[$i], ".") == 0) {
                $priceReversed = "0" . $priceReversed;
                break;
            } else {
                break;
            }
        }

        return strrev(substr($priceReversed, $subStrIndex));
    }
}