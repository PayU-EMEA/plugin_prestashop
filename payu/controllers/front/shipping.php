<?php
/**
 * PayU shipping
 * 
 * @author    PayU
 * @copyright Copyright (c) 2014 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
 */

class PayUShippingModuleFrontController extends ModuleFrontController
{
	public function process()
	{
		if (Tools::getIsset('DOCUMENT'))
		{
			$data = Tools::getValue('DOCUMENT');
			$result = OpenPayU_Order::consumeMessage($data, false);

			if ($result->getMessage() == 'ShippingCostRetrieveRequest')
			{
				$id_payu_session = $result->getSessionId();
				$iso_country_code = $result->getCountryCode();

				$payu = new PayU();
				$order_payment = $payu->getOrderPaymentBySessionId($id_payu_session);
				$id_cart = $order_payment['id_cart'];

				if (!empty($id_cart))
				{
					$payu->id_cart = $id_cart;
					$payu->id_session = $id_payu_session;
					$payu->id_request = $result->getReqId();

					$xml = $payu->shippingCostRetrieveRequest($iso_country_code);

					if (!empty($xml))
					{
						header('Content-Type:text/xml');
						echo $xml;
					}
				}
			}
		}
		exit;
	}
}
