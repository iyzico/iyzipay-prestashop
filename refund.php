<?php

include(dirname(__FILE__) . '/../../config/config.inc.php');

include(dirname(__FILE__) . '/iyzicocheckoutform.php');

require_once 'IyzipayBootstrap.php';


$cookie = new Cookie('psAdmin');

$token  = Tools::getAdminToken('AdminOrders'.(int)(Tab::getIdFromClassName('AdminOrders')).(int)($cookie->id_employee));



$message = array(
    'msg' => 'Fail',
    'response' => 'Admin girişiniz doğrulanamıyor'
);


if(Tools::getValue('token')) {

    if(Tools::getValue('token')!==$token ) {      
        
        
        echo json_encode($message);
        exit;
    }

    $cookie = new Cookie('psAdmin');

    if(!$cookie->id_employee){
        
        $message['response'] = 'Admin girişiniz zaman aşımına uğramış olabilir.';
        echo json_encode($message);
        exit;
    }

} else {

    echo json_encode($message);
    exit;
}


try {
    IyzipayBootstrap::init();
    $error_msg = '';
    

    $payment_id     = pSQL(Tools::getValue('payment_id'));
    $refunded       = pSQL(Tools::getValue('refunded'));
    $refund_price   = pSQL(Tools::getValue('refund_price'));
    $language       = Tools::getValue('language');

    
    $query = 'SELECT * FROM ' . _DB_PREFIX_ . 'iyzico_cart_detail WHERE payment_transaction_id = "' . $payment_id . '"';
    $refund_amount = Db::getInstance()->ExecuteS($query);
    
    //iyzico order details not found
    if (empty($refund_amount)) {
        $message = array(
            'msg' => 'Fail',
            'response' => 'Invalid Refund'
        );
        echo json_encode($message);
        exit;
    }
   
    $total_refund = $refund_amount[0]['paid_price'] - $refund_amount[0]['total_refunded_amount'];
    $refund = number_format($refund_price, 2, '.', '');
    
    //Set api,secret and base url
    $options = new \Iyzipay\Options();
    $options->setApiKey(Configuration::get('IYZICO_FORM_LIVE_API_ID'));
    $options->setSecretKey(Configuration::get('IYZICO_FORM_LIVE_SECRET'));
    $options->setBaseUrl("https://api.iyzipay.com");

    //refund amount validation.
    if ($refund > number_format($total_refund, 2, '.', '')) {
        $message = array(
            'msg' => 'Fail',
            'response' => 'You cannot refund more than ' . $total_refund . '  ' . $refund_amount[0]['currency']  
        );
        echo json_encode($message);
        exit;
    }
    
   //refund order
    if (empty($payment_id)) {
        $message = array(
            'msg' => 'Fail',
            'response' => 'Payment Id not found'
        );
        echo json_encode($message);
        exit;
    }
    
    if (!empty($language) && $language == 'tr') {
        $lang = 'tr';
    } else {
        $lang = 'en';
    }
    
    $locale = ($lang == "tr") ? Iyzipay\Model\Locale::TR : Iyzipay\Model\Locale::EN;
    
    $redirect_url = $_SERVER['HTTP_REFERER'];

    // create request class
    $request = new \Iyzipay\Request\CreateRefundRequest();
    $request->setLocale($locale);
    $request->setConversationId(uniqid() . '_ps');
    $request->setPaymentTransactionId($payment_id);
    $request->setPrice($refund_price);
    $request->setCurrency($refund_amount[0]['currency']);
    $request->setIp((string) Tools::getRemoteAddr());
    
    //request form api log
    $insert_api_log = Db::getInstance()->insert("iyzico_api_log", array(
        'id' => Tools::getValue('id'),
        'order_id' => $refund_amount[0]['order_id'],
        'item_id' => $refund_amount[0]['item_id'],
        'transaction_status' => '',
        'api_request' => pSQL($request->toJsonString()),
        'api_response' => '',
        'request_type' => 'order_refund',
        'note' => '',
        'created' => date('Y-m-d H:i:s'),
        'updated' => date('Y-m-d H:i:s'),
    ));

    $last_insert_id = Db::getInstance()->Insert_ID();

    // make request
    $response = \Iyzipay\Model\Refund::create($request, $options);

    //$response = $client->refund($request);
    $status = $response->getStatus();

    // update api log
    if (empty($status) || (!empty($status) && 'failure' == $status)) {
        $update_array = array(
            'transaction_status' => 'fail',
            'api_response' => pSQL($response->getRawResult()),
            'updated' => date('Y-m-d H:i:s'),
            'note' => $response->getErrorMessage(),
        );
        $update = Db::getInstance()->update('iyzico_api_log', $update_array, 'id = ' . (int) $last_insert_id);
        throw new \Exception($response->getErrorMessage());
    } else {
        $update_array = array(
            'transaction_status' => 'success',
            'api_response' => pSQL($response->getRawResult()),
            'updated' => date('Y-m-d H:i:s'),
            'note' => 'Refund has been done succesfully.<br/> Refund Price: ' . $refund_price,
        );
        $update = Db::getInstance()->update('iyzico_api_log', $update_array, 'id = ' . (int) $last_insert_id);
    }

    $total_refunded = $refunded + $refund_price;

    $query = 'UPDATE ' . _DB_PREFIX_ . 'iyzico_cart_detail SET total_refunded_amount = "' . $total_refunded . '"  WHERE  `payment_transaction_id`= "' . $payment_id . '"';
    $refund_result = Db::getInstance()->ExecuteS($query);

    //success response
    $message = array(
        'msg' => 'success',
        'response' => 'Refund successfully.'
    );
    echo json_encode($message);
    exit;
} catch (\Exception $ex) {
    $error_msg = $ex->getMessage();
    $error_msg = !empty($error_msg) ? $error_msg : 'Some error occured.Please try again.';
    $message = array(
        'msg' => 'Fail',
        'response' => $error_msg
    );
    echo json_encode($message);
    exit;
}