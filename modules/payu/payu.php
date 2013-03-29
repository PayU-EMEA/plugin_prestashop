<?php
/**
 *	ver. 1.9.6
 *	PayU Payment Modules
 *
 *	@copyright  Copyright 2012 by PayU
 *	@license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
 *	http://www.payu.com
 *	http://twitter.com/openpayu
 */
if (!defined('_PS_VERSION_'))
    exit;

include_once (_PS_ROOT_DIR_ . '/tools/payu/sdk/openpayu.php');
include_once (_PS_ROOT_DIR_ . '/modules/payu/payu_abstract.php');
include_once (_PS_ROOT_DIR_ . '/modules/payu/payu_session.php');

if (_PS_VERSION_ < '1.5')
    include(_PS_MODULE_DIR_.'/payu/payu_1.4.php');
else
    include(_PS_MODULE_DIR_.'/payu/payu_1.5.php');

