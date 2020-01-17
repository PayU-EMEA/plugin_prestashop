<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(_PS_MODULE_DIR_ . '/payu/tools/sdk/openpayu.php');
include_once(_PS_MODULE_DIR_ . '/payu/tools/sdk/PayUSDKInitializer.php');
include_once(_PS_MODULE_DIR_ . '/payu/tools/SimplePayuLogger/SimplePayuLogger.php');

class PayMethodsCache
{
    const PAYU_PAY_METHODS_CACHE_CONFIG_PREFIX = 'PAYU_PAY_METHODS_';

    public static function isDelayedPaymentAvailable($currency, $version)
    {
        return self::isPaytypeAvailable('dp', $currency, $version);

    }

    public static function isInstallmentsAvailable($currency, $version)
    {
        return self::isPaytypeAvailable('ai', $currency, $version);
    }

    public static function isPaytypeAvailable($paytype, $currency, $version, $noCache = false)
    {
        try {
            return self::isPayTypeEnabled($paytype, $currency, $version, $noCache);
        } catch (Exception $e) {
            return false;
        }
    }

    private static function isPayTypeEnabled($payTypeStringValue, $currency, $version, $noCache)
    {
        $payTypeEnabled = false;
        $currentTime = new DateTime();

        $sdkInitializer = new PayUSDKInitializer();
        $sdkInitializer->initializeOpenPayU($currency['iso_code'], $version);
        $cacheKey = OpenPayU_Configuration::getMerchantPosId() . '_' . $payTypeStringValue;

        $cachedValue = self::get($cacheKey);
        if ($noCache !== true && $cachedValue !== null && $cachedValue['valid_to'] > $currentTime) {
            $payTypeEnabled = $cachedValue['enabled'];
        } else {
            $payMethods = OpenPayU_Retrieve::payMethods();

            foreach ($payMethods->getResponse()->payByLinks as $payType) {
                if ($payType->value === $payTypeStringValue) {
                    $payTypeEnabled = $payType->status === "ENABLED";
                }
            }

            $validityTime = $currentTime;
            $validityTime->add(new DateInterval('PT15M')); // paymethods are cached for 15m
            $toBeCached = array('enabled' => $payTypeEnabled, 'valid_to' => $validityTime);
            self::set($cacheKey, $toBeCached);
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