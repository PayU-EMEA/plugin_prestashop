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

class OpenPayUOAuthenticate extends OpenPayUOAuth
{
	/**
	 * Function returns authorize by code response
	 * @access public
	 * @param string $code
	 * @param string $return_uri
	 * @param bool $debug
	 * @return OpenPayUResultOAuth $result
	 */
	public static function accessTokenByCode($code, $return_uri, $debug = true)
	{
		$url = OpenPayUConfiguration::getServiceUrl().'user/oauth/authorize';

		$result = new OpenPayUResultOAuth();
		$result->setUrl($url);
		$result->setCode($code);

		if ($debug)
		{
			OpenPayU::addOutputConsole('retrieve accessToken, authorization code mode, url', $url);
			OpenPayU::addOutputConsole('return_uri', $return_uri);
		}

		try
		{
			OpenPayU::setOpenPayuEndPoint($url);
			$json = OpenPayuOAuth::getAccessTokenByCode($code, OpenPayUConfiguration::getClientId(), OpenPayUConfiguration::getClientSecret(), $return_uri);

			$result->setAccessToken($json->{'access_token'});
			if (isset($json->{'payu_user_email'}))
				$result->setPayuUserEmail($json->{'payu_user_email'});
			if (isset($json->{'payu_user_id'}))
				$result->setPayuUserId($json->{'payu_user_id'});
			$result->setExpiresIn($json->{'expires_in'});
			if (isset($json->{'refresh_token'}))
				$result->setRefreshToken($json->{'refresh_token'});
			$result->setSuccess(1);
		}
		catch (Exception $ex)
		{
			$result->setSuccess(0);
			$result->setError($ex->getMessage());
		}

		return $result;
	}

	/**
	 * Function returns authorize by client credentials response
	 * @access public
	 * @param bool $debug
	 * @return OpenPayUResultOAuth $result
	 */
	public static function accessTokenByClientCredentials($debug = true)
	{
		$url = OpenPayUConfiguration::getServiceUrl().'oauth/authorize';

		$result = new OpenPayUResultOAuth();
		$result->setUrl($url);

		OpenPayU::setOpenPayuEndPoint($url);

		if ($debug)
			OpenPayU::addOutputConsole('retrieve accessToken', 'retrieve accessToken, client credentials mode, url: '.$url);

		try
		{
			OpenPayU::setOpenPayuEndPoint($url);
			$json = OpenPayUOAuth::getAccessTokenByClientCredentials(OpenPayUConfiguration::getClientId(), OpenPayUConfiguration::getClientSecret());

			$result->setAccessToken($json->{'access_token'});
			if (isset($json->{'payu_user_email'}))
				$result->setPayuUserEmail($json->{'payu_user_email'});
			if (isset($json->{'payu_user_id'}))
				$result->setPayuUserId($json->{'payu_user_id'});
			$result->setExpiresIn($json->{'expires_in'});
			if (isset($json->{'refresh_token'}))
				$result->setRefreshToken($json->{'refresh_token'});
			$result->setSuccess(1);
		}
		catch (Exception $ex)
		{
			$result->setSuccess(0);
			$result->setError($ex->getMessage());
		}

		return $result;
	}
}
