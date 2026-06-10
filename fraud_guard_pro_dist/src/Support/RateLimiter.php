<?php

namespace FraudChecker\Support;

class RateLimiter {
    private $cache;
    private $maxRequests;

    public function __construct($config) {
        $this->cache = new LocalCache($config['paths']['cache']);
        $this->maxRequests = $config['security']['rate_limit']['max_requests'] ?? 60;
    }

    public function check($identifier) {
        $key = 'rl_' . md5($identifier . date('Y-m-d-H')); // Key per hour
        $current = $this->cache->get($key) ?: 0;

        if ($current >= $this->maxRequests) {
            return false;
        }

        $this->cache->set($key, $current + 1, 3600);
        return true;
    }
}
