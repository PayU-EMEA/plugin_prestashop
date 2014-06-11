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
	 * @param $order_id
	 * @param $description
	 * @param int $amount Amount of refund in pennies
	 * @return null|OpenPayUResult
	 * @throws OpenPayUException
	 */
	public static function create($order_id, $description, $amount = null)
	{
		if (empty($order_id))
			throw new OpenPayUException('Invalid orderId value for refund');

		if (empty($description))
			throw new OpenPayUException('Invalid description of refund');

		$refund = array(
			'OrderId' => $order_id,
			'Refund' => array('Description' => $description)
		);

		if (!empty($amount))
			$refund['Refund']['Amount'] = (int)$amount;

		$path_url = OpenPayUConfiguration::getServiceUrl().'order/'.$order_id.'/refund';
		$data = OpenPayUUtil::buildJsonFromArray($refund, 'RefundCreateRequest');

		if (empty($data))
			throw new OpenPayUException('Empty message RefundCreateResponse');

		$result = self::verifyResponse(OpenPayUHttp::post($path_url, $data), 'RefundCreateResponse');

		return $result;
	}

	/**
	 * @param string $response
	 * @param string $message_name
	 * @return null|OpenPayUResult
	 */
	public static function verifyResponse($response, $message_name)
	{
		$data = array();
		$http_status = $response['code'];

		$message = OpenPayUUtil::convertJsonToArray($response['response'], true);

		if (isset($message['OpenPayU'][$message_name]))
		{
			$status = $message['OpenPayU'][$message_name]['Status'];
			$data['Status'] = $status;
			unset($message['OpenPayU'][$message_name]['Status']);
			$data['Response'] = $message['OpenPayU'][$message_name];
		}
		elseif (isset($message['OpenPayU']))
		{
			$status = $message['OpenPayU']['Status'];
			$data['Status'] = $status;
			unset($message['OpenPayU']['Status']);
		}

		$result = self::build($data);

		if ($http_status == 200 || $http_status == 201 || $http_status == 422 || $http_status == 302)
			return $result;
		else
			OpenPayUHttp::throwHttpStatusException($http_status, $result);

		return null;
	}
}