<?php

/**
 * Test Runner using Composer Autoloader
 */
require_once __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

use FraudChecker\FraudChecker;
use FraudChecker\Couriers\Steadfast;
use FraudChecker\Couriers\Pathao;
use FraudChecker\Couriers\RedX;
use FraudChecker\Couriers\Paperfly;

// 1. Setup Config
$config = require 'config/settings.php';

// 2. Initialize Engine
$checker = new FraudChecker();

// 3. Add Couriers
$checker->addCourier(new Steadfast($config['api']['steadfast']));
$checker->addCourier(new Pathao($config['api']['pathao']));
$checker->addCourier(new RedX($config['api']['redx']));
$checker->addCourier(new Paperfly($config['api']['paperfly']));

// 4. Run Check
$result = $checker->check('01712345678');

header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);
