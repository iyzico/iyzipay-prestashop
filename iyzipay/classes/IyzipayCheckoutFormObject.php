<?php

use Iyzipay\Model\Address;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Model\Buyer;

include_once 'IyzipayHelper.php';

class IyzipayCheckoutFormObject
{
    public static function buyer($buyerAddress)
    {

        $buyer = new Buyer();

        $buyer->setId(strlen($buyerAddress->id) > 0 ? $buyerAddress->id : uniqid());
        $buyer->setName(strlen($buyerAddress->firstname) > 0 ? $buyerAddress->firstname : 'buyerName');
        $buyer->setSurname(strlen($buyerAddress->lastname) > 0 ? $buyerAddress->lastname : 'buyerSurname');
        $buyer->setIdentityNumber('11111111111');
        $buyer->setEmail(strlen($buyerAddress->email) > 0 ? $buyerAddress->email : 'dummy@gmail.com');
        $buyer->setGsmNumber(strlen($buyerAddress->phone) > 0 ? $buyerAddress->phone : '905555555555');
        $buyer->setRegistrationAddress(strlen($buyerAddress->address1 . $buyerAddress->address2) > 0 ? $buyerAddress->address1 . $buyerAddress->address2 : 'dummy address');
        $buyer->setRegistrationDate(strlen($buyerAddress->date_add) > 0 ? $buyerAddress->date_add : date('Y-m-d H:i:s'));
        $buyer->setLastLoginDate(strlen($buyerAddress->date_upd) > 0 ? $buyerAddress->date_upd : date('Y-m-d H:i:s'));
        $buyer->setCity(strlen($buyerAddress->city) > 0 ? $buyerAddress->city : 'dummy city');
        $buyer->setCountry(strlen($buyerAddress->country) > 0 ? $buyerAddress->country : 'dummy country');
        $buyer->setZipCode(strlen($buyerAddress->postcode) > 0 ? $buyerAddress->postcode : 'dummy zip code');
        $buyer->setIp(Tools::getRemoteAddr());

        return $buyer;
    }

    public static function shippingAddress($shippingAddressInfo)
    {
        $shippingAddress = new Address();

        $shippingAddress->setAddress(strlen($shippingAddressInfo->address1 . $shippingAddressInfo->address2) > 0 ? $shippingAddressInfo->address1 . $shippingAddressInfo->address2 : 'dummy address');
        $shippingAddress->setZipCode(strlen($shippingAddressInfo->postcode) > 0 ? $shippingAddressInfo->postcode : 'dummy zip code');
        $shippingAddress->setContactName(strlen($shippingAddressInfo->firstname) > 0 ? $shippingAddressInfo->firstname : 'dummy name');
        $shippingAddress->setCity(strlen($shippingAddressInfo->city) > 0 ? $shippingAddressInfo->city : 'dummy city');
        $shippingAddress->setCountry(strlen($shippingAddressInfo->country) > 0 ? $shippingAddressInfo->country : 'dummy country');

        return $shippingAddress;
    }

    public static function billingAddress($billingAddressInfo)
    {

        $billingAddress = new Address();

        $billingAddress->setAddress(strlen($billingAddressInfo->address1 . $billingAddressInfo->address2) > 0 ? $billingAddressInfo->address1 . $billingAddressInfo->address2 : 'dummy address');
        $billingAddress->setZipCode(strlen($billingAddressInfo->postcode) > 0 ? $billingAddressInfo->postcode : 'dummy zip code');
        $billingAddress->setContactName(strlen($billingAddressInfo->firstname) > 0 ? $billingAddressInfo->firstname : 'dummy name');
        $billingAddress->setCity(strlen($billingAddressInfo->city) > 0 ? $billingAddressInfo->city : 'dummy city');
        $billingAddress->setCountry(strlen($billingAddressInfo->country) > 0 ? $billingAddressInfo->country : 'dummy country');

        return $billingAddress;
    }

    public static function basketItems($items, $shipping)
    {
        $basketItems = [];

        foreach ($items as $item) {
            $basketItem = new BasketItem();
            $basketItem->setId($item['id_product_attribute']);
            $basketItem->setPrice(IyzipayHelper::priceParser($item['total_wt']));
            $basketItem->setName($item['name']);
            $basketItem->setCategory1($item['category']);
            $basketItem->setItemType(BasketItemType::PHYSICAL);

            $basketItems[] = $basketItem;
        }

        if (!empty($shipping)) {
            $basketItem = new BasketItem();
            $basketItem->setId(uniqid());
            $basketItem->setPrice(IyzipayHelper::priceParser($shipping));
            $basketItem->setName('Cargo');
            $basketItem->setCategory1('Cargo');
            $basketItem->setItemType(BasketItemType::PHYSICAL);

            $basketItems[] = $basketItem;
        }

        return $basketItems;
    }
}
