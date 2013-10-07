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

	protected $_debugLevel = 0;
	protected $_errorLog = '';
	protected $_allErrors = array();

	/**
	 * Log the errors according to the class
	 *
	 * @return int 1 on success
	 */
	protected function _logError($errorString, $level = self::DEBUG_ERROR)
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

		if (empty($this->_errorLog[$level]))
			$this->_errorLog[$level] = '';

		$this->_errorLog[$level] .= $debug_text.' '.__CLASS__.': '.$errorString.'<br/>';
		return 1;
	}

	/**
	 * Method will merge all the error logs
	 *
	 * @param array $newLog this is the new log to be added to the main list of errors
	 * @return 1 on success
	 */
	protected function _mergeErrorLogs($newLog)
	{
		if (count($newLog))
		{ // if there are errors and the debug is set
			if (empty($this->_allErrors[$this->_debugLevel])) // if the entry is not set the set it to a default
				$this->_allErrors[$this->_debugLevel] = '';

			if (!empty($newLog[self::DEBUG_WARNING]) && count($newLog[self::DEBUG_WARNING]) > 0)
				$this->_allErrors[self::DEBUG_ALL] .= $newLog[self::DEBUG_WARNING];

			if (!empty($newLog[self::DEBUG_ERROR]) && count($newLog[self::DEBUG_ERROR]) > 0)
				$this->_allErrors[self::DEBUG_ALL] .= $newLog[self::DEBUG_ERROR];

			if (!empty($newLog[self::DEBUG_FATAL]) && count($newLog[self::DEBUG_FATAL]) > 0)
				$this->_allErrors[self::DEBUG_ALL] .= $newLog[self::DEBUG_FATAL];
		}
		return 1;
	}

}
