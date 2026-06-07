<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string|bool grantToken()
 * @method static string|bool getAccessToken()
 * @method static array createPayment($amount, $invoiceNumber, $payerReference = '01770618575')
 * @method static array executePayment($paymentID)
 * @method static array queryPayment($paymentID)
 * @method static array refundPayment($paymentID, $amount, $trxID, $reason = 'Customer Request')
 *
 * @see \App\Services\BkashService
 */
class Bkash extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'bkash';
    }
}
