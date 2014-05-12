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

class PayuLu extends PayuSettings
{

	private $_merchantId = '';
	private $_secretKey = '';
	private $_AutoMode = 0;
	private $_TestMode = false;
	private $_luQueryUrl = '';
	private $_Discount;
	private $_Language;
	private $_OrderRef;
	private $_OrderDate;
	private $_PriceCurrency;
	private $_Currency;
	private $_BackRef;
	private $_PayMethod;
	private $_Debug;
	private $_billingAddress;
	private $_deliveryAddress;
	private $_destinationAddress;
	private $_OrderShipping;
	private $_allProducts = array();
	private $_tempProds = array();
	private $_htmlFormCode;
	private $_htmlCode;
	private $_hashString;
	private $_HASH;
	public $_explained;


	/**
	 * @param $merchantId
	 * @param $secretKey
	 */
	public function __construct($merchantId, $secretKey)
	{
		$this->_merchantId = $merchantId; // store the merchant id
		$this->_secretKey = $secretKey; // store the secretkey
		if (empty($merchantId) && empty($secretKey))
		{
			self::_logError('MECHANT id and SECRET KEY missing');
			return 0;
		}
		$this->_allErrors[self::DEBUG_WARNING] = '';
		$this->_allErrors[self::DEBUG_ERROR] = '';
		$this->_allErrors[self::DEBUG_ALL] = '';
		return 1;
	}

	/**
	 * Adds Address for the delivery set
	 *
	 * @param PayuAddress $currentAddress the address to be used as the delivery
	 * @return int returns 1 upon success
	 */
	public function setDeliveryAddress(PayuAddress $currentAddress)
	{
		if ($currentAddress)
		{
			$this->_deliveryAddress = $currentAddress;
			$possibleErrors = $currentAddress->validate(); // read errors for the current product
			$this->_mergeErrorLogs($possibleErrors);
			return 1;
		}
		return 0;
	}

	/**
	 * Adds Address for the billing set
	 *
	 * @param PayuAddress $currentAddress the address to be used as the billing
	 * @return int returns 1 upon success
	 */
	public function setBillingAddress(PayuAddress $currentAddress)
	{
		if ($currentAddress)
		{
			$this->_billingAddress = $currentAddress;
			$possibleErrors = $currentAddress->validate(); // read errors for the current product
			$this->_mergeErrorLogs($possibleErrors);
			return 1;
		}
		return 0;
	}

	/**
	 * Adds Address for the destination set
	 *
	 * @param PayuAddress $currentAddress the address to be used as the billing
	 * @return int returns 1 upon success
	 */
	public function setDestinationAddress(PayuAddress $currentAddress)
	{
		if ($currentAddress)
		{
			$this->_destinationAddress = $currentAddress;
			$possibleErrors = $currentAddress->validate(); // read errors for the current product
			$this->_mergeErrorLogs($possibleErrors);
			return 1;
		}
		return 0;
	}

	/**
	 * Adds Products to be sent to PAYU via LiveUpdate
	 *
	 * @param PayuProduct $currentProduct the product to be added
	 * @return int returns 1 upon success
	 */
	public function addProduct(PayuProduct $currentProduct)
	{
		if ($currentProduct)
		{
			$this->_allProducts[] = $currentProduct; // add the current product
			$possibleErrors = $currentProduct->validate(); // read errors for the current product
			$this->_mergeErrorLogs($possibleErrors);
			return 1;
		}
		return 0;
	}

	/**
	 * Method will render all the fields needed for the Payment request
	 *
	 * @return string contains all the errors encountered
	 */
	public function renderPaymentInputs()
	{
		$this->_validate();
		$this->_setOrderDate();
		$this->_makeHashString();
		$this->_makeHash();
		$this->_makeFields();
		if (!empty($this->_allErrors[$this->_debugLevel]))
			echo $this->_allErrors[$this->_debugLevel];

		echo $this->_htmlFormCode;
		return $this->_allErrors[self::DEBUG_ALL];
	}

