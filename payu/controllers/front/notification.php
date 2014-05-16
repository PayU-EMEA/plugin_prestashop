<?php
/**
 * @copyright  Copyright (c) 2013 PayU
 * @license    http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
 *
 */


class PayUNotificationModuleFrontController extends ModuleFrontController
{

	public function process()
	{
	    $body = file_get_contents ( 'php://input' );
	    $data = stripslashes ( trim ( $body ) );
	    
	    $result = OpenPayU_Order::consumeNotification ( $data );
		
		$response = $result->getResponse();

		if (isset($response->order->orderId))
		{
			$payu = new PayU();
			$payu->id_session = $response->order->orderId;
			$order_payment = $payu->getOrderPaymentBySessionId($payu->id_session);
			$id_order = (int)$order_payment['id_order'];

			// if order not validated yet
			if ($id_order == 0 && $order_payment['status'] == PayU::PAYMENT_STATUS_NEW)
			{
				$cart = new Cart($order_payment['id_cart']);

				$payu->validateOrder(
					$cart->id, (int)Configuration::get('PAYU_PAYMENT_STATUS_PENDING'),
					$cart->getOrderTotal(true, Cart::BOTH), $payu->display_name,
					'PayU cart ID: '.$cart->id.', sessionId: '.$payu->id_session,
					null, (int)$cart->id_currency, false, $cart->secure_key,
					Context::getContext()->shop->id ? new Shop((int)Context::getContext()->shop->id) : null
				);
				$id_order = $payu->current_order = $payu->currentOrder;
				$payu->updateOrderPaymentStatusBySessionId(PayU::PAYMENT_STATUS_INIT);
			}

			if (!empty($id_order))
			{
				$payu->id_order = $id_order;
				$payu->updateOrderData();
			}
			
			$rsp = OpenPayU::buildOrderNotifyResponse ( $response->order->orderId );
			
			header("Content-Type: application/json");
			echo $rsp;
			
		}

		exit;
	}
}
