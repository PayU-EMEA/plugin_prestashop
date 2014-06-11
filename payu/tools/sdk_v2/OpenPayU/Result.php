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

class OpenPayUResult
{
	private $status = '';
	private $error = '';
	private $success = 0;
	private $request = '';
	private $response = '';
	private $session_id = '';
	private $message = '';
	private $country_code = '';
	private $req_id = '';

	/**
	 * @access public
	 * @return string
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * @access public
	 * @param $value
	 */
	public function setStatus($value)
	{
		$this->status = $value;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * @access public
	 * @param $value
	 */
	public function setError($value)
	{
		$this->error = $value;
	}

	/**
	 * @access public
	 * @return int
	 */
	public function getSuccess()
	{
		return $this->success;
	}

	/**
	 * @access public
	 * @param $value
	 */
	public function setSuccess($value)
	{
		$this->success = $value;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * @access public
	 * @param $value
	 */
	public function setRequest($value)
	{
		$this->request = $value;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * @access public
	 * @param $value
	 */
	public function setResponse($value)
	{
		$this->response = $value;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getSessionId()
	{
		return $this->session_id;
	}

	/**
	 * @access public
	 * @param $value
	 */
	public function setSessionId($value)
	{
		$this->session_id = $value;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getMessage()
	{
		return $this->message;
	}

	/**
	 * @access public
	 * @param $value
	 */
	public function setMessage($value)
	{
		$this->message = $value;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getCountryCode()
	{
		return $this->country_code;
	}

	/**
	 * @access public
	 * @param $value
	 */
	public function setCountryCode($value)
	{
		$this->country_code = $value;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getReqId()
	{
		return $this->req_id;
	}

	/**
	 * @access public
	 * @param $value
	 */
	public function setReqId($value)
	{
		$this->req_id = $value;
	}

	public function init($attributes)
	{
		$attributes = OpenPayUUtil::parseArrayToObject($attributes);

		if (!empty($attributes))
		{
			foreach ($attributes as $name => $value)
				$this->set($name, $value);
		}
	}

	public function set($name, $value)
	{
		$this->{$name} = $value;
	}

	public function __get($name)
	{
		if (isset($this->{$name}))
			return $this->name;

		return null;
	}

	public function __call($method_name, $args)
	{
		if (preg_match('~^(set|get)([A-Z])(.*)$~', $method_name, $matches))
		{

			$property = Tools::strtolower($matches[2]).$matches[3];

			if (!property_exists($this, $property))
				throw new Exception('Property '.$property.' not exists');

			switch ($matches[1])
			{
				case 'get':
					$this->checkArguments($args, 0, 0, $method_name);
					return $this->get($property);
				case 'default':
					throw new Exception('Method '.$method_name.' not exists');
			}
		}
	}
}