	/**
	 * Method will render the form needed needed for the Payment request
	 *
	 * @param  boolean $autoSubmit this will autosubmit the form upon rendering
	 * @return string all errors that have been generated
	 */
	public function renderPaymentForm($autoSubmit = false)
	{
		$this->_validate();
		$this->_setOrderDate();
		$this->_hashString = $this->_makeHashString();
		$this->_makeHash();
		$this->_makeFields();
		if (!empty($this->_allErrors[$this->_debugLevel]))
			echo $this->_allErrors[$this->_debugLevel];

		$this->_makeForm($autoSubmit);

		return $this->_htmlCode;
	}

	/**
	 * Method will gather and assemble all the fields needed for the form
	 *
	 * @return int 1 on success
	 */
	private function _makeFields()
	{
		$this->_htmlFormCode .= $this->_addInput('MERCHANT', $this->_merchantId);
		$this->_htmlFormCode .= $this->_addInput('ORDER_HASH', $this->_HASH);
		$this->_htmlFormCode .= (!empty($this->_BackRef) ? $this->_addInput('BACK_REF', $this->_BackRef) : '');
		$this->_htmlFormCode .= $this->_addInput('LANGUAGE', (empty($this->_Language) ? '' : $this->_Language));
		$this->_htmlFormCode .= $this->_addInput('ORDER_REF', (empty($this->_OrderRef) ? '' : $this->_OrderRef));
		$this->_htmlFormCode .= $this->_addInput('ORDER_DATE', (empty($this->_OrderDate) ? '' : $this->_OrderDate));
		$this->_htmlFormCode .= $this->_addInput(
			'DESTINATION_CITY',
			(empty($this->_destinationAddress->city) ? '' : $this->_destinationAddress->city)
		);
		$this->_htmlFormCode .= $this->_addInput(
			'DESTINATION_STATE',
			(empty($this->_destinationAddress->state) ? '' : $this->_destinationAddress->state)
		);
		$this->_htmlFormCode .= $this->_addInput(
			'DESTINATION_COUNTRY',
			(empty($this->_destinationAddress->countryCode) ? '' : $this->_destinationAddress->countryCode)
		);
		$this->_htmlFormCode .= $this->_addInput(
			'ORDER_SHIPPING',
			(empty($this->_OrderShipping) ? '' : $this->_OrderShipping)
		);

		$this->_htmlFormCode .= (!empty($this->_billingAddress->firstName) ? $this->_addInput(
			'BILL_FNAME',
			$this->_billingAddress->firstName
		) : '');
		$this->_htmlFormCode .= (!empty($this->_billingAddress->lastName) ? $this->_addInput(
			'BILL_LNAME',
			$this->_billingAddress->lastName
		) : '');
		$this->_htmlFormCode .= (!empty($this->_billingAddress->ciSerial) ? $this->_addInput(
			'BILL_CISERIAL',
			$this->_billingAddress->ciSerial
		) : '');
		$this->_htmlFormCode .= (!empty($this->_billingAddress->ciNumber) ? $this->_addInput(
			'BILL_CINUMBER',
			$this->_billingAddress->ciNumber
		) : '');
		$this->_htmlFormCode .= (!empty($this->_billingAddress->cnp) ? $this->_addInput(
			'BILL_CNP',
			$this->_billingAddress->cnp
		) : '');
		$this->_htmlFormCode .= (!empty($this->_billingAddress->company) ? $this->_addInput(
			'BILL_COMPANY',
			$this->_billingAddress->company
		) : '');
		$this->_htmlFormCode .= (!empty($this->_billingAddress->fiscalCode) ? $this->_addInput(
			'BILL_FISCALCODE',
			$this->_billingAddress->fiscalCode
		) : '');
		$this->_htmlFormCode .= (!empty($this->_billingAddress->regNumber) ? $this->_addInput(
			'BILL_REGNUMBER',
			$this->_billingAddress->regNumber
		) : '');
		$this->_htmlFormCode .= (!empty($this->_billingAddress->bank) ? $this->_addInput(
			'BILL_BANK',
			$this->_billingAddress->bank
		) : '');
		$this->_htmlFormCode .= (!empty($this->_billingAddress->bankAccount) ? $this->_addInput(
			'BILL_BANKACCOUNT',
			$this->_billingAddress->bankAccount
		) : '');
		$this->_htmlFormCode .= (!empty($this->_billingAddress->email) ? $this->_addInput(
			'BILL_EMAIL',
			$this->_billingAddress->email
		) : '');
		$this->_htmlFormCode .= (!empty($this->_billingAddress->phone) ? $this->_addInput(
			'BILL_PHONE',
			$this->_billingAddress->phone
		) : '');
		$this->_htmlFormCode .= (!empty($this->_billingAddress->fax) ? $this->_addInput(
			'BILL_FAX',
			$this->_billingAddress->fax
		) : '');
		$this->_htmlFormCode .= (!empty($this->_billingAddress->address) ? $this->_addInput(
			'BILL_ADDRESS',
			$this->_billingAddress->address
		) : '');
		$this->_htmlFormCode .= (!empty($this->_billingAddress->address2) ? $this->_addInput(
			'BILL_ADDRESS2',
			$this->_billingAddress->address2
		) : '');
		$this->_htmlFormCode .= (!empty($this->_billingAddress->zipCode) ? $this->_addInput(
			'BILL_ZIPCODE',
			$this->_billingAddress->zipCode
		) : '');
		$this->_htmlFormCode .= (!empty($this->_billingAddress->city) ? $this->_addInput(
			'BILL_CITY',
			$this->_billingAddress->city
		) : '');
		$this->_htmlFormCode .= (!empty($this->_billingAddress->state) ? $this->_addInput(
			'BILL_STATE',
			$this->_billingAddress->state
		) : '');
		$this->_htmlFormCode .= (!empty($this->_billingAddress->countryCode) ? $this->_addInput(
			'BILL_COUNTRYCODE',
			$this->_billingAddress->countryCode
		) : '');

		$this->_htmlFormCode .= (!empty($this->_deliveryAddress->firstName) ? $this->_addInput(
			'DELIVERY_FNAME',
			$this->_deliveryAddress->firstName
		) : '');
		$this->_htmlFormCode .= (!empty($this->_deliveryAddress->lastName) ? $this->_addInput(
			'DELIVERY_LNAME',
			$this->_deliveryAddress->lastName
		) : '');
		$this->_htmlFormCode .= (!empty($this->_deliveryAddress->ciSerial) ? $this->_addInput(
			'DELIVERY_CISERIAL',
			$this->_deliveryAddress->ciSerial
		) : '');
		$this->_htmlFormCode .= (!empty($this->_deliveryAddress->ciNumber) ? $this->_addInput(
			'BILL_CINUMBER',
			$this->_deliveryAddress->ciNumber
		) : '');
		$this->_htmlFormCode .= (!empty($this->_deliveryAddress->cnp) ? $this->_addInput(
			'DELIVERY_CNP',
			$this->_deliveryAddress->cnp
		) : '');
		$this->_htmlFormCode .= (!empty($this->_deliveryAddress->company) ? $this->_addInput(
			'DELIVERY_COMPANY',
			$this->_deliveryAddress->company
		) : '');
		$this->_htmlFormCode .= (!empty($this->_deliveryAddress->fiscalCode) ? $this->_addInput(
			'DELIVERY_FISCALCODE',
			$this->_deliveryAddress->fiscalCode
		) : '');
		$this->_htmlFormCode .= (!empty($this->_deliveryAddress->regNumber) ? $this->_addInput(
			'DELIVERY_REGNUMBER',
			$this->_deliveryAddress->regNumber
		) : '');
		$this->_htmlFormCode .= (!empty($this->_deliveryAddress->bank) ? $this->_addInput(
			'DELIVERY_BANK',
			$this->_deliveryAddress->bank
		) : '');
		$this->_htmlFormCode .= (!empty($this->_deliveryAddress->bankAccount) ? $this->_addInput(
			'DELIVERY_BANKACCOUNT',
			$this->_deliveryAddress->bankAccount
		) : '');
		$this->_htmlFormCode .= (!empty($this->_deliveryAddress->email) ? $this->_addInput(
			'DELIVERY_EMAIL',
			$this->_deliveryAddress->email
		) : '');
		$this->_htmlFormCode .= (!empty($this->_deliveryAddress->phone) ? $this->_addInput(
			'DELIVERY_PHONE',
			$this->_deliveryAddress->phone
		) : '');
		$this->_htmlFormCode .= (!empty($this->_deliveryAddress->fax) ? $this->_addInput(
			'DELIVERY_FAX',
			$this->_deliveryAddress->fax
		) : '');
		$this->_htmlFormCode .= (!empty($this->_deliveryAddress->address) ? $this->_addInput(
			'DELIVERY_ADDRESS',
			$this->_deliveryAddress->address
		) : '');
		$this->_htmlFormCode .= (!empty($this->_deliveryAddress->address2) ? $this->_addInput(
			'DELIVERY_ADDRESS2',
			$this->_deliveryAddress->address2
		) : '');
		$this->_htmlFormCode .= (!empty($this->_deliveryAddress->zipCode) ? $this->_addInput(
			'DELIVERY_ZIPCODE',
			$this->_deliveryAddress->zipCode
		) : '');
		$this->_htmlFormCode .= (!empty($this->_deliveryAddress->city) ? $this->_addInput(
			'DELIVERY_CITY',
			$this->_deliveryAddress->city
		) : '');
		$this->_htmlFormCode .= (!empty($this->_deliveryAddress->state) ? $this->_addInput(
			'DELIVERY_STATE',
			$this->_deliveryAddress->state
		) : '');
		$this->_htmlFormCode .= (!empty($this->_deliveryAddress->countryCode) ? $this->_addInput(
			'DELIVERY_COUNTRYCODE',
			$this->_deliveryAddress->countryCode
		) : '');

		$this->_htmlFormCode .= $this->_addInput('DISCOUNT', $this->_Discount);
		$this->_htmlFormCode .= $this->_addInput('PAY_METHOD', $this->_PayMethod);


		foreach ($this->_tempProds as $prodCode => $product)
		{
			$this->_htmlFormCode .= $this->_addInput('ORDER_PNAME[]', $product['prodName']);
			$this->_htmlFormCode .= $this->_addInput('ORDER_PCODE[]', $prodCode);
			$this->_htmlFormCode .= $this->_addInput(
				'ORDER_PINFO[]',
				(empty($product['prodInfo']) ? '' : $product['prodInfo'])
			);
			$this->_htmlFormCode .= $this->_addInput('ORDER_PRICE[]', $product['prodPrice']);
			$this->_htmlFormCode .= $this->_addInput('ORDER_QTY[]', $product['prodQuantity']);
			$this->_htmlFormCode .= $this->_addInput('ORDER_VAT[]', $product['prodVat']);
			$this->_htmlFormCode .= $this->_addInput(
				'ORDER_PRICE_TYPE[]',
				$product['prodPriceType']
			);
		}

		$this->_htmlFormCode .= $this->_addInput('CUSTOM_PLUGIN', "PRESTASHOP");
		$this->_htmlFormCode .= $this->_addInput('PRICES_CURRENCY', $this->_PriceCurrency);
		$this->_htmlFormCode .= (!empty($this->_Currency) ? $this->_addInput('CURRENCY', $this->_Currency) : '');
		$this->_htmlFormCode .= (!empty($this->_Debug) ? $this->_addInput('DEBUG', 'TRUE') : '');
		$this->_htmlFormCode .= (!empty($this->_TestMode) ? $this->_addInput('TESTORDER', $this->_TestMode) : '');
		$this->_htmlFormCode .= (!empty($this->_AutoMode) ? $this->_addInput('AUTOMODE', '1') : '');
		$this->_htmlFormCode .= (!empty($this->_OrderTimeout) ? $this->_addInput(
			'ORDER_TIMEOUT',
			$this->_OrderTimeout
		) : '');
		$this->_htmlFormCode .= (!empty($this->_OrderTimeoutUrl) ? $this->_addInput(
			'TIMEOUT_URL',
			$this->_OrderTimeoutUrl
		) : '');

		return 1;
	}

