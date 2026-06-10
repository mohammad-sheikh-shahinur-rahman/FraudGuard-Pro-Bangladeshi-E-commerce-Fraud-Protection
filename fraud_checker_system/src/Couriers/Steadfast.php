<?php

namespace FraudChecker\Couriers;

use FraudChecker\Contracts\CourierInterface;
use FraudChecker\Support\HttpClient;

class Steadfast implements CourierInterface
{
    private $apiKey;
    private $secretKey;

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'] ?? '';
        $this->secretKey = $config['secret_key'] ?? '';
    }

    public function getName(): string { return 'Steadfast'; }

    public function getStats(string $phone): array
    {
        // Example implementation using FraudBD or Steadfast direct API
        // For this system, we use the unified FraudBD approach as an example
        $response = HttpClient::post('https://fraudbd.com/api/check-courier-info', [
            'phone_number' => $phone
        ], ['api_key: ' . $this->apiKey]);

        if ($response['status'] === 200 && isset($response['body']['data']['Summaries']['Steadfast'])) {
            $data = $response['body']['data']['Summaries']['Steadfast'];
            return [
                'success' => $data['success'] ?? 0,
                'cancel' => $data['cancel'] ?? 0,
                'total' => $data['total'] ?? 0,
                'risk' => ($data['cancel'] > 3) ? 'High' : 'Low'
            ];
        }

        return ['success' => 0, 'cancel' => 0, 'total' => 0, 'risk' => 'Unknown'];
    }
}
