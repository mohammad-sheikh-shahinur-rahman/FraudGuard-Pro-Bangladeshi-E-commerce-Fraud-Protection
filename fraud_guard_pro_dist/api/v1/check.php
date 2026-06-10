<?php
/**
 * FraudGuard Pro - REST API v1
 * Provides real-time fraud analysis via official courier data.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// 1. Load Composer Autoloader & Environment
require_once __DIR__ . '/../../vendor/autoload.php';

if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();
}

// 2. Load Configuration
$config = require_once '../../config/settings.php';

// 2. Security Check (Token Validation)
$valid_tokens = $config['security']['tokens'];
$headers = apache_request_headers();
$auth_header = $headers['Authorization'] ?? '';
$token = str_replace('Bearer ', '', $auth_header);

if (!in_array($token, $valid_tokens)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Invalid API Token']);
    exit;
}

// 3. Rate Limiting
use FraudChecker\Support\RateLimiter;

$limiter = new RateLimiter($config);
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!$limiter->check($ip)) {
    http_response_code(429);
    echo json_encode(['status' => 'error', 'message' => 'Too Many Requests: Rate limit exceeded']);
    exit;
}

// 4. Input Validation
$phone = $_GET['phone'] ?? null;
if (!$phone || !preg_match('/^01[3-9]\d{8}$/', $phone)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Valid 11-digit BD phone number required']);
    exit;
}

// 4. Load Engine
use FraudChecker\FraudChecker;
use FraudChecker\Couriers\FraudBD;

$checker = new FraudChecker();
$checker->addCourier(new FraudBD($config['api']['fraudbd']));

// 5. Fetch Data
$result = $checker->check($phone);

// 5. Check Community Blacklist (Optional Logic)
// Here you can query your `community_reports` table

echo json_encode([
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'data' => $result
]);
