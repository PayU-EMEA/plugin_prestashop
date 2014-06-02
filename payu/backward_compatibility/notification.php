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

ob_clean();
if (Tools::getIsset('DOCUMENT'))
{
	$data = Tools::getValue('DOCUMENT');
	$result = OpenPayUOrder::consumeMessage($data, false);

	if ($result->getSuccess() && $result->getMessage() == 'OrderNotifyRequest')
	{
		$payu = new PayU();
		$payu->id_session = $result->getSessionId();
		$order_payment = $payu->getOrderPaymentBySessionId($payu->id_session);
		$id_order = (int)$order_payment['id_order'];

		/*if order not validated yet*/
		if ($id_order == 0 && $order_payment['status'] == PayU::PAYMENT_STATUS_NEW)
		{
			$cart = new Cart($order_payment['id_cart']);

			$payu->validateOrder(
				$cart->id, Configuration::get('PAYU_PAYMENT_STATUS_PENDING'),
				$cart->getOrderTotal(true, Cart::BOTH), 'PayU cart ID: '.$cart->id.', sessionId: '.$payu->id_session,
				null,
				null, false, $cart->secure_key
			);

			$id_order = $payu->current_order = $payu->currentOrder;
			$payu->updateOrderPaymentStatusBySessionId(PayU::PAYMENT_STATUS_INIT);
		}

		if (!empty($id_order))
		{
			$payu->id_order = $id_order;
			$payu->updateOrderData();

			header('Content-Type:text/xml');
			echo $result->getResponse();
		}
	}
}
ob_end_flush();
exit;
