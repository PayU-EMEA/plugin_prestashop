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
include(dirname(__FILE__).'/../../../header.php');

$payu = new PayU();

$success = $payu->interpretReturnParameters($_SERVER);

if (version_compare(_PS_VERSION_, '1.5', 'lt'))
	Tools::redirect('history.php'.($success?'':'?payu_order_error=1'), __PS_BASE_URI__, null, 'HTTP/1.1 301 Moved Permanently');
