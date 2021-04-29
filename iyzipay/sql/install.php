<?php
/**
* 2007-2018 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    iyzico <info@iyzico.com>
*  @copyright 2018 iyzico
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of iyzico
*/

$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'iyzipay_order` (
	    `iyzipay_order_id` int(11) NOT NULL AUTO_INCREMENT,
	    `payment_id`  int(11) NOT NULL,
	    `order_id` int(11) NOT NULL,
	    `total_amount` decimal( 10, 2 ) NOT NULL,
	    `status` varchar(20) NOT NULL,
	    `created_at`  timestamp DEFAULT current_timestamp,
    PRIMARY KEY  (`iyzipay_order_id`)
) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'iyzipay_card` (
    `iyzipay_card_id` int(11) NOT NULL AUTO_INCREMENT,
    `customer_id` INT(11) NOT NULL,
    `card_user_key` varchar(50) NOT NULL,
    `api_key` varchar(50) NOT NULL,
    `created_at`  timestamp DEFAULT current_timestamp,
    PRIMARY KEY  (`iyzipay_card_id`)
) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';


foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}