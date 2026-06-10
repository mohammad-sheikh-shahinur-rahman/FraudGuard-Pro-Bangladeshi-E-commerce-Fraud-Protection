<?php

/**
 * Real API Bridge for the Dashboard
 * This file connects to official courier APIs for real data.
 */

// 1. Simple Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'FraudChecker\\';
    $base_dir = __DIR__ . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

use FraudChecker\FraudChecker;
use FraudChecker\Couriers\FraudBD;

header('Content-Type: application/json');

$phone = $_GET['phone'] ?? null;

if (!$phone) {
    echo json_encode(['error' => 'Phone number is required']);
    exit;
}

// ---------------------------------------------------------
// 2. CONFIGURATION - FraudBD API Key
// ---------------------------------------------------------
$fraudBDConfig = [
    'api_key' => 'YOUR_FRAUDBD_API_KEY' // Insert your FraudBD key here
];
// ---------------------------------------------------------

$checker = new FraudChecker();
$checker->addCourier(new FraudBD($fraudBDConfig));

// 3. Perform Check
// If API key is not provided, we show demo data
if ($fraudBDConfig['api_key'] === 'YOUR_FRAUDBD_API_KEY') {
    $isHighRisk = (strpos($phone, '7') !== false);
    $result = [
        'phone' => $phone,
        'timestamp' => date('Y-m-d H:i:s'),
        'from_cache' => false,
        'details' => [
            'Pathao' => ['success' => 0, 'cancel' => 0, 'total' => 0, 'risk' => $isHighRisk ? 'High' : 'Low'],
            'Steadfast' => ['success' => $isHighRisk ? 1 : 8, 'cancel' => $isHighRisk ? 5 : 1, 'total' => $isHighRisk ? 6 : 9, 'risk' => $isHighRisk ? 'High' : 'Low'],
            'RedX' => ['success' => $isHighRisk ? 0 : 3, 'cancel' => $isHighRisk ? 4 : 0, 'total' => $isHighRisk ? 4 : 3, 'risk' => $isHighRisk ? 'High' : 'Low']
        ],
        'aggregate' => [
            'total_success' => $isHighRisk ? 1 : 11,
            'total_cancel' => $isHighRisk ? 9 : 1,
            'total_orders' => $isHighRisk ? 10 : 12,
            'success_rate' => $isHighRisk ? 10 : 91.67,
            'risk_level' => $isHighRisk ? 'High' : 'Low',
            'recommendation' => $isHighRisk ? 'Caution: Significant cancellation history.' : 'Trusted: Reliable customer record.'
        ]
    ];
} else {
    $rawResult = $checker->check($phone);
    
    // Transform FraudBD nested summaries into our flat dashboard structure
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
}

echo json_encode($result);
