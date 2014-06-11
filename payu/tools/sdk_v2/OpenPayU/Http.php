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

class OpenPayUHttp
{
	/**
	 * @param $path_url
	 * @param $data
	 * @return mixed
	 */
	public static function post($path_url, $data)
	{
		//$signature = OpenPayUUtil::generateSignData($data,
		//OpenPayUConfiguration::getHashAlgorithm(), OpenPayUConfiguration::getMerchantPosId(),
		//OpenPayUConfiguration::getSignatureKey());

		$pos_id = OpenPayUConfiguration::getMerchantPosId();
		$siganture_key = OpenPayUConfiguration::getSignatureKey();

		$response = OpenPayUHttpCurl::doRequest('POST', $path_url, $data, $pos_id, $siganture_key);

		return $response;
	}

	public static function postWithSignature($path_url, $data)
	{
		//$signature = OpenPayUUtil::generateSignData($data, OpenPayUConfiguration::getHashAlgorithm(),
		//OpenPayUConfiguration::getMerchantPosId(),
		//OpenPayUConfiguration::getSignatureKey());

		$pos_id = OpenPayUConfiguration::getMerchantPosId();
		$siganture_key = OpenPayUConfiguration::getSignatureKey();

		$response = OpenPayUHttpCurl::doRequest('POST', $path_url, $data, $pos_id, $siganture_key);

		return $response;
	}

	/**
	 * @param $path_url
	 * @param $data
	 * @return mixed
	 */
	public static function get($path_url, $data)
	{
		$pos_id = OpenPayUConfiguration::getMerchantPosId();
		$siganture_key = OpenPayUConfiguration::getSignatureKey();

		$response = OpenPayUHttpCurl::doRequest('GET', $path_url, $data, $pos_id, $siganture_key);

		return $response;
	}

	/**
	 * @param $path_url
	 * @param $data
	 * @return mixed
	 */
	public static function put($path_url, $data)
	{
		$pos_id = OpenPayUConfiguration::getMerchantPosId();
		$siganture_key = OpenPayUConfiguration::getSignatureKey();

		$response = OpenPayUHttpCurl::doRequest('PUT', $path_url, $data, $pos_id, $siganture_key);

		return $response;
	}

	/**
	 * @param $path_url
	 * @param $data
	 * @return mixed
	 */
	public static function delete($path_url, $data)
	{
		$pos_id = OpenPayUConfiguration::getMerchantPosId();
		$siganture_key = OpenPayUConfiguration::getSignatureKey();

		$response = OpenPayUHttpCurl::doRequest('DELETE', $path_url, $data, $pos_id, $siganture_key);

		return $response;
	}

	/**
	 *
	 *
	 * @param $status_code
	 * @param null $message
	 * @throws OpenPayUException
	 * @throws OpenPayUExceptionAuthorization
	 * @throws OpenPayUExceptionNetwork
	 * @throws OpenPayUExceptionServerMaintenance
	 * @throws OpenPayUExceptionServerError
	 */
	public static function throwHttpStatusException($status_code, $message = null)
	{
		switch ($status_code)
		{
			default:
				throw new OpenPayUExceptionNetwork('Unexpected HTTP code response', $status_code);
			case 400:
				throw new OpenPayUException(trim($message->{'Status'}->{'StatusCode'}.(isset($message->{'Status'}->{'StatusDesc'}) ?
					' - '.$message->{'Status'}->{'StatusDesc'} : '')), $status_code);
			case 403:
				throw new OpenPayUExceptionAuthorization(trim($message->{'Status'}->{'StatusCode'}), $status_code);
			case 404:
				throw new OpenPayUExceptionNetwork('The end point of the url not found');
			case 408:
				throw new OpenPayUExceptionServerError('Request timeout', $status_code);
			case 500:
				throw new OpenPayUExceptionServerError('Server Error: ['.(isset($message->{'Status'}->{'StatusDesc'}) ?
					$message->{'Status'}->{'StatusDesc'} : '').']', $status_code);
			case 503:
				throw new OpenPayUExceptionServerMaintenance('Service unavailable', $status_code);
		}
	}
}
