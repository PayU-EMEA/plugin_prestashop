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


class PayuIRN
{
	const DEBUG_NONE = '0';
	const DEBUG_ALL = '9999';
	const DEBUG_FATAL = '9990';
	const DEBUG_ERROR = '2';
	const DEBUG_WARNING = '1';

	private $debug_level = 0;
	private $error_log = '';
	private $all_errors = array();
	private $irn_query_url = 'https://secure.payu.ro/order/irn.php';
	private $payu_ref_no;
	private $order_amount;
	private $refund_amount;
	private $amount;
	private $order_currency;
	private $products = array();
	private $product_ids = array();
	private $product_qty = array();
	private $product_string = '';
	private $hash_product_ids = '';
	private $hash_product_qtys = '';

	/**
	 * PayuLu Constructor
	 *
	 * @param string $merchant_id Merchant Id that will be used for the IRN request
	 * @param string $secret_key Secret Key that will be used for the IRN request
	 */
	public function __construct($merchant_id, $secret_key)
	{
		$this->_merchant_id = $merchant_id; // store the merchant id
		$this->_secret_key = $secret_key; // store the secretkey

		if (!in_array('curl', get_loaded_extensions()))  // check if curl is installed and die if not
			die('<h1>Curl Extension is needed for Payu Server interaction</h1>');

		return 1;
	}

	/**
	 * Sets the query url for the IRN request
	 *
	 * @param string $url url to be used to the query
	 * @return int 1 on success
	 */
	public function setQueryUrl($url)
	{
		$this->irn_query_url = $url;
		return 1;
	}

	/**
	 * Sets the query url for the IRN request
	 *
	 * @param string $payu_ref_no url to be used to the query
	 * @return int 1 on success
	 */
	public function setPayuReference($payu_ref_no)
	{
		$this->payu_ref_no = $payu_ref_no;
		return 1;
	}

	/**
	 * Sets the order amount for the IRN request
	 *
	 * @param float $order_amount url to be used to the query
	 * @return int 1 on success
	 */
	public function setOrderAmount($order_amount)
	{
		$this->order_amount = $order_amount;
		return 1;
	}

	/**
	 * Sets the amount to be refunded
	 *
	 * @param float $amount
	 * @return int 1 on success
	 */
	public function setRefundAmount($amount)
	{
		$this->refund_amount = $amount;
		return 1;
	}

	/**
	 * Sets the query url for the irn request
	 *
	 * @param string $order_currency currency to be used to the query
	 * @return int 1 on success
	 */
	public function setOrderCurrency($order_currency)
	{
		$this->order_currency = $order_currency;
		return 1;
	}

	/**
	 * Adds Products to the IRN process
	 *
	 * @param string $product_id the id of the product to be refunded
	 * @param string $product_qty the quantity of the product to be refunded
	 * @return int 1 on success
	 */
	public function addProduct($product_id, $product_qty)
	{
		if ($product_id && $product_qty)
		{
			$this->product_string .= 'PRODUCTS_IDS[]='.$product_id.'&';
			$this->product_string .= 'PRODUCTS_QTY[]='.$product_qty.'';

			$this->hash_product_ids .= strlen($product_id).$product_id;
			$this->hash_product_qtys .= strlen($product_qty).$product_qty;
		} else {
			if (!$product_id)
				$this->logError('Product id is missing, product will be ignored', self::DEBUG_ERROR);
			if (!$product_qty)
				$this->logError('Product quantity is missing, product will be ignored', self::DEBUG_ERROR);
		}
		return 1;
	}

	private function validate()
	{
		if ($this->checkEmptyVar($this->payu_ref_no))
			$this->logError('Payu reference number is missing', self::DEBUG_FATAL);

		if ($this->checkEmptyVar($this->order_amount))
			$this->logError('Order ammount is missing', self::DEBUG_FATAL);

		if ($this->checkEmptyVar($this->order_currency))
			$this->logError('Order currency is missing', self::DEBUG_FATAL);
	}

	private function checkEmptyVar($string)
	{
		return (strlen(trim($string)) == 0);
	}

