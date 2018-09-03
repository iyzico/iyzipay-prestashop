<?php 


class IyzipayRequest {


	public static function checkoutFormRequest($endpoint,$json,$authorization) {

			$endpoint = $endpoint.'/payment/iyzipos/checkoutform/initialize/auth/ecom';

		    return IyzipayRequest::curlPost($json,$authorization,$endpoint);

	}

	public static function checkoutFormRequestDetail($endpoint,$json,$authorization) {

			$endpoint = $endpoint.'/payment/iyzipos/checkoutform/auth/ecom/detail';

		    return IyzipayRequest::curlPost($json,$authorization,$endpoint);

	}

	public static function callOverlayScript($endpoint = false,$overlayScriptJson,$authorization) {
		
		$endpoint = 'https://iyziup.iyzipay.com/v1/iyziup/protected/shop/detail/overlay-script';
			 
	    return IyzipayRequest::curlPost($overlayScriptJson,$authorization,$endpoint);

	}

	public static function paymentCancel($endpoint,$json,$authorization) {

		$endpoint = $endpoint.'/payment/cancel';
			 
	    return IyzipayRequest::curlPost($json,$authorization,$endpoint);

	}


	public static function curlPost($json,$authorization,$endpoint) {

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $endpoint);
		$content_length = 0;
		if ($json) {
		    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		    curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		curl_setopt($curl, CURLOPT_TIMEOUT, 150);
		
		curl_setopt(        
		    $curl, CURLOPT_HTTPHEADER, array(
		        "Authorization: " .$authorization['authorization'],
		        "x-iyzi-rnd:".$authorization['randValue'], 
		        "Content-Type: application/json",
		    )
		);
		$result = json_decode(curl_exec($curl));
		curl_close($curl);

		return $result;
	}


}