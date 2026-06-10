<?php
/**
 * FraudGuard Pro - Central Settings
 */

return [
    'api' => [
        'fraudbd' => [
            'api_key' => getenv('FRAUDBD_API_KEY') ?: 'YOUR_FRAUDBD_API_KEY',
        ],
        'steadfast' => [
            'api_key' => getenv('STEADFAST_API_KEY') ?: '',
            'secret_key' => getenv('STEADFAST_SECRET_KEY') ?: '',
        ],
        'pathao' => [
            'client_id' => getenv('PATHAO_CLIENT_ID') ?: '',
            'client_secret' => getenv('PATHAO_CLIENT_SECRET') ?: '',
        ],
        'redx' => [
            'api_token' => getenv('REDX_API_TOKEN') ?: '',
        ],
        'paperfly' => [
            'api_key' => getenv('PAPERFLY_API_KEY') ?: '',
        ],
    ],
    
    'security' => [
        'tokens' => [
            getenv('FG_SECRET_TOKEN') ?: 'FG-SECRET-789',
            'CLIENT-TOKEN-456',
        ],
        'rate_limit' => [
            'enabled' => getenv('RATE_LIMIT_ENABLED') !== 'false',
            'max_requests' => (int)(getenv('MAX_REQUESTS_PER_HOUR') ?: 60),
        ],
    ],

    'database' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'name' => getenv('DB_NAME') ?: 'fraud_guard',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
    ],
    
    'notifications' => [
        'enabled' => false,
        'webhook_url' => '', // e.g., Slack/Discord Webhook
        'admin_email' => 'admin@example.com',
    ],
    
    'paths' => [
        'storage' => __DIR__ . '/../storage/',
        'cache'   => __DIR__ . '/../storage/cache/',
        'logs'    => __DIR__ . '/../storage/logs/',
    ]
];
