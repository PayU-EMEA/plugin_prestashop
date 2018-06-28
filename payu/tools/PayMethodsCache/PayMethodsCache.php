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

    public function isDelayedPaymentAvailable($currency, $version)
    {
        try {
            return $this->isPayTypeEnabled("dp", $currency, $version);
        } catch (Exception $e) {
            return false;
        }
    }

    public function isInstallmentsAvailable($currency, $version)
    {
        try {
            return $this->isPayTypeEnabled("ai", $currency, $version);
        } catch (Exception $e) {
            return false;
        }
    }

    private function isPayTypeEnabled($payTypeStringValue, $currency, $version)
    {
        $payTypeEnabled = false;
        $currentTime = new DateTime();
        $cachedValue = $this->get($payTypeStringValue);
        if ($cachedValue !== null && $cachedValue['valid_to'] > $currentTime) {
            $payTypeEnabled = $cachedValue['enabled'];
        } else {
            $sdkInitializer = new PayUSDKInitializer();
            $sdkInitializer->initializeOpenPayU($currency['iso_code'], $version);
            $payMethods = OpenPayU_Retrieve::payMethods();

            foreach ($payMethods->getResponse()->payByLinks as $payType) {
                if ($payType->value === $payTypeStringValue) {
                    $payTypeEnabled = $payType->status === "ENABLED";
                }
            }

            $validityTime = $currentTime;
            $validityTime->add(new DateInterval('PT1H')); // paymethods are cached for 1h
            $toBeCached = array('enabled' => $payTypeEnabled, 'valid_to' => $validityTime);
            $this->set($payTypeStringValue, $toBeCached);
        }

        return $payTypeEnabled;
    }

    private function get($key)
    {
        $cache = Configuration::get(self::PAYU_PAY_METHODS_CACHE_CONFIG_PREFIX . $key);
        return $cache === false ? null : unserialize($cache);
    }

    private function set($key, $value)
    {
        return Configuration::updateValue(self::PAYU_PAY_METHODS_CACHE_CONFIG_PREFIX . $key, serialize($value));
    }

}