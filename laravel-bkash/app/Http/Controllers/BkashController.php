<?php

namespace App\Http\Controllers;

use App\Services\BkashService;
use Illuminate\Http\Request;

class BkashController extends Controller
{
    protected $bkashService;

    public function __construct(BkashService $bkashService)
    {
        $this->bkashService = $bkashService;
    }

    /**
     * Redirect customer to bKash payment gateway.
     */
    public function pay(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payer_reference' => 'nullable|string',
        ]);

        $amount = $request->amount;
        $invoice = 'INV-' . strtoupper(uniqid());
        $payer = $request->payer_reference ?? '01770618575'; // default test wallet
        
        $response = $this->bkashService->createPayment($amount, $invoice, $payer);

        if (isset($response['statusCode']) && $response['statusCode'] === '0000' && !empty($response['bkashURL'])) {
            // Redirect customer to bKash OTP/PIN inputs
            return redirect()->away($response['bkashURL']);
        }

        return back()->with('error', $response['statusMessage'] ?? 'Failed to create payment');
    }

    /**
     * Handle bKash checkout callback redirect.
     */
    public function callback(Request $request)
    {
        $paymentID = $request->get('paymentID');
        $status = $request->get('status');

        if (!$paymentID) {
            return redirect()->route('payment.failed')->with('error', 'Missing bKash Payment ID.');
        }

        if ($status === 'success') {
            $response = $this->bkashService->executePayment($paymentID);

            if (isset($response['statusCode']) && $response['statusCode'] === '0000') {
                // SUCCESS: Finalize order status in database here
                $trxID = $response['trxID'];
                return redirect()->route('payment.success')->with([
                    'trxID' => $trxID,
                    'paymentID' => $paymentID,
                    'message' => 'Payment Completed Successfully'
                ]);
            }

            return redirect()->route('payment.failed')->with('error', $response['statusMessage'] ?? 'Payment execution failed.');
        }

        if ($status === 'cancel') {
            return redirect()->route('payment.failed')->with('error', 'Payment cancelled by user.');
        }

        return redirect()->route('payment.failed')->with('error', 'Payment transaction failed.');
    }

    /**
     * Optional: Refund Transaction
     */
    public function refund(Request $request)
    {
        $request->validate([
            'payment_id' => 'required|string',
            'trx_id' => 'required|string',
            'amount' => 'required|numeric',
            'reason' => 'nullable|string',
        ]);

        $response = $this->bkashService->refundPayment(
            $request->payment_id,
            $request->amount,
            $request->trx_id,
            $request->reason ?? 'Refund Requested'
        );

        if (isset($response['statusCode']) && $response['statusCode'] === '0000') {
            return back()->with('success', 'Refund successful! TrxID: ' . ($response['refundTrxID'] ?? 'N/A'));
        }

        return back()->with('error', $response['statusMessage'] ?? 'Refund failed.');
    }
}
