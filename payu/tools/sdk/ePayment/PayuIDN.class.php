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

class PayuIDN
{
	const DEBUG_NONE = '0';
	const DEBUG_ALL = '9999';
	const DEBUG_FATAL = '9990';
	const DEBUG_ERROR = '2';
	const DEBUG_WARNING = '1';

	/** @var int */
	private $debug_level = 0;

	/** @var string */
	private $error_log = '';

	/** @var array */
	private $all_errors = array();

	/** @var string */
	private $idn_query_url = '';

	/** @var string */
	private $payu_ref_no;

	/** @var float */
	private $order_amount;

	/** @var string */
	private $order_currency;

	/** @var float */
	private $order_charge_amount;

	/** @var string */
	private $order_idn_prn;

	/**
	 * @param $merchant_id Merchant Id that will be used for the IDN request
	 * @param $secret_key Secret Key that will be used for the IDN request
	 * @return int 1 on success
	 */
	public function __construct($merchant_id, $secret_key)
	{
		$this->merchant_id = $merchant_id; // store the merchant id
		$this->secret_key = $secret_key; // store the secretkey

		if (!in_array('curl', get_loaded_extensions())) // check if curl is installed and die if not
			die('<h1>Curl Extension is needed for Payu Server interaction</h1>');

		return 1;
	}

	/**
	 * Sets the query url for the IDN request
	 *
	 * @param string $url url to be used to the query
	 * @return int 1 on success
	 */
	public function setQueryUrl($url)
	{
		$this->idn_query_url = $url;
		return true;
	}

	/**
	 * @param int
	 * @return int 1 on success
	 */
	public function setPayuReference($payu_ref_no)
	{
		$this->payu_ref_no = $payu_ref_no;
		return true;
	}

	/**
	 * @param $order_amount
	 * @return bool
	 */
	public function setOrderAmount($order_amount)
	{
		$this->order_amount = $order_amount;
		return true;
	}

	/**
	 * @param $order_currency
	 * @return bool
	 */
	public function setOrderCurrency($order_currency)
	{
		$this->order_currency = $order_currency;
		return true;
	}

	/**
	 * @param $charge_amount
	 * @return int
	 */
	public function setChargeAmount($charge_amount)
	{
		$this->order_charge_amount = $charge_amount;
		return 1;
	}

	/**
	 * @param $idn_prn
	 * @return int
	 */
	public function setIdnPrn($idn_prn)
	{
		$this->order_idn_prn = $idn_prn;
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

	/**
	 * @param $string
	 * @return bool
	 */
	private function checkEmptyVar($string)
	{
		return Tools::strlen(trim($string)) == 0;
	}

	/**
	 * Process the IDN data
	 *
	 * @return TRUE on success
	 */
	public function processRequest()
	{
		$return_array = array();

		$this->validate();

		if (!isset($this->all_errors[self::DEBUG_FATAL]) || !$this->all_errors[self::DEBUG_FATAL])
		{
			$process_string = Tools::strlen($this->merchant_id).$this->merchant_id;
			$process_string .= Tools::strlen($this->payu_ref_no).$this->payu_ref_no;
			$process_string .= Tools::strlen($this->order_amount).$this->order_amount;
			$process_string .= Tools::strlen($this->order_currency).$this->order_currency;
			$idn_date = date('Y-m-d H:i:s', time());
			$process_string .= Tools::strlen($idn_date).$idn_date;

			if (!empty($this->order_charge_amount))
				$process_string .= Tools::strlen($this->order_charge_amount).$this->order_charge_amount;

			if (!empty($this->order_idn_prn))
				$process_string .= Tools::strlen($this->order_idn_prn).$this->order_idn_prn;

			$hash = PayuSignature::generateHmac($this->secret_key, $process_string);

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_URL, $this->idn_query_url);
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt(
				$curl,
				CURLOPT_POSTFIELDS,
				'MERCHANT='.$this->merchant_id.'&ORDER_REF='.$this->payu_ref_no.'&ORDER_AMOUNT='.$this->order_amount
				.'&ORDER_CURRENCY='.$this->order_currency.'&IDN_DATE='.$idn_date.'&'.((!empty($this->order_charge_amount)) ?
					'CHARGE_AMOUNT='.$this->order_charge_amount.'&' : '').((!empty($this->order_idn_prn)) ?
					'IDN_PRN='.$this->order_idn_prn.'&' : '').'ORDER_HASH='.$hash);

			curl_setopt($curl, CURLOPT_COOKIEJAR, 'cookie.txt');
			$contents = curl_exec($curl);
			curl_close($curl);
			$contents = explode('|', $contents);

			$return_array['RESPONSE_CODE'] = $contents[1];
			$return_array['RESPONSE_MSG'] = $contents[2];

			return $return_array;
		}
		else
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
		else
		{
			echo '<pre>['.date('d-m-y H:s')."]\n<br/>";
			print_r($entry);
			echo '</pre>';
		}
	}

	/**
	 * Sets the debug mode for the class
	 *
	 * @param int
	 * @return TRUE on success
	 */
	public function setDebug($debug_level)
	{
		$this->debug_level = $debug_level;
		return true;
	}

	/**
	 * Log the errors according to the class
	 *
	 * @param $error_string
	 * @param string $level
	 * @return bool true on success
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
		}

		if (empty($this->error_log[$level]))
			$this->error_log[$level] = '';

		$this->error_log[$level] .= $debug_text.' '.__CLASS__.': '.$error_string.'<br/>';
		return true;
	}

}
