<?php
/**
 *	OpenPayU
 *
 *	@author    PayU
 *	@copyright Copyright (c) 2011-2014 PayU
 *	@license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *	http://www.payu.com
 *	http://developers.payu.com
 *	http://twitter.com/openpayu
 */

class EpaymentPrestaShopAdapter
{
	/**
	 * @var PayU
	 */
	private $module;

	public function __construct($module)
	{
		$this->module = $module;
	}

	/**
	 * @param CartCore $cart
	 * @return string
	 */
	public function getLuForm(CartCore $cart)
	{
		$merchant_id = Configuration::get('PAYU_EPAYMENT_MERCHANT');
		$secret_key = Configuration::get('PAYU_EPAYMENT_SECRET_KEY');
		$url = $this->module->getBusinessPartnerSetting('lu_url');

		if (empty($merchant_id) || empty($secret_key) || empty($url))
			return false;

		$live_update = new PayuLu($merchant_id, $secret_key);
		$live_update->setQueryUrl($url);

		$this->module->validateOrder($cart->id, (int)Configuration::get('PAYU_PAYMENT_STATUS_PENDING'),
			$cart->getOrderTotal(true, Cart::BOTH), $this->module->displayName, null,
			null, (int)$cart->id_currency, false, $cart->secure_key,
			Context::getContext()->shop->id ? new Shop((int)Context::getContext()->shop->id) : null
		);

		$this->module->current_order = $this->module->currentOrder;
		$this->module->current_order_reference = $this->module->currentOrderReference;

		$live_update->setBackRef(Context::getContext()->link->getModuleLink('payu', 'return', array('order_ref' => $this->module->current_order)));

		if (version_compare(_PS_VERSION_, '1.5', 'lt'))
		{
			$internal_reference = '#'.str_pad($this->module->current_order, 6, '0', STR_PAD_LEFT);
			$order_ref = $this->module->current_order.'|'.str_pad($this->module->current_order, 6, '0', STR_PAD_LEFT);
			$order_id = $this->module->current_order;
		}
		else
		{
			$internal_reference = $this->module->currentOrderReference;
			$order_ref = $this->module->currentOrder.'|'.$this->module->currentOrderReference;
			$order_id = $this->module->currentOrder;
		}

		$live_update->setOrderRef($order_ref);

		$currency = Currency::getCurrency($cart->id_currency);
		$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
		$lang_iso_code = Language::getIsoById($default_lang);
		$live_update->setPaymentCurrency($currency['iso_code']);
		$live_update->setLanguage(strtoupper($lang_iso_code));

		$payu_product = new PayuProduct();
		$payu_product->setName('Payment for order '.$internal_reference);
		$payu_product->setCode($internal_reference);
		$payu_product->setPrice($cart->getOrderTotal(true, Cart::BOTH));
		$payu_product->setTax(0);
		$payu_product->setQuantity(1);

		$live_update->addProduct($payu_product);

		if (!empty($cart->id_customer))
		{
			$customer = new Customer((int)$cart->id_customer);
			if ($customer->email)
			{
				if (!empty($cart->id_address_invoice) && Configuration::get('PS_INVOICE'))
				{
					$address = new Address((int)$cart->id_address_invoice);
					$country = new Country((int)$address->id_country);

					$billing = new PayuAddress();
					$billing->setFirstName($address->firstname);
					$billing->setLastName($address->lastname);
					$billing->setEmail($customer->email);
					$billing->setPhone(!$address->phone ? $address->phone_mobile : $address->phone);
					$billing->setAddress($address->address1);
					$billing->setAddress2($address->address2);
					$billing->setZipCode($address->postcode);
					$billing->setCity($address->city);
					$billing->setCountryCode(strtoupper($country->iso_code));

					$live_update->setBillingAddress($billing);
				}

				if (!empty($cart->id_address_delivery))
				{
					$address = new Address((int)$cart->id_address_delivery);
					$country = new Country((int)$address->id_country);

					$delivery = new PayuAddress();
					$delivery->setFirstName($address->firstname);
					$delivery->setLastName($address->lastname);
					$delivery->setEmail($customer->email);
					$delivery->setPhone(!$address->phone ? $address->phone_mobile : $address->phone);
					$delivery->setAddress($address->address1);
					$delivery->setAddress2($address->address2);
					$delivery->setZipCode($address->postcode);
					$delivery->setCity($address->city);
					$delivery->setCountryCode(strtoupper($country->iso_code));
					$live_update->setDeliveryAddress($delivery);
				}
			}
		}

		$lu_form = $live_update->renderPaymentForm(null);

		$this->module->savePayuTransaction($order_id, $cart->getOrderTotal(true, Cart::BOTH), Currency::getCurrency($cart->id_currency));

		return $lu_form;
	}
}
