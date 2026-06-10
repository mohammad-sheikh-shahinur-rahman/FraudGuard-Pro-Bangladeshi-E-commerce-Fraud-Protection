<?php

return [
    'default_api_key' => env('FRAUDBD_API_KEY', ''),
    
    'couriers' => [
        'steadfast' => [
            'enabled' => true,
            'api_key' => env('STEADFAST_API_KEY', ''),
            'secret_key' => env('STEADFAST_SECRET_KEY', ''),
        ],
        'pathao' => [
            'enabled' => true,
            'client_id' => env('PATHAO_CLIENT_ID', ''),
            'client_secret' => env('PATHAO_CLIENT_SECRET', ''),
        ],
        'redx' => [
            'enabled' => true,
            'api_token' => env('REDX_API_TOKEN', ''),
        ]
    ]
];