	/**
	 * Method will generate the actual FORM
	 *
	 * @param boolean $autoSubmit makes the form autosubmit
	 * @return int 1 on success
	 */
	private function _makeForm($autoSubmit = false)
	{
		$this->_htmlCode .= '<form action="'.$this->_luQueryUrl.'" method="POST" id="payForm" name="payForm">'."\n";
		$this->_htmlCode .= $this->_htmlFormCode;
		if ($autoSubmit === false)
			$this->_htmlCode .= '<input type="submit" value="Submit Payment Form" class="exclusive_large"/>'."\n";

		$this->_htmlCode .= '</form>';

		if ($autoSubmit === true)
			$this->_htmlCode .= '<script>document.payForm.submit();</script>';


		return 1;
	}

	/**
	 * Method will assemble the hash string
	 *
	 * @param string type of returned text HTML/plain
	 * @return int 1 on success
	 */
	private function _makeHashString($type = 'plain')
	{
		$finalPriceType = '';

		$this->HashString = $this->_addHashValue($this->_merchantId, 'MerchantId', $type);
		$this->HashString .= $this->_addHashValue($this->_OrderRef, 'OrderRef', $type);
		$this->HashString .= $this->_addHashValue($this->_OrderDate, 'OrderDate', $type);

		foreach ($this->_allProducts as $product)
		{
			$tempProd['prodName'] = $product->productName;
			$tempProd['prodInfo'] = $product->productInfo;
			$tempProd['prodPrice'] = $product->productPrice;
			$tempProd['prodQuantity'] = $product->productQuantity;
			$tempProd['prodVat'] = $product->productVat;
			$tempProd['prodPriceType'] = $product->productPriceType;
			$tempProd['customFields'] = $product->customFields;

			if (!empty($tempProds[$product->productCode]['prodQuantity']))
			{
				if ($tempProds[$product->productCode]['prodPrice'] != $product->productPrice)
				{
					$this->_logError(
						'Found more entries with same product code: '.$product->productCode.' (product code must be unique) and different prices'
					);
					$tempProds[$product->productCode] = $tempProd;
				} else
				{
					$this->_logError(
						'Found more entries with same product code: '.$product->productCode.', merged into 1',
						1
					);
					$tempProds[$product->productCode]['prodQuantity'] += $product->productQuantity;
				}
			} else
				$tempProds[$product->productCode] = $tempProd;
		}

		$prodNames = '';
		$prodInfo = '';
		$prodPrice = '';
		$prodQuantity = '';
		$prodVat = '';
		$prodCodes = '';
		$finalPriceType = '';

		$iterator = 0;
		foreach ($tempProds as $prodCode => $product)
		{
			$prodNames .= $this->_addHashValue($product['prodName'], 'ProductName['.$iterator.']', $type);
			$prodInfo .= $this->_addHashValue(
				(empty($product['prodInfo']) ? '' : $product['prodInfo']),
				'ProductInfo['.$iterator.']',
				$type
			);
			$prodPrice .= $this->_addHashValue($product['prodPrice'], 'ProductPrice['.$iterator.']', $type);
			$prodQuantity .= $this->_addHashValue($product['prodQuantity'], 'ProductQuality['.$iterator.']', $type);
			$prodVat .= $this->_addHashValue($product['prodVat'], 'ProductVat['.$iterator.']', $type);
			$prodCodes .= $this->_addHashValue($prodCode, 'ProductCode['.$iterator.']', $type);
			$finalPriceType .= $this->_addHashValue(
				(empty($product['prodPriceType']) ? '' : $product['prodPriceType']),
				'ProductPriceType['.$iterator.']',
				$type
			);

			$iterator++;
		}

		$this->HashString .= $prodNames;
		$this->HashString .= $prodCodes;
		$this->HashString .= $prodInfo;
		$this->HashString .= $prodPrice;
		$this->HashString .= $prodQuantity;
		$this->HashString .= $prodVat;


		$this->_tempProds = $tempProds;
		$this->HashString .= $this->_addHashValue(
			($this->checkEmptyVar($this->_OrderShipping) ? '' : $this->_OrderShipping),
			'OrderShipping',
			$type
		);
		$this->HashString .= $this->_addHashValue(
			($this->checkEmptyVar($this->_PriceCurrency) ? '' : $this->_PriceCurrency),
			'PriceCurrency',
			$type
		);
		$this->HashString .= $this->_addHashValue((empty($this->_Discount) ? '' : $this->_Discount), 'Discount', $type);
		$this->HashString .= $this->_addHashValue(
			(empty($this->_destinationAddress->city) ? '' : $this->_destinationAddress->city),
			'DestinationCity',
			$type
		);
		$this->HashString .= $this->_addHashValue(
			(empty($this->_destinationAddress->state) ? '' : $this->_destinationAddress->state),
			'DestinationState',
			$type
		);
		$this->HashString .= $this->_addHashValue(
			(empty($this->_destinationAddress->countryCode) ? '' : $this->_destinationAddress->countryCode),
			'DestinationCountryCode',
			$type
		);

		$this->HashString .= $this->_addHashValue(
			(empty($this->_PayMethod) ? '' : $this->_PayMethod),
			'PayMethod',
			$type
		);
		$this->HashString .= $finalPriceType;

		$this->HashString .= $this->_addHashValue('PRESTASHOP', 'CUSTOM_PLUGIN');

		return $this->HashString;
	}

