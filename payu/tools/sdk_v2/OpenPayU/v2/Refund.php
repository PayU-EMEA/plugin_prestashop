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

class OpenPayURefund extends OpenPayU {

	public static function create($order_id, $description, $amount = null)
	{
		if (empty($order_id))
			throw new OpenPayUException('Invalid orderId value for refund');

		if (empty($description))
			throw new OpenPayUException('Invalid description of refund');

		$refund = array (
			'orderId' => $order_id,
			'refund' => array (
				'description' => $description
			)
		);

		if (! empty($amount))
			$refund['refund']['amount'] = (int)$amount;

		$refund['refund']['currencyCode'] = 'PLN';
		$path_url = OpenPayUConfiguration::getServiceUrl().
			'orders/'.
			$refund['orderId'].
			'/refund';

		$data = OpenPayUUtil::buildJsonFromArray($refund);

		if (empty($data))
			throw new OpenPayUException('Empty message RefundCreateResponse');

		$result = self::verifyResponse( OpenPayUHttp::post($path_url, $data), 'RefundCreateResponse');

		return $result;
	}

	public static function verifyResponse($response, $message_name = '')
	{
		$data = array ();
		$http_status = $response['code'];

		$message = OpenPayUUtil::convertJsonToArray($response['response'], true);

		if (isset($message[$message_name]))
		{
			$data['status'] = isset($message['status']['statusCode']) ? $message['status']['statusCode'] : null;
			unset($message[$message_name]['Status']);
			$data['response'] = $message[$message_name];
		} elseif (isset($message))
		{
			$data['response'] = $message;
			$data['status'] = isset($message['status']['statusCode']) ? $message['status']['statusCode'] : null;
			unset($message['status']);
		}

		$result = self::build($data);

		if ($http_status == 200 ||
			$http_status == 201 ||
			$http_status == 422 ||
			$http_status == 302 ||
			$http_status == 400 ||
			$http_status == 404)
			return $result;
		else
			OpenPayUHttp::throwHttpStatusException($http_status, $result);

		return null;
	}
}