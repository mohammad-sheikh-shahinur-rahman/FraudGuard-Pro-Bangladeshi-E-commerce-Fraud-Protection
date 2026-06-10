<?php
/**
 * FraudGuard Pro - CLI Setup Wizard
 */

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

echo "------------------------------------------\n";
echo "🛡️ FraudGuard Pro - System Setup Wizard\n";
echo "------------------------------------------\n\n";

// 1. Check Requirements
echo "🔍 Checking system requirements...\n";
$requirements = [
    'curl' => extension_loaded('curl'),
    'json' => extension_loaded('json'),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'storage_writable' => is_writable(__DIR__ . '/storage')
];

foreach ($requirements as $name => $met) {
    echo $met ? "✅ $name\n" : "❌ $name\n";
    if (!$met && $name !== 'storage_writable') {
        die("\nFATAL: Requirement '$name' not met. Please install it and try again.\n");
    }
}

if (!$requirements['storage_writable']) {
    echo "⚠️ Warning: 'storage' directory is not writable. Attempting to fix...\n";
    @chmod(__DIR__ . '/storage', 0775);
    @chmod(__DIR__ . '/storage/cache', 0775);
}

// 2. Environment Setup
if (!file_exists(__DIR__ . '/.env')) {
    echo "\n📄 Creating .env file from template...\n";
    copy(__DIR__ . '/.env.example', __DIR__ . '/.env');
} else {
    echo "\nℹ️ .env file already exists. Skipping creation.\n";
}

// 3. Database Initialization
echo "\n🗄️ Database Setup\n";
$db_host = readline("Enter Database Host [localhost]: ") ?: 'localhost';
$db_name = readline("Enter Database Name [fraud_guard]: ") ?: 'fraud_guard';
$db_user = readline("Enter Database User [root]: ") ?: 'root';
$db_pass = readline("Enter Database Password: ");

try {
    $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✨ Creating database if not exists...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db_name` ");

    echo "🔨 Creating 'fraud_checks' table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS `fraud_checks` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `phone` VARCHAR(15) NOT NULL,
        `success_rate` DECIMAL(5,2),
        `total_cancel` INT,
        `risk_level` ENUM('Low', 'Medium', 'High'),
        `recommendation` TEXT,
        `checked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    
    echo "✅ Database setup successful!\n";

    // Update .env with DB credentials
    $envContent = file_get_contents(__DIR__ . '/.env');
    $envContent = preg_replace('/DB_HOST=.*/', "DB_HOST=$db_host", $envContent);
    $envContent = preg_replace('/DB_NAME=.*/', "DB_NAME=$db_name", $envContent);
    $envContent = preg_replace('/DB_USER=.*/', "DB_USER=$db_user", $envContent);
    $envContent = preg_replace('/DB_PASS=.*/', "DB_PASS=$db_pass", $envContent);
    file_put_contents(__DIR__ . '/.env', $envContent);

} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    echo "Skipping database table creation. You may need to run it manually.\n";
}

echo "\n🚀 Setup Complete! You can now use FraudGuard Pro.\n";
echo "Dashboard: http://localhost/fraud_checker_system/dashboard.php\n";
echo "------------------------------------------\n";
