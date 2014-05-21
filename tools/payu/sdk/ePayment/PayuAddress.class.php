<?php

/**
 * @copyright  Copyright (c) 2013 PayU
 * @license	http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
 *
 */

class PayuAddress extends PayuSettings
{

	public $first_name;
	public $last_name;
	public $ci_serial;
	public $ci_number;
	public $ci_issuer;
	public $cnp;
	public $company;
	public $fiscal_code;
	public $reg_number;
	public $bank;
	public $bank_account;
	public $email;
	public $phone;
	public $fax;
	public $address;
	public $address2;
	public $zip_code;
	public $city;
	public $state;
	public $country_code;

	/**
	 * Constructor
	 *
	 * @param string $first_name name of the client
	 * @param string $last_name last name of the client
	 * @param string $ci_serial id serial
	 * @param string $ci_number id serial number
	 * @param string $ci_issuer issuer for the id
	 * @param string $cnp private numeric identification
	 * @param string $company company of the client
	 * @param string $fiscal_code fiscal code for the company
	 * @param string $reg_number registration number for the company
	 * @param string $bank bank for the client
	 * @param string $bank_account bank account for the client
	 * @param string $email email for the client
	 * @param string $phone phone number for the client
	 * @param string $fax fax number for the client
	 * @param string $address address for the client
	 * @param string $address2 optional address for the client
	 * @param string $zip_code zipcode for the client
	 * @param string $city city for the client
	 * @param string $state state/province for the client
	 * @param string $country_code iso country code
	 * @return int returns 1 upon success
	 */
	public function __construct($first_name = '',
		$last_name = '',
		$ci_serial = '',
		$ci_number = '',
		$ci_issuer = '',
		$cnp = '',
		$company = '',
		$fiscal_code = '',
		$reg_number = '',
		$bank = '',
		$bank_account = '',
		$email = '',
		$phone = '',
		$fax = '',
		$address = '',
		$address2 = '',
		$zip_code = '',
		$city = '',
		$state = '',
		$country_code = '')
	{
		if (!empty($first_name))
			$this->setFirstName($first_name);

		if (!empty($last_name))
			$this->setLastName($last_name);

		if (!empty($ci_serial))
			$this->setCiSerial($ci_serial);

		if (!empty($ci_number))
			$this->setCiNumber($ci_number);

		if (!empty($ci_issuer))
			$this->setCiIssuer($ci_issuer);

		if (!empty($cnp))
			$this->setCnp($cnp);

		if (!empty($company))
			$this->setCompany($company);

		if (!empty($fiscal_code))
			$this->setFiscalCode($fiscal_code);

		if (!empty($reg_number))
			$this->setRegNumber($reg_number);

		if (!empty($bank))
			$this->setBank($bank);

		if (!empty($bank_account))
			$this->setBankAccount($bank_account);

		if (!empty($email))
			$this->setEmail($email);

		if (!empty($phone))
			$this->setPhone($phone);

		if (!empty($fax))
			$this->setFax($fax);

		if (!empty($address))
			$this->setAddress($address);

		if (!empty($address2))
			$this->setAddress2($address2);

		if (!empty($zip_code))
			$this->setZipCode($zip_code);

		if (!empty($city))
			$this->setCity($city);

		if (!empty($state))
			$this->setState($state);

		if (!empty($country_code))
			$this->setCountryCode($country_code);

		return 1;
	}

	/**
	 *  Set first name for the client
	 *
	 * @param string $first_name
	 * @return int 1 on success
	 */
	public function setFirstName($first_name)
	{
		$this->first_name = $first_name;
		return 1;
	}

	/**
	 *  Set last name for the client
	 *
	 * @param string $last_name
	 * @return int 1 on success
	 */
	public function setLastName($last_name)
	{
		$this->last_name = $last_name;
		return 1;
	}

	/**
	 *  Set ci serial
	 *
	 * @param string $ci_serial
	 * @return int 1 on success
	 */
	public function setCiSerial($ci_serial)
	{
		$this->ci_serial = $ci_serial;
		return 1;
	}

	/**
	 *  Set ci serial number
	 *
	 * @param string $ci_number
	 * @return int 1 on success
	 */
	public function setCiNumber($ci_number)
	{
		$this->ci_number = $ci_number;
		return 1;
	}

