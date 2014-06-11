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

class OpenPayUUtil
{
	/**
	 * Function generate sign data
	 * @access public
	 * @param string $data
	 * @param string $algorithm
	 * @param string $merchant_pos_id
	 * @param string $signature_key
	 * @return string $sign_data
	 * @throws OpenPayUExceptionConfiguration
	 */
	public static function generateSignData($data, $algorithm = 'SHA', $merchant_pos_id = '', $signature_key = '')
	{
		if (empty($signature_key))
			throw new OpenPayUExceptionConfiguration('Merchant Signature Key should not be null or empty.');

		if (empty($merchant_pos_id))
			throw new OpenPayUExceptionConfiguration('MerchantPosId should not be null or empty.');

		$signature = '';

		$data = $data.$signature_key;

		if ($algorithm == 'MD5')
			$signature = md5($data);

		else if (in_array($algorithm, array('SHA', 'SHA1', 'SHA-1')))
		{
			$signature = sha1($data);
			$algorithm = 'SHA-1';
		}

		else if (in_array($algorithm, array('SHA-256', 'SHA256', 'SHA_256')))
		{
			$signature = hash('sha256', $data);
			$algorithm = 'SHA-256';
		}

		$sign_data = 'sender='.$merchant_pos_id.';signature='.$signature.';algorithm='.$algorithm.';content=DOCUMENT';

		return $sign_data;
	}

	/**
	 * Function returns signature data object
	 * @param string $data
	 * @return null|object
	 */
	public static function parseSignature($data)
	{
		if (empty($data))
			return null;

		$signature_data = array();

		$list = explode(';', rtrim($data, ';'));
		if (empty($list))
			return null;

		foreach ($list as $value)
		{
			$explode = explode('=', $value);
			$signature_data[$explode[0]] = $explode[1];
		}

		return (object)$signature_data;
	}

	/**
	 * Function returns signature validate
	 * @param $message
	 * @param $signature
	 * @param $signature_key
	 * @param $algorithm
	 * @return bool
	 */
	public static function verifySignature($message, $signature, $signature_key, $algorithm = 'MD5')
	{
		$hash = '';

		if (isset($signature))
		{
			if ($algorithm == 'MD5')
				$hash = md5($message.$signature_key);
			else if (in_array($algorithm, array('SHA', 'SHA1', 'SHA-1')))
				$hash = sha1($message.$signature_key);
			else if (in_array($algorithm, array('SHA-256', 'SHA256', 'SHA_256')))
				$hash = hash('sha256', $message.$signature_key);

			if (strcmp($signature, $hash) == 0)
				return true;
		}

		return false;
	}

	/**
	 * Function builds OpenPayU Json Document
	 * @access public
	 * @param string $data
	 * @param string $root_element
	 * @return string $xml
	 */
	public static function buildJsonFromArray($data, $root_element = '')
	{
		if (!is_array($data))
			return null;

		if (!empty($root_element))
			$data = array($root_element => $data);

		$data = self::setSenderProperty($data);

		return Tools::jsonEncode($data);
	}

	/**
	 * Function builds OpenPayU Xml Document
	 * @access public
	 * @param string $data
	 * @param string $root_element
	 * @param string $version
	 * @param string $encoding
	 * @param string $root_element_xsi
	 * @return string $xml
	 */
	public static function buildXmlFromArray($data, $root_element, $version = '1.0', $encoding = 'UTF-8', $root_element_xsi = null)
	{
		if (!is_array($data))
			return null;

		$xml = new XmlWriter();

		$xml->openMemory();

		$xml->setIndent(true);

		$xml->startDocument($version, $encoding);
		$xml->startElementNS(null, 'OpenPayU', 'http://www.openpayu.com/20/openpayu.xsd');

		$xml->startElement($root_element);

		if (!empty($root_element_xsi))
		{
			$xml->startAttributeNs('xsi', 'type', 'http://www.w3.org/2001/XMLSchema-instance');
			$xml->text($root_element_xsi);
			$xml->endAttribute();
		}

		self::convertArrayToXml($xml, $data);
		$xml->endElement();

		$xml->endElement();
		$xml->endDocument();

		return trim($xml->outputMemory(true));
	}

