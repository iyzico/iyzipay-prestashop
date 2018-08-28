<?php 

include_once 'IyzipayHelper.php';
include_once 'IyzipayModel.php';

class IyzipayCheckoutFormObject {


	public static function option($params,$currency,$context,$apiKey) {
		
   		$currency = new Currency((int)$params['cookie']->id_currency);
        $thisUserCurrency = $currency->iso_code;

		$shipping                   			= $params['cart']->getOrderTotal(true,Cart::ONLY_SHIPPING);
        $basketItems                			= $params['cart']->getProducts();

		$iyzico = new stdClass();
		$iyzico->locale                       = $context->language->iso_code;
		$iyzico->conversationId               = $params['cookie']->id_cart;
		$iyzico->price                        = IyzipayHelper::orderProductCalc($basketItems,$shipping);
		$iyzico->paidPrice                    = IyzipayHelper::priceParser($params['cart']->getOrderTotal());
		$iyzico->currency                     = $thisUserCurrency;
		$iyzico->basketId                     = $params['cookie']->id_cart;
		$iyzico->paymentGroup                 = 'PRODUCT';
		$iyzico->forceThreeDS                 = "0";
		$iyzico->callbackUrl                  = Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://'.htmlspecialchars($_SERVER['HTTP_HOST'],ENT_COMPAT,'UTF-8').__PS_BASE_URI__.'index.php?module_action=init&fc=module&module=iyzipay&controller=callback';
		$iyzico->cardUserKey                  = IyzipayModel::findUserCardKey($params['cookie']->id_customer,$apiKey);
		$iyzico->paymentSource                = _PS_VERSION_.'|PIE|1.0';

		return $iyzico;
	}

	public static function buyer($buyerAddress) {


		$buyer = new stdClass();

		$buyer->id 							= $buyerAddress->id;
		$buyer->name 						= $buyerAddress->firstname;
		$buyer->surname 					= $buyerAddress->lastname;
		$buyer->identityNumber 				= '11111111111'; 
		$buyer->email 						= $buyerAddress->email;
		$buyer->gsmNumber 					= $buyerAddress->phone; 
		$buyer->registrationDate 			= $buyerAddress->date_add;
		$buyer->lastLoginDate 				= $buyerAddress->date_add;
		$buyer->registrationAddress 		= $buyerAddress->address1.$buyerAddress->address2;
		$buyer->city 						= $buyerAddress->city;
		$buyer->country 					= $buyerAddress->country;
		$buyer->zipCode 					= $buyerAddress->postcode;
		$buyer->ip 							= Tools::getRemoteAddr();

		return $buyer;
	}

	public static function shippingAddress($shippingAddressInfo) {

		$shippingAddress = new stdClass();

		$shippingAddress->address 			= $shippingAddressInfo->address1.$shippingAddressInfo->address2;
		$shippingAddress->zipCode 			= $shippingAddressInfo->postcode;
		$shippingAddress->contactName 		= $shippingAddressInfo->firstname;
		$shippingAddress->city 				= $shippingAddressInfo->city;
		$shippingAddress->country 			= $shippingAddressInfo->country;

		


		return $shippingAddress;
	}

	public static function billingAddress($billingAddressInfo) {

		$billingAddress = new stdClass();

		$billingAddress->address 			= $billingAddressInfo->address1.$billingAddressInfo->address2;
		$billingAddress->zipCode 			= $billingAddressInfo->postcode;
		$billingAddress->contactName 		= $billingAddressInfo->firstname;
		$billingAddress->city 				= $billingAddressInfo->city;
		$billingAddress->country 			= $billingAddressInfo->country; 

		return $billingAddress;
	}
	
	public static function basketItems($items,$shipping) {

	$keyNumber = 0;
	
	foreach ($items as $key => $item) {


		$basketItems[$keyNumber] = new stdClass();

		$basketItems[$keyNumber]->id                = $item['id_product_attribute'];
		$basketItems[$keyNumber]->price             = IyzipayHelper::priceParser($item['total_wt']);
		$basketItems[$keyNumber]->name              = $item['name'];
		$basketItems[$keyNumber]->category1         = $item['category'];
		$basketItems[$keyNumber]->itemType          = 'PHYSICAL';

		$keyNumber++;

	}

		if(!empty($shipping)) {

			$basketItems[$keyNumber] = new stdClass();

			$basketItems[$keyNumber]->id                = uniqid();
			$basketItems[$keyNumber]->price             = IyzipayHelper::priceParser($shipping);
			$basketItems[$keyNumber]->name              = 'Cargo';
			$basketItems[$keyNumber]->category1         = 'Cargo';
			$basketItems[$keyNumber]->itemType          = 'PHYSICAL';

			
		}

		return $basketItems;
	}


	public static function CheckoutFormObjectSort($objectData) {


		$form_object = new stdClass();

		$form_object->locale 						= $objectData->locale;
		$form_object->conversationId 				= $objectData->conversationId;
		$form_object->price 						= $objectData->price;
		$form_object->basketId 						= $objectData->basketId;
		$form_object->paymentGroup 					= $objectData->paymentGroup;

		$form_object->buyer = new stdClass();
		$form_object->buyer = $objectData->buyer;

		$form_object->shippingAddress = new stdClass();
		$form_object->shippingAddress = $objectData->shippingAddress;

		$form_object->billingAddress = new stdClass();
		$form_object->billingAddress = $objectData->billingAddress;

		foreach ($objectData->basketItems as $key => $item) {
			
			$form_object->basketItems[$key] = new stdClass();
			$form_object->basketItems[$key] = $item;
			
		}

		$form_object->callbackUrl 			= $objectData->callbackUrl;
		$form_object->paymentSource 		= $objectData->paymentSource;
		$form_object->currency 	  			= $objectData->currency;
		$form_object->paidPrice   			= $objectData->paidPrice;
		$form_object->forceThreeDS 			= $objectData->forceThreeDS;
		$form_object->cardUserKey 			= $objectData->cardUserKey;

		return $form_object;
	}

	public static function responseObject($conversationId,$token,$locale) {

		$responseObject = new stdClass();

		$responseObject->locale 			= $locale;
		$responseObject->conversationId 	= $conversationId;
		$responseObject->token 				= $token;

		return $responseObject;
	}


	public static function cancelObject($locale,$paymentId,$ip) {

		$responseObject = new stdClass();

		$responseObject->locale 			= $locale;
		$responseObject->paymentId 			= $paymentId;
		$responseObject->ip 				= $ip;

		return $responseObject;

	}

}