	/**
	 *  Set the issuer for the ci
	 *
	 * @param string $ci_issuer
	 * @return int 1 on success
	 */
	public function setCiIssuer($ci_issuer)
	{
		$this->ci_issuer = $ci_issuer;
		return 1;
	}

	/**
	 *  Set cnp
	 *
	 * @param string $cnp
	 * @return int 1 on success
	 */
	public function setCnp($cnp)
	{
		$this->cnp = $cnp;
		return 1;
	}

	/**
	 *  Set company
	 *
	 * @param string $company
	 * @return int 1 on success
	 */
	public function setCompany($company)
	{
		$this->company = $company;
		return 1;
	}

	/**
	 *  Set the fiscal code
	 *
	 * @param string $fiscal_code
	 * @return int 1 on success
	 */
	public function setFiscalCode($fiscal_code)
	{
		$this->fiscal_code = $fiscal_code;
		return 1;
	}

	/**
	 *  Set the registration number
	 *
	 * @param string $reg_number
	 * @return int 1 on success
	 */
	public function setRegNumber($reg_number)
	{
		$this->reg_number = $reg_number;
		return 1;
	}

	/**
	 *  Set the bank
	 *
	 * @param string $bank
	 * @return int 1 on success
	 */
	public function setBank($bank)
	{
		$this->bank = $bank;
		return 1;
	}

	/**
	 *  Set the bank account
	 *
	 * @param string $bank_account
	 * @return int 1 on success
	 */
	public function setBankAccount($bank_account)
	{
		$this->bank_account = $bank_account;
		return 1;
	}

	/**
	 *  Set email
	 *
	 * @param string $email
	 * @return int 1 on success
	 */
	public function setEmail($email)
	{
		$this->email = $email;
		return 1;
	}

	/**
	 *  Set phone number
	 *
	 * @param string $phone
	 * @return int 1 on success
	 */
	public function setPhone($phone)
	{
		$this->phone = $phone;
		return 1;
	}

	/**
	 *  Set the fax number
	 *
	 * @param string $fax
	 * @return int 1 on success
	 */
	public function setFax($fax)
	{
		$this->fax = $fax;
		return 1;
	}

	/**
	 *  Set address
	 *
	 * @param string $address
	 * @return int 1 on success
	 */
	public function setAddress($address)
	{
		$this->address = $address;
		return 1;
	}

	/**
	 *  Set optional address
	 *
	 * @param string $address2
	 * @return int 1 on success
	 */
	public function setAddress2($address2)
	{
		$this->address2 = $address2;
		return 1;
	}

	/**
	 *  Set zip code
	 *
	 * @param string $zip_code
	 * @return int 1 on success
	 */
	public function setZipCode($zip_code)
	{
		$this->zip_code = $zip_code;
		return 1;
	}

	/**
	 *  Set the city
	 *
	 * @param string $city
	 * @return int 1 on success
	 */
	public function setCity($city)
	{
		$this->city = $city;
		return 1;
	}

	/**
	 *  Set state
	 *
	 * @param string $state
	 * @return int 1 on success
	 */
	public function setState($state)
	{
		$this->state = $state;
		return 1;
	}

	/**
	 *  Set country code
	 *
	 * @param string $country_code
	 * @return int 1 on success
	 */
	public function setCountryCode($country_code)
	{
		$this->country_code = $country_code;
		return 1;
	}

	/**
	 * Method will check for the address integrity and needed values
	 *
	 * @return int 1 on success
	 */
	public function checkAddress()
	{
		if ($this->ci_number && !is_numeric($this->ci_number))
			$this->logError('CI Number is not a number');

		if ($this->email && preg_match(
				'/^[a-z0-9&\'\.\-_\+]+@[a-z0-9\-]+\.([a-z0-9\-]+\.)*+[a-z]{2}/is',
				$this->email
			) === 0)
			$this->logError('Email is invalid');

		return 1;
	}

	/**
	 * Read errors for the current class and assert if there are any more errors regarding the current product
	 *
	 * @return int 1 on success
	 */
	public function validate()
	{
		self::checkAddress();
		return $this->error_log;
	}

}
