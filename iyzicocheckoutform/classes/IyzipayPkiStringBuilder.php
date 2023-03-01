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

class IyzipayPkiStringBuilder
{
    /**
     * @param $objectData
     * @return string
     */
    public static function pkiStringGenerate($objectData)
    {
        $pki_value = '[';

        foreach ($objectData as $key => $data) {
            if (is_object($data)) {
                $name = var_export($key, true);
                $name = str_replace("'", '', $name);
                $pki_value .= $name.'=[';
                $end_key = count(get_object_vars($data));
                $count = 0;

                foreach ($data as $key => $value) {
                    ++$count;
                    $name = var_export($key, true);
                    $name = str_replace("'", '', $name);
                    $pki_value .= $name.'='.''.$value;
                    if ($end_key != $count) {
                        $pki_value .= ',';
                    }
                }

                $pki_value .= ']';
            } elseif (is_array($data)) {
                $name = var_export($key, true);
                $name = str_replace("'", '', $name);
                $pki_value .= $name.'=[';
                $end_key = count($data);
                $count = 0;

                foreach ($data as $key => $result) {
                    ++$count;
                    $pki_value .= '[';

                    foreach ($result as $key => $item) {
                        $name = var_export($key, true);
                        $name = str_replace("'", '', $name);
                        $pki_value .= $name.'='.''.$item;
                        if (end($result) != $item) {
                            $pki_value .= ',';
                        }
                        if (end($result) == $item) {
                            if ($end_key != $count) {
                                $pki_value .= '], ';
                            } else {
                                $pki_value .= ']';
                            }
                        }
                    }
                }
                if (end($data) == $result) {
                    $pki_value .= ']';
                }
            } else {
                $name = var_export($key, true);
                $name = str_replace("'", '', $name);
                $pki_value .= $name.'='.''.$data.'';
            }
            if (end($objectData) != $data) {
                $pki_value .= ',';
            }
        }
        $pki_value .= ']';

        return $pki_value;
    }

    /**
     * @param $pkiString
     * @param $apiKey
     * @param $secretKey
     * @param $rand
     * @return array
     */
    public static function authorization($pkiString, $apiKey, $secretKey, $rand)
    {
        $hash_value = $apiKey.$rand.$secretKey.$pkiString;
        $hash = base64_encode(sha1($hash_value, true));
        $authorizationText = 'IYZWS '.$apiKey.':'.$hash;

        $authorization = array(
            'authorization' => $authorizationText,
            'randValue' => $rand,
        );

        return $authorization;
    }
}
