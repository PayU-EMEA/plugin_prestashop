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

$payu->payu_order_id = $id_session;
$payu->id_cart = $cart->id;

$payu->addOrderSessionId(PayU::PAYMENT_STATUS_NEW);

header(
    'Location: '.Tools::getValue('redirectUri')
);
