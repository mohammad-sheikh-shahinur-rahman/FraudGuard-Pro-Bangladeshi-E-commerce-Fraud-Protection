<?php

namespace FraudChecker\Support;

/**
 * A simple file-based cache for Core PHP.
 * In Laravel, we would use the built-in Cache facade.
 */
class LocalCache
{
    private static $cacheDir = __DIR__ . '/../../storage/cache/';

    public static function set(string $key, $data, int $ttl = 3600)
    {
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0777, true);
        }

        $content = [
            'expires_at' => time() + $ttl,
            'data' => $data
        ];

        file_put_contents(self::$cacheDir . md5($key) . '.json', json_encode($content));
    }

    public static function get(string $key)
    {
        $file = self::$cacheDir . md5($key) . '.json';

        if (!file_exists($file)) {
            return null;
        }

        $content = json_decode(file_get_contents($file), true);

        if (time() > $content['expires_at']) {
            unlink($file); // Expired
            return null;
        }

        return $content['data'];
    }
}
