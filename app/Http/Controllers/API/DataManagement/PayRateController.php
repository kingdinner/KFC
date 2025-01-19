<?php

namespace App\Http\Controllers\API\DataManagement;

use App\Http\Controllers\Controller;
use App\Models\PayRate;
use App\Traits\HandlesHelperController;
use Illuminate\Http\Request;

class PayRateController extends Controller
{
    use HandlesHelperController;
    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));
        $perPage = (int) $request->input('per_page', 10);

        $payRatesQuery = PayRate::with('storeEmployee.employee');

        if (!empty($search)) {
            $payRatesQuery->where(function ($query) use ($search) {
                $query->where('position', 'ILIKE', "%{$search}%")
                      ->orWhere('rate_label', 'ILIKE', "%{$search}%")
                      ->orWhereHas('storeEmployee.employee', function ($employeeQuery) use ($search) {
                          $employeeQuery->where('firstname', 'ILIKE', "%{$search}%")
                                        ->orWhere('lastname', 'ILIKE', "%{$search}%");
                      });
            });
        }

        $paginatedPayRates = $payRatesQuery->paginate($perPage);

        $formattedPayRates = $paginatedPayRates->getCollection()->map(function ($payRate) {
            return [
                'pay_rate_id' => $payRate->id,
                'position' => $payRate->position,
                'rate_label' => $payRate->rate_label,
                'hourly_rate' => $payRate->hourly_rate,
                'employee_name' => optional($payRate->storeEmployee->employee)->firstname 
                    . ' ' . optional($payRate->storeEmployee->employee)->lastname,
                'store_number' => optional($payRate->storeEmployee)->store_number,
            ];
        });

        return $this->paginateResponse($paginatedPayRates->setCollection($formattedPayRates));
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