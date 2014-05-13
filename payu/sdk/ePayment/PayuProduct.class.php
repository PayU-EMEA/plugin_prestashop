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

	public $productName = '';
	public $productCode = '';
	public $productInfo = '';
	public $productPrice = 0.0;
	public $productPriceType = '';
	public $productQuantity = 0;
	public $productVat = 0.0;
	public $Discount = '';
	public $customFields = array();

	/**
	 * Constructor
	 *
	 * @param string $productName name of the product
	 * @param string $productCode code of the product
	 * @param string $productInfo info of the product
	 * @param float $productPrice price of the product
	 * @param string $productPriceType price type for the
	 * @param int $productQuantity quantity for the product
	 * @param string $productTax tax of the product
	 */
	public function __construct($productName = '',
		$productCode = '',
		$productInfo = '',
		$productPrice = 0.0,
		$productPriceType = '',
		$productQuantity = 0,
		$productTax = '')
	{
		$this->setName($productName);
		$this->setCode($productCode);
		$this->setInfo($productInfo);
		$this->setPrice($productPrice);
		$this->setPriceType($productPriceType);
		$this->setQuantity($productQuantity);
		$this->setTax($productTax);
	}

	/**
	 * Adds a custom field to the product
	 *
	 * @param string $fieldName value of the fields name
	 * @param string $fieldValue value of the field
	 * @return int 1 on success
	 */
	public function addCustomField($fieldName, $fieldValue)
	{
		if (!$fieldName)
		{
			self::_logError(
				'Custom field name for product with name '.$this->productName.' is not valid, field will be ignored',
				1
			);
			return 0;
		}
		$this->customFields[$fieldName] = $fieldValue;
		return 1;
	}

	/**
	 * Sets the product code for the current product must be unique for each product
	 *
	 * @param string $productName value of the product code
	 * @return int 1 on success
	 */
	public function setName($productName)
	{
		$this->productName = $productName;
		return 1;
	}

	/**
	 * Sets the product code for the current product must be unique for each product
	 *
	 * @param string $setCode value of the product code
	 * @return int 1 on success
	 */
	public function setCode($setCode)
	{
		$this->productCode = $setCode;
		return 1;
	}

	/**
	 * Sets the information for the current product (long description) it is optional
	 *
	 * @param string $productInfo value of the information
	 * @return int 1 on success
	 */
	public function setInfo($productInfo)
	{
		$this->productInfo = $productInfo;
		return 1;
	}

	/**
	 * Sets the Price for the current product must be above zero
	 *
	 * @param float $productPrice value of the price must be a above zero
	 * @return int 1 on success
	 */
	public function setPrice($productPrice)
	{
		$this->productPrice = $productPrice;
		return 1;
	}

	/**
	 * Sets the Price Type for the current product must be either NET or GROSS
	 *
	 * @param string $productPriceType value of the Price Type must be either NET or GROSS
	 * @return int 1 on success
	 */
	public function setPriceType($productPriceType)
	{
		$this->productPriceType = $productPriceType;
		return 1;
	}

	/**
	 * Sets the Quantity for the current product
	 *
	 * @param int $productQuantity value of the quantity must be a integer
	 * @return int 1 on success
	 */
	public function setQuantity($productQuantity)
	{
		$this->productQuantity = $productQuantity;
		return 1;
	}

	/**
	 * Sets the VAT (TAX) for the current product
	 *
	 * @param int $productVat value of the VAT
	 * @return int 1 on success
	 */
	public function setTax($productVat)
	{
		$this->productVat = $productVat;
		return 1;
	}

	/**
	 * Sets the discount per product
	 *
	 * @param int $discountPercent value of the discount to be applied
	 * @param string $discountPaymentMethod the payment method for which the discount applies
	 * @param string $discountPaymentOptions the payment option for which the discount applies
	 * @return int 1 on success
	 */
	public function setDiscount($discountPercent,
		$discountPaymentMethod,
		$discountPaymentOptions = PayuProduct::PAY_OPTION_ALL)
	{
		if (!empty($discountPaymentOptions))
		{
			if (!in_array(
				$discountPaymentOptions,
				array(
					self::PAY_OPTION_VISA,
					self::PAY_OPTION_VISA_ELECTRON,
					self::PAY_OPTION_MASTERCARD,
					self::PAY_OPTION_MAESTRO,
					self::PAY_OPTION_ALL
				)
			))
			{
				$discountPaymentOptions = self::PAY_OPTION_ALL;
				self::_logError(
					' Payment Option for product with name '.$this->productName.' is not valid assumed ALL',
					1
				);
			}
		}
		else
		{
			$discountPaymentOptions = self::PAY_OPTION_ALL;
			self::_logError(
				' Payment Option for product with name '.$this->productName.' is not valid assumed ALL',
				1
			);
		}


		if (empty($discountPaymentMethod))
		{
			self::_logError(
				' Payment Method is missing for product with name '.$this->productName.' discount will be ignored',
				1
			);
			return 0;
		}
		else
		{
			if (!in_array(
				$discountPaymentMethod,
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
				self::_logError(
					' Payment Method is missing for product with name '.$this->productName.' discount will be ignored',
					1
				);
				return 0;
			}

			if ($discountPaymentMethod != self::PAY_METHOD_CCVISAMC && $discountPaymentOptions != self::PAY_OPTION_ALL)
			{
				self::_logError(
					' Payment Method is incompatible with Payment Option for product with name '.$this->productName
					.' Payment Option will be assumed as  PayuProduct::PAY_OPTION_ALL',
					1
				);
				$discountPaymentOptions = self::PAY_OPTION_ALL;
			}
		}

		$formatedDiscountPercent = number_format($discountPercent, 2);
		if (!empty($discountPercent))
		{
			if ($formatedDiscountPercent != $discountPercent)
			{
				self::_logError(
					' Discount percent for product with name '.$this->productName.' has more then 2 zecimals and was truncated ',
					1
				);
				$discountPercent = $formatedDiscountPercent;
			}

			if ($discountPercent <= 0)
				self::_logError(
					' Discount percent for product with name '.$this->productName.' must be a positive number ',
					2
				);

			if ($discountPercent > 99.9)
				self::_logError(
					' Discount percent for product with name '.$this->productName.' must be a below 99.9 ',
					2
				);

		}
		else
		{
			self::_logError(
				' Payment Method is missing for product with name '.$this->productName.' discount will be ignored',
				1
			);
			return 0;
		}


		if ($discountPercent && $discountPaymentMethod && $discountPaymentOptions)
			$this->Discount = $discountPercent.'|'.$discountPaymentMethod.'|'.$discountPaymentOptions;

		return 1;
	}

	/**
	 * Method will check for the product integrity and needed values
	 *
	 * @return int 1 on success
	 */
	public function checkProduct()
	{
		if (strlen($this->productName) > 155)
		{
			$this->_logError(
				'Product Name for product with name '.$this->productName.' must not exceede 155 chars. String was truncated.',
				1
			);
			$this->productName = substr($this->productName, 0, 155);
		}

		if (strlen($this->productInfo) > 255)
		{
			$this->_logError(
				'Product Info for product with name '.$this->productName.' must not exceede 255 chars. String was truncated.',
				1
			);
			$this->productInfo = substr($this->productInfo, 0, 255);
		}

		if (strlen($this->productCode) > 50)
		{
			$this->_logError(
				'Product Code for product with name '.$this->productName.' must not exceede 50 chars. String was truncated.'
			);
			$this->productCode = substr($this->productCode, 0, 50);
		}

		if ($this->productPrice < 0 || !is_numeric($this->productPrice) || $this->productPrice == 0)
		{
			$this->_logError(
				'Price for product with name '.$this->productName.' must be a positive number above 0'
			);
		}

		if ($this->productQuantity < 0 || !is_numeric($this->productQuantity) || (int)$this->productQuantity == 0)
		{
			$this->_logError(
				'Quantity for product with name '.$this->productName.' must be a positive number above 0'
			);
		}

		if ($this->productQuantity != (int)$this->productQuantity)
		{
			$this->_logError(
				'Quantity for product with name '.$this->productName.' must be a integer number recieved: '.$this->productQuantity.' assumed: '.(int)$this->productQuantity,
				1
			);
			$this->productQuantity = (int)$this->productQuantity;
		}


		if ((string)$this->productVat == (string)(float)$this->productVat)
			if ($this->productVat < 0)
				$this->_logError('Tax for '.$this->productName.' must be a positive number');
		else
			$this->_logError('Tax for '.$this->productName.' must be a positive number');


		if ($this->productVat > 0 && $this->productVat < 1)
			$this->_logError('Tax for '.$this->productName.' must be a number above 1 or 0');

		if (!$this->productName)
			$this->_logError(
				'Name is missing'.($this->productCode ? ' for product with code:'.$this->productCode : ''));

		if (!$this->productCode)
			$this->_logError(
				'Code is missing'.($this->productName ? ' for product with name:'.$this->productName : '')
			);

		if (!$this->productPriceType || ($this->productPriceType != self::PRICE_TYPE_GROSS && $this->productPriceType != self::PRICE_TYPE_NET))
		{
			$this->_logError(
				'PriceType is missing'.($this->productName ? ' for product with name:'.$this->productName : '').' assumed NET',
				1
			);
			$this->productPriceType = self::PRICE_TYPE_NET;
		}
		return 1;
	}

	/**
	 * Read errors for the current class and assert if there are any more errors regarding the current product
	 *
	 * @return int 1 on success
	 */
	public function validate()
	{
		self::checkProduct();
		return $this->_errorLog;
	}

}
