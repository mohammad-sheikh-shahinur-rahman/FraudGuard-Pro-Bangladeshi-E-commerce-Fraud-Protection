<?php

/**
 * Real API Bridge for the Dashboard
 * This file connects to official courier APIs for real data.
 */

// 1. Load Composer Autoloader & Environment
require_once __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// 2. Load Configuration
$config = require_once 'config/settings.php';

// 3. Security: Bearer Token Validation
$headers = array_change_key_case(getallheaders(), CASE_LOWER);
$authHeader = $headers['authorization'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);

if (!in_array($token, $config['security']['tokens'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized: Invalid API Token']);
    exit;
}

use FraudChecker\FraudChecker;
use FraudChecker\Couriers\FraudBD;
use FraudChecker\Support\HistoryService;
use FraudChecker\Support\RateLimiter;

use FraudChecker\Support\AlertService;

header('Content-Type: application/json');

$phone = $_GET['phone'] ?? null;
$action = $_GET['action'] ?? 'check';

$historyService = new HistoryService($config);
$limiter = new RateLimiter($config);
$alertService = new AlertService($config);

// Handle History Fetch
if ($action === 'history') {
    echo json_encode([
        'history' => $historyService->getAll(),
        'stats' => $historyService->getStats()
    ]);
    exit;
}

// Handle Risk Stats
if ($action === 'stats') {
    echo json_encode([
        'distribution' => $historyService->getRiskDistribution(),
        'overview' => $historyService->getStats()
    ]);
    exit;
}

// Handle Config Fetch
if ($action === 'config') {
    $maskedConfig = [
        'System' => [
            'Rate Limiting' => ($config['security']['rate_limit']['enabled'] ? 'Enabled' : 'Disabled'),
            'Max Requests' => $config['security']['rate_limit']['max_requests'] . ' per hour',
            'Storage Path' => basename($config['paths']['storage'])
        ],
        'Database' => [
            'Host' => $config['database']['host'],
            'Database' => $config['database']['name'],
            'User' => $config['database']['user']
        ],
        'API Status' => [
            'FraudBD' => ($config['api']['fraudbd']['api_key'] === 'YOUR_FRAUDBD_API_KEY' ? 'Demo Mode' : 'Live')
        ]
    ];
    echo json_encode($maskedConfig);
    exit;
}

if (!$phone) {
    echo json_encode(['error' => 'Phone number is required']);
    exit;
}

// Rate Limiting
if (!$limiter->check($_SERVER['REMOTE_ADDR'] ?? 'local')) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded. Please wait an hour.']);
    exit;
}

$checker = new FraudChecker();

// Add all available couriers
$checker->addCourier(new FraudBD($config['api']['fraudbd']));
$checker->addCourier(new \FraudChecker\Couriers\Pathao($config['api']['pathao'] ?? []));
$checker->addCourier(new \FraudChecker\Couriers\Steadfast($config['api']['steadfast'] ?? []));
$checker->addCourier(new \FraudChecker\Couriers\RedX($config['api']['redx'] ?? []));
$checker->addCourier(new \FraudChecker\Couriers\Paperfly($config['api']['paperfly'] ?? []));

// 3. Perform Check
$rawResult = $checker->check($phone);

// Transform FraudBD nested summaries into our flat dashboard structure if needed
if (isset($rawResult['details']['FraudBD']['summaries'])) {
    $summaries = $rawResult['details']['FraudBD']['summaries'];
    
    $reportDetails = [];
    foreach ($summaries as $courierName => $stats) {
        $reportDetails[$courierName] = [
            'success' => $stats['success'] ?? 0,
            'cancel' => $stats['cancel'] ?? 0,
            'total' => $stats['total'] ?? 0,
            'risk' => $stats['risk_level'] ?? 'Low'
        ];
    }

    // Merge direct courier results if they have data
    foreach ($rawResult['details'] as $courier => $data) {
        if ($courier !== 'FraudBD' && ($data['total'] ?? 0) > 0) {
            $reportDetails[$courier] = $data;
        }
    }

    $result = [
        'phone' => $rawResult['phone'],
        'timestamp' => $rawResult['timestamp'],
        'from_cache' => $rawResult['from_cache'],
        'details' => $reportDetails,
        'aggregate' => $rawResult['aggregate']
    ];
} else {
    $result = $rawResult;
}

// Save to History
$historyService->add($phone, $result['aggregate']);

// Trigger Alert if High Risk
if ($result['aggregate']['risk_level'] === 'High') {
    $alertService->trigger($phone, $result['aggregate']);
}

echo json_encode($result);
