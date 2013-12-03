<?php
/**
 * ver. 1.9.11
 * PayU Payment Modules
 *
 * @copyright  Copyright 2012 by PayU
 * @license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
 * http://www.payu.com
 * http://twitter.com/openpayu
 */
session_start();
$useSSL = true;
include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/../../header.php');
$payu = new Payu();
echo $payu->execValidation();
include_once(dirname(__FILE__) . '/../../footer.php');
