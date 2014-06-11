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

class OpenPayUNetwork
{
	/** @var string OpenPayU EndPoint Url */
	protected static $open_payu_end_point_url = '';

	/**
	 * The function sets EndPointUrl param of OpenPayU
	 * @access public
	 * @param string $ep
	 */
	public static function setOpenPayuEndPoint($ep)
	{
		self::$open_payu_end_point_url = $ep;
	}

	/**
	 * This function checks the availability of cURL
	 * @access private
	 * @return bool
	 */
	private static function isCurlInstalled()
	{
		if (in_array('curl', get_loaded_extensions()))
			return true;

		return false;
	}

	/**
	 * The function returns the parameter EndPointUrl OpenPayU
	 * @access public
	 * @return string
	 * @throws Exception
	 */
	public static function getOpenPayuEndPoint()
	{
		if (empty(self::$open_payu_end_point_url))
			throw new Exception('OpenPayUNetwork::$open_payu_end_point_url is empty');

		return self::$open_payu_end_point_url;
	}

	/**
	 * This function sends data to the EndPointUrl OpenPayU
	 * @access public
	 * @param string $doc
	 * @return string
	 * @throws Exception
	 */
	public static function sendOpenPayuDocument($doc)
	{
		if (empty(self::$open_payu_end_point_url))
			throw new Exception('OpenPayUNetwork::$open_payu_end_point_url is empty');

		if (!self::isCurlInstalled())
			throw new Exception('cURL is not available');

		$xml = urlencode($doc);
		return OpenPayU::sendData(self::$open_payu_end_point_url, 'DOCUMENT='.$xml);
	}

	/**
	 * This function sends auth data to the EndPointUrl OpenPayU
	 * @access public
	 * @param string $doc
	 * @param integer $merchant_pos_id
	 * @param string $signature_key
	 * @param string $algorithm
	 * @return string
	 * @throws Exception
	 */
	public static function sendOpenPayuDocumentAuth($doc, $merchant_pos_id, $signature_key, $algorithm = 'MD5')
	{
		if (empty(self::$open_payu_end_point_url))
			throw new Exception('OpenPayUNetwork::$open_payu_end_point_url is empty');

		if (empty($signature_key))
			throw new Exception('Merchant Signature Key should not be null or empty.');

		if (empty($merchant_pos_id))
			throw new Exception('merchant_pos_id should not be null or empty.');

		$tosigndata = $doc.$signature_key;
		$xml = urlencode($doc);
		$signature = '';
		if ($algorithm == 'MD5')
			$signature = md5($tosigndata);
		else if ($algorithm == 'SHA')
			$signature = sha1($tosigndata);
		else if ($algorithm == 'SHA-256' || $algorithm == 'SHA256' || $algorithm == 'SHA_256')
			$signature = hash('sha256', $tosigndata);
		$auth_data = 'sender='.$merchant_pos_id.';signature='.$signature.';algorithm='.$algorithm.';content=DOCUMENT';

		if (!self::isCurlInstalled())
			throw new Exception('curl is not available');

		return OpenPayU::sendDataAuth(self::$open_payu_end_point_url, 'DOCUMENT='.$xml, $auth_data);
	}

	/**
	 * This function sends auth data to the EndPointUrl OpenPayU
	 * @access public
	 * @param string $url
	 * @param string $doc
	 * @param string $auth_data
	 * @return string $response
	 */
	public static function sendDataAuth($url, $doc, $auth_data)
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $doc);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_SSLVERSION, 3);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($ch, CURLOPT_HTTPHEADER, array('OpenPayu-Signature:'.$auth_data, 'X-OpenPayU-Signature:'.$auth_data));

		$response = curl_exec($ch);

		return $response;
	}

	/**
	 * This function sends data to the EndPointUrl OpenPayU
	 * @access public
	 * @param string $url
	 * @param string $doc
	 * @return string $response
	 */
	public static function sendData($url, $doc)
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $doc);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_SSLVERSION, 3);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);

		return $response;
	}
}
