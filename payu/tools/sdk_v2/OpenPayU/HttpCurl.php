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

class OpenPayUHttpCurl implements OpenPayUHttpProtocol
{
	/**
	 * @var
	 */
	static $headers;

	/**
	 * @param $request_type
	 * @param $path_url
	 * @param $data
	 * @param $signature
	 * @return mixed
	 * @throws OpenPayUExceptionConfiguration
	 * @throws OpenPayUExceptionNetwork
	 * @throws OpenPayUExceptionAuthorization
	 */
	public static function doRequest($request_type, $path_url, $data, $pos_id, $signature_key)
	{
		if (empty($path_url))
			throw new OpenPayUExceptionConfiguration('The end point is empty');

		if (empty($pos_id))
			throw new OpenPayUExceptionConfiguration('PosId is empty');

		if (empty($signature_key))
			throw new OpenPayUExceptionConfiguration('SignatureKey is empty');

		$user_name_and_password = $pos_id.':'.$signature_key;

		$header = array();

		if (OpenPayUConfiguration::getApiVersion() >= 2)
		{
			$header[] = 'Content-Type:application/json';
			$header[] = 'Accept:application/json';
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $path_url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_type);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, 'OpenPayUHttpCurl::readHeader');
		curl_setopt($ch, CURLOPT_POSTFIELDS, (OpenPayUConfiguration::getApiVersion() < 2) ? 'DOCUMENT='.urlencode($data) : $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSLVERSION, 3);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $user_name_and_password);

		$response = curl_exec($ch);
		$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if ($response === false)
			throw new OpenPayUExceptionNetwork(curl_error($ch));

		curl_close($ch);

		return array('code' => $http_status, 'response' => trim($response));
	}

	/**
	 * @param $headers
	 *
	 * @return mixed
	 */
	public static function getSignature($headers)
	{
		foreach ($headers as $name => $value)
		{
			if (preg_match('/X-OpenPayU-Signature/i', $name))
				return $value;
		}
	}

	/**
	 * @param $ch
	 * @param $header
	 * @return int
	 */
	public static function readHeader($ch, $header)
	{
		is_string($ch);

		if (preg_match('/([^:]+): (.+)/m', $header, $match))
			self::$headers[$match[1]] = trim($match[2]);

		return Tools::strlen($header);
	}

	/**
	 * @param  $headers
	 */
	public static function setHeaders($headers)
	{
		self::$headers = $headers;
	}

	/**
	 * @return mixed
	 */
	public static function getHeader()
	{
		return self::$headers;
	}
}
