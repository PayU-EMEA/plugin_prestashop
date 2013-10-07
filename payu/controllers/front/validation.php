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


class PayUValidationModuleFrontController extends ModuleFrontController
{
	public function postProcess()
	{
		$cart = $this->context->cart;
		$id_session = Tools::getValue('sessionId');

		$payu = new PayU();

		$payu->id_session = $id_session;
		$payu->id_cart = $cart->id;

		$payu->addOrderSessionId(PayU::PAYMENT_STATUS_NEW);

		Tools::redirect(
			OpenPayUConfiguration::getSummaryUrl().'?sessionId='.$id_session.'&oauth_token='.
			Tools::getValue('oauth_token').'&lang='.Tools::getValue('lang')
		);
	}
}
