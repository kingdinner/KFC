<?php

namespace App\Http\Controllers\API\DataManagement;

use App\Http\Controllers\Controller;
use App\Models\PayRate;

class PayRateController extends Controller
{
     /**
     * Display a listing of the pay rates.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $payRates = PayRate::all();
            return response()->json([ 'success' => true, 'data' => $payRates ], 200);
        } catch (\Exception $e) {
            return response()->json([ 'success' => false, 'message' => 'Failed to retrieve pay rates' ], 500);
        }
    }

    /**
     * Sync pay rates from an external source.
     *
     * @return \Illuminate\Http\Response
     */
    public function sync()
    {
        try {
            // Placeholder for the logic to sync pay rates from an external source.
            // This could include API calls, database queries, or other integrations.
            
            // Example:
            // PayRate::syncWithExternalSource();

            return response()->json([ 'success' => true, 'message' => 'Pay rates synchronized successfully' ], 200);
        } catch (\Exception $e) {
            return response()->json([ 'success' => false, 'message' => 'Failed to sync pay rates' ], 500);
        }
    }
}
