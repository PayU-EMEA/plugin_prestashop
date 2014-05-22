<?php

/**
 * @copyright  Copyright (c) 2013 PayU
 * @license	http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
 *
 */

class PayuProduct extends PayuSettings
{

	public $product_name = '';
	public $product_code = '';
	public $product_info = '';
	public $product_price = 0.0;
	public $product_price_type = '';
	public $product_quantity = 0;
	public $product_vat = 0.0;
	public $discount = '';
	public $custom_fields = array();

	/**
	 * Constructor
	 *
	 * @param string $product_name name of the product
	 * @param string $product_code code of the product
	 * @param string $product_info info of the product
	 * @param float $product_price price of the product
	 * @param string $product_price_type price type for the
	 * @param int $product_quantity quantity for the product
	 * @param string $product_tax tax of the product
	 */
	public function __construct($product_name = '',
		$product_code = '',
		$product_info = '',
		$product_price = 0.0,
		$product_price_type = '',
		$product_quantity = 0,
		$product_tax = '')
	{
		$this->setName($product_name);
		$this->setCode($product_code);
		$this->setInfo($product_info);
		$this->setPrice($product_price);
		$this->setPriceType($product_price_type);
		$this->setQuantity($product_quantity);
		$this->setTax($product_tax);
	}

	/**
	 * Adds a custom field to the product
	 *
	 * @param string $field_name value of the fields name
	 * @param string $field_value value of the field
	 * @return int 1 on success
	 */
	public function addCustomField($field_name, $field_value)
	{
		if (!$field_name)
		{
			self::logError(
				'Custom field name for product with name '.$this->product_name.' is not valid, field will be ignored',
				1
			);
			return 0;
		}
		$this->custom_fields[$field_name] = $field_value;
		return 1;
	}

	/**
	 * Sets the product code for the current product must be unique for each product
	 *
	 * @param string $product_name value of the product code
	 * @return int 1 on success
	 */
	public function setName($product_name)
	{
		$this->product_name = $product_name;
		return 1;
	}

	/**
	 * Sets the product code for the current product must be unique for each product
	 *
	 * @param string $code value of the product code
	 * @return int 1 on success
	 */
	public function setCode($code)
	{
		$this->product_code = $code;
		return 1;
	}

	/**
	 * Sets the information for the current product (long description) it is optional
	 *
	 * @param string $product_info value of the information
	 * @return int 1 on success
	 */
	public function setInfo($product_info)
	{
		$this->product_info = $product_info;
		return 1;
	}

	/**
	 * Sets the Price for the current product must be above zero
	 *
	 * @param float $product_price value of the price must be a above zero
	 * @return int 1 on success
	 */
	public function setPrice($product_price)
	{
		$this->product_price = $product_price;
		return 1;
	}

	/**
	 * Sets the Price Type for the current product must be either NET or GROSS
	 *
	 * @param string $product_price_type value of the Price Type must be either NET or GROSS
	 * @return int 1 on success
	 */
	public function setPriceType($product_price_type)
	{
		$this->product_price_type = $product_price_type;
		return 1;
	}

	/**
	 * Sets the Quantity for the current product
	 *
	 * @param int $product_quantity value of the quantity must be a integer
	 * @return int 1 on success
	 */
	public function setQuantity($product_quantity)
	{
		$this->product_quantity = $product_quantity;
		return 1;
	}

	/**
	 * Sets the VAT (TAX) for the current product
	 *
	 * @param int $product_vat value of the VAT
	 * @return int 1 on success
	 */
	public function setTax($product_vat)
	{
		$this->product_vat = $product_vat;
		return 1;
	}

