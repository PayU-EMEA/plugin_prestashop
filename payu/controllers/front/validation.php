<?php
/**
 * PayU validation
 * 
 * @author    PayU
 * @copyright Copyright (c) 2014 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
 */

class PayUValidationModuleFrontController extends ModuleFrontController
{
	public function postProcess()
	{
		$cart = $this->context->cart;

		//$id_session = Tools::getValue('sessionId');
		$id_session = $this->context->cookie->__get('payu_order_id');
		$redirect_uri = Tools::getValue('redirectUri');

		$payu = new PayU();

		$payu->id_session = $id_session;
		$payu->id_cart = $cart->id;

		file_put_contents(_PS_MODULE_DIR_.'/../log/payu.log', print_r($payu, true));

		$payu->addOrderSessionId(PayU::PAYMENT_STATUS_NEW);

		Tools::redirect($redirect_uri);
	}
}
