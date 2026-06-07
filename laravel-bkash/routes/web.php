<?php

use App\Http\Controllers\BkashController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes for bKash Integration
|--------------------------------------------------------------------------
|
| Define the payment initiation and gateway callback routes.
|
*/

Route::post('/bkash/pay', [BkashController::class, 'pay'])->name('bkash.pay');
Route::get('/bkash/callback', [BkashController::class, 'callback'])->name('bkash.callback');
Route::post('/bkash/refund', [BkashController::class, 'refund'])->name('bkash.refund');

// Success and Failure Redirect Targets
Route::get('/payment/success', function() {
    return view('payment-success', [
        'trxID' => session('trxID'),
        'paymentID' => session('paymentID'),
        'message' => session('message')
    ]);
})->name('payment.success');

Route::get('/payment/failed', function() {
    return view('payment-failed', [
        'error' => session('error')
    ]);
})->name('payment.failed');
