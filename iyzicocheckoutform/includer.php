<?php

/**
 * This file is main initialized file that loads other library file
 */

if (!defined('_PS_VERSION_') || (is_object(Context::getContext()->customer) && !Tools::getToken(false, Context::getContext())))
	exit;

require_once _PS_MODULE_DIR_.'iyzicocheckoutform/classes/IyzicocheckoutformOrder.php';