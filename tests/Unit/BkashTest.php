<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use BkashTokenized;

class BkashTest extends TestCase
{
    protected $config;

    protected function setUp(): void
    {
        $this->config = [
            'sandbox' => true,
            'app_key' => 'test_key',
            'app_secret' => 'test_secret',
            'username' => 'test_user',
            'password' => 'test_pass'
        ];
    }

    public function test_can_instantiate_bkash_service()
    {
        $bkash = new BkashTokenized($this->config);
        $this->assertInstanceOf(BkashTokenized::class, $bkash);
    }

    public function test_can_get_logs()
    {
        $bkash = new BkashTokenized($this->config);
        $this->assertIsArray($bkash->getLogs());
    }
}
