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

class PayuSignature
{
	/**
	 * Utility for calculation of Hmac sinatures
	 *
	 * @param string key to be used for the hmac
	 * @param string data to be encoded
	 * @return string signed data
	 */
	public static function generateHmac($key, $data)
	{
		$b = 64; // byte length for md5
		if (strlen($key) > $b)
			$key = pack('H*', md5($key));

		$key = str_pad($key, $b, chr(0x00));
		$ipad = str_pad('', $b, chr(0x36));
		$opad = str_pad('', $b, chr(0x5c));
		$k_ipad = $key ^ $ipad;
		$k_opad = $key ^ $opad;
		return md5($k_opad.pack('H*', md5($k_ipad.$data)));
	}

	/**
	 * @param $data
	 * @param array $skip
	 * @return string
	 */
	public static function signatureString(Array $data, $skip = array())
	{
		self::removeExtraData($data, $skip);

		$str = '';
		foreach ($data as $v)
			$str .= self::convertData($v);

		return $str;
	}

	/**
	 * @param $val
	 * @return string
	 */
	protected static function convertData($val)
	{
		return is_array($val) ? self::convertArray($val) : self::convertString($val);
	}

	/**
	 * @param $array
	 * @return string
	 */
	protected static function convertArray(Array $array)
	{
		$return = '';
		foreach ($array as $v)
			if (is_array($v))
				$return .= self::convertArray($v);
			else
				$return .= self::convertString($v);

		return $return;
	}

	/**
	 * @param $string
	 * @return string
	 */
	protected static function convertString($string)
	{
		return mb_strlen($string, '8bit').$string;
	}

	/**
	 * @param $data
	 * @param $skip
	 */
	public static function removeExtraData(Array &$data, $skip)
	{
		foreach ($data as $k => &$v)
			if (in_array((string)$k, $skip))
				unset($data[$k]);
			elseif (is_array($v))
				self::removeExtraData($v, $skip);

	}

	/**
	 * @param $server
	 * @param $key
	 * @return bool
	 */
	public static function validateSignature(Array $server, $key)
	{
		parse_str($server['QUERY_STRING'], $params);

		if (!isset($params['ctrl']) || empty($key))
			return false;

		$string_signature = $params['ctrl'];

		unset($params['ctrl']);

		$query_string = http_build_query($params);
		$query_string = str_replace('+', '%20', $query_string);

		$url = '';

		if (!isset($server['HTTPS']) || $server['HTTPS'] != 'on')
			$url .= 'http://';
		else
			$url .= 'https://';

		$url .= $server['SERVER_NAME'];

		if ($server['SERVER_PORT'] != '80')
			$url .= ':'.$server['SERVER_PORT'];

		$url .= $server['PHP_SELF'];
		$url .= '?'.$query_string;
		$url = strlen($url).$url;

		$check_signature = self::generateHmac($key, $url);

		if ($check_signature !== $string_signature)
			return false;

		return true;
	}
}
