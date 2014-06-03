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

class PayuLu extends PayuSettings
{

	/**
	 * @var string
	 */
	private $merchant_id = '';

	/**
	 * @var string
	 */
	private $secret_key = '';

	/**
	 * @var int
	 */
	private $auto_mode = 0;

	/**
	 * @var bool
	 */
	private $test_mode = false;

	/**
	 * @var string
	 */
	private $lu_query_url = '';

	/**
	 * @var float
	 */
	private $discount;

	/**
	 * @var string
	 */
	private $language;

	/**
	 * @var string
	 */
	private $order_ref;

	/**
	 * @var string date in "Y-m-d H:i:s" format
	 */
	private $order_date;

	/**
	 * @var string
	 */
	private $price_currency;

	/**
	 * @var string
	 */
	private $currency;

	/**
	 * @var string
	 */
	private $back_ref;

	/**
	 * @var string
	 */
	private $pay_method;

	/**
	 * @var int
	 */
	private $debug;

	/**
	 * @var PayuAddress
	 */
	private $billing_address;

	/**
	 * @var PayuAddress
	 */
	private $delivery_address;

	/**
	 * @var PayuAddress
	 */
	private $destination_address;

	/**
	 * @var string|mixed
	 */
	private $order_shipping;

	/**
	 * @var PayuProduct[]
	 */
	private $all_products = array();

	/**
	 * @var PayuProduct[]
	 */
	private $temp_prods = array();

	/**
	 * @var string
	 */
	private $html_form_code;

	/**
	 * @var string
	 */
	private $html_code;

	/**
	 * @var string
	 */
	private $hash_string;

	/**
	 * @var string
	 */
	private $hash;

	/**
	 * @var string|int
	 */
	private $order_timeout;

	/**
	 * @var string
	 */
	private $order_timeout_url;


	/**
	 * @param $merchant_id
	 * @param $secret_key
	 */
	public function __construct($merchant_id, $secret_key)
	{
		$this->merchant_id = $merchant_id; // store the merchant id
		$this->secret_key = $secret_key; // store the secretkey
		if (empty($merchant_id) && empty($secret_key))
			self::logError('MECHANT id and SECRET KEY missing');
		$this->all_errors[self::DEBUG_WARNING] = '';
		$this->all_errors[self::DEBUG_ERROR] = '';
		$this->all_errors[self::DEBUG_ALL] = '';
	}

	/**
	 * Adds Address for the delivery set
	 *
	 * @param PayuAddress $current_address the address to be used as the delivery
	 * @return int returns 1 upon success
	 */
	public function setDeliveryAddress(PayuAddress $current_address)
	{
		if ($current_address)
		{
			$this->delivery_address = $current_address;
			$possible_errors = $current_address->validate(); // read errors for the current product
			$this->mergeErrorLogs($possible_errors);
			return 1;
		}
		return 0;
	}

	/**
	 * Adds Address for the billing set
	 *
	 * @param PayuAddress $current_address the address to be used as the billing
	 * @return int returns 1 upon success
	 */
	public function setBillingAddress(PayuAddress $current_address)
	{
		if ($current_address)
		{
			$this->billing_address = $current_address;
			$possible_errors = $current_address->validate(); // read errors for the current product
			$this->mergeErrorLogs($possible_errors);
			return 1;
		}
		return 0;
	}

	/**
	 * Adds Address for the destination set
	 *
	 * @param PayuAddress $current_address the address to be used as the billing
	 * @return int returns 1 upon success
	 */
	public function setDestinationAddress(PayuAddress $current_address)
	{
		if ($current_address)
		{
			$this->destination_address = $current_address;
			$possible_errors = $current_address->validate(); // read errors for the current product
			$this->mergeErrorLogs($possible_errors);
			return 1;
		}
		return 0;
	}