	/**
	 * Process the IRN data
	 *
	 */
	public function processRequest()
	{
		$return_array = array();
		$this->validate();
		$this->mergeErrorLogs($this->error_log);
		if (!$this->error_log[self::DEBUG_FATAL])
		{
			$process_string = strlen($this->_merchant_id).$this->_merchant_id;
			$process_string .= strlen($this->payu_ref_no).$this->payu_ref_no;
			$process_string .= strlen($this->order_amount).$this->order_amount;
			$process_string .= strlen($this->order_currency).$this->order_currency;
			$irn_date = date('Y-m-d H:i:s', time());
			$process_string .= strlen($irn_date).$irn_date;
			$process_string .= $this->hash_product_ids;
			$process_string .= $this->hash_product_qtys;
			$process_string .= '00';
			$process_string .= strlen($this->refund_amount).$this->refund_amount;

			$hash = PayuSignature::generateHmac($this->_secret_key, $process_string);
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_URL, $this->irn_query_url);
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
			$value = 'MERCHANT='.$this->_merchant_id.
				'&ORDER_REF='.$this->payu_ref_no.
				'&ORDER_AMOUNT='.$this->order_amount.
				'&ORDER_CURRENCY='.$this->order_currency.
				'&IRN_DATE='.$irn_date.
				'&ORDER_HASH='.$hash.
				'&REGENERATE_CODES=&LICENSE_HANDLING=&AMOUNT='.$this->refund_amount.'&'.$this->product_string;
			curl_setopt(
				$curl,
				CURLOPT_POSTFIELDS,
				$value
			);

			curl_setopt($curl, CURLOPT_COOKIEJAR, 'cookie.txt');
			$contents = curl_exec($curl);
			curl_close($curl);

			if (strpos($contents, '|') !== false)
			{
				$contents = explode('|', $contents);
				$return_array['RESPONSE_CODE'] = $contents[1];
				$return_array['RESPONSE_MSG'] = $contents[2];
			} else
				$return_array['RESPONSE'] = $contents;

			$return_array['ERRORS'] = $this->all_errors[self::DEBUG_ALL];

			return $return_array;
		} else
			return $this->all_errors[self::DEBUG_ALL];

	}

	/*
	 * Utility Class for output debug purposes
	 *
	 * @param  $entry the entry to be printed on screen
	 */

	public static function debug($entry)
	{
		if (!is_array($entry))
			echo '['.date('d-m-y H:s').'] '.$entry."\n<br/>";
		else {
			echo '<pre>['.date('d-m-y H:s')."]\n<br/>";
			print_r($entry);
			echo '</pre>';
		}
	}

	/**
	 * Sets the debug mode for the class
	 *
	 * @param $debug_level
	 * @return bool TRUE on success
	 */
	public function setDebug($debug_level)
	{
		$this->debug_level = $debug_level;
		return true;
	}

	/**
	 * Method will merge all the error logs
	 *
	 * @param array $new_log this is the new log to be added to the main list of errors
	 * @return int 1 on success
	 */
	private function mergeErrorLogs($new_log)
	{
		if (count($new_log))
		{ // if there are errors and the debug is set
			if (empty($this->all_errors[$this->debug_level])) // if the entry is not set the set it to a default
				$this->all_errors[$this->debug_level] = '';

			if (!empty($new_log[self::DEBUG_WARNING]) && count($new_log[self::DEBUG_WARNING]) > 0)
				$this->all_errors[self::DEBUG_ALL] .= $new_log[self::DEBUG_WARNING];

			if (!empty($new_log[self::DEBUG_ERROR]) && count($new_log[self::DEBUG_ERROR]) > 0)
				$this->all_errors[self::DEBUG_ALL] .= $new_log[self::DEBUG_ERROR];

			if (!empty($new_log[self::DEBUG_FATAL]) && count($new_log[self::DEBUG_FATAL]) > 0)
				$this->all_errors[self::DEBUG_ALL] .= $new_log[self::DEBUG_FATAL];
		}
		return 1;
	}

	/**
	 * Log the errors according to the class
	 *
	 * @param string $error_string this is the new string to be added to the error log
	 * @param int|string $level this is the level of the error
	 * @return int 1 on success
	 */
	private function logError($error_string, $level = self::DEBUG_ERROR)
	{
		switch ($level)
		{
			case self::DEBUG_FATAL:
				$debug_text = 'FATAL ERROR in';
				break;
			case self::DEBUG_ERROR:
				$debug_text = 'ERROR in';
				break;
			case self::DEBUG_WARNING:
				$debug_text = 'WARNING in';
				break;
			default:
				$debug_text = '';
				break;
		}

		if (empty($this->error_log[$level]))
			$this->error_log[$level] = '';

		$this->error_log[$level] .= $debug_text.' '.__CLASS__.': '.$error_string.'<br/>';
		return 1;
	}
}
