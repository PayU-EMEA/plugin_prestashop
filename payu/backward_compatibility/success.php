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

$id_cart = Tools::getValue('id_cart');

global $cookie;
$id_payu_session =  $cookie->__get('payu_order_id');

if (Tools::getValue('error'))
	Tools::redirect('order.php?error='.Tools::getValue('error'), __PS_BASE_URI__, null, 'HTTP/1.1 301 Moved Permanently');

$payu->id_cart = $id_cart;
$payu->id_session = $id_payu_session;

$order_payment = $payu->getOrderPaymentBySessionId($payu->id_session);
$id_order = (int)$order_payment['id_order'];

/* if order not validated yet */
if ($id_order == 0 && $order_payment['status'] == PayU::PAYMENT_STATUS_NEW)
{
	$cart = new Cart($payu->id_cart);
	$payu->validateOrder(
		$cart->id, Configuration::get('PAYU_PAYMENT_STATUS_PENDING'),
		$cart->getOrderTotal(true, Cart::BOTH), 'PayU cart ID: '.$cart->id.', sessionId: '.$payu->id_session, null,
		null, false, $cart->secure_key
	);

	$payu->id_order = $payu->current_order = $payu->currentOrder;
	$payu->updateOrderPaymentStatusBySessionId(PayU::PAYMENT_STATUS_INIT);
}

$id_order = $payu->getOrderIdBySessionId($id_payu_session);

if (!empty($id_order))
{
	$payu->id_order = $id_order;
	$payu->updateOrderData();
}

Tools::redirect('history.php', __PS_BASE_URI__, null, 'HTTP/1.1 301 Moved Permanently');