	/**
	 * Adds Products to be sent to PAYU via LiveUpdate
	 *
	 * @param PayuProduct $current_product the product to be added
	 * @return int returns 1 upon success
	 */
	public function addProduct(PayuProduct $current_product)
	{
		if ($current_product)
		{
			$this->all_products[] = $current_product; // add the current product
			$possible_errors = $current_product->validate(); // read errors for the current product
			$this->mergeErrorLogs($possible_errors);
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
		$this->validate();
		$this->setOrderDate();
		$this->makeHashString();
		$this->makeHash();
		$this->makeFields();
		if (!empty($this->all_errors[$this->debug_level]))
			echo $this->all_errors[$this->debug_level];

		echo $this->html_form_code;
		return $this->all_errors[self::DEBUG_ALL];
	}

	/**
	 * Method will render the form needed needed for the Payment request
	 *
	 * @param  boolean $auto_submit this will autosubmit the form upon rendering
	 * @return string all errors that have been generated
	 */
	public function renderPaymentForm($auto_submit = false)
	{
		$this->validate();
		$this->setOrderDate();
		$this->hash_string = $this->makeHashString();
		$this->makeHash();
		$this->makeFields();
		if (!empty($this->all_errors[$this->debug_level]))
			echo $this->all_errors[$this->debug_level];

		$this->makeForm($auto_submit);

		return $this->html_code;
	}

	/**
	 * Method will gather and assemble all the fields needed for the form
	 *
	 * @return int 1 on success
	 */
	private function makeFields()
	{
		$this->html_form_code .= $this->addInput('MERCHANT', $this->merchant_id);
		$this->html_form_code .= $this->addInput('ORDER_HASH', $this->hash);
		$this->html_form_code .= (!empty($this->back_ref) ? $this->addInput('BACK_REF', $this->back_ref) : '');
		$this->html_form_code .= $this->addInput('LANGUAGE', (empty($this->language) ? '' : $this->language));
		$this->html_form_code .= $this->addInput('ORDER_REF', (empty($this->order_ref) ? '' : $this->order_ref));
		$this->html_form_code .= $this->addInput('ORDER_DATE', (empty($this->order_date) ? '' : $this->order_date));
		$this->html_form_code .= $this->addInput(
			'DESTINATION_CITY',
			(empty($this->destination_address->city) ? '' : $this->destination_address->city)
		);
		$this->html_form_code .= $this->addInput(
			'DESTINATION_STATE',
			(empty($this->destination_address->state) ? '' : $this->destination_address->state)
		);
		$this->html_form_code .= $this->addInput(
			'DESTINATION_COUNTRY',
			(empty($this->destination_address->country_code) ? '' : $this->destination_address->country_code)
		);
		$this->html_form_code .= $this->addInput(
			'ORDER_SHIPPING',
			(empty($this->order_shipping) ? '' : $this->order_shipping)
		);

		$this->html_form_code .= (!empty($this->billing_address->first_name) ? $this->addInput(
			'BILL_FNAME',
			$this->billing_address->first_name
		) : '');
		$this->html_form_code .= (!empty($this->billing_address->last_name) ? $this->addInput(
			'BILL_LNAME',
			$this->billing_address->last_name
		) : '');
		$this->html_form_code .= (!empty($this->billing_address->ci_serial) ? $this->addInput(
			'BILL_CISERIAL',
			$this->billing_address->ci_serial
		) : '');
		$this->html_form_code .= (!empty($this->billing_address->ci_number) ? $this->addInput(
			'BILL_CINUMBER',
			$this->billing_address->ci_number
		) : '');
		$this->html_form_code .= (!empty($this->billing_address->cnp) ? $this->addInput(
			'BILL_CNP',
			$this->billing_address->cnp
		) : '');
		$this->html_form_code .= (!empty($this->billing_address->company) ? $this->addInput(
			'BILL_COMPANY',
			$this->billing_address->company
		) : '');
		$this->html_form_code .= (!empty($this->billing_address->fiscal_code) ? $this->addInput(
			'BILL_FISCALCODE',
			$this->billing_address->fiscal_code
		) : '');
		$this->html_form_code .= (!empty($this->billing_address->reg_number) ? $this->addInput(
			'BILL_REGNUMBER',
			$this->billing_address->reg_number
		) : '');
		$this->html_form_code .= (!empty($this->billing_address->bank) ? $this->addInput(
			'BILL_BANK',
			$this->billing_address->bank
		) : '');
		$this->html_form_code .= (!empty($this->billing_address->bank_account) ? $this->addInput(
			'BILL_BANKACCOUNT',
			$this->billing_address->bank_account
		) : '');
		$this->html_form_code .= (!empty($this->billing_address->email) ? $this->addInput(
			'BILL_EMAIL',
			$this->billing_address->email
		) : '');
		$this->html_form_code .= (!empty($this->billing_address->phone) ? $this->addInput(
			'BILL_PHONE',
			$this->billing_address->phone
		) : '');
		$this->html_form_code .= (!empty($this->billing_address->fax) ? $this->addInput(
			'BILL_FAX',
			$this->billing_address->fax
		) : '');
		$this->html_form_code .= (!empty($this->billing_address->address) ? $this->addInput(
			'BILL_ADDRESS',
			$this->billing_address->address
		) : '');
		$this->html_form_code .= (!empty($this->billing_address->address2) ? $this->addInput(
			'BILL_ADDRESS2',
			$this->billing_address->address2
		) : '');
		$this->html_form_code .= (!empty($this->billing_address->zip_code) ? $this->addInput(
			'BILL_ZIPCODE',
			$this->billing_address->zip_code
		) : '');
		$this->html_form_code .= (!empty($this->billing_address->city) ? $this->addInput(
			'BILL_CITY',
			$this->billing_address->city
		) : '');
		$this->html_form_code .= (!empty($this->billing_address->state) ? $this->addInput(
			'BILL_STATE',
			$this->billing_address->state
		) : '');
		$this->html_form_code .= (!empty($this->billing_address->country_code) ? $this->addInput(
			'BILL_COUNTRYCODE',
			$this->billing_address->country_code
		) : '');

		$this->html_form_code .= (!empty($this->delivery_address->first_name) ? $this->addInput(
			'DELIVERY_FNAME',
			$this->delivery_address->first_name
		) : '');
		$this->html_form_code .= (!empty($this->delivery_address->last_name) ? $this->addInput(
			'DELIVERY_LNAME',
			$this->delivery_address->last_name
		) : '');
		$this->html_form_code .= (!empty($this->delivery_address->ci_serial) ? $this->addInput(
			'DELIVERY_CISERIAL',
			$this->delivery_address->ci_serial
		) : '');
		$this->html_form_code .= (!empty($this->delivery_address->ci_number) ? $this->addInput(
			'BILL_CINUMBER',
			$this->delivery_address->ci_number
		) : '');
		$this->html_form_code .= (!empty($this->delivery_address->cnp) ? $this->addInput(
			'DELIVERY_CNP',
			$this->delivery_address->cnp
		) : '');
		$this->html_form_code .= (!empty($this->delivery_address->company) ? $this->addInput(
			'DELIVERY_COMPANY',
			$this->delivery_address->company
		) : '');
		$this->html_form_code .= (!empty($this->delivery_address->fiscal_code) ? $this->addInput(
			'DELIVERY_FISCALCODE',
			$this->delivery_address->fiscal_code
		) : '');
		$this->html_form_code .= (!empty($this->delivery_address->reg_number) ? $this->addInput(
			'DELIVERY_REGNUMBER',
			$this->delivery_address->reg_number
		) : '');
		$this->html_form_code .= (!empty($this->delivery_address->bank) ? $this->addInput(
			'DELIVERY_BANK',
			$this->delivery_address->bank
		) : '');
		$this->html_form_code .= (!empty($this->delivery_address->bank_account) ? $this->addInput(
			'DELIVERY_BANKACCOUNT',
			$this->delivery_address->bank_account
		) : '');
		$this->html_form_code .= (!empty($this->delivery_address->email) ? $this->addInput(
			'DELIVERY_EMAIL',
			$this->delivery_address->email
		) : '');
		$this->html_form_code .= (!empty($this->delivery_address->phone) ? $this->addInput(
			'DELIVERY_PHONE',
			$this->delivery_address->phone
		) : '');
		$this->html_form_code .= (!empty($this->delivery_address->fax) ? $this->addInput(
			'DELIVERY_FAX',
			$this->delivery_address->fax
		) : '');
		$this->html_form_code .= (!empty($this->delivery_address->address) ? $this->addInput(
			'DELIVERY_ADDRESS',
			$this->delivery_address->address
		) : '');
		$this->html_form_code .= (!empty($this->delivery_address->address2) ? $this->addInput(
			'DELIVERY_ADDRESS2',
			$this->delivery_address->address2
		) : '');
		$this->html_form_code .= (!empty($this->delivery_address->zip_code) ? $this->addInput(
			'DELIVERY_ZIPCODE',
			$this->delivery_address->zip_code
		) : '');
		$this->html_form_code .= (!empty($this->delivery_address->city) ? $this->addInput(
			'DELIVERY_CITY',
			$this->delivery_address->city
		) : '');
		$this->html_form_code .= (!empty($this->delivery_address->state) ? $this->addInput(
			'DELIVERY_STATE',
			$this->delivery_address->state
		) : '');
		$this->html_form_code .= (!empty($this->delivery_address->country_code) ? $this->addInput(
			'DELIVERY_COUNTRYCODE',
			$this->delivery_address->country_code
		) : '');

		$this->html_form_code .= $this->addInput('DISCOUNT', $this->discount);
		$this->html_form_code .= $this->addInput('PAY_METHOD', $this->pay_method);

		foreach ($this->temp_prods as $prod_code => $product)
		{
			$this->html_form_code .= $this->addInput('ORDER_PNAME[]', $product['prodName']);
			$this->html_form_code .= $this->addInput('ORDER_PCODE[]', $prod_code);
			$this->html_form_code .= $this->addInput(
				'ORDER_PINFO[]',
				(empty($product['prodInfo']) ? '' : $product['prodInfo'])
			);
			$this->html_form_code .= $this->addInput('ORDER_PRICE[]', $product['prodPrice']);
			$this->html_form_code .= $this->addInput('ORDER_QTY[]', $product['prodQuantity']);
			$this->html_form_code .= $this->addInput('ORDER_VAT[]', $product['prodVat']);
			$this->html_form_code .= $this->addInput(
				'ORDER_PRICE_TYPE[]',
				$product['prodPriceType']
			);
		}

		$this->html_form_code .= $this->addInput('CUSTOM_PLUGIN', 'PRESTASHOP');
		$this->html_form_code .= $this->addInput('PRICES_CURRENCY', $this->price_currency);
		$this->html_form_code .= (!empty($this->currency) ? $this->addInput('CURRENCY', $this->currency) : '');
		$this->html_form_code .= (!empty($this->debug) ? $this->addInput('DEBUG', 'TRUE') : '');
		$this->html_form_code .= (!empty($this->test_mode) ? $this->addInput('TESTORDER', $this->test_mode) : '');
		$this->html_form_code .= (!empty($this->auto_mode) ? $this->addInput('AUTOMODE', '1') : '');
		$this->html_form_code .= (!empty($this->order_timeout) ? $this->addInput(
			'ORDER_TIMEOUT',
			$this->order_timeout
		) : '');
		$this->html_form_code .= (!empty($this->order_timeout_url) ? $this->addInput(
			'TIMEOUT_URL',
			$this->order_timeout_url
		) : '');

		return 1;
	}

	/**
	 * Method will generate the actual FORM
	 *
	 * @param boolean $auto_submit makes the form autosubmit
	 * @return int 1 on success
	 */
	private function makeForm($auto_submit = false)
	{
		$this->html_code .= '<form action="'.$this->lu_query_url.'" method="POST" id="payForm" name="payForm">'."\n";
		$this->html_code .= $this->html_form_code;
		if ($auto_submit === false)
			$this->html_code .= '<input type="submit" value="Submit Payment Form" class="exclusive_large"/>'."\n";

		$this->html_code .= '</form>';

		if ($auto_submit === true)
			$this->html_code .= '<script>document.payForm.submit();</script>';

		return 1;
	}

	/**
	 * Method will assemble the hash string
	 *
	 * @param string $type of returned text HTML/plain
	 * @return int 1 on success
	 */
	private function makeHashString($type = 'plain')
	{
		$this->hash_string = $this->addHashValue($this->merchant_id, 'MerchantId', $type);
		$this->hash_string .= $this->addHashValue($this->order_ref, 'OrderRef', $type);
		$this->hash_string .= $this->addHashValue($this->order_date, 'OrderDate', $type);

		$temp_prod = array();
		$temp_prods = array();

		foreach ($this->all_products as $product)
		{
			/** @var $product PayuProduct */
			$temp_prod['prodName'] = $product->product_name;
			$temp_prod['prodInfo'] = $product->product_info;
			$temp_prod['prodPrice'] = $product->product_price;
			$temp_prod['prodQuantity'] = $product->product_quantity;
			$temp_prod['prodVat'] = $product->product_vat;
			$temp_prod['prodPriceType'] = $product->product_price_type;
			$temp_prod['custom_fields'] = $product->custom_fields;

			if (!empty($temp_prods[$product->product_code]['prodQuantity']))
			{
				if ($temp_prods[$product->product_code]['prodPrice'] != $product->product_price)
				{
					$this->logError(
						'Found more entries with same product code: '.$product->product_code.' (product code must be unique) and different prices'
					);
					$temp_prods[$product->product_code] = $temp_prod;
				}
				else
				{
					$this->logError(
						'Found more entries with same product code: '.$product->product_code.', merged into 1',
						1
					);
					$temp_prods[$product->product_code]['prodQuantity'] += $product->product_quantity;
				}
			}
			else
				$temp_prods[$product->product_code] = $temp_prod;
		}

		$prod_names = '';
		$prod_info = '';
		$prod_price = '';
		$prod_quantity = '';
		$prod_vat = '';
		$prod_codes = '';
		$final_price_type = '';

		$iterator = 0;
		foreach ($temp_prods as $prod_code => $product)
		{
			$prod_names .= $this->addHashValue($product['prodName'], 'ProductName['.$iterator.']', $type);
			$prod_info .= $this->addHashValue(
				(empty($product['prodInfo']) ? '' : $product['prodInfo']),
				'ProductInfo['.$iterator.']',
				$type
			);
			$prod_price .= $this->addHashValue($product['prodPrice'], 'ProductPrice['.$iterator.']', $type);
			$prod_quantity .= $this->addHashValue($product['prodQuantity'], 'ProductQuality['.$iterator.']', $type);
			$prod_vat .= $this->addHashValue($product['prodVat'], 'ProductVat['.$iterator.']', $type);
			$prod_codes .= $this->addHashValue($prod_code, 'ProductCode['.$iterator.']', $type);
			$final_price_type .= $this->addHashValue(
				(empty($product['prodPriceType']) ? '' : $product['prodPriceType']),
				'ProductPriceType['.$iterator.']',
				$type
			);

			$iterator++;
		}

		$this->hash_string .= $prod_names;
		$this->hash_string .= $prod_codes;
		$this->hash_string .= $prod_info;
		$this->hash_string .= $prod_price;
		$this->hash_string .= $prod_quantity;
		$this->hash_string .= $prod_vat;

		$this->temp_prods = $temp_prods;
		$this->hash_string .= $this->addHashValue(
			($this->checkEmptyVar($this->order_shipping) ? '' : $this->order_shipping),
			'OrderShipping',
			$type
		);
		$this->hash_string .= $this->addHashValue(
			($this->checkEmptyVar($this->price_currency) ? '' : $this->price_currency),
			'PriceCurrency',
			$type
		);
		$this->hash_string .= $this->addHashValue((empty($this->discount) ? '' : $this->discount), 'Discount', $type);
		$this->hash_string .= $this->addHashValue(
			(empty($this->destination_address->city) ? '' : $this->destination_address->city),
			'DestinationCity',
			$type
		);
		$this->hash_string .= $this->addHashValue(
			(empty($this->destination_address->state) ? '' : $this->destination_address->state),
			'DestinationState',
			$type
		);
		$this->hash_string .= $this->addHashValue(
			(empty($this->destination_address->country_code) ? '' : $this->destination_address->country_code),
			'DestinationCountryCode',
			$type
		);

		$this->hash_string .= $this->addHashValue(
			(empty($this->pay_method) ? '' : $this->pay_method),
			'PayMethod',
			$type
		);
		$this->hash_string .= $final_price_type;

		$this->hash_string .= $this->addHashValue('PRESTASHOP', 'CUSTOM_PLUGIN');

		return $this->hash_string;
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
	private function makeHash()
	{
		$this->hash = PayuSignature::generateHmac($this->secret_key, $this->hash_string);
		return 1;
	}

	/**
	 * Sets the AUTOMODE for the LU query (the payment process will skip to the last step if all the data for BILLING and DELIVERY is ok)
	 *
	 * @return int 1 on success
	 */
	public function setAutoMode()
	{
		$this->auto_mode = 1;
		return 1;
	}

	/**
	 * Sets the TESTORDER for the LU query (Order will be processed as a test)
	 *
	 * @return int 1 on success
	 */
	public function setTestMode()
	{
		$this->test_mode = true;
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
		$this->discount = $discount;
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
		$this->language = $lang;
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
		$this->order_ref = $refno;
		return 1;
	}

	/**
	 * Sets the Order Date for the order this is your internal order number
	 *
	 * @return int 1 on success
	 */
	private function setOrderDate()
	{
		$this->order_date = date('Y-m-d H:i:s', time());
		return 1;
	}

	/**
	 * Sets the Pay Method for the order
	 *
	 * @param string $pay_method value Payment method (please refer to the static vars in this class)
	 * @return int 1 on success
	 */
	public function setPayMethod($pay_method)
	{
		$this->pay_method = $pay_method;
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
		$this->price_currency = $currency;
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
		$this->currency = $currency;
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
		$this->order_timeout = $timeout;
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
		$this->order_timeout_url = $url;
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
		if (!empty($this->hash_string))
		{
			if ($debug === true)
				return 'Hover on the substring for explanation:<br/><style>.puHidden{display:none;}.puInline{display: block;float: left;}</style>'
					.$this->makeHashString('HTML').' <script type="javascript"></script>';
		else
			return $this->hash_string;

		}
		else
		{
			$this->logError('Hash String not ready. Try renderPaymentForm or renderPaymentInputs first ', 1);
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
		$this->back_ref = $url;
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
		$this->order_shipping = $shipping;
		return 1;
	}

	/**
	 * Sets the DEBUG for the LU., this sends a debug request to the Payment page
	 *
	 * @return int 1 on success
	 */
	public function setTrace()
	{
		$this->debug = 1;
		return 1;
	}


	/**
	 * Method will do a last minute validation of the object params
	 *
	 * @return string contains all errors
	 */
	private function validate()
	{
		if (!empty($this->discount) && ($this->discount < 0 || !is_numeric($this->discount)))
			$this->logError('Discount must be a positive number');

		if (!empty($this->pay_method) &&
			($this->pay_method != self::PAY_METHOD_CCVISAMC &&
				$this->pay_method != self::PAY_METHOD_CCAMEX &&
				$this->pay_method != self::PAY_METHOD_CCDINERS &&
				$this->pay_method != self::PAY_METHOD_CCJCB &&
				$this->pay_method != self::PAY_METHOD_WIRE &&
				$this->pay_method != self::PAY_METHOD_PAYPAL &&
				$this->pay_method != self::PAY_METHOD_CASH))
		{
			$this->logError('Payment Method: '.$this->pay_method.' is not supported reverted to none', 1);
			$this->pay_method = '';
		}
		$this->mergeErrorLogs($this->error_log);
		return $this->error_log;
	}

	/**
	 * Sets the debug mode for the class
	 *
	 * @param int debug level constants should be used
	 * @return int 1 on success
	 */
	public function setDebug($debug_level)
	{
		$this->debug_level = $debug_level;
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
		$this->lu_query_url = $url;
		return 1;
	}

	/*
	 * Utility used by the assemble function
	 *
	 * @param string string to be added to the hash
	 * @param string name of the string
	 * @param string type of returned text HTML/plain
	 */

	private function addHashValue($string, $name = '', $type = null)
	{
		if ($this->checkEmptyVar($string))
		{
			$return_html_value = '<div class="puInline" onmouseover="document.getElementById(\''.md5(
					$name
				).'\').innerHTML=\''.$name.'\';document.getElementById(\''.md5(
					$name
				).'\').style.display=\'block\';this.style.border=\'1px solid\'" onmouseout="document.getElementById(\''.md5(
					$name
				).'\').style.display=\'none\';this.style.border=\'0\'"><b style="color:red">0</b><strong id="'.md5(
					$name
				).'" class="puHidden"></strong></div>';
			$return_value = '0';
		}
		else
		{
			$return_html_value = '<div class="puInline" onmouseover="document.getElementById(\''.md5(
					$name
				).'\').innerHTML=\''.$name.'\';document.getElementById(\''.md5(
					$name
				).'\').style.display=\'block\';this.style.border=\'1px solid\'" onmouseout="document.getElementById(\''.md5(
					$name
				).'\').style.display=\'none\';this.style.border=\'0\'"><b style="color:red">'.strlen(
					$string
				).'</b>'.$string.'<strong id="'.md5($name).'" class="puHidden"></strong></div>';
			$return_value = strlen($string).$string;
		}

		if ($type == 'HTML')
			return $return_html_value;
		else
			return $return_value;
	}

	/*
	 * Add the input html code
	 *
	 * @param string name of the input
	 * @param string value of the input
	 */

	private function addInput($string, $value)
	{
		return '<input type="hidden" name="'.strtoupper($string).'" value="'.htmlentities(
			$value,
			ENT_COMPAT,
			'UTF-8'
		).'" />'."\n";
	}

}

