<?php

/**
 * @copyright  Copyright (c) 2011-2012 PayU
 * @license	http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * OpenPayU Standard Library
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
 */

class OpenPayUConfiguration
{
	public static $env = 'sandbox';
	public static $merchant_pos_id = '';
	public static $pos_auth_key = '';
	public static $client_id = '';
	public static $client_secret = '';
	public static $signature_key = '';

	public static $service_url = '';
	public static $summary_url = '';
	public static $auth_url = '';
	public static $service_domain = '';

	/**
	 * @access public
	 * @param string $value
	 * @param string $domain
	 * @param string $country
	 * @throws Exception
	 */
	public static function setEnvironment($value = 'sandbox', $domain = 'payu.pl', $country = 'pl')
	{
		$value = Tools::strtolower($value);
		$domain = Tools::strtolower($domain);
		$country = Tools::strtolower($country);

		if ($value == 'sandbox' || $value == 'secure')
		{
			self::$env = $value;
			self::$service_domain = $domain;

			self::$service_url = 'https://'.$value.'.'.$domain.'/'.$country.'/standard/';
			self::$summary_url = self::$service_url.'co/summary';
			self::$auth_url = self::$service_url.'oauth/user/authorize';
		}
		else if ($value == 'custom')
		{
			self::$env = $value;

			self::$service_url = $domain.'/'.$country.'/standard/';
			self::$summary_url = self::$service_url.'co/summary';
			self::$auth_url = self::$service_url.'oauth/user/authorize';
		}
		else
			throw new Exception('Invalid value:'.$value.' for environment. Proper values are: "sandbox" or "secure".');
	}

	/**
	 * @access public
	 * @return string
	 */
	public static function getServiceUrl()
	{
		return self::$service_url;
	}

	/**
	 * @access public
	 * @return string
	 */
	public static function getSummaryUrl()
	{
		return self::$summary_url;
	}

	/**
	 * @access public
	 * @return string
	 */
	public static function getAuthUrl()
	{
		return self::$auth_url;
	}

	/**
	 * @access public
	 * @return string
	 */
	public static function getEnvironment()
	{
		return self::$env;
	}

	/**
	 * @access public
	 * @param string
	 */
	public static function setMerchantPosid($value)
	{
		self::$merchant_pos_id = trim($value);
	}

	/**
	 * @access public
	 * @return string
	 */
	public static function getMerchantPosid()
	{
		return self::$merchant_pos_id;
	}

	/**
	 * @access public
	 * @param string
	 */
	public static function setPosAuthkey($value)
	{
		self::$pos_auth_key = trim($value);
	}

	/**
	 * @access public
	 * @return string
	 */
	public static function getPosAuthkey()
	{
		return self::$pos_auth_key;
	}

	/**
	 * @access public
	 * @param string
	 */
	public static function setClientId($value)
	{
		self::$client_id = trim($value);
	}

	/**
	 * @access public
	 * @return string
	 */
	public static function getClientId()
	{
		return self::$client_id;
	}

	/**
	 * @access public
	 * @param string
	 */
	public static function setClientSecret($value)
	{
		self::$client_secret = trim($value);
	}

	/**
	 * @access public
	 * @return string
	 */
	public static function getClientSecret()
	{
		return self::$client_secret;
	}

	/**
	 * @access public
	 * @param string
	 */
	public static function setSignatureKey($value)
	{
		self::$signature_key = trim($value);
	}

	/**
	 * @access public
	 * @return string
	 */
	public static function getSignatureKey()
	{
		return self::$signature_key;
	}

}
