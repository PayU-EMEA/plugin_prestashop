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

if (!defined('OPEN_PAYU_LIBRARY'))
	exit;

/**
 * Class OpenPayUOrder
 */
class OpenPayUOrder extends OpenPayU
{
	const ORDER_SERVICE = 'orders/';
	const SUCCESS = 'SUCCESS';

	/**
	 * @var array Default form parameters
	 */
	protected static $default_form_params = array(
		'formClass' => '',
		'formId' => 'payu-payment-form',
		'submitClass' => '',
		'submitId' => '',
		'submitContent' => '',
		'submitTarget' => '_blank'
	);

	/**
	 * Creates new Order
	 * - Sends to PayU OrderCreateRequest
	 *
	 * @access public
	 * @param array $order A array containing full Order
	 * @return object $result Response array with OrderCreateResponse
	 * @throws OpenPayUException
	 */
	public static function create($order)
	{
		$path_url = OpenPayUConfiguration::getServiceUrl().self::ORDER_SERVICE;
		$data = OpenPayUUtil::buildJsonFromArray($order);

		if (empty($data))
			throw new OpenPayUException('Empty message OrderCreateRequest');

		$result = self::verifyResponse(OpenPayUHttp::post($path_url, $data), 'OrderCreateResponse');

		return $result;
	}

	/**
	 * Retrieves information about the order
	 *  - Sends to PayU OrderRetrieveRequest
	 *
	 * @access public
	 * @param string $order_id PayU OrderId sent back in OrderCreateResponse
	 * @return OpenPayUResult $result Response array with OrderRetrieveResponse
	 * @throws OpenPayUException
	 */
	public static function retrieve($order_id)
	{
		if (empty($order_id))
			throw new OpenPayUException('Empty value of orderId');

		$path_url = OpenPayUConfiguration::getServiceUrl().self::ORDER_SERVICE.$order_id;

		$result = self::verifyResponse(OpenPayUHttp::get($path_url, $path_url), 'OrderRetrieveResponse');

		return $result;
	}

	/**
	 * Cancels Order
	 * - Sends to PayU OrderCancelRequest
	 *
	 * @access public
	 * @param string $order_id PayU OrderId sent back in OrderCreateResponse
	 * @return OpenPayUResult $result Response array with OrderCancelResponse
	 * @throws OpenPayUException
	 */
	public static function cancel($order_id)
	{
		if (empty($order_id))
			throw new OpenPayUException('Empty value of orderId');

		$path_url = OpenPayUConfiguration::getServiceUrl().self::ORDER_SERVICE.$order_id;

		$result = self::verifyResponse(OpenPayUHttp::delete($path_url, $path_url), 'OrderCancelResponse');
		return $result;
	}

	/**
	 * Updates Order status
	 * - Sends to PayU OrderStatusUpdateRequest
	 *
	 * @access public
	 * @param string $orderStatus A array containing full OrderStatus
	 * @return OpenPayUResult $result Response array with OrderStatusUpdateResponse
	 * @throws OpenPayUException
	 */
	public static function statusUpdate($order_status_update)
	{
		$data = array();
		if (empty($order_status_update))
			throw new OpenPayUException('Empty order status data');

		$data = OpenPayUUtil::buildJsonFromArray($order_status_update);
		$order_id = $order_status_update['orderId'];

		$path_url = OpenPayUConfiguration::getServiceUrl().self::ORDER_SERVICE.$order_id.'/status';

		$result = self::verifyResponse(OpenPayUHttp::put($path_url, $data), 'OrderStatusUpdateResponse');
		return $result;
	}

	/**
	 * Consume notification message
	 *
	 * @access public
	 * @param $data Request array received from with PayU OrderNotifyRequest
	 * @return null|OpenPayUResult Response array with OrderNotifyRequest
	 * @throws OpenPayUException
	 */
	public static function consumeNotification($data)
	{
		$ssl_connection = self::isSecureConnection();

		if (empty($data))
			throw new OpenPayUException('Empty value of data');

		$headers = OpenPayUUtil::getRequestHeaders();

		$incoming_signature = OpenPayUHttpCurl::getSignature($headers);

		if ($ssl_connection)
			self::verifyBasicAuthCredentials();
		else
			self::verifyDocumentSignature($data, $incoming_signature);

		return OpenPayUOrder::verifyResponse(
				array('response' => $data, 'code' => 200),
				'OrderNotifyRequest');
	}

	/**
	 * Verify response from PayU
	 *
	 * @param string $response
	 * @param string $message_name
	 * @return null|OpenPayUResult
	 */
	public static function verifyResponse($response, $message_name)
	{
		$data = array();
		$http_status = $response['code'];

		$message = OpenPayUUtil::convertJsonToArray($response['response'], true);

		if (isset($message[$message_name]))
		{
			$data['status'] = isset($message['status']['statusCode']) ? $message['status']['statusCode'] : null;
			unset($message[$message_name]['Status']);
			$data['response'] = $message[$message_name];
		}
		elseif (isset($message))
		{
			$data['response'] = $message;
			$data['status'] = isset($message['status']['statusCode']) ? $message['status']['statusCode'] : null;
			unset($message['status']);
		}

		$result = self::build($data);

		if ($http_status == 200 || $http_status == 201 || $http_status == 422 || $http_status == 302)
			return $result;
		else
			OpenPayUHttp::throwHttpStatusException($http_status, $result);

		return null;
	}

	/**
	 * Generate a form body for hosted order
	 *
	 * @access public
	 * @param $order An array containing full Order
	 * @param $params An optional array with form elements' params
	 * @return string Response html form
	 */
	public static function hostedOrderForm($order, $params = array())
	{
		$order_form_url = OpenPayUConfiguration::getServiceUrl().'order';

		$usorted_form_field_values_as_array = array();
		$html_form_fields = OpenPayUUtil::convertArrayToHtmlForm($order, '', $usorted_form_field_values_as_array);
		ksort($usorted_form_field_values_as_array);
		$sorted_form_field_values_as_string = implode('', array_values($usorted_form_field_values_as_array));

		$signature = OpenPayUUtil::generateSignData(
			$sorted_form_field_values_as_string,
			OpenPayUConfiguration::getHashAlgorithm(),
			OpenPayUConfiguration::getMerchantPosId(),
			OpenPayUConfiguration::getSignatureKey()
		);

		$form_params = array_merge(self::$default_form_params, $params);

		$html_output = sprintf('<form method="POST" action="%s" id="%s" class="%s">\n',
				$order_form_url,
				$form_params['formId'],
				$form_params['formClass']);

		$html_output .= $html_form_fields;
		$html_output .= sprintf('<input type="hidden" name="OpenPayu-Signature" value="%s" />', $signature);
		$html_output .= sprintf('<button type="submit" formtarget="%s" id="%s" class="%s">%s</button>',
				$form_params['submitTarget'],
				$form_params['submitId'],
				$form_params['submitClass'],
				$form_params['submitContent']);
		$html_output .= '</form>\n';

		return $html_output;
	}
}