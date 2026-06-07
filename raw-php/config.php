<?php
if (session_status() === PHP_SESSION_ACTIVE) {
    // If not already in session, load the default sandbox credentials
    if (!isset($_SESSION['bkash_config'])) {
        $_SESSION['bkash_config'] = [
            'sandbox' => true,
            'app_key' => '4f6o0cjiki2rfm34kfdadl1eqq',
            'app_secret' => '2is7hdktrekvrbljjh44ll3d9l1dtjo4pasmjvs5vl5qr3fug4b',
            'username' => 'sandboxTokenizedUser02',
            'password' => 'sandboxTokenizedUser02@12345'
        ];
    }
} else {
    // Session not active, return default configs
    return [
        'sandbox' => true,
        'app_key' => '4f6o0cjiki2rfm34kfdadl1eqq',
        'app_secret' => '2is7hdktrekvrbljjh44ll3d9l1dtjo4pasmjvs5vl5qr3fug4b',
        'username' => 'sandboxTokenizedUser02',
        'password' => 'sandboxTokenizedUser02@12345'
    ];
}
