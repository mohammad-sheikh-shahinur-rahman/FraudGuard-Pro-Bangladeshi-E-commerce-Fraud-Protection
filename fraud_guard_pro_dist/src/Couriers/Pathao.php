<?php

namespace FraudChecker\Couriers;

use FraudChecker\Contracts\CourierInterface;
use FraudChecker\Support\HttpClient;

class Pathao implements CourierInterface
{
    private $apiKey;

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'] ?? '';
    }

    public function getName(): string { return 'Pathao'; }

    public function getStats(string $phone): array
    {
        $response = HttpClient::post('https://fraudbd.com/api/check-courier-info', [
            'phone_number' => $phone
        ], ['api_key: ' . $this->apiKey]);

        if ($response['status'] === 200 && isset($response['body']['data']['Summaries']['Pathao'])) {
            $data = $response['body']['data']['Summaries']['Pathao'];
            
            // Pathao usually gives a rating rather than counts in some APIs
            return [
                'rating' => $data['customer_rating'] ?? 'N/A',
                'risk' => $data['risk_level'] ?? 'Unknown',
                'message' => $data['message'] ?? '',
                'success' => $data['success'] ?? 0,
                'cancel' => $data['cancel'] ?? 0,
                'total' => $data['total'] ?? 0,
            ];
        }

        return ['risk' => 'Unknown', 'success' => 0, 'cancel' => 0];
    }
}
