<?php
/**
 * FraudGuard Pro - REST API v1
 * Provides real-time fraud analysis via official courier data.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// 1. Simple Security (In production, check against a DB table of API Keys)
$valid_tokens = ['FG-SECRET-789', 'CLIENT-TOKEN-456'];
$headers = apache_request_headers();
$auth_header = $headers['Authorization'] ?? '';
$token = str_replace('Bearer ', '', $auth_header);

if (!in_array($token, $valid_tokens)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Invalid API Token']);
    exit;
}

// 2. Input Validation
$phone = $_GET['phone'] ?? null;
if (!$phone || !preg_match('/^01[3-9]\d{8}$/', $phone)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Valid 11-digit BD phone number required']);
    exit;
}

// 3. Load Engine
require_once '../../test_core.php';
use FraudChecker\FraudChecker;
use FraudChecker\Couriers\FraudBD;

$checker = new FraudChecker();
$checker->addCourier(new FraudBD(['api_key' => 'YOUR_FRAUDBD_API_KEY']));

// 4. Fetch Data
$result = $checker->check($phone);

// 5. Check Community Blacklist (Optional Logic)
// Here you can query your `community_reports` table

echo json_encode([
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'data' => $result
]);
