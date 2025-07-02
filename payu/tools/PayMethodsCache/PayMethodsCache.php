<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(_PS_MODULE_DIR_ . '/payu/tools/sdk/openpayu.php');
include_once(_PS_MODULE_DIR_ . '/payu/tools/sdk/PayUSDKInitializer.php');
include_once(_PS_MODULE_DIR_ . '/payu/tools/SimplePayuLogger/SimplePayuLogger.php');
include_once(_PS_MODULE_DIR_ . '/payu/tools/PayMethods/CreditPaymentMethod.php');

class PayMethodsCache
{
    const RETRIEVE_SUCCESS = 'SUCCESS';
    const PAY_BY_LINK_ENABLED = 'ENABLED';
    const PAYU_PAY_METHODS_CACHE_CONFIG_PREFIX = 'PAYU_PAY_METHODS_';
    const ANY_CREDIT_ENABLED_PREFIX = 'ANY_CREDIT_ENABLED_';
    const NO_PAY_METHODS_FOUND = 'No pay methods found on POS: ';

    private static $retrieveCache = [];

    public static function isPaytypeAvailable($paytype, $currency, $lang, $amount, $version, $noCache = false)
    {
        try {
            return self::isPayTypeEnabled($paytype, $currency, $lang, $amount, $version, $noCache);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param array $currency
     * @param string $lang
     * @param string $version
     * @return mixed|OpenPayU_Result|null
     * @throws OpenPayU_Exception
     * @throws OpenPayU_Exception_Configuration
     */
    public static function getPayMethods($currency, $lang, $version)
    {
        $init = static::initializeOpenPayU($currency, $version);
        if (!$init) {
            throw new \Exception('OPU not properly configured for currency: ' . $currency);
        }

        $posId = OpenPayU_Configuration::getMerchantPosId();

        if (isset(static::$retrieveCache[$posId])) {
            return static::$retrieveCache[$posId];
        }

        static::$retrieveCache[$posId] = OpenPayU_Retrieve::payMethods($lang);
        return static::$retrieveCache[$posId];

    }

    /**
     * @param array $retrieve
     * @return array
     */
    public static function extractPayByLinks($retrieve)
    {
        if (!empty($retrieve->getResponse()->payByLinks)) {
            return $retrieve->getResponse()->payByLinks;
        }
        SimplePayuLogger::addLog('retrieve', __FUNCTION__, self::NO_PAY_METHODS_FOUND . OpenPayU_Configuration::getMerchantPosId());
        Logger::addLog('PayU - ' . self::NO_PAY_METHODS_FOUND . OpenPayU_Configuration::getMerchantPosId(), 1);
        return [];
    }

    /**
     * @param array $currency
     * @param string $lang
     * @param string $version
     *
     * @return bool
     */
    public static function isAnyCreditPaytypeEnabled($currency, $lang, $version)
    {
        $init = static::initializeOpenPayU($currency, $version);
        if (!$init) {
            return false;
        }

        $currentTime = new DateTime();
        $cacheKey = self::ANY_CREDIT_ENABLED_PREFIX . OpenPayU_Configuration::getMerchantPosId();
        $cachedValue = self::get($cacheKey);

        if (isset($cachedValue['enabled']) && $cachedValue['valid_to'] > $currentTime) {
            return $cachedValue['enabled'];
        } else {
            try {
                $retrieve = static::getPayMethods($currency, $lang, $version);
                $creditPaytypeFound = false;
                if ($retrieve->getStatus() == self::RETRIEVE_SUCCESS) {
                    foreach (static::extractPayByLinks($retrieve) as $payType) {
                        if ($payType->status === self::PAY_BY_LINK_ENABLED && in_array($payType->value, CreditPaymentMethod::getAll())) {
                            $creditPaytypeFound = true;
                            break;
                        }
                    }
                }
                $toBeCached = array('enabled' => $creditPaytypeFound, 'valid_to' => static::getValidityTime($currentTime));
                self::set($cacheKey, $toBeCached);
                return $creditPaytypeFound;
            } catch (OpenPayU_Exception $e) {
                return false;
            }
        }
    }

    /**
     * @param object $payMethod
     * @param float $amount
     * @return bool
     */
    public static function checkMinMax($payMethod, $amount)
    {
        if (isset($payMethod->minAmount) && $amount * 100 < $payMethod->minAmount) {
            return false;
        }

        if (isset($payMethod->maxAmount) && $amount * 100 > $payMethod->maxAmount) {
            return false;
        }

        return true;
    }

    /**
     * @param array $currency
     * @param string $version
     * @return bool
     */
    private static function initializeOpenPayU($currency, $version)
    {
        $sdkInitializer = new PayUSDKInitializer();
        return $sdkInitializer->initializeOpenPayU($currency['iso_code'], $version);
    }

    private static function isPayTypeEnabled($payTypeStringValue, $currency, $lang, $amount, $version, $noCache)
    {
        $init = static::initializeOpenPayU($currency, $version);
        if (!$init) {
            return false;
        }
        $payTypeEnabled = false;
        $currentTime = new DateTime();
        $cacheKey = OpenPayU_Configuration::getMerchantPosId() . '_' . $payTypeStringValue;
        $cachedValue = self::get($cacheKey);

        if ($noCache !== true && isset($cachedValue['paytype']) && $cachedValue['valid_to'] > $currentTime) {
            $payTypeEnabled = $cachedValue['paytype']->status === self::PAY_BY_LINK_ENABLED && static::checkMinMax($cachedValue['paytype'], $amount);
        } else {
            try {
                $retrieve = static::getPayMethods($currency, $lang, $version);
                if ($retrieve->getStatus() == self::RETRIEVE_SUCCESS) {
                    foreach (static::extractPayByLinks($retrieve) as $payType) {
                        if ($payType->value === $payTypeStringValue) {
                            $payTypeEnabled = $payType->status === self::PAY_BY_LINK_ENABLED && static::checkMinMax($payType, $amount);
                            $toBeCached = array('paytype' => $payType, 'valid_to' => static::getValidityTime($currentTime));
                            self::set($cacheKey, $toBeCached);
                            break;
                        }
                    }
                }
            } catch (OpenPayU_Exception $e) {
                return false;
            }
        }

        return $payTypeEnabled;
    }

    private static function get($key)
    {
        $cache = Configuration::get(self::PAYU_PAY_METHODS_CACHE_CONFIG_PREFIX . $key);
        return $cache === false ? null : unserialize($cache);
    }

    private static function set($key, $value)
    {
        return Configuration::updateValue(self::PAYU_PAY_METHODS_CACHE_CONFIG_PREFIX . $key, serialize($value));
    }

    /**
     * @param DateTime $currentTime
     *
     * @return DateTime
     */
    private static function getValidityTime($currentTime)
    {
        $validityTime = $currentTime;
        $validityTime->add(new DateInterval('PT10M')); // values are cached for 10m
        return $validityTime;
    }

}