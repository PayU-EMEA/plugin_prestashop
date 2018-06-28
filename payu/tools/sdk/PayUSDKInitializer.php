<?php
/**
 *
 * @author    PayU
 * @copyright Copyright (c) 2014-2018 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(_PS_MODULE_DIR_ . '/payu/tools/sdk/openpayu.php');
include_once(_PS_MODULE_DIR_ . '/payu/tools/PayuOauthCache/OauthCachePresta.php');

class PayUSDKInitializer
{
    public function initializeOpenPayU($currencyIsoCode, $version)
    {
        $prefix = Configuration::get('PAYU_SANDBOX') ? 'SANDBOX_' : '';
        $payuPosId = Tools::unSerialize(Configuration::get($prefix . 'PAYU_MC_POS_ID'));
        $payuSignatureKey = Tools::unSerialize(Configuration::get($prefix . 'PAYU_MC_SIGNATURE_KEY'));
        $payuOauthClientId = Tools::unSerialize(Configuration::get($prefix . 'PAYU_MC_OAUTH_CLIENT_ID'));
        $payuOauthClientSecret = Tools::unSerialize(Configuration::get($prefix . 'PAYU_MC_OAUTH_CLIENT_SECRET'));

        if (!is_array($payuPosId) ||
            !is_array($payuSignatureKey) ||
            !$payuPosId[$currencyIsoCode] ||
            !$payuSignatureKey[$currencyIsoCode]
        ) {
            return false;
        }

        OpenPayU_Configuration::setEnvironment( Configuration::get('PAYU_SANDBOX') ? 'sandbox' : 'secure');
        OpenPayU_Configuration::setMerchantPosId($payuPosId[$currencyIsoCode]);
        OpenPayU_Configuration::setSignatureKey($payuSignatureKey[$currencyIsoCode]);
        if ($payuOauthClientId[$currencyIsoCode] && $payuOauthClientSecret[$currencyIsoCode]) {
            OpenPayU_Configuration::setOauthClientId($payuOauthClientId[$currencyIsoCode]);
            OpenPayU_Configuration::setOauthClientSecret($payuOauthClientSecret[$currencyIsoCode]);
            OpenPayU_Configuration::setOauthTokenCache(new OauthCachePresta());
        }
        OpenPayU_Configuration::setSender($version);

        return true;
    }
}