	private function checkEmptyVar($string)
	{
		return (strlen(trim($string)) == 0);
	}

	/**
	 * Method will calculate the hash string
	 *
	 * @return int 1 on success
	 */
	private function _makeHash()
	{
		$this->_HASH = PayuSignature::generateHmac($this->_secretKey, $this->_hashString);
		return 1;
	}

	/**
	 * Sets the AUTOMODE for the LU query (the payment process will skip to the last step if all the data for BILLING and DELIVERY is ok)
	 *
	 * @return int 1 on success
	 */
	public function setAutoMode()
	{
		$this->_AutoMode = 1;
		return 1;
	}

	/**
	 * Sets the TESTORDER for the LU query (Order will be processed as a test)
	 *
	 * @param boolean testMode parameter is default TRUE
	 * @return int 1 on success
	 */
	public function setTestMode()
	{
		$this->_TestMode = true;
		return 1;
	}

	/**
	 * Sets the Discount for the order must be positive number
	 *
	 * @param float $discount value of the Discount for the LU
	 * @return int 1 on success
	 */
	public function setGlobalDiscount($discount)
	{
		$this->_Discount = $discount;
		return 1;
	}

	/**
	 * Sets the Language for the order
	 *
	 * @param string $lang value of the Language for the LU (RO, EN, etc)
	 * @return int 1 on success
	 */
	public function setLanguage($lang)
	{
		$this->_Language = $lang;
		return 1;
	}

