<?php
/**
 *
 *  @author iyzico entegrasyon <entegrasyon@iyzico.com>
 *  @copyright  2016 iyzi payments
 *  @license    http://iyzico.com  
 */

require_once _PS_MODULE_DIR_.'iyzicocheckoutform/includer.php';


class IyzicocheckoutformOrder extends ObjectModel {

	public $id;
	public $order_id;
	public $amount;
	public static $definition = array(
		'table' => 'iyzico_order_form',
		'primary' => 'id',
		'multilang' => false,
		'fields' => array(
			'order_id' => array('type' => self::TYPE_INT, 'required' => true),
			'amount' => array('type' => self::TYPE_FLOAT, 'required' => true),
		),
	);

	public static function getByPsOrderId($order_id)
	{
		return Db::getInstance()->getRow(
			'SELECT * FROM `'._DB_PREFIX_.'iyzico_order_form`
			WHERE order_id = "'.pSQL($order_id).'"'
		);
	}

	public static function insertOrder($result)
	{
            $dbFields = '`' . implode('`,`', array_keys($result)) . '`'; 
            $dbParams = "'" . implode("','", array_values($result)) . "'";
            $query = "INSERT INTO `"._DB_PREFIX_."iyzico_order_form` ({$dbFields}) VALUES ({$dbParams})";
            return Db::getInstance()->execute($query);
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
