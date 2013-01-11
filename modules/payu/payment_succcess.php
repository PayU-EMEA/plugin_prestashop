<?php
/**
*	ver. 1.8
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

$payu = new Payu();
$values = array('order' => Tools::getValue('order'), 'error' => Tools::getValue('error'));

if(Tools::getValue('error'))
{
    Tools::redirectLink($payu->getModuleAddress(true, true) . 'payment_error.php?'.http_build_query($values, '', '&'));
    exit;
}

$payu->execSuccessOrder((int)(Tools::getValue('order')));
exit();