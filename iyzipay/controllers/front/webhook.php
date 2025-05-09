<?php

require_once rtrim(_PS_MODULE_DIR_, '/') . '/iyzipay/controllers/front/callback.php';

class IyzipayWebhookModuleFrontController extends ModuleFrontController {

    private $paymentConversationId;
    private $paymentId;
    private $token;
    private $iyziEventType;
    private $iyziSignature;
    private $status;

    public function __construct(){
        $this->context = Context::getContext();
    }

    public function postProcess(){
        $post   = file_get_contents("php://input");
        $params = json_decode($post, true);

        if (isset(getallheaders()['x-iyz-signature-v3'])){
            $this->iyziSignature = getallheaders()['x-iyz-signature-v3'];
        }

        $this->orderControlViaWebhook($params);
    }

    public function orderControlViaWebhook($params){
        if (isset($params['iyziEventType']) && isset($params['token']) && isset($params['paymentConversationId'])) {

            $this->paymentConversationId    = $params['paymentConversationId'];
            $this->token                    = $params['token'];
            $this->iyziEventType            = $params['iyziEventType'];
            $this->paymentId                = $params['iyziPaymentId'];
            $this->status                   = $params['status'];

            if ($this->iyziSignature){
                $secretKey              = Configuration::get('iyzipay_secret_key');
                $key                    = $secretKey.$this->iyziEventType.$this->paymentId.$this->token.$this->paymentConversationId.$this->status;
                $createIyzicoSignature  = bin2hex(hash_hmac('sha256', $key, $secretKey, true));

                if ($this->iyziSignature == $createIyzicoSignature){
                    return $this->iyzicoWebhookResponse();
                }
                else{
                    self::webhookHttpResponse("signature_not_valid - X-IYZ-SIGNATURE-V3 geçersiz", 404);
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
        $responseCode = $iyzicoCallback->init("webhook", $this->paymentConversationId, $this->token , $this->iyziEventType);
        return $responseCode;
    }

    public static function webhookHttpResponse($message,$status){
        $httpMessage = array('message' => $message);
        header('Content-Type: application/json, Status: '. $status, true, $status);
        echo json_encode($httpMessage);
        exit();
    }

    public static function webhookResponseOrderNote($message,$orderStatus,$orderId){

      $query = "UPDATE `"._DB_PREFIX_."orders` SET note='".$message."', current_state = '".$orderStatus."' WHERE id_cart =".$orderId; //end of the query
       Db::getInstance()->Execute($query);
       return $query;

    }

}
