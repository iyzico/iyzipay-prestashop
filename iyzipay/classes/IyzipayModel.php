<?php

class IyzipayModel extends ObjectModel
{
    /**
     * @param $customerId
     * @param $apiKey
     * @return string
     */
    public static function findUserCardKey($customerId, $apiKey)
    {
        $tableName = 'iyzipay_card';

        $sql = 'SELECT * FROM '._DB_PREFIX_.bqSQL($tableName).' WHERE `api_key` = \''.$apiKey.'\' AND `customer_id` = \''.$customerId.'\'';

        $results = Db::getInstance()->ExecuteS($sql);

        if (isset($results[0]['card_user_key'])) {
            return $results[0]['card_user_key'];
        } else {
            return '';
        }
    }

    /**
     * @param $customerId
     * @param $cardUserKey
     * @param $apiKey
     * @return mixed
     */
    public static function insertCardUserKey($customerId, $cardUserKey, $apiKey)
    {

        $tableName = 'iyzipay_card';

        $sql = 'INSERT INTO '._DB_PREFIX_.bqSQL($tableName).'(`customer_id`,`card_user_key`,`api_key`)
		        VALUES
		        (\''.$customerId.'\',
		         \''.$cardUserKey.'\',
		         \''.$apiKey.'\')';

        return Db::getInstance()->execute($sql);
    }

    /**
     * @param $iyzicoLocalOrder
     * @return mixed
     */
    public static function insertIyzicoOrder($iyzicoLocalOrder)
    {

        

        $sql = 'INSERT INTO '._DB_PREFIX_.'iyzipay_order (`payment_id`,`order_id`,`total_amount`,`status`)
		        VALUES
		        (\''.$iyzicoLocalOrder->orderId.'\',
		         \''.$iyzicoLocalOrder->paymentId.'\',
		         \''.$iyzicoLocalOrder->totalAmount.'\',
		         \''.$iyzicoLocalOrder->status.'\')';

        return Db::getInstance()->execute($sql);
    }

    /**
     * @param $price
     * @param $order_id
     * @return mixed
     */
    public static function updateOrderTotal($price, $order_id)
    {

        $tableName = 'orders';
        $order_id  = (int) $order_id;

        $sql = 'UPDATE '._DB_PREFIX_.bqSQL($tableName).'
		    SET `total_paid` = \''.$price.'\',
		     	`total_paid_tax_incl` = \''.$price.'\',
		     	`total_paid_tax_excl` = \''.$price.'\',
		     	`total_paid_real` = \''.$price.'\'
		    WHERE `id_order` = \''.$order_id.'\'';

        return Db::getInstance()->execute($sql);
    }

    /**
     * @param $price
     * @param $reference
     * @return mixed
     */
    public static function updateOrderPayment($price, $reference)
    {
        $tableName = 'order_payment';
        $reference  = $reference;

        $sql = 'UPDATE '._DB_PREFIX_.bqSQL($tableName).'
		    SET `amount` = \''.$price.'\'
		    WHERE `order_reference` = \''.$reference.'\'';

        return Db::getInstance()->execute($sql);
    }

    /**
     * @param $price
     * @param $order_id
     * @return mixed
     */
    public static function updateOrderInvoiceTotal($price, $order_id)
    {

        $tableName = 'order_invoice';
        $order_id  = (int) $order_id;

        $sql = 'UPDATE '._DB_PREFIX_.bqSQL($tableName).'
		    SET `total_paid_tax_incl` = \''.$price.'\',
		     	`total_products_wt` = \''.$price.'\'
		    WHERE `id_order` = \''.$order_id.'\'';

        return Db::getInstance()->execute($sql);
    }
}
