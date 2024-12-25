<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ProxyController extends Controller
{
    public function __construct()
    {
        ini_set('max_execution_time', 1200); // Allow long-running requests
    }

    public function handle(Request $request)
    {
        try {
            // Extract request data
            $targetEndpoint = ltrim($request->input('endpoint', ''), '/');
            $method = strtoupper($request->input('method', 'POST'));
            $data = $request->input('data', []);
            $headers = $request->input('headers', []);

            if (!$targetEndpoint) {
                return response()->json(['error' => 'Endpoint is required'], 400);
            }

            // Build full API URL
            $fullUrl = url($targetEndpoint);
            Log::info('Requesting URL:', compact('fullUrl', 'method', 'headers', 'data'));

            // Perform the HTTP request
            $response = Http::withHeaders($headers)
                ->timeout(env('HTTP_TIMEOUT', 60))
                ->connectTimeout(env('HTTP_CONNECT_TIMEOUT', 30))
                ->retry(3, 500, throw: false)
                ->send($method, $fullUrl, [
                    'json' => $data,
                    'on_stats' => function ($stats) {
                        Log::info('Transfer Stats:', [
                            'url' => $stats->getEffectiveUri(),
                            'time' => $stats->getTransferTime(),
                        ]);
                    },
                ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('Response Received:', [
                    'status' => $response->status(),
                    'data'   => $responseData,
                ]);

                return response()->json([
                    'status_code' => $response->status(),
                    'data'        => $responseData,
                ], $response->status());
            }

            // Log request failure
            Log::error('Request Failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return response()->json([
                'error'   => 'Request failed',
                'status'  => $response->status(),
                'message' => $response->body(),
            ], $response->status());

        } catch (\Throwable $e) {
            // Log unexpected exceptions
            Log::error('Unexpected Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'An unexpected error occurred.',
            ], 500);
        }
    }
}

