<?php

namespace App\Http\Controllers\API\ScheduleManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\StoreEmployee;

use App\Models\Availability;

use App\Models\Leave;
use Carbon\Carbon;

class LaborManagementController extends Controller
{
    /**
     * Generate labor schedule dynamically based on workload, trading hours, and given target MH.
     */
    public function generateLaborSchedule(Request $request)
    {
        // Validate input
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'hourly_data' => 'required|array',
            'projected_sales' => 'required|numeric',
            'trading_hours' => 'required|integer|min:9|max:24',
            'target_mh' => 'required|integer|min:1',
        ]);

        // Extract input data
        $storeId = $request->input('store_id');
        $hourlyData = $request->input('hourly_data');
        $projectedSales = $request->input('projected_sales');
        $tradingHours = $request->input('trading_hours');
        $targetMH = $request->input('target_mh');
        $month = $request->input('month', Carbon::now()->format('Y-m'));

        // Step 1: Calculate workload for each hour
        $hourlyCollection = collect($hourlyData)->map(function ($data, $hour) {
            $data['hour'] = $hour;
            $data['total'] = $data['tc'] + $data['sales']; // Calculate total workload
            return $data;
        });

        // Step 2: Determine peak hours
        $peakHours = $hourlyCollection->sortByDesc('total')->take(6)->pluck('hour')->toArray(); // Convert to array

        // Step 3: Fetch employees directly using StoreEmployee and roles via AuthenticationAccount
        $storeEmployees = StoreEmployee::with([
            'employee.authenticationAccount.roles', // Access roles through AuthenticationAccount
        ])->where('store_id', $storeId)->get();

        // Step 4: Prepare availability and roles
        $availabilityData = $storeEmployees->map(function ($storeEmployee) use ($month) {
            $storeEmployeeId = $storeEmployee->id;

            // Mock getAvailabilityAndLeaves for demonstration (replace with real implementation)
            $availability = collect([
                [
                    'date' => Carbon::now()->format('Y-m-d'),
                    'is_available' => true,
                ],
            ]);

            $availableDates = $availability->map(function ($item) {
                return $item['is_available'] ? $item['date'] : null;
            })->filter();

            return [
                'store_employee_id' => $storeEmployeeId,
                'employee_name' => $storeEmployee->employee->firstname . ' ' . $storeEmployee->employee->lastname,
                'role' => $storeEmployee->employee->authenticationAccount->roles->pluck('name')->first() ?: 'No Role Assigned',
                'available_dates' => $availableDates->toArray(),
            ];
        })->filter(); // Remove null entries

        // Step 5: Filter employees based on availability
        $currentDate = Carbon::now()->toDateString();
        $availableEmployees = $availabilityData->filter(function ($data) use ($currentDate) {
            return in_array($currentDate, $data['available_dates']);
        });

        // Step 6: Assign employees to each hour
        $schedule = [];
        foreach ($hourlyCollection as $hourData) {
            $hour = $hourData['hour'];
            $tc = $hourData['tc'];
            $sales = $hourData['sales'];
        
            // Parse the hour range into start and end times
            [$startHour, $endHour] = explode('-', $hour);
            $shiftStart = Carbon::createFromTimeString($startHour . ':00');
            $shiftEnd = $shiftStart->copy()->addHours(1);
        
            // Dynamically calculate manpower needed
            $manPowerNeeded = ceil(($tc / 10) + ($sales / 5000));
        
            // Increase manpower allocation during peak hours
            if (in_array($hour, $peakHours)) {
                $manPowerNeeded = min($manPowerNeeded + 2, $targetMH);
            }
        
            // Adjust based on available employees and target MH
            $manPowerNeeded = min($manPowerNeeded, $availableEmployees->count());
        
            // Allocate employees for the current hour
            $allocatedEmployees = $availableEmployees->take($manPowerNeeded);
        
            foreach ($allocatedEmployees as $employee) {
                $schedule[] = [
                    'employee_name' => $employee['employee_name'],
                    'role' => $employee['role'],
                    'shift_start' => $shiftStart->format('H:i'),
                    'shift_end' => $shiftEnd->format('H:i'),
                    'is_peak_hour' => in_array($hour, $peakHours),
                ];
            }
        
            // Remove allocated employees from the available pool
            $availableEmployees = $availableEmployees->diffKeys($allocatedEmployees->keys());
        }
        

        // Step 7: Return the schedule response
        return response()->json([
            'message' => 'Labor schedule generated successfully!',
            'store_name' => Store::find($storeId)->name,
            'projected_sales' => $projectedSales,
            'trading_hours' => $tradingHours,
            'target_mh' => $targetMH,
            'peak_hours' => $peakHours,
            'schedule' => $schedule,
        ]);
    }


    /**
     * Get availability and leave data for a given store employee and month.
     */
    protected function getAvailabilityAndLeaves($storeEmployeeId, $month)
    {
        $startOfMonth = Carbon::parse($month)->startOfMonth();
        $endOfMonth = Carbon::parse($month)->endOfMonth();

        // Fetch availability
        $availability = Availability::where('store_employee_id', $storeEmployeeId)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get();

        // Fetch leave data
        $leaves = Leave::where('employee_id', $storeEmployeeId)
            ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('date_applied', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('date_ended', [$startOfMonth, $endOfMonth]);
            })
            ->get();

        // Combine availability and leave data
        $allDates = [];
        foreach ($availability as $available) {
            $allDates[$available->date] = ['is_available' => true, 'reason' => null];
        }
        foreach ($leaves as $leave) {
            $leavePeriod = Carbon::parse($leave->date_applied)->toPeriod($leave->date_ended);
            foreach ($leavePeriod as $date) {
                $allDates[$date->toDateString()] = ['is_available' => false, 'reason' => 'On Leave'];
            }
        }

        return collect($allDates);
    }
}
