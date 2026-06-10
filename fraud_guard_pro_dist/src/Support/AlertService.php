<?php

namespace FraudChecker\Support;

class AlertService {
    private $config;

    public function __construct($config) {
        $this->config = $config['notifications'];
    }

    public function trigger($phone, $aggregate) {
        if (!$this->config['enabled']) return;

        $message = "🚨 *Fraud Alert!* \nPhone: {$phone}\nRisk: {$aggregate['risk_level']}\nSuccess Rate: {$aggregate['success_rate']}%\nRecommendation: {$aggregate['recommendation']}";

        // 1. Webhook Alert (e.g., Slack)
        if (!empty($this->config['webhook_url'])) {
            HttpClient::post($this->config['webhook_url'], [
                'text' => $message,
                'username' => 'FraudGuard Bot'
            ]);
        }

        // 2. Email Alert
        if (!empty($this->config['admin_email'])) {
            $subject = "Fraud Alert: High Risk Detected ($phone)";
            $headers = "From: no-reply@fraudguard.pro";
            @mail($this->config['admin_email'], $subject, strip_tags($message), $headers);
        }
    }
}
