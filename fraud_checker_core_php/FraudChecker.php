<?php

class FraudChecker {
    private $apiKey;
    private $apiUrl = "https://fraudbd.com/api/check-courier-info";

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    /**
     * Check a phone number for fraud data across multiple couriers.
     * 
     * @param string $phoneNumber
     * @return array
     */
    public function checkNumber($phoneNumber) {
        // Simple validation: check if phone number is provided and looks like a BD number
        if (empty($phoneNumber)) {
            return ["status" => false, "message" => "Phone number is required."];
        }

        $data = array("phone_number" => $phoneNumber);
        $payload = json_encode($data);

        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        // Header configuration
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'api_key: ' . $this->apiKey
        ));

        // For local development where SSL might be an issue (uncomment if needed)
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            return ["status" => false, "message" => "CURL Error: " . $error_msg];
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            return ["status" => false, "message" => "API returned HTTP code: " . $http_code];
        }

        return json_decode($result, true);
    }
}
?>
