<?php
/**
 * PayU success
 * 
 * @author    PayU
 * @copyright Copyright (c) 2014 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
 */

class PayUSuccessModuleFrontController extends ModuleFrontController
{
	public function initContent()
	{
		$payu = new PayU();

		$id_cart = Tools::getValue('id_cart');
		//$id_payu_session = Tools::getValue('id_payu_session');

		$id_payu_session = $this->context->cookie->__get('payu_order_id');

		//$id_payu_session = Tools::getValue('sessionId');

		if (Tools::getValue('error'))
			Tools::redirect('order.php?error='.Tools::getValue('error'), __PS_BASE_URI__, null, 'HTTP/1.1 301 Moved Permanently');

		$payu->id_cart = $id_cart;
		$payu->id_session = $id_payu_session;

		$order_payment = $payu->getOrderPaymentBySessionId($payu->id_session);

		$id_order = (int)$order_payment['id_order'];
		$payu->id_cart = (int)$order_payment['id_cart'];

		// if order not validated yet
		$cart_id = $payu->id_cart;
		if ($id_order == 0 && $order_payment['status'] == PayU::PAYMENT_STATUS_NEW)
		{
			$cart = new Cart($payu->id_cart);
			$cart_id = $cart->id;

			$payu->validateOrder(
				$cart->id, (int)Configuration::get('PAYU_PAYMENT_STATUS_PENDING'),
				$cart->getOrderTotal(true, Cart::BOTH), $payu->displayName,
				'PayU cart ID: '.$cart_id.', sessionId: '.$payu->id_session,
				null, (int)$cart->id_currency, false, $cart->secure_key,
				Context::getContext()->shop->id ? new Shop((int)Context::getContext()->shop->id) : null
			);

			//$payu->id_order = $payu->current_order = $payu->currentOrder;
			//file_put_contents(_PS_MODULE_DIR_.'/../log/isOrderIdSuccess.log',$payu->id_order.'\n',FILE_APPEND);

			$payu->updateOrderPaymentStatusBySessionId(PayU::PAYMENT_STATUS_INIT);
		}

		$id_order = $payu->getOrderIdBySessionId($id_payu_session);

		if (!empty($id_order))
		{
			$payu->id_order = $id_order;
			$payu->updateOrderData();
		}

		Tools::redirect(
			'index.php?controller=order-confirmation&id_cart='.$cart_id, __PS_BASE_URI__, null,
			'HTTP/1.1 301 Moved Permanently'
		);
	}
}