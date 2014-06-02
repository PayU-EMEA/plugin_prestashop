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
	 * @param $pathUrl
	 * @param $data
	 * @return mixed
	 */
	public static function post($pathUrl, $data)
	{
		//$signature = OpenPayUUtil::generateSignData($data, OpenPayUConfiguration::getHashAlgorithm(), OpenPayUConfiguration::getMerchantPosId(), OpenPayUConfiguration::getSignatureKey());

		$posId = OpenPayUConfiguration::getMerchantPosId();
		$sigantureKey = OpenPayUConfiguration::getSignatureKey();

		$response = OpenPayUHttpCurl::doRequest('POST', $pathUrl, $data, $posId, $sigantureKey);

		return $response;
	}

	public static function postWithSignature($pathUrl, $data)
	{
		//$signature = OpenPayUUtil::generateSignData($data, OpenPayUConfiguration::getHashAlgorithm(), OpenPayUConfiguration::getMerchantPosId(), OpenPayUConfiguration::getSignatureKey());

		$posId = OpenPayUConfiguration::getMerchantPosId();
		$sigantureKey = OpenPayUConfiguration::getSignatureKey();

		$response = OpenPayUHttpCurl::doRequest('POST', $pathUrl, $data, $posId, $sigantureKey);

		return $response;
	}

	/**
	 * @param $pathUrl
	 * @param $data
	 * @return mixed
	 */
	public static function get($pathUrl, $data)
	{
		$posId = OpenPayUConfiguration::getMerchantPosId();
		$sigantureKey = OpenPayUConfiguration::getSignatureKey();

		$response = OpenPayUHttpCurl::doRequest('GET', $pathUrl, $data, $posId, $sigantureKey);

		return $response;
	}

	/**
	 * @param $pathUrl
	 * @param $data
	 * @return mixed
	 */
	public static function put($pathUrl, $data)
	{
		$posId = OpenPayUConfiguration::getMerchantPosId();
		$sigantureKey = OpenPayUConfiguration::getSignatureKey();

		$response = OpenPayUHttpCurl::doRequest('PUT', $pathUrl, $data, $posId, $sigantureKey);

		return $response;
	}

	/**
	 * @param $pathUrl
	 * @param $data
	 * @return mixed
	 */
	public static function delete($pathUrl, $data)
	{
		$posId = OpenPayUConfiguration::getMerchantPosId();
		$sigantureKey = OpenPayUConfiguration::getSignatureKey();

		$response = OpenPayUHttpCurl::doRequest('DELETE', $pathUrl, $data, $posId, $sigantureKey);

		return $response;
	}

	/**
	 *
	 *
	 * @param $statusCode
	 * @param null $message
	 * @throws OpenPayUException
	 * @throws OpenPayUException_Authorization
	 * @throws OpenPayUException_Network
	 * @throws OpenPayUException_ServerMaintenance
	 * @throws OpenPayUException_ServerError
	 */
	public static function throwHttpStatusException($statusCode, $message = null)
	{
		switch ($statusCode) {
			default:
				throw new OpenPayUException_Network('Unexpected HTTP code response', $statusCode);
			case 400:
				throw new OpenPayUException(trim($message->Status->StatusCode.(isset($message->Status->StatusDesc) ?
					' - '.$message->Status->StatusDesc : '')), $statusCode);
			case 403:
				throw new OpenPayUException_Authorization(trim($message->Status->StatusCode), $statusCode);
			case 404:
				throw new OpenPayUException_Network('The end point of the url not found');
			case 408:
				throw new OpenPayUException_ServerError('Request timeout', $statusCode);
			case 500:
				throw new OpenPayUException_ServerError('Server Error: ['.(isset($message->Status->StatusDesc) ?
					$message->Status->StatusDesc : '').']', $statusCode);
			case 503:
				throw new OpenPayUException_ServerMaintenance('Service unavailable', $statusCode);
		}
	}
}
