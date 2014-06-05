<?php
/**
 * OpenPayU
 *
 * @copyright  Copyright (c) 2013 PayU
 * @license	http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
 *
 */

include(dirname(__FILE__).'/../../../config/config.inc.php');
include(dirname(__FILE__).'/../../../init.php');

/* discard output of header.php */
ob_start();
include(dirname(__FILE__).'/../../../header.php');
ob_end_clean();

$payu = new PayU();

$response = $payu->interpretIPN($_POST);

if (isset($response['date'], $response['hash']))
	echo '<EPAYMENT>'.$response['date'].'|'.$response['hash'].'</EPAYMENT>';
elseif (isset($response['error']))
	echo '<EPAYMENT_ERROR>'.Tools::htmlentitiesUTF8($response['error']).'</EPAYMENT_ERROR>';
else
	echo '<EPAYMENT_ERROR>Unknown error</EPAYMENT_ERROR>';

exit;
