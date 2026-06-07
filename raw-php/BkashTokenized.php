<?php

class BkashTokenized {
    private $appKey;
    private $appSecret;
    private $username;
    private $password;
    private $baseUrl;
    private $isSandbox;
    private $logs = [];

    public function __construct($config) {
        $this->isSandbox = isset($config['sandbox']) ? (bool)$config['sandbox'] : true;
        $this->appKey = $config['app_key'] ?? '';
        $this->appSecret = $config['app_secret'] ?? '';
        $this->username = $config['username'] ?? '';
        $this->password = $config['password'] ?? '';
        
        $this->baseUrl = $this->isSandbox 
            ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta' 
            : 'https://tokenized.pay.bka.sh/v1.2.0-beta';
    }

    /**
     * Perform a HTTP request using curl and log details.
     */
    private function request($endpoint, $method = 'POST', $headers = [], $body = null) {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init($url);

        $defaultHeaders = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        $allHeaders = array_merge($defaultHeaders, $headers);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) {
                $jsonBody = is_array($body) ? json_encode($body) : $body;
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);

        // For local development on XAMPP, disable SSL verification if it causes issues, but keep enabled by default.
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

        $startTime = microtime(true);
        $response = curl_exec($ch);
        $endTime = microtime(true);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $executionTime = round(($endTime - $startTime) * 1000, 2);

        // Sanitize sensitive info in logs
        $sanitizedHeaders = [];
        foreach ($allHeaders as $h) {
            if (stripos($h, 'password:') === 0) {
                $sanitizedHeaders[] = 'password: [REDACTED]';
            } elseif (stripos($h, 'Authorization:') === 0) {
                $sanitizedHeaders[] = 'Authorization: Bearer ' . substr($h, 22, 15) . '...[REDACTED]';
            } else {
                $sanitizedHeaders[] = $h;
            }
        }

        $sanitizedBody = $body;
        if (is_array($body)) {
            if (isset($body['app_secret'])) $sanitizedBody['app_secret'] = '[REDACTED]';
            if (isset($body['app_key'])) $sanitizedBody['app_key'] = '[REDACTED]';
            if (isset($body['refresh_token'])) $sanitizedBody['refresh_token'] = substr($body['refresh_token'], 0, 15) . '...[REDACTED]';
        }