	/**
	 * Sets the Order Reference for the order this is your internal order number
	 *
	 * @param $refno value of the Order Reference  for the LU
	 * @return int 1 on success
	 */
	public function setOrderRef($refno)
	{
		$this->_OrderRef = $refno;
		return 1;
	}

	/**
	 * Sets the Order Date for the order this is your internal order number
	 *
	 * @return int 1 on success
	 */
	private function _setOrderDate()
	{
		$this->_OrderDate = date('Y-m-d H:i:s', time());
		return 1;
	}

	/**
	 * Sets the Pay Method for the order
	 *
	 * @param string $payMethod value Payment method (please refer to the static vars in this class)
	 * @return int 1 on success
	 */
	public function setPayMethod($payMethod)
	{
		$this->_PayMethod = $payMethod;
		return 1;
	}

	/**
	 * Sets the currency in which the prices are sent
	 *
	 * @param string $currency value of the Currency (RON, USD, GBP, etc )
	 * @return int 1 on success
	 */
	public function setPaymentCurrency($currency)
	{
		$this->_PriceCurrency = $currency;
		return 1;
	}

	/**
	 * Sets the currency in which the prices will be tentatively interpreted
	 *
	 * @param string $currency value of the Currency
	 * @return int 1 on success
	 */
	public function setCurrency($currency)
	{
		$this->_Currency = $currency;
		return 1;
	}

