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

class OpenPayU extends OpenPayUBase
{

	/**
	 * Function builds OrderCreateRequest Document
	 * @access public
	 * @param string $data
	 * @return string
	 */
	public static function buildOrderCreateRequest($data)
	{
		$xml = OpenPayU::buildOpenPayURequestDocument($data, 'OrderCreateRequest');
		return $xml;
	}

	/**
	 * Function builds OrderRetrieveRequest Document
	 * @access public
	 * @param array $data
	 * @return string $xml
	 */
	public static function buildOrderRetrieveRequest($data)
	{
		$xml = OpenPayU::buildOpenPayURequestDocument($data, 'OrderRetrieveRequest');
		return $xml;
	}

	/**
	 * Function builds ShippingCostRetrieveResponse Document
	 * @access public
	 * @param array $data
	 * @param string $reqId
	 * @return string $xml
	 */
	public static function buildShippingCostRetrieveResponse($data, $req_id)
	{
		$cost = array(
			'ResId' => $req_id,
			'Status' => array('StatusCode' => 'OpenPayUSUCCESS'),
			'AvailableShippingCost' => $data
		);

		$xml = OpenPayU::buildOpenPayUResponseDocument($cost, 'ShippingCostRetrieveResponse');
		return $xml;
	}

	/**
	 * Function builds buildOrderNotifyResponse Document
	 * @access public
	 * @param string $reqId
	 * @return string $xml
	 */
	public static function buildOrderNotifyResponse($req_id)
	{
		$data = array(
			'resId' => $req_id,
			'status' => array('statusCode' => 'SUCCESS')
		);

		$xml = OpenPayUUtil::buildJsonFromArray($data);
		return $xml;
	}

	/**
	 * Function builds verifyResponse Status
	 * @access public
	 * @param string $data
	 * @param string $message
	 * @return string $xml
	 */
	public static function verifyResponse($data, $message)
	{
		$document = OpenPayUUtil::parseXmlDocument(Tools::stripslashes($data));
		$status = null;

		if (OpenPayUConfiguration::getApiVersion() < 2)
			$status = $document['OpenPayU']['OrderDomainResponse'][$message]['Status'];
		else
			$status = $document['OpenPayU'][$message]['Status'];

		if (empty($status) && OpenPayUConfiguration::getApiVersion() < 2)
			$status = $document['OpenPayU']['HeaderResponse']['Status'];

		return $status;
	}

	/**
	 * Function returns OrderCancelResponse Status Document
	 * @access public
	 * @param string $data
	 * @return OpenPayUResult
	 */
	public static function verifyOrderCancelResponseStatus($data)
	{
		return OpenPayU::verifyResponse($data, 'OrderCancelResponse');
	}

	/**
	 * Function returns OrderStatusUpdateResponse Status Document
	 * @access public
	 * @param string $data
	 * @return mixed
	 */
	public static function verifyOrderStatusUpdateResponseStatus($data)
	{
		return OpenPayU::verifyResponse($data, 'OrderStatusUpdateResponse');
	}

	/**
	 * Function returns OrderCreateResponse Status
	 * @access public
	 * @param string $data
	 * @return mixed
	 */
	public static function verifyOrderCreateResponse($data)
	{
		return OpenPayU::verifyResponse($data, 'OrderCreateResponse');
	}

	/**
	 * Function returns OrderRetrieveResponse Status
	 * @access public
	 * @param string $data
	 * @return mixed
	 */
	public static function verifyOrderRetrieveResponseStatus($data)
	{
		return OpenPayU::verifyResponse($data, 'OrderRetrieveResponse');
	}

	/**
	 * Function returns OrderRetrieveResponse Data
	 * @access public
	 * @param string $data
	 * @return array $document
	 */
	public static function getOrderRetrieveResponse($data)
	{
		$response = OpenPayU::parseXmlDocument(Tools::stripslashes($data));

		$document = $response['OpenPayU']['OrderDomainResponse']['OrderRetrieveResponse'];

		if (OpenPayUConfiguration::getApiVersion() >= 2)
			$document = $response['OpenPayU']['OrderRetrieveResponse'];

		return $document;
	}

	/**
	 * Function builds OrderCancelRequest Document
	 * @access public
	 * @param string $data
	 * @return string $xml
	 */
	public static function buildOrderCancelRequest($data)
	{
		$xml = OpenPayU::buildOpenPayURequestDocument($data, 'OrderCancelRequest');

		return $xml;
	}

	/**
	 * Function builds OrderStatusUpdateRequest Document
	 * @access public
	 * @param string $data
	 * @return string $xml
	 */
	public static function buildOrderStatusUpdateRequest($data)
	{
		$xml = OpenPayU::buildOpenPayURequestDocument($data, 'OrderStatusUpdateRequest');

		return $xml;
	}

	protected static function build($data)
	{
		$instance = new OpenPayUResult();
		$instance->init($data);

		return $instance;
	}

	/**
	 * @throws OpenPayUExceptionAuthorization
	 */
	public static function verifyBasicAuthCredentials()
	{
		if (isset($_SERVER['PHP_AUTH_USER']))
			$user = $_SERVER['PHP_AUTH_USER'];
		else
			OpenPayUExceptionAuthorization('Empty user name');

		if (isset($_SERVER['PHP_AUTH_PW']))
			$password = $_SERVER['PHP_AUTH_PW'];
		else
			OpenPayUExceptionAuthorization('Empty password');

		if ($user !== OpenPayUConfiguration::getMerchantPosId() ||
			$password !== OpenPayUConfiguration::getSignatureKey())
			throw new OpenPayUExceptionAuthorization('invalid credentials');
	}

	/**
	 * @param $data
	 * @param $incomingSignature
	 * @throws OpenPayUExceptionAuthorization
	 */
	public static function verifyDocumentSignature($data, $incoming_signature)
	{
		$sign = OpenPayUUtil::parseSignature($incoming_signature);

		if (false === OpenPayUUtil::verifySignature(
				$data,
				$sign->signature,
				OpenPayUConfiguration::getSignatureKey(),
				$sign->algorithm
			))
			throw new OpenPayUExceptionAuthorization('Invalid signature - '.$sign->signature);
	}

	/**
	 * @return bool
	 */
	public static function isSecureConnection()
	{
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
			return true;
		return false;
	}
}