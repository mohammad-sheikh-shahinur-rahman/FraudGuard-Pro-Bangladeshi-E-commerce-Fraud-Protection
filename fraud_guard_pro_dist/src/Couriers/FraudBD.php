<?php

namespace FraudChecker\Couriers;

use FraudChecker\Contracts\CourierInterface;
use FraudChecker\Support\HttpClient;

/**
 * FraudBD Master Driver
 * Fetches aggregated data from multiple couriers via FraudBD.com API.
 */
class FraudBD implements CourierInterface
{
    private $apiKey;
    private $apiUrl = "https://fraudbd.com/api/check-courier-info";

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'] ?? '';
    }

    public function getName(): string { return 'FraudBD'; }

    public function getStats(string $phone): array
    {
        if (empty($this->apiKey) || $this->apiKey === 'YOUR_FRAUDBD_API_KEY') {
            return ['error' => 'Valid FraudBD API Key is required'];
        }

        $response = HttpClient::post($this->apiUrl, 
            ['phone_number' => $phone], 
            ['api_key: ' . $this->apiKey]
        );

        if ($response['status'] === 200 && isset($response['body']['data'])) {
            $data = $response['body']['data'];
            
            // Format the data to match our system's expected structure
            // FraudBD returns 'Summaries' for individual couriers and 'totalSummary' for aggregate
            return [
                'is_master' => true,
                'summaries' => $data['Summaries'] ?? [],
                'success' => $data['totalSummary']['success'] ?? 0,
                'cancel' => $data['totalSummary']['cancel'] ?? 0,
                'total' => $data['totalSummary']['total'] ?? 0,
                'success_rate' => $data['totalSummary']['successRate'] ?? 0
            ];
        }

        return ['success' => 0, 'cancel' => 0, 'total' => 0];
    }
}
