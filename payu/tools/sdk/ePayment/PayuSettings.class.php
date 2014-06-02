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

abstract class PayuSettings
{
	const DEBUG_NONE = '0';
	const DEBUG_ALL = '9999';
	const DEBUG_FATAL = '9990';
	const DEBUG_ERROR = '2';
	const DEBUG_WARNING = '1';

	const PAY_METHOD_CCVISAMC = 'CCVISAMC';
	const PAY_METHOD_CCAMEX = 'CCAMEX';
	const PAY_METHOD_CCDINERS = 'CCDINERS';
	const PAY_METHOD_CCJCB = 'CCJCB';
	const PAY_METHOD_WIRE = 'WIRE';
	const PAY_METHOD_PAYPAL = 'PAYPAL';
	const PAY_METHOD_CASH = 'CASH';

	const PRICE_TYPE_GROSS = 'GROSS'; /* price includes VAT */
	const PRICE_TYPE_NET = 'NET'; /* price does not include VAT */

	const PAY_OPTION_VISA = 'VISA';
	const PAY_OPTION_MASTERCARD = 'MASTERCARD';
	const PAY_OPTION_MAESTRO = 'MAESTRO';
	const PAY_OPTION_VISA_ELECTRON = 'VISA ELECTRON';
	const PAY_OPTION_ALL = 'ALL';

	protected $debug_level = 0;

	protected $error_log = array(
		self::DEBUG_WARNING => array(),
		self::DEBUG_ERROR => array(),
		self::DEBUG_FATAL => array(),
		self::DEBUG_ALL => array(),
	);

	protected $all_errors = array(
		self::DEBUG_WARNING => array(),
		self::DEBUG_ERROR => array(),
		self::DEBUG_FATAL => array(),
		self::DEBUG_ALL => array(),
	);

	/**
	 * Log the errors according to the class
	 *
	 * @return int 1 on success
	 */
	protected function logError($error_string, $level = self::DEBUG_ERROR)
	{
		switch ($level)
		{
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
		return 1;
	}

	/**
	 * Method will merge all the error logs
	 *
	 * @param array $new_log this is the new log to be added to the main list of errors
	 * @return int 1 on success
	 */
	protected function mergeErrorLogs($new_log)
	{
		if (count($new_log))
		{ // if there are errors and the debug is set
			if (empty($this->all_errors[$this->debug_level])) // if the entry is not set the set it to a default
				$this->all_errors[$this->debug_level] = '';

			if (!empty($new_log[self::DEBUG_WARNING]) && count($new_log[self::DEBUG_WARNING]) > 0)
				$this->all_errors[self::DEBUG_ALL][] = $new_log[self::DEBUG_WARNING];

			if (!empty($new_log[self::DEBUG_ERROR]) && count($new_log[self::DEBUG_ERROR]) > 0)
				$this->all_errors[self::DEBUG_ALL][] = $new_log[self::DEBUG_ERROR];

			if (!empty($new_log[self::DEBUG_FATAL]) && count($new_log[self::DEBUG_FATAL]) > 0)
				$this->all_errors[self::DEBUG_ALL][] = $new_log[self::DEBUG_FATAL];
		}
		return 1;
	}

}
