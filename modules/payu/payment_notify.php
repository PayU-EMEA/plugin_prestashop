<?php
/**
 *	ver. 1.9.2
 *	PayU Payment Modules
 *
 *	@copyright  Copyright 2012 by PayU
 *	@license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
 *	http://www.payu.com
 *	http://twitter.com/openpayu
 */
$useSSL = true;
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
ob_clean();
if(Tools::getValue('DOCUMENT'))
{
    $payu = new Payu();
    $payu->execNotifyOrder(Tools::getValue('DOCUMENT'));
}
ob_end_flush();
exit();