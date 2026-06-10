<?php

namespace FraudChecker\Couriers;

use FraudChecker\Contracts\CourierInterface;
use FraudChecker\Support\HttpClient;

class Paperfly implements CourierInterface
{
    private $apiKey;

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'] ?? '';
    }

    public function getName(): string { return 'Paperfly'; }

    public function getStats(string $phone): array
    {
        // For now, Paperfly data is also aggregated via FraudBD in this implementation
        $response = HttpClient::post('https://fraudbd.com/api/check-courier-info', [
            'phone_number' => $phone
        ], ['api_key: ' . $this->apiKey]);

        if ($response['status'] === 200 && isset($response['body']['data']['Summaries']['Paperfly'])) {
            $data = $response['body']['data']['Summaries']['Paperfly'];
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
