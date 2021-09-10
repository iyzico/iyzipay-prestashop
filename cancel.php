<?php

include(dirname(__FILE__) . '/../../config/config.inc.php');

include(dirname(__FILE__) . '/../../header.php');

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


$redirect_url = $_SERVER['HTTP_REFERER'];
try {
    IyzipayBootstrap::init();
    $error_msg = '';
    
    //Set api,secret and base url option to call iyzico API
    $options = new \Iyzipay\Options();
    $options->setApiKey(Configuration::get('IYZICO_FORM_LIVE_API_ID'));
    $options->setSecretKey(Configuration::get('IYZICO_FORM_LIVE_SECRET'));
    $options->setBaseUrl("https://api.iyzipay.com");

    //cancel order
    $transaction_id     = pSQL(Tools::getValue('transaction_id'));

    if (empty($transaction_id)) {
        $error_msg = 'Invalid Order.';
    }

    $query = 'SELECT * FROM `' . _DB_PREFIX_ . 'iyzico_order_form` WHERE `transaction_id`= "' . $transaction_id . '"';
    $order_detail = Db::getInstance()->ExecuteS($query);
    $order_array = json_decode($order_detail[0]['response_data']);
    $payment_id = $order_array->paymentId;
    
    if (!empty(Tools::getValue('language')) && Tools::getValue('language') == 'tr') {
        $lang = 'tr';
    } else {
        $lang = 'en';
    }
    $locale = ($lang == "tr") ? Iyzipay\Model\Locale::TR : Iyzipay\Model\Locale::EN;

    // create request class
    $request = new \Iyzipay\Request\CreateCancelRequest();
    $request->setLocale($locale);
    $request->setConversationId(uniqid() . '_ps');
    $request->setIp((string) Tools::getRemoteAddr());
    $request->setPaymentId($payment_id);
 
    //request form api log
    $insert_api_log = Db::getInstance()->insert("iyzico_api_log", array(
        'id' => Tools::getValue('id'),
        'order_id' => (int) $order_detail[0]['order_id'],
        'item_id' => 0,
        'transaction_status' => '',
        'api_request' => pSQL($request->toJsonString()),
        'api_response' => '',
        'request_type' => 'order_cancel',
        'note' => '',
        'created' => date('Y-m-d H:i:s'),
        'updated' => date('Y-m-d H:i:s'),
    ));

    $last_insert_id = Db::getInstance()->Insert_ID();

    // make request
    $response = \Iyzipay\Model\Cancel::create($request, $options);

    //update api log
    $update_array = array(
        'transaction_status' => 'success',
        'api_response' => pSQL($response->getRawResult()),
        'updated' => date('Y-m-d H:i:s'),
        'note' => 'Cancel has been done succesfully.',
    );

    $update = Db::getInstance()->update('iyzico_api_log', $update_array, 'id = ' . (int) $last_insert_id);

    if ($response->getStatus() == 'failure') {
        throw new \Exception($response->getErrorMessage());
    }

    //update order in order table.
    $update_order = 'UPDATE ' . _DB_PREFIX_ . 'orders SET current_state =' . _PS_OS_CANCELED_ . ' WHERE id_order =' . $order_detail[0]['order_id'];
    $cancelled = Db::getInstance()->ExecuteS($update_order);

    $id_employee        = pSQL(Tools::getValue('id_employee'));

    //insert history
    $insert_order_history = 'INSERT INTO ' . _DB_PREFIX_ . 'order_history (id_employee,id_order,id_order_state,date_add) values ("' . $id_employee . '","' . $order_detail[0]['order_id'] . '","' . _PS_OS_CANCELED_ . '","' . date('Y-m-d H:i:s') . '")';
    $history = Db::getInstance()->ExecuteS($insert_order_history);

    header("Location: " . $redirect_url);
} catch (\Exception $ex) {
    $error_msg = $ex->getMessage();
    $error_msg = !empty($error_msg) ? $error_msg : $this->l('Some error occured.Please try again.');
    header("Location: " . $redirect_url . '&error=' . $error_msg);
}
