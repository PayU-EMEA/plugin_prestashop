<?php
/**
 * @copyright  Copyright (c) 2013 PayU
 * @license	http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
 *
 */


include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/payu.php');

$payu = new PayU();
$response = $payu->interpretIPN($_POST);

if ($response !== false)
	echo '<EPAYMENT>'.$response['date'].'|'.$response['hash'].'</EPAYMENT>';
