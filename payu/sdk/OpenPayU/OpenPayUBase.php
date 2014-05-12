<?php

/**
 * @copyright  Copyright (c) 2011-2012 PayU
 * @license	http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * OpenPayU Standard Library
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
 */

if (!defined('OPENPAYU_LIBRARY'))
	exit;

include_once('Configuration.php');

class OpenPayUBase extends OpenPayUNetwork
{

	/** @var string outputConsole message */
	protected static $output_console = '';

	/**
	 * Show outputConsole message
	 * @access public
	 */
	public static function printOutputConsole()
	{
		echo OpenPayU::$output_console;
	}

	/**
	 * Add $outputConsole message
	 * @access public
	 * @param string $header
	 * @param string $text
	 */
	public static function addOutputConsole($header, $text = '')
	{
		OpenPayU::$output_console .= '<br/><strong>'.$header.':</strong><br />'.$text.'<br/>';
	}

	/**
	 * Function builds OpenPayU Request Document
	 * @access public
	 * @param string $data
	 * @param string $start_element Name of Document Element
	 * @param string $version Xml Version
	 * @param string $xml_encoding Xml Encoding
	 * @return string
	 */
	public static function buildOpenPayURequestDocument($data, $start_element, $version = '1.0', $xml_encoding = 'UTF-8')
	{
		return OpenPayUBase::buildOpenPayUDocument($data, $start_element, 1, $version, $xml_encoding);
	}

	/**
	 * Function builds OpenPayU Response Document
	 * @access public
	 * @param string $data
	 * @param string $start_element Name of Document Element
	 * @param string $version Xml Version
	 * @param string $xml_encoding Xml Encoding
	 * @return string
	 */
	public static function buildOpenPayUResponseDocument($data, $start_element, $version = '1.0', $xml_encoding = 'UTF-8')
	{
		return OpenPayUBase::buildOpenPayUDocument($data, $start_element, 0, $version, $xml_encoding);
	}

	/**
	 * Function converts array to XML document
	 * @access public
	 * @param XMLWriter $xml
	 * @param array $data
	 */
	public static function arr2xml(XMLWriter $xml, $data)
	{
		if (!empty($data) && is_array($data))
		{
			foreach ($data as $key => $value)
			{
				if (is_array($value))
				{
					if (is_numeric($key))
						self::arr2xml($xml, $value);
					else
					{
						$xml->startElement($key);
						self::arr2xml($xml, $value);
						$xml->endElement();
					}
					continue;
				}
				$xml->writeElement($key, $value);
			}
		}
	}

	/**
	 * Function converts array to Form
	 * @access public
	 * @param array $data
	 * @param string $parent
	 * @param integer $index
	 * @return string $fragment
	 */
	public static function arr2form($data, $parent, $index)
	{
		$fragment = '';

		if (!empty($data) && is_array($data))
		{
			foreach ($data as $key => $value)
			{
				if (is_array($value))
				{
					if (is_numeric($key))
						$fragment .= OpenPayUBase::arr2form($value, $parent, $key);
					else
					{
						$p = $parent != '' ? $parent.'.'.$key : $key;
						if (is_numeric($index))
							$p .= '['.$index.']';
						$fragment .= OpenPayUBase::arr2form($value, $p, $key);
					}
					continue;
				}

				$path = $parent != '' ? $parent.'.'.$key : $key;
				$fragment .= OpenPayUBase::buildFormFragmentInput($path, $value);
			}
		}

		return $fragment;
	}

	/**
	 * Function converts xml to array
	 * @access public
	 * @param XMLReader $xml
	 * @return array $tree
	 */
	public static function read($xml)
	{
		$tree = null;
		while ($xml->read())
		{
			if ($xml->nodeType == XMLReader::END_ELEMENT)
				return $tree;
			else if ($xml->nodeType == XMLReader::ELEMENT)
			{
				if (!$xml->isEmptyElement)
					$tree[$xml->name] = OpenPayUBase::read($xml);
			} else if ($xml->nodeType == XMLReader::TEXT)
				$tree = $xml->value;
		}
		return $tree;
	}

	/**
	 * Function builds OpenPayU Xml Document
	 * @access public
	 * @param string $data
	 * @param string $start_element
	 * @param integer $request
	 * @param string $xml_version
	 * @param string $xml_encoding
	 * @return string $xml
	 */
	public static function buildOpenPayUDocument($data, $start_element, $request = 1, $xml_version = '1.0', $xml_encoding = 'UTF-8')
	{
		if (!is_array($data))
			return false;

		$xml = new XmlWriter();
		$xml->openMemory();
		$xml->startDocument($xml_version, $xml_encoding);
		$xml->startElementNS(null, 'OpenPayU', 'http://www.openpayu.com/openpayu.xsd');

		$header = $request == 1 ? 'HeaderRequest' : 'HeaderResponse';

		$xml->startElement($header);

		$xml->writeElement('Algorithm', 'MD5');

		$xml->writeElement('SenderName', 'POSID=' . OpenPayUConfiguration::getMerchantPosid() .';CUSTOM_PLUGIN=PRESTASHOP');
		$xml->writeElement('Version', $xml_version);

		$xml->endElement();

		// domain level - open
		$xml->startElement(OpenPayUDomain::getDomain4Message($start_element));

		// message level - open
		$xml->startElement($start_element);

		self::arr2xml($xml, $data);

		// message level - close
		$xml->endElement();
		// domain level - close
		$xml->endElement();
		// document level - close
		$xml->endElement();

	return $xml->outputMemory(true);
	}

	/**
	 * Function builds form input element
	 * @access public
	 * @param string $name
	 * @param string $value
	 * @param string $type
	 * @return string
	 */
	public static function buildFormFragmentInput($name, $value, $type = 'hidden')
	{
		return "<input type='".$type."' name='".$name."' value='".$value."'>\n";
	}

	/**
	 * Function builds OpenPayU Form
	 * @access public
	 * @param string $data
	 * @param string $msg_name
	 * @param string $version
	 * @return string
	 */
	public static function buildOpenPayuForm($data, $msg_name, $version = '1.0')
	{
		if (!is_array($data))
			return false;

		$url = OpenPayUNetwork::getOpenPayuEndPoint();

		$form = "<form method='post' action='".$url."'>\n";
		$form .= OpenPayUBase::buildFormFragmentInput('HeaderRequest.Version', $version);
		$form .= OpenPayUBase::buildFormFragmentInput('HeaderRequest.Name', $msg_name);
		$form .= OpenPayUBase::arr2form($data, '', '');
		$form .= '</form>';

		return $form;
	}

	/**
	 * Function converts Xml string to array
	 * @access public
	 * @param string $xmldata
	 * @return array $assoc
	 */
	public static function parseOpenPayUDocument($xmldata)
	{
		$xml = new XMLReader();
		$xml->XML($xmldata);

		$assoc = self::read($xml);

		return $assoc;
	}
}
