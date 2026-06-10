<?php

namespace FraudChecker\Couriers;

use FraudChecker\Contracts\CourierInterface;

/**
 * SteadfastDirect Driver
 * Fetches real delivery data directly from Steadfast Official Merchant API.
 */
class SteadfastDirect implements CourierInterface
{
    private $apiKey;
    private $secretKey;
    private $apiUrl = "https://steadfast.com.bd/api/v1/delivery_check/";

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'] ?? '';
        $this->secretKey = $config['secret_key'] ?? '';
    }

    public function getName(): string { return 'Steadfast Official'; }

    public function getStats(string $phone): array
    {
        if (empty($this->apiKey) || empty($this->secretKey)) {
            return ['success' => 0, 'cancel' => 0, 'total' => 0, 'risk' => 'Unknown', 'message' => 'API Keys missing'];
        }

        $ch = curl_init($this->apiUrl . $phone);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Api-Key: {$this->apiKey}",
            "Secret-Key: {$this->secretKey}",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if (isset($data['status']) && $data['status'] === 200) {
                $success = $data['delivery_status']['success'] ?? 0;
                $cancel = $data['delivery_status']['cancelled'] ?? 0;
                return [
                    'success' => $success,
                    'cancel' => $cancel,
                    'total' => $success + $cancel,
                    'risk' => ($cancel > 2) ? 'High' : 'Low'
                ];
            }
        }

        return ['success' => 0, 'cancel' => 0, 'total' => 0, 'risk' => 'Unknown'];
    }
}
