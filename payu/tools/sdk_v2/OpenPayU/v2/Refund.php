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

class OpenPayURefund extends OpenPayU
{
	/**
	 * Function make refund for order
	 * @param $orderId
	 * @param $description
	 * @param int $amount Amount of refund in pennies
	 * @return null|OpenPayUResult
	 * @throws OpenPayUException
	 */
	public static function create($orderId, $description, $amount = null)
	{
		if (empty($orderId))
			throw new OpenPayUException('Invalid orderId value for refund');

		if (empty($description))
			throw new OpenPayUException('Invalid description of refund');

		$refund = array(
			'OrderId' => $orderId,
			'Refund' => array('Description' => $description)
		);

		if (!empty($amount))
			$refund['Refund']['Amount'] = (int)$amount;

		$pathUrl = OpenPayUConfiguration::getServiceUrl().'order/'.$orderId.'/refund' .
		$data = OpenPayUUtil::buildJsonFromArray($refund, 'RefundCreateRequest');

		if (empty($data))
			throw new OpenPayUException('Empty message RefundCreateResponse');

		$result = self::verifyResponse(OpenPayUHttp::post($pathUrl, $data), 'RefundCreateResponse');

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

		$message = OpenPayUUtil::convertJsonToArray($response['response'], true);

		if (isset($message['OpenPayU'][$messageName])) {
			$status = $message['OpenPayU'][$messageName]['Status'];
			$data['Status'] = $status;
			unset($message['OpenPayU'][$messageName]['Status']);
			$data['Response'] = $message['OpenPayU'][$messageName];
		}
elseif (isset($message['OpenPayU'])) {
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