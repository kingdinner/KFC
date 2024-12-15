<?php

namespace App\Http\Controllers\API\DataManagement;

use App\Http\Controllers\Controller;
use App\Models\PayRate;

class PayRateController extends Controller
{
    /**
     * Display a listing of the pay rates along with store and employee details.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $payRates = PayRate::with('storeEmployee.employee')->get();

            $formattedPayRates = $payRates->map(function ($payRate) {
                return [
                    'pay_rate_id' => $payRate->id,
                    'position' => $payRate->position,
                    'rate_label' => $payRate->rate_label,
                    'hourly_rate' => $payRate->hourly_rate,
                    'employee_name' => optional($payRate->storeEmployee->employee)->firstname 
                        . ' ' . optional($payRate->storeEmployee->employee)->lastname,
                    'store_id' => optional($payRate->storeEmployee)->store_id,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedPayRates,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve pay rates',
            ], 500);
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
            $externalPayRates = $this->fetchExternalPayRates();

            $localPayRates = PayRate::all();

            foreach ($externalPayRates as $externalRate) {
                $payRate = $localPayRates->firstWhere('id', $externalRate['id']);

                if ($payRate) {
                    $payRate->update([
                        'position' => $externalRate['position'],
                        'rate_label' => $externalRate['rate_label'],
                        'hourly_rate' => $externalRate['hourly_rate'],
                    ]);
                } else {
                    PayRate::create([
                        'id' => $externalRate['id'],
                        'position' => $externalRate['position'],
                        'rate_label' => $externalRate['rate_label'],
                        'hourly_rate' => $externalRate['hourly_rate'],
                    ]);
                }
            }

            $externalIds = collect($externalPayRates)->pluck('id');
            PayRate::whereNotIn('id', $externalIds)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pay rates synchronized successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync pay rates: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch pay rates from an external source.
     *
     * @return array
     */
    private function fetchExternalPayRates()
    {
        return [
            [
                'id' => 1,
                'position' => 'Team Member',
                'rate_label' => 'per hour',
                'hourly_rate' => 500,
            ],
            [
                'id' => 2,
                'position' => 'Supervisor',
                'rate_label' => 'per hour',
                'hourly_rate' => 700,
            ],
        ];
    }
}