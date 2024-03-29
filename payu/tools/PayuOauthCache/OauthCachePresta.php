<?php

class OauthCachePresta implements OauthCacheInterface
{
    const PAYU_CACHE_CONFIG_PREFIX = 'PAYU_';

    public function get($key)
    {
        $cache = Configuration::get(self::PAYU_CACHE_CONFIG_PREFIX . $key);
        if ($cache !== false) {
            $cache = @unserialize($cache);
        }
        return $cache === false ? null : $cache;
    }

    public function set($key, $value)
    {
        return Configuration::updateValue(self::PAYU_CACHE_CONFIG_PREFIX . $key, serialize($value));
    }

    public function invalidate($key)
    {
        return Configuration::deleteByName(self::PAYU_CACHE_CONFIG_PREFIX . $key);
    }

}