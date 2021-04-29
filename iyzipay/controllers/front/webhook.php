<?php

require_once "callback.php";

class IyzipayWebhookModuleFrontController extends ModuleFrontController {

    private $paymentConversationId;
    private $token;
    private $iyziEventType;
    private $iyziSignature;

    public function __construct(){
        $this->context = Context::getContext();
    }

    public function postProcess(){
        $post = file_get_contents("php://input");
        $params = json_decode($post, true);

        if (isset(getallheaders()['x-iyz-signature'])){
            $this->iyziSignature = getallheaders()['x-iyz-signature'];
        }

        $this->orderControlViaWebhook($params);
    }

    public function orderControlViaWebhook($params){
        if (isset($params['iyziEventType']) && isset($params['token']) && isset($params['paymentConversationId'])) {

            $this->paymentConversationId = $params['paymentConversationId'];
            $this->token = $params['token'];
            $this->iyziEventType = $params['iyziEventType'];

            if ($this->iyziSignature){

                $secretKey       = Configuration::get('iyzipay_secret_key');
                $createIyzicoSignature = base64_encode(sha1($secretKey . $this->iyziEventType . $this->token, true));

                if ($this->iyziSignature == $createIyzicoSignature){
                    return $this->iyzicoWebhookResponse();
                }
                else{
                    self::webhookHttpResponse("signature_not_valid - X-IYZ-SIGNATURE geçersiz", 404);
                }
            }
            else{
                return $this->iyzicoWebhookResponse();
            }


        }
        else{
            self::webhookHttpResponse("invalid_parameters - Gönderilen parametreler geçersiz", 404);
        }
    }

    public function iyzicoWebhookResponse(){
        $iyzicoCallback = new IyzipayCallBackModuleFrontController();
        $responseCode = $iyzicoCallback->init("webhook", $this->paymentConversationId, $this->token);
        return $responseCode;
    }

    public static function webhookHttpResponse($message,$status){
        $httpMessage = array('message' => $message);
        header('Content-Type: application/json, Status: '. $status, true, $status);
        echo json_encode($httpMessage);
        exit();
    }

}

