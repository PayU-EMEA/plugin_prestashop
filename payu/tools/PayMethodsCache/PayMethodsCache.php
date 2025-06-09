<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(_PS_MODULE_DIR_ . '/payu/tools/sdk/openpayu.php');
include_once(_PS_MODULE_DIR_ . '/payu/tools/sdk/PayUSDKInitializer.php');
include_once(_PS_MODULE_DIR_ . '/payu/tools/SimplePayuLogger/SimplePayuLogger.php');
include_once(_PS_MODULE_DIR_ . '/payu/tools/sdk/OpenPayU/Model/CreditPaymentMethod.php');

class PayMethodsCache
{
    const RETRIEVE_SUCCESS = 'SUCCESS';
    const PAY_BY_LINK_ENABLED = 'ENABLED';
    const PAYU_PAY_METHODS_CACHE_CONFIG_PREFIX = 'PAYU_PAY_METHODS_';

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
    public static function getPaymethods($currency, $lang, $version) {
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
        $retrieve = static::getPaymethods($currency, $lang, $version);
        if ($retrieve->getStatus() == self::RETRIEVE_SUCCESS) {
            $creditPaytypes = array_filter($retrieve->getResponse()->payByLinks, function($pbl) {
                return $pbl->status === self::PAY_BY_LINK_ENABLED && in_array($pbl->value, CreditPaymentMethod::getAll());
            });
            return count($creditPaytypes) > 0;
        }
        return false;
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
                $retrieve = static::getPaymethods($currency, $lang, $version);
                if ($retrieve->getStatus() == self::RETRIEVE_SUCCESS) {
                    foreach ($retrieve->getResponse()->payByLinks as $payType) {
                        if ($payType->value === $payTypeStringValue) {
                            $payTypeEnabled = $payType->status === self::PAY_BY_LINK_ENABLED && static::checkMinMax($payType, $amount);

                            $validityTime = $currentTime;
                            $validityTime->add(new DateInterval('PT10M')); // paymethods are cached for 10m
                            $toBeCached = array('paytype' => $payType, 'valid_to' => $validityTime);
                            self::set($cacheKey, $toBeCached);
                            break;
                        }
                    }
                } else {
                    return false;
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

}