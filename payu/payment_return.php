<?php
/**
 * PayU
 * 
 * @author    PayU
 * @copyright Copyright (c) 2014 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
 */

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');

$payu = new PayU();
$payu->interpretReturnParameters($_SERVER);

if (version_compare(_PS_VERSION_, '1.5', 'lt'))
	Tools::redirect('history.php', __PS_BASE_URI__, null, 'HTTP/1.1 301 Moved Permanently');
else
	Tools::redirect('index.php?controller=history', __PS_BASE_URI__, null, 'HTTP/1.1 301 Moved Permanently');
