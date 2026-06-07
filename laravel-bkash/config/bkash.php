<?php

return [
    /*
    |--------------------------------------------------------------------------
    | bKash Tokenized Checkout Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may define your bKash merchant API credentials and environment.
    | Set BKASH_SANDBOX to true for testing, and false for production.
    |
    */

    'sandbox' => env('BKASH_SANDBOX', true),
    'app_key' => env('BKASH_APP_KEY', '4f6o0cjiki2rfm34kfdadl1eqq'),
    'app_secret' => env('BKASH_APP_SECRET', '2is7hdktrekvrbljjh44ll3d9l1dtjo4pasmjvs5vl5qr3fug4b'),
    'username' => env('BKASH_USERNAME', 'sandboxTokenizedUser02'),
    'password' => env('BKASH_PASSWORD', 'sandboxTokenizedUser02@12345'),
    
    // Callback URL path that receives redirection from bKash checkout page
    'callback_url' => env('BKASH_CALLBACK_URL', '/bkash/callback'),
];
