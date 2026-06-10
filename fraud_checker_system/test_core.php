<?php

/**
 * Custom Autoloader for the Fraud Checker System
 */
spl_autoload_register(function ($class) {
    $prefix = 'FraudChecker\\';
    $base_dir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use FraudChecker\FraudChecker;
use FraudChecker\Couriers\Steadfast;

// 1. Setup Config
$config = [
    'steadfast' => ['api_key' => 'YOUR_FRAUDBD_API_KEY']
];

// 2. Initialize Engine
$checker = new FraudChecker();

// 3. Add Couriers
$checker->addCourier(new Steadfast($config['steadfast']));

// 4. Run Check
$result = $checker->check('01712345678');

header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);