	/**
	 * Sets the order timeout for this order
	 *
	 * @param int $timeout value of the timeout
	 * @return int 1 on success
	 */
	public function setOrderTimeout($timeout)
	{
		$this->_OrderTimeout = $timeout;
		return 1;
	}

	/**
	 * Sets the order timeout for this order
	 *
	 * @param string $url value of the url
	 * @return int 1 on success
	 */
	public function setTimeoutUrl($url)
	{
		$this->_OrderTimeoutUrl = $url;
		return 1;
	}

	/**
	 * Method will retrieve the hash string
	 *
	 * @param boolean $debug parameter will render the hashstring more visible with the length of strings highlited
	 * @return int 1 on success
	 */
	public function getHashString($debug = false)
	{
		if (!empty($this->_hashString))
		{
			if ($debug === true)
				return 'Hover on the substring for explanation:<br/><style>.puHidden{display:none;}.puInline{display: block;float: left;}</style>'.$this->_makeHashString(
					'HTML'
				).' <script type="javascript"></script>';
		else
			return $this->_hashString;

		} else
		{
			$this->_logError('Hash String not ready. Try renderPaymentForm or renderPaymentInputs first ', 1);
			return 0;
		}
	}

	/**
	 * Sets the BACK_REF for the order must be a http address
	 *
	 * @param string $url value of the url to be redirected at the end
	 * @return int 1 on success
	 */
	public function setBackRef($url)
	{
		$this->_BackRef = $url;
		return 1;
	}


