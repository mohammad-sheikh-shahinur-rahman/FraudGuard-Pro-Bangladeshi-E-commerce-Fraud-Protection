<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BkashService
{
    protected $baseUrl;
    protected $appKey;
    protected $appSecret;
    protected $username;
    protected $password;

    public function __construct()
    {
        $this->appKey = config('bkash.app_key');
        $this->appSecret = config('bkash.app_secret');
        $this->username = config('bkash.username');
        $this->password = config('bkash.password');
        
        $this->baseUrl = config('bkash.sandbox')
            ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta'
            : 'https://tokenized.pay.bka.sh/v1.2.0-beta';
    }

    /**
     * Helper to perform HTTP request to bKash with credentials and log errors.
     */
    private function request($endpoint, $body, $headers = [])
    {
        $defaultHeaders = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        try {
            $response = Http::withHeaders(array_merge($defaultHeaders, $headers))
                ->post($this->baseUrl . $endpoint, $body);

            if ($response->failed()) {
                Log::error("bKash API Error: " . $response->body());
                return ['statusCode' => '9999', 'statusMessage' => 'HTTP Request Failed'];
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("bKash Connection Exception: " . $e->getMessage());
            return ['statusCode' => '9999', 'statusMessage' => $e->getMessage()];
        }
    }

    /**
     * Grant Token API (v1.2.0-beta)
     */
    public function grantToken()
    {
        $headers = [
            'username' => $this->username,
            'password' => $this->password
        ];
        
        $body = [
            'app_key' => $this->appKey,
            'app_secret' => $this->appSecret
        ];

        $response = $this->request('/tokenized/checkout/token/grant', $body, $headers);

        if (isset($response['id_token'])) {
            // Cache token to avoid repeated calls within its 1-hour lifetime
            Cache::put('bkash_id_token', $response['id_token'], now()->addSeconds($response['expires_in'] - 300));
            Cache::put('bkash_refresh_token', $response['refresh_token'], now()->addDays(28));
            return $response['id_token'];
        }

        return false;
    }

    /**
     * Refresh Token API
     */
    public function refreshToken($refreshToken)
    {
        $headers = [
            'username' => $this->username,
            'password' => $this->password
        ];
        
        $body = [
            'app_key' => $this->appKey,
            'app_secret' => $this->appSecret,
            'refresh_token' => $refreshToken
        ];

        $response = $this->request('/tokenized/checkout/token/refresh', $body, $headers);

        if (isset($response['id_token'])) {
            Cache::put('bkash_id_token', $response['id_token'], now()->addSeconds($response['expires_in'] - 300));
            Cache::put('bkash_refresh_token', $response['refresh_token'], now()->addDays(28));
            return $response['id_token'];
        }

        return false;
    }

    /**
     * Helper to get cached or fresh access token.
     */
    public function getAccessToken()
    {
        if (Cache::has('bkash_id_token')) {
            return Cache::get('bkash_id_token');
        }

        if (Cache::has('bkash_refresh_token')) {
            $token = $this->refreshToken(Cache::get('bkash_refresh_token'));
            if ($token) return $token;
        }

        return $this->grantToken();
    }

    /**
     * Create Checkout Payment
     */
    public function createPayment($amount, $invoiceNumber, $payerReference = '01770618575')
    {
        $token = $this->getAccessToken();
        if (!$token) return ['statusCode' => '9997', 'statusMessage' => 'Authentication Failed'];

        $headers = [
            'Authorization' => $token,
            'X-App-Key' => $this->appKey
        ];

        $body = [
            'mode' => '0011',
            'payerReference' => $payerReference,
            'callbackURL' => url(config('bkash.callback_url')),
            'amount' => number_format((float)$amount, 2, '.', ''),
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => $invoiceNumber
        ];

        return $this->request('/tokenized/checkout/create', $body, $headers);
    }

    /**
     * Execute Payment after OTP/PIN redirect
     */
    public function executePayment($paymentID)
    {
        $token = $this->getAccessToken();
        if (!$token) return ['statusCode' => '9997', 'statusMessage' => 'Authentication Failed'];

        $headers = [
            'Authorization' => $token,
            'X-App-Key' => $this->appKey
        ];

        $body = [
            'paymentID' => $paymentID
        ];

        return $this->request('/tokenized/checkout/execute', $body, $headers);
    }

    /**
     * Query Payment Status
     */
    public function queryPayment($paymentID)
    {
        $token = $this->getAccessToken();
        if (!$token) return ['statusCode' => '9997', 'statusMessage' => 'Authentication Failed'];

        $headers = [
            'Authorization' => $token,
            'X-App-Key' => $this->appKey
        ];

        $body = [
            'paymentID' => $paymentID
        ];

        return $this->request('/tokenized/checkout/payment/status', $body, $headers);
    }

    /**
     * Refund Transaction
     */
    public function refundPayment($paymentID, $amount, $trxID, $reason = 'Customer Request')
    {
        $token = $this->getAccessToken();
        if (!$token) return ['statusCode' => '9997', 'statusMessage' => 'Authentication Failed'];

        $headers = [
            'Authorization' => $token,
            'X-App-Key' => $this->appKey
        ];

        $body = [
            'paymentID' => $paymentID,
            'amount' => number_format((float)$amount, 2, '.', ''),
            'trxID' => $trxID,
            'sku' => 'default',
            'reason' => $reason
        ];

        return $this->request('/tokenized/checkout/payment/refund', $body, $headers);
    }

    /**
     * Create Agreement (Wallet Tokenization Request)
     */
    public function createAgreement($payerReference)
    {
        $token = $this->getAccessToken();
        if (!$token) return ['statusCode' => '9997', 'statusMessage' => 'Authentication Failed'];

        $headers = [
            'Authorization' => $token,
            'X-App-Key' => $this->appKey
        ];

        $body = [
            'mode' => '0001', // Agreement mode
            'payerReference' => $payerReference,
            'callbackURL' => url(config('bkash.callback_url'))
        ];

        return $this->request('/tokenized/checkout/create', $body, $headers);
    }

    /**
     * Create Payment under existing Agreement (Simplified checkout)
     */
    public function createPaymentWithAgreement($agreementID, $amount, $invoiceNumber, $payerReference = '01770618575')
    {
        $token = $this->getAccessToken();
        if (!$token) return ['statusCode' => '9997', 'statusMessage' => 'Authentication Failed'];

        $headers = [
            'Authorization' => $token,
            'X-App-Key' => $this->appKey
        ];

        $body = [
            'mode' => '0011',
            'agreementID' => $agreementID,
            'payerReference' => $payerReference,
            'callbackURL' => url(config('bkash.callback_url')),
            'amount' => number_format((float)$amount, 2, '.', ''),
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => $invoiceNumber
        ];

        return $this->request('/tokenized/checkout/create', $body, $headers);
    }

    /**
     * Search Transaction Details by trxID
     */
    public function searchTransaction($trxID)
    {
        $token = $this->getAccessToken();
        if (!$token) return ['statusCode' => '9997', 'statusMessage' => 'Authentication Failed'];

        $headers = [
            'Authorization' => $token,
            'X-App-Key' => $this->appKey
        ];

        $body = ['trxID' => $trxID];

        return $this->request('/tokenized/checkout/general/searchTransaction', $body, $headers);
    }

    /**
     * Query Agreement Status
     */
    public function queryAgreement($agreementID)
    {
        $token = $this->getAccessToken();
        if (!$token) return ['statusCode' => '9997', 'statusMessage' => 'Authentication Failed'];

        $headers = [
            'Authorization' => $token,
            'X-App-Key' => $this->appKey
        ];

        $body = ['agreementID' => $agreementID];

        return $this->request('/tokenized/checkout/agreement/status', $body, $headers);
    }

    /**
     * Cancel Agreement
     */
    public function cancelAgreement($agreementID)
    {
        $token = $this->getAccessToken();
        if (!$token) return ['statusCode' => '9997', 'statusMessage' => 'Authentication Failed'];

        $headers = [
            'Authorization' => $token,
            'X-App-Key' => $this->appKey
        ];

        $body = ['agreementID' => $agreementID];

        return $this->request('/tokenized/checkout/agreement/cancel', $body, $headers);
    }
}