	/**
	 * Function converts array to XML document
	 * @access public
	 * @param XMLWriter $xml
	 * @param array $data
	 */
	public static function convertArrayToXml(XMLWriter $xml, $data)
	{
		if (!empty($data) && is_array($data))
		{
			foreach ($data as $key => $value)
			{
				if (is_array($value))
				{
					if (is_numeric($key))
						self::convertArrayToXml($xml, $value);
					else
					{
						$xml->startElement($key);
						self::convertArrayToXml($xml, $value);
						$xml->endElement();
					}
					continue;
				}
				$xml->writeElement($key, $value);
			}
		}
	}

	/**
	 * @param $data
	 * @return array
	 */
	public static function parseXmlDocument($data)
	{
		if (empty($data))
			return null;

		$assoc = self::convertXmlToArray($data);

		return $assoc;
	}

	/**
	 * Function converts xml to array
	 * @access public
	 * @param string $xml
	 * @return array $tree
	 */
	public static function convertXmlToArray($xml)
	{
		$xml_object = simplexml_load_string($xml);
		$xml_array = array( $xml_object->getName() => (array)$xml_object );
		return Tools::jsonDecode(Tools::jsonEncode($xml_array), 1);
	}

	/**
	 * @param $data
	 * @param bool $assoc
	 * @return mixed|null
	 */
	public static function convertJsonToArray($data, $assoc = false)
	{
		if (empty($data))
			return null;

		return Tools::jsonDecode($data, $assoc);
	}

	/**
	 * @param $array
	 * @return bool|stdClass
	 */
	public static function parseArrayToObject($array)
	{
		if (!is_array($array))
			return $array;

		if (self::isAssocArray($array))
			$object = new stdClass();
		else
			$object = array();

		if (is_array($array) && count($array) > 0)
		{
			foreach ($array as $name => $value)
			{
				$name = trim($name);
				if (isset($name))
				{
					if (is_numeric($name))
						$object[] = self::parseArrayToObject($value);
					else
						$object->$name = self::parseArrayToObject($value);
				}
			}
			return $object;
		}

		return false;
	}

	/**
	 * @return mixed
	 */
	public static function getRequestHeaders()
	{
		if (!function_exists('apache_request_headers'))
		{
				$headers = array();
				foreach ($_SERVER as $key => $value)
				{
					if (Tools::substr($key, 0, 5) == 'HTTP_')
						$headers[str_replace(' ', '-', ucwords(str_replace('_', ' ', Tools::strtolower(Tools::substr($key, 5)))))] = $value;
				}
				return $headers;
		}
		else
			return apache_request_headers();
	}

	/**
	 * @param $array
	 * @param string $namespace
	 * @return string
	 */
	public static function convertArrayToHtmlForm($array, $namespace = '', &$output_fields)
	{
		$i = 0;
		$html_output = '';
		$assoc = self::isAssocArray($array);

		foreach ($array as $key => $value)
		{

			//Temporary important changes only for order by form method
			$key = self::changeFormFieldFormat($namespace, $key);

			if ($namespace && $assoc)
				$key = $namespace.'.'.$key;
			elseif ($namespace && !$assoc)
				$key = $namespace.'['.$i++.']';

			if (is_array($value))
				$html_output .= self::convertArrayToHtmlForm($value, $key, $output_fields);
			else
			{
				$html_output .= sprintf('<input type="hidden" name="%s" value="%s" />\n', $key, $value);
				$output_fields[$key] = $value;
			}
		}
		return $html_output;
	}

	/**
	 * @param $arr
	 * @return bool
	 */
	public static function isAssocArray($arr)
	{
		$arr_keys = array_keys($arr);
		sort($arr_keys, SORT_NUMERIC);
		return $arr_keys !== range(0, count($arr) - 1);
	}

	/**
	 * @param $namespace
	 * @param $key
	 * @return string
	 */
	public static function changeFormFieldFormat($namespace, $key)
	{
		$key = Tools::ucfirst($key);

		if ($key === $namespace && $key[Tools::strlen($key) - 1] == 's')
			return Tools::substr($key, 0, -1);

		return $key;
	}

	/**
	 * @param $data
	 * @return mixed
	 */
	public static function setSenderProperty($data)
	{
		$data['properties']['properties'][0]['name'] = 'sender';
		$data['properties']['properties'][0]['value'] = OpenPayUConfiguration::getFullSenderName();
		return $data;
	}

}