	/**
	 * Sets the discount per product
	 *
	 * @param int $discount_percent value of the discount to be applied
	 * @param string $discount_payment_method the payment method for which the discount applies
	 * @param string $discount_payment_options the payment option for which the discount applies
	 * @return int 1 on success
	 */
	public function setDiscount($discount_percent,
		$discount_payment_method,
		$discount_payment_options = PayuProduct::PAY_OPTION_ALL)
	{
		if (!empty($discount_payment_options))
		{
			if (!in_array(
				$discount_payment_options,
				array(
					self::PAY_OPTION_VISA,
					self::PAY_OPTION_VISA_ELECTRON,
					self::PAY_OPTION_MASTERCARD,
					self::PAY_OPTION_MAESTRO,
					self::PAY_OPTION_ALL
				)
			))
			{
				$discount_payment_options = self::PAY_OPTION_ALL;
				self::logError(
					' Payment Option for product with name '.$this->product_name.' is not valid assumed ALL',
					1
				);
			}
		}
		else
		{
			$discount_payment_options = self::PAY_OPTION_ALL;
			self::logError(
				' Payment Option for product with name '.$this->product_name.' is not valid assumed ALL',
				1
			);
		}

		if (empty($discount_payment_method))
		{
			self::logError(
				' Payment Method is missing for product with name '.$this->product_name.' discount will be ignored',
				1
			);
			return 0;
		}
		else
		{
			if (!in_array(
				$discount_payment_method,
				array(
					self::PAY_METHOD_CCVISAMC,
					self::PAY_METHOD_CCAMEX,
					self::PAY_METHOD_CCDINERS,
					self::PAY_METHOD_CCJCB,
					self::PAY_METHOD_WIRE,
					self::PAY_METHOD_PAYPAL,
					self::PAY_METHOD_CASH
				)
			))
			{
				self::logError(
					' Payment Method is missing for product with name '.$this->product_name.' discount will be ignored',
					1
				);
				return 0;
			}

			if ($discount_payment_method != self::PAY_METHOD_CCVISAMC && $discount_payment_options != self::PAY_OPTION_ALL)
			{
				self::logError(
					' Payment Method is incompatible with Payment Option for product with name '.$this->product_name
					.' Payment Option will be assumed as  PayuProduct::PAY_OPTION_ALL',
					1
				);
				$discount_payment_options = self::PAY_OPTION_ALL;
			}
		}

		$formatted_discount_percent = number_format($discount_percent, 2);
		if (!empty($discount_percent))
		{
			if ($formatted_discount_percent != $discount_percent)
			{
				self::logError(
					' Discount percent for product with name '.$this->product_name.' has more then 2 zecimals and was truncated ',
					1
				);
				$discount_percent = $formatted_discount_percent;
			}

			if ($discount_percent <= 0)
				self::logError(
					' Discount percent for product with name '.$this->product_name.' must be a positive number ',
					2
				);

			if ($discount_percent > 99.9)
				self::logError(
					' Discount percent for product with name '.$this->product_name.' must be a below 99.9 ',
					2
				);

		}
		else
		{
			self::logError(
				' Payment Method is missing for product with name '.$this->product_name.' discount will be ignored',
				1
			);
			return 0;
		}

		if ($discount_percent && $discount_payment_method && $discount_payment_options)
			$this->discount = $discount_percent.'|'.$discount_payment_method.'|'.$discount_payment_options;

		return 1;
	}

	/**
	 * Method will check for the product integrity and needed values
	 *
	 * @return int 1 on success
	 */
	public function checkProduct()
	{
		if (strlen($this->product_name) > 155)
		{
			$this->logError(
				'Product Name for product with name '.$this->product_name.' must not exceede 155 chars. String was truncated.',
				1
			);
			$this->product_name = substr($this->product_name, 0, 155);
		}

		if (strlen($this->product_info) > 255)
		{
			$this->logError(
				'Product Info for product with name '.$this->product_name.' must not exceede 255 chars. String was truncated.',
				1
			);
			$this->product_info = substr($this->product_info, 0, 255);
		}

		if (strlen($this->product_code) > 50)
		{
			$this->logError(
				'Product Code for product with name '.$this->product_name.' must not exceede 50 chars. String was truncated.'
			);
			$this->product_code = substr($this->product_code, 0, 50);
		}

		if ($this->product_price < 0 || !is_numeric($this->product_price) || $this->product_price == 0)
		{
			$this->logError(
				'Price for product with name '.$this->product_name.' must be a positive number above 0'
			);
		}

		if ($this->product_quantity < 0 || !is_numeric($this->product_quantity) || (int)$this->product_quantity == 0)
		{
			$this->logError(
				'Quantity for product with name '.$this->product_name.' must be a positive number above 0'
			);
		}

		if ($this->product_quantity != (int)$this->product_quantity)
		{
			$this->logError(
				'Quantity for product with name '.$this->product_name.' must be a integer number recieved: '.$this->product_quantity
				.' assumed: '.(int)$this->product_quantity,
				1
			);
			$this->product_quantity = (int)$this->product_quantity;
		}

		if ((string)$this->product_vat == (string)(float)$this->product_vat)
			if ($this->product_vat < 0)
				$this->logError('Tax for '.$this->product_name.' must be a positive number');
		else
			$this->logError('Tax for '.$this->product_name.' must be a positive number');

		if ($this->product_vat > 0 && $this->product_vat < 1)
			$this->logError('Tax for '.$this->product_name.' must be a number above 1 or 0');

		if (!$this->product_name)
			$this->logError(
				'Name is missing'.($this->product_code ? ' for product with code:'.$this->product_code : ''));

		if (!$this->product_code)
			$this->logError(
				'Code is missing'.($this->product_name ? ' for product with name:'.$this->product_name : '')
			);

		if (!$this->product_price_type || ($this->product_price_type != self::PRICE_TYPE_GROSS && $this->product_price_type != self::PRICE_TYPE_NET))
		{
			$this->logError(
				'PriceType is missing'.($this->product_name ? ' for product with name:'.$this->product_name : '').' assumed NET',
				1
			);
			$this->product_price_type = self::PRICE_TYPE_NET;
		}
		return 1;
	}

	/**
	 * Read errors for the current class and assert if there are any more errors regarding the current product
	 *
	 */
	public function validate()
	{
		self::checkProduct();
		return $this->error_log;
	}

}
