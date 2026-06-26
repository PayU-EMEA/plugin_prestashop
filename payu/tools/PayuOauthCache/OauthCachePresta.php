<?php

class OauthCachePresta implements OauthCacheInterface
{
    private const PAYU_CACHE_CONFIG_PREFIX = 'PAYU_';

    public function get(string $key): ?OauthResultClientCredentials
    {
        $cache = Configuration::get(self::PAYU_CACHE_CONFIG_PREFIX . $key);
        if ($cache !== false) {
            try {
                $cache = unserialize(
                    $cache,
                    ['allowed_classes' => [OauthResultClientCredentials::class, \DateTime::class]]
                );
            } catch (\Throwable $e) {
                $cache = false;
            }
        }

        return $cache === false ? null : $cache;
    }

    public function set(string $key, OauthResultClientCredentials $value): bool
    {
        return Configuration::updateValue(self::PAYU_CACHE_CONFIG_PREFIX . $key, serialize($value));
    }

    public function invalidate(string $key): bool
    {
        return Configuration::deleteByName(self::PAYU_CACHE_CONFIG_PREFIX . $key);
    }

}