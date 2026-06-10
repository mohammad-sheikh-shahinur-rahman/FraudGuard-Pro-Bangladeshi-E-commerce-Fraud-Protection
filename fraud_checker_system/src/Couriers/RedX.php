<?php

namespace FraudChecker\Couriers;

use FraudChecker\Contracts\CourierInterface;
use FraudChecker\Support\HttpClient;

class RedX implements CourierInterface
{
    private $apiKey;

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'] ?? '';
    }

    public function getName(): string { return 'RedX'; }

    public function getStats(string $phone): array
    {
        $response = HttpClient::post('https://fraudbd.com/api/check-courier-info', [
            'phone_number' => $phone
        ], ['api_key: ' . $this->apiKey]);

        if ($response['status'] === 200 && isset($response['body']['data']['Summaries']['RedX'])) {
            $data = $response['body']['data']['Summaries']['RedX'];
            return [
                'success' => $data['success'] ?? 0,
                'cancel' => $data['cancel'] ?? 0,
                'total' => $data['total'] ?? 0,
                'risk' => ($data['cancel'] > 2) ? 'High' : 'Low'
            ];
        }

        return ['success' => 0, 'cancel' => 0, 'total' => 0, 'risk' => 'Unknown'];
    }
}