	/**
	 * Sets the order shipping value
	 *
	 * @param $shipping
	 * @return int
	 */


	public function setOrderShipping($shipping)
	{
		$this->_OrderShipping = $shipping;
		return 1;
	}

	/**
	 * Sets the DEBUG for the LU., this sends a debug request to the Payment page
	 *
	 * @return int 1 on success
	 */
	public function setTrace()
	{
		$this->_Debug = 1;
		return 1;
	}


	/**
	 * Method will do a last minute validation of the object params
	 *
	 * @return string contains all errors
	 */
	private function _validate()
	{
		if (!empty($this->_Discount) && ($this->_Discount < 0 || !is_numeric($this->_Discount)))
			$this->_logError('Discount must be a positive number');


		if (!empty($this->_PayMethod) &&
			($this->_PayMethod != self::PAY_METHOD_CCVISAMC &&
				$this->_PayMethod != self::PAY_METHOD_CCAMEX &&
				$this->_PayMethod != self::PAY_METHOD_CCDINERS &&
				$this->_PayMethod != self::PAY_METHOD_CCJCB &&
				$this->_PayMethod != self::PAY_METHOD_WIRE &&
				$this->_PayMethod != self::PAY_METHOD_PAYPAL &&
				$this->_PayMethod != self::PAY_METHOD_CASH))
		{
			$this->_logError('Payment Method: '.$this->_PayMethod.' is not supported reverted to none', 1);
			$this->_PayMethod = '';
		}
		$this->_mergeErrorLogs($this->_errorLog);
		return $this->_errorLog;
	}

	/**
	 * Sets the debug mode for the class
	 *
	 * @param int debug level constants should be used
	 * @return int 1 on success
	 */
	public function setDebug($debugLevel)
	{
		$this->_debugLevel = $debugLevel;
		return 1;
	}

	/**
	 * Sets the the query url for the LiveUpdate request
	 *
	 * @param string the url to be used
	 * @return int 1 on success
	 */
	public function setQueryUrl($url)
	{
		$this->_luQueryUrl = $url;
		return 1;
	}

	/*
	 * Utility used by the assemble function
	 *
	 * @param string string to be added to the hash
	 * @param string name of the string
	 * @param string type of returned text HTML/plain
	 */

	private function _addHashValue($string, $name = '', $type)
	{
		if ($this->checkEmptyVar($string))
		{
			$returnHtmlValue = '<div class="puInline" onmouseover="document.getElementById(\''.md5(
					$name
				).'\').innerHTML=\''.$name.'\';document.getElementById(\''.md5(
					$name
				).'\').style.display=\'block\';this.style.border=\'1px solid\'" onmouseout="document.getElementById(\''.md5(
					$name
				).'\').style.display=\'none\';this.style.border=\'0\'"><b style="color:red">0</b><strong id="'.md5(
					$name
				).'" class="puHidden"></strong></div>';
			$returnValue = '0';
		} else
		{
			$returnHtmlValue = '<div class="puInline" onmouseover="document.getElementById(\''.md5(
					$name
				).'\').innerHTML=\''.$name.'\';document.getElementById(\''.md5(
					$name
				).'\').style.display=\'block\';this.style.border=\'1px solid\'" onmouseout="document.getElementById(\''.md5(
					$name
				).'\').style.display=\'none\';this.style.border=\'0\'"><b style="color:red">'.strlen(
					$string
				).'</b>'.$string.'<strong id="'.md5($name).'" class="puHidden"></strong></div>';
			$returnValue = strlen($string).$string;
		}

		if ($type == 'HTML')
			return $returnHtmlValue;
		else
			return $returnValue;
	}

	/*
	 * Add the input html code
	 *
	 * @param string name of the input
	 * @param string value of the input
	 */

	private function _addInput($string, $value)
	{
		return '<input type="hidden" name="'.strtoupper($string).'" value="'.htmlentities(
			$value,
			ENT_COMPAT,
			'UTF-8'
		).'" />'."\n";
	}

}

