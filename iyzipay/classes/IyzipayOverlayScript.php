<?php 

class IyzipayOverlayScript {

	public static function generateOverlayScriptObject($apiKey,$secretKey,$isoCode,$randNumer) {

        $overlayObject = new stdClass();
        $overlayObject->locale          = $isoCode;
        $overlayObject->conversationId  = $randNumer;
        $overlayObject->position        = Tools::getValue('iyzipay_overlay_position');

        return $overlayObject;

	}

}
