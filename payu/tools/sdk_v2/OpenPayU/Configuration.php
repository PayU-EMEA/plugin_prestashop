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

class OpenPayUConfiguration
{
	private static $available_environment = array('custom', 'secure');
	public static $env = 'secure';
	public static $merchant_pos_id = '';
	public static $pos_auth_key = '';
	public static $client_id = '';
	public static $client_secret = '';
	public static $signature_key = '';

	public static $service_url = '';
	public static $summary_url = '';
	public static $auth_url = '';
	public static $service_domain = '';

	private static $api_version = 2;
	private static $available_hash_algorithm = array('MD5', 'SHA', 'SHA1', 'SHA-1', 'SHA-256', 'SHA256', 'SHA_256');
	private static $hash_algorithm = 'SHA-1';

	private static $data_format = 'json';

	private static $sender = 'Generic';

	const COMPOSER_JSON = '/composer.json';
	const DEFAULT_SDK_VERSION = 'PHP SDK 2.0.X';


	/**
	 * @access public
	 * @param int $version
	 * @throws OpenPayUExceptionConfiguration
	 */
	public static function setApiVersion($version)
	{
		if (empty($version))
			throw new OpenPayUExceptionConfiguration('Invalid API version');

		self::$api_version = (int)$version;
	}

	/**
	 * @return int
	 */
	public static function getApiVersion()
	{
		return self::$api_version;
	}

	/**
	 * @access public
	 * @param string
	 * @throws OpenPayUExceptionConfiguration
	 */
	public static function setHashAlgorithm($value)
	{
		if (!in_array($value, self::$available_hash_algorithm))
			throw new OpenPayUExceptionConfiguration($value.' - is not available');

		self::$hash_algorithm = $value;
	}

	/**
	 * @access public
	 * @return string
	 */
	public static function getHashAlgorithm()
	{
		return self::$hash_algorithm;
	}

	/**
	 * @access public
	 * @param string $value
	 * @param string $domain
	 * @param string $country
	 * @throws OpenPayUExceptionConfiguration
	 */
	public static function setEnvironment($value = 'secure', $domain = 'payu.pl', $country = 'pl')
	{
		$value = Tools::strtolower($value);
		$domain = Tools::strtolower($domain).'/';
		$country = Tools::strtolower($country).'/';
		$service = 'standard/';

		if (!in_array($value, self::$available_environment))
			throw new OpenPayUExceptionConfiguration($value.' - is not valid environment');

		if (self::getApiVersion() >= 2)
		{
			$country = 'api/';
			$service = 'v2/';
		}

		if ($value == 'secure')
		{
			self::$env = $value;

			if (self::getApiVersion() >= 2)
				$domain = 'payu.com/';

			self::$service_domain = $domain;

			self::$service_url = 'https://'.$value.'.'.$domain.$country.$service;
			self::$summary_url = self::$service_url.'co/summary';
			self::$auth_url = self::$service_url.'oauth/user/authorize';
		}
		else if ($value == 'custom')
		{
			self::$env = $value;

			self::$service_url = $domain.$country.$service;
			self::$summary_url = self::$service_url.'co/summary';
			self::$auth_url = self::$service_url.'oauth/user/authorize';
		}
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
	public static function setMerchantPosId($value)
	{
		self::$merchant_pos_id = trim($value);
	}

	/**
	 * @access public
	 * @return string
	 */
	public static function getMerchantPosId()
	{
		return self::$merchant_pos_id;
	}

	/**
	 * @access public
	 * @param string
	 */
	public static function setPosAuthKey($value)
	{
		self::$pos_auth_key = trim($value);
	}

	/**
	 * @access public
	 * @return string
	 */
	public static function getPosAuthKey()
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

	/**
	 * @access public
	 * @return string
	 */
	public static function getDataFormat($with_dot = false)
	{
		if ($with_dot)
			return '.'.self::$data_format;

		return self::$data_format;
	}

	/**
	 * @access public
	 * @param string $sender
	 */
	public static function setSender($sender)
	{
		self::$sender = $sender;
	}

	/**
	 * @return string
	 */
	public static function getSender()
	{
		return self::$sender;
	}

	/**
	 * @return string
	 */
	public static function getFullSenderName()
	{
		return sprintf('%s@%s', self::getSender(), self::getSdkVersion());
	}

	/**
	 * @return string
	 */
	public static function getSdkVersion()
	{
		$composer_file_path = self::getComposerFilePath();
		if (file_exists($composer_file_path))
		{
			$file_content = Tools::file_get_contents($composer_file_path);
			$composer_data = Tools::jsonDecode($file_content);
			if (isset($composer_data->version) && isset($composer_data->extra[0]->engine))
				return sprintf('%s %s', $composer_data->extra[0]->engine, $composer_data->version);
		}
		return self::DEFAULT_SDK_VERSION;
	}

	/**
	 * @return string
	 */
	private static function getComposerFilePath()
	{
		return '../../'.self::COMPOSER_JSON;
	}
}