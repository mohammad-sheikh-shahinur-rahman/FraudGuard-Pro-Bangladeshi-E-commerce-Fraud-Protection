<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FraudCheckerController extends Controller
{
    /**
     * Check customer fraud status using FraudBD API.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function checkCustomer(Request $request)
    {
        $request->validate([
            'phone' => 'required|regex:/^(?:\+88|88)?(01[3-9]\d{8})$/',
        ], [
            'phone.regex' => 'Please provide a valid Bangladeshi phone number.',
        ]);

        $phoneNumber = $request->input('phone');
        $apiKey = config('services.fraudbd.key') ?? env('FRAUDBD_API_KEY');

        if (!$apiKey) {
            return back()->with('error', 'FraudBD API Key is not configured.');
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'api_key' => $apiKey,
            ])->timeout(10)->post('https://fraudbd.com/api/check-courier-info', [
                'phone_number' => $phoneNumber,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['status']) && $data['status']) {
                    // For API response
                    if ($request->wantsJson()) {
                        return response()->json($data['data']);
                    }

                    // For Web view
                    return view('fraud.report', ['report' => $data['data']]);
                }

                return back()->with('error', $data['message'] ?? 'Unable to retrieve fraud data.');
            }

            Log::error('FraudBD API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return back()->with('error', 'API connection failed. Please try again later.');

        } catch (\Exception $e) {
            Log::error('FraudBD Integration Exception', ['message' => $e->getMessage()]);
            return back()->with('error', 'An unexpected error occurred: ' . $e->getMessage());
        }
    }
}
