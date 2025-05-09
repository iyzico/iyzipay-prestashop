<?php

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