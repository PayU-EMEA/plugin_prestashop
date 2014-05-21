<?php
/**
 * OpenPayU
 *
 * @copyright  Copyright (c) 2013 PayU
 * @license    http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
 *
 */

include(dirname(__FILE__).'/../../../config/config.inc.php');
include(dirname(__FILE__).'/../../../init.php');
include(dirname(__FILE__).'/../../../header.php');

$id_session = Tools::getValue('sessionId');

$payu = new PayU();

$payu->id_session = $id_session;
$payu->id_cart = $cart->id;

$payu->addOrderSessionId(PayU::PAYMENT_STATUS_NEW);

header(
	'Location: '.OpenPayUConfiguration::getSummaryUrl().'?sessionId='.$id_session.'&oauth_token='.
	Tools::getValue('oauth_token').'&lang='.Tools::getValue('lang')
);
