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

	public $firstName;
	public $lastName;
	public $ciSerial;
	public $ciNumber;
	public $ciIssuer;
	public $cnp;
	public $company;
	public $fiscalCode;
	public $regNumber;
	public $bank;
	public $bankAccount;
	public $email;
	public $phone;
	public $fax;
	public $address;
	public $address2;
	public $zipCode;
	public $city;
	public $state;
	public $countryCode;

	/**
	 * Constructor
	 *
	 * @param string $firstName name of the client
	 * @param string $lastName last name of the client
	 * @param string $ciSerial id serial
	 * @param string $ciNumber id serial number
	 * @param string $ciIssuer issuer for the id
	 * @param string $cnp private numeric identification
	 * @param string $company company of the client
	 * @param string $fiscalCode fiscal code for the company
	 * @param string $regNumber registration number for the company
	 * @param string $bank bank for the client
	 * @param string $bankAccount bank account for the client
	 * @param string $email email for the client
	 * @param string $phone phone number for the client
	 * @param string $fax fax number for the client
	 * @param string $address address for the client
	 * @param string $address2 optional address for the client
	 * @param string $zipCode zipcode for the client
	 * @param string $city city for the client
	 * @param string $state state/province for the client
	 * @param string $countryCode iso country code
	 * @return int returns 1 upon success
	 */
	public function __construct($firstName = '',
		$lastName = '',
		$ciSerial = '',
		$ciNumber = '',
		$ciIssuer = '',
		$cnp = '',
		$company = '',
		$fiscalCode = '',
		$regNumber = '',
		$bank = '',
		$bankAccount = '',
		$email = '',
		$phone = '',
		$fax = '',
		$address = '',
		$address2 = '',
		$zipCode = '',
		$city = '',
		$state = '',
		$countryCode = '')
	{
		if (!empty($firstName))
			$this->setFirstName($firstName);

		if (!empty($lastName))
			$this->setLastName($lastName);

		if (!empty($ciSerial))
			$this->setCiSerial($ciSerial);

		if (!empty($ciNumber))
			$this->setCiNumber($ciNumber);

		if (!empty($ciIssuer))
			$this->setCiIssuer($ciIssuer);

		if (!empty($cnp))
			$this->setCnp($cnp);

		if (!empty($company))
			$this->setCompany($company);

		if (!empty($fiscalCode))
			$this->setFiscalCode($fiscalCode);

		if (!empty($regNumber))
			$this->setRegNumber($regNumber);

		if (!empty($bank))
			$this->setBank($bank);

		if (!empty($bankAccount))
			$this->setBankAccount($bankAccount);

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

		if (!empty($zipCode))
			$this->setZipCode($zipCode);

		if (!empty($city))
			$this->setCity($city);

		if (!empty($state))
			$this->setState($state);

		if (!empty($countryCode))
			$this->setCountryCode($countryCode);

		return 1;
	}

	/**
	 *  Set first name for the client
	 *
	 * @param string $firstName
	 * @return int 1 on success
	 */
	public function setFirstName($firstName)
	{
		$this->firstName = $firstName;
		return 1;
	}

	/**
	 *  Set last name for the client
	 *
	 * @param string $lastName
	 * @return int 1 on success
	 */
	public function setLastName($lastName)
	{
		$this->lastName = $lastName;
		return 1;
	}

	/**
	 *  Set ci serial
	 *
	 * @param string $ciSerial
	 * @return int 1 on success
	 */
	public function setCiSerial($ciSerial)
	{
		$this->ciSerial = $ciSerial;
		return 1;
	}

	/**
	 *  Set ci serial number
	 *
	 * @param string $ciNumber
	 * @return int 1 on success
	 */
	public function setCiNumber($ciNumber)
	{
		$this->ciNumber = $ciNumber;
		return 1;
	}

	/**
	 *  Set the issuer for the ci
	 *
	 * @param string $ciIssuer
	 * @return int 1 on success
	 */
	public function setCiIssuer($ciIssuer)
	{
		$this->ciIssuer = $ciIssuer;
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
	 * @param string $fiscalCode
	 * @return int 1 on success
	 */
	public function setFiscalCode($fiscalCode)
	{
		$this->fiscalCode = $fiscalCode;
		return 1;
	}

	/**
	 *  Set the registration number
	 *
	 * @param string $regNumber
	 * @return int 1 on success
	 */
	public function setRegNumber($regNumber)
	{
		$this->regNumber = $regNumber;
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
	 * @param string $bankAccount
	 * @return int 1 on success
	 */
	public function setBankAccount($bankAccount)
	{
		$this->bankAccount = $bankAccount;
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
	 * @param string $zipCode
	 * @return int 1 on success
	 */
	public function setZipCode($zipCode)
	{
		$this->zipCode = $zipCode;
		return 1;
	}

	/**
	 *  Set the city
	 *
	 * @param string $City
	 * @return int 1 on success
	 */
	public function setCity($City)
	{
		$this->city = $City;
		return 1;
	}

	/**
	 *  Set state
	 *
	 * @param string $State
	 * @return int 1 on success
	 */
	public function setState($State)
	{
		$this->state = $State;
		return 1;
	}

	/**
	 *  Set country code
	 *
	 * @param string $CountryCode
	 * @return int 1 on success
	 */
	public function setCountryCode($CountryCode)
	{
		$this->countryCode = $CountryCode;
		return 1;
	}

	/**
	 * Method will check for the address integrity and needed values
	 *
	 * @return int 1 on success
	 */
	public function checkAddress()
	{
		if ($this->ciNumber && !is_numeric($this->ciNumber))
			$this->_logError('CI Number is not a number');

		if ($this->email && preg_match(
				'/^[a-z0-9&\'\.\-_\+]+@[a-z0-9\-]+\.([a-z0-9\-]+\.)*+[a-z]{2}/is',
				$this->email
			) === 0)
			$this->_logError('Email is invalid');

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
		return $this->_errorLog;
	}

}