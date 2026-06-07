<?php
require_once 'db.php';

use Shahinur\Bkash\BkashDb;

// Accept JSON payload from bKash server
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

// Setup log entry
$logEntry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'headers' => getallheaders(),
    'payload' => $data ?? $payload,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
];

// Append to a local file-based log for the developer console
$logFile = __DIR__ . '/ipn_log.json';
$logs = [];
if (file_exists($logFile)) {
    $logs = json_decode(file_get_contents($logFile), true) ?: [];
}
// Keep last 20 webhooks
array_unshift($logs, $logEntry);
$logs = array_slice($logs, 0, 20);
file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));

if ($data && isset($data['paymentID'])) {
    $paymentID = $data['paymentID'];
    $trxID = $data['trxID'] ?? '';
    $status = $data['transactionStatus'] ?? '';

    // If payment executed successfully via IPN
    if ($status === 'Completed' && $paymentID && $trxID) {
        BkashDb::completePaymentRecord($paymentID, $trxID);
    } elseif ($status === 'Refunded' && $paymentID) {
        BkashDb::refundPaymentRecord($paymentID, $data['refundTrxID'] ?? '');
    } elseif ($paymentID && $status) {
        BkashDb::updateStatus($paymentID, $status);
    }

    // Acknowledge receipt to bKash
    header('Content-Type: application/json');
    echo json_encode([
        'statusCode' => '0000',
        'statusMessage' => 'IPN Received Successfully'
    ]);
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode([
        'statusCode' => '9999',
        'statusMessage' => 'Invalid Webhook Payload'
    ]);
}
