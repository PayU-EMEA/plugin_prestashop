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

if (!defined('OpenPayULIBRARY'))
	exit;

class OpenPayUToken extends OpenPayU
{
	/**
	 * @deprecated
	 * @param array $data
	 * @return OpenPayUResult
	 */
	public static function create($data)
	{
		$pathUrl = OpenPayUConfiguration::getServiceUrl().'token'.OpenPayUConfiguration::getDataFormat(true);

		$xml = OpenPayUUtil::buildXmlFromArray($data, 'TokenCreateRequest');

		$result = self::verifyResponse(OpenPayUHttp::post($pathUrl, $xml), 'TokenCreateResponse');

		return $result;
	}

	/**
	 * @param string $response
	 * @param string $messageName
	 * @return null|OpenPayUResult
	 */
	public static function verifyResponse($response, $messageName)
	{
		$data = array();
		$httpStatus = $response['code'];
		$message = OpenPayUUtil::parseXmlDocument($response['response']);

		if (isset($message['OpenPayU'][$messageName])) {
			$status = $message['OpenPayU'][$messageName]['Status'];
			$data['Status'] = $status;
			unset($message['OpenPayU'][$messageName]['Status']);
			$data['Response'] = $message['OpenPayU'][$messageName];
		}
		elseif(isset($message['OpenPayU']))
		{
			$status = $message['OpenPayU']['Status'];
			$data['Status'] = $status;
			unset($message['OpenPayU']['Status']);
		}

		$result = self::build($data);

		if ($httpStatus == 200 || $httpStatus == 201 || $httpStatus == 422 || $httpStatus == 302)
			return $result;
		else {
			OpenPayUHttp::throwHttpStatusException($httpStatus, $result);
		}

		return null;
	}
}