        $logEntry = [
            'url' => $url,
            'method' => $method,
            'headers' => $sanitizedHeaders,
            'request_body' => $sanitizedBody,
            'response_code' => $httpCode,
            'response_body' => json_decode($response, true) ?? $response,
            'error' => $error,
            'execution_time_ms' => $executionTime,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        $this->logs[] = $logEntry;

        // If session is active, append to session logs
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!isset($_SESSION['bkash_logs'])) {
                $_SESSION['bkash_logs'] = [];
            }
            $_SESSION['bkash_logs'][] = $logEntry;
        }

        if ($response === false) {
            return [
                'statusCode' => '9999',
                'statusMessage' => 'CURL Error: ' . $error
            ];
        }

        $decoded = json_decode($response, true);
        return $decoded ?: [
            'statusCode' => '9998',
            'statusMessage' => 'Invalid JSON response from bKash: ' . $response
        ];
    }

    /**
     * Get access token (checks session or fetches a new one / refreshes it)
     */
    public function getAccessToken() {
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['bkash_token'])) {
            $tokenInfo = $_SESSION['bkash_token'];
            // Check if token is still valid (expiry time has a 5-minute safety margin)
            if ($tokenInfo['expires_at'] > time() + 300) {
                return $tokenInfo['id_token'];
            }
            
            // Try refreshing
            if (!empty($tokenInfo['refresh_token'])) {
                $result = $this->refreshToken($tokenInfo['refresh_token']);
                if (isset($result['statusCode']) && $result['statusCode'] === '0000' && !empty($result['id_token'])) {
                    return $result['id_token'];
                }
            }
        }

        // Grant new token if none existed or refresh failed
        $result = $this->grantToken();
        if (isset($result['statusCode']) && $result['statusCode'] === '0000' && !empty($result['id_token'])) {
            return $result['id_token'];
        }

        return false;
    }

    /**
     * Call Grant Token API
     */
    public function grantToken() {
        $headers = [
            'username: ' . $this->username,
            'password: ' . $this->password
        ];
        $body = [
            'app_key' => $this->appKey,
            'app_secret' => $this->appSecret
        ];

        $response = $this->request('/tokenized/checkout/token/grant', 'POST', $headers, $body);

        if (isset($response['id_token'])) {
            $response['statusCode'] = '0000';
            $response['statusMessage'] = 'Successful';

            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['bkash_token'] = [
                    'id_token' => $response['id_token'],
                    'refresh_token' => $response['refresh_token'] ?? '',
                    'expires_at' => time() + ($response['expires_in'] ?? 3600)
                ];
            }
        }

        return $response;
    }

    /**
     * Call Refresh Token API
     */
    public function refreshToken($refreshToken) {
        $headers = [
            'username: ' . $this->username,
            'password: ' . $this->password
        ];
        $body = [
            'app_key' => $this->appKey,
            'app_secret' => $this->appSecret,
            'refresh_token' => $refreshToken
        ];

        $response = $this->request('/tokenized/checkout/token/refresh', 'POST', $headers, $body);

        if (isset($response['id_token'])) {
            $response['statusCode'] = '0000';
            $response['statusMessage'] = 'Successful';

            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['bkash_token'] = [
                    'id_token' => $response['id_token'],
                    'refresh_token' => $response['refresh_token'] ?? $refreshToken,
                    'expires_at' => time() + ($response['expires_in'] ?? 3600)
                ];
            }
        }

        return $response;
    }

    /**
     * Create Payment
     */
    public function createPayment($amount, $invoiceNumber, $callbackUrl, $payerReference = '01770618575') {
        $idToken = $this->getAccessToken();
        if (!$idToken) {
            return [
                'statusCode' => '9997',
                'statusMessage' => 'Authentication Failed: Could not get a valid access token.'
            ];
        }

        $headers = [
            'Authorization: ' . $idToken,
            'X-App-Key: ' . $this->appKey
        ];

        $body = [
            'mode' => '0011',
            'payerReference' => $payerReference,
            'callbackURL' => $callbackUrl,
            'amount' => number_format((float)$amount, 2, '.', ''),
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => $invoiceNumber
        ];

        return $this->request('/tokenized/checkout/create', 'POST', $headers, $body);
    }

    /**
     * Execute Payment
     */
    public function executePayment($paymentID) {
        $idToken = $this->getAccessToken();
        if (!$idToken) {
            return [
                'statusCode' => '9997',
                'statusMessage' => 'Authentication Failed: Could not get a valid access token.'
            ];
        }

        $headers = [
            'Authorization: ' . $idToken,
            'X-App-Key: ' . $this->appKey
        ];

        $body = [
            'paymentID' => $paymentID
        ];

        return $this->request('/tokenized/checkout/execute', 'POST', $headers, $body);
    }

    /**
     * Query Payment Status
     */
    public function queryPayment($paymentID) {
        $idToken = $this->getAccessToken();
        if (!$idToken) {
            return [
                'statusCode' => '9997',
                'statusMessage' => 'Authentication Failed: Could not get a valid access token.'
            ];
        }

        $headers = [
            'Authorization: ' . $idToken,
            'X-App-Key: ' . $this->appKey
        ];

        $body = [
            'paymentID' => $paymentID
        ];

        return $this->request('/tokenized/checkout/payment/status', 'POST', $headers, $body);
    }

    /**
     * Refund Payment
     */
    public function refundPayment($paymentID, $amount, $trxID, $reason = 'Customer Request', $sku = 'default') {
        $idToken = $this->getAccessToken();
        if (!$idToken) {
            return [
                'statusCode' => '9997',
                'statusMessage' => 'Authentication Failed: Could not get a valid access token.'
            ];
        }

        $headers = [
            'Authorization: ' . $idToken,
            'X-App-Key: ' . $this->appKey
        ];

        $body = [
            'paymentID' => $paymentID,
            'amount' => number_format((float)$amount, 2, '.', ''),
            'trxID' => $trxID,
            'sku' => $sku,
            'reason' => $reason
        ];

        return $this->request('/tokenized/checkout/payment/refund', 'POST', $headers, $body);
    }

    /**
     * Create Agreement (Wallet Tokenization Request)
     */
    public function createAgreement($payerReference, $callbackUrl) {
        $idToken = $this->getAccessToken();
        if (!$idToken) {
            return [
                'statusCode' => '9997',
                'statusMessage' => 'Authentication Failed: Could not get a valid access token.'
            ];
        }

        $headers = [
            'Authorization: ' . $idToken,
            'X-App-Key: ' . $this->appKey
        ];

        $body = [
            'mode' => '0001', // Agreement mode
            'payerReference' => $payerReference,
            'callbackURL' => $callbackUrl
        ];

        return $this->request('/tokenized/checkout/create', 'POST', $headers, $body);
    }

    /**
     * Create Payment under existing Agreement (Simplified checkout)
     */
    public function createPaymentWithAgreement($agreementID, $amount, $invoiceNumber, $callbackUrl, $payerReference = '01770618575') {
        $idToken = $this->getAccessToken();
        if (!$idToken) {
            return [
                'statusCode' => '9997',
                'statusMessage' => 'Authentication Failed: Could not get a valid access token.'
            ];
        }

        $headers = [
            'Authorization: ' . $idToken,
            'X-App-Key: ' . $this->appKey
        ];

        $body = [
            'mode' => '0011',
            'agreementID' => $agreementID,
            'payerReference' => $payerReference,
            'callbackURL' => $callbackUrl,
            'amount' => number_format((float)$amount, 2, '.', ''),
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => $invoiceNumber
        ];

        return $this->request('/tokenized/checkout/create', 'POST', $headers, $body);
    }

    /**
     * Search Transaction Details by trxID
     */
    public function searchTransaction($trxID) {
        $idToken = $this->getAccessToken();
        if (!$idToken) {
            return [
                'statusCode' => '9997',
                'statusMessage' => 'Authentication Failed'
            ];
        }

        $headers = [
            'Authorization: ' . $idToken,
            'X-App-Key: ' . $this->appKey
        ];

        $body = [
            'trxID' => $trxID
        ];

        return $this->request('/tokenized/checkout/general/searchTransaction', 'POST', $headers, $body);
    }

    /**
     * Query Agreement Status
     */
    public function queryAgreement($agreementID) {
        $idToken = $this->getAccessToken();
        if (!$idToken) {
            return [
                'statusCode' => '9997',
                'statusMessage' => 'Authentication Failed'
            ];
        }

        $headers = [
            'Authorization: ' . $idToken,
            'X-App-Key: ' . $this->appKey
        ];

        $body = [
            'agreementID' => $agreementID
        ];

        return $this->request('/tokenized/checkout/agreement/status', 'POST', $headers, $body);
    }

    /**
     * Cancel Agreement
     */
    public function cancelAgreement($agreementID) {
        $idToken = $this->getAccessToken();
        if (!$idToken) {
            return [
                'statusCode' => '9997',
                'statusMessage' => 'Authentication Failed'
            ];
        }

        $headers = [
            'Authorization: ' . $idToken,
            'X-App-Key: ' . $this->appKey
        ];

        $body = [
            'agreementID' => $agreementID
        ];

        return $this->request('/tokenized/checkout/agreement/cancel', 'POST', $headers, $body);
    }

    /**
     * Get logs for current instance execution.
     */
    public function getLogs() {
        return $this->logs;
    }
}

