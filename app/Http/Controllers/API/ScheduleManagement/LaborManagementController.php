<?php

namespace App\Http\Controllers\API\ScheduleManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\StoreEmployee;
use Carbon\Carbon;

class LaborManagementController extends Controller
{
    public function generateLaborSchedule(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'hourly_data' => 'required|array',
            'projected_sales' => 'required|numeric',
            'trading_hours' => 'required|integer|min:9|max:24',
            'target_mh' => 'required|integer|min:1',
        ]);
    
        $storeId = $request->input('store_id');
        $hourlyData = $request->input('hourly_data');
        $targetMH = $request->input('target_mh');
    
        // Step 1: Process hourly data
        $hourlyCollection = collect($hourlyData)->map(function ($data, $hour) {
            $data['hour'] = $hour;
            $data['total'] = $data['tc'] + $data['sales'];
            return $data;
        });
    
        $peakHours = $hourlyCollection->sortByDesc('total')->take(6)->pluck('hour')->toArray();
    
        // Step 2: Retrieve employees
        $storeEmployees = StoreEmployee::with(['employee.authenticationAccount.roles'])
            ->where('store_id', $storeId)
            ->get();
    
        $employees = $storeEmployees->map(function ($storeEmployee) {
            return [
                'id' => $storeEmployee->id,
                'name' => $storeEmployee->employee->firstname . ' ' . $storeEmployee->employee->lastname,
                'role' => $storeEmployee->employee->authenticationAccount->roles->pluck('name')->toArray(),
                'station' => $storeEmployee->employee->station,
                'hours_assigned' => 0,
                'current_shift_hours' => 0,
            ];
        })->filter(function ($employee) {
            return !in_array('admin', $employee['role']);
        });
    
        $schedule = collect();
    
        // Step 3: Assign shifts with rotation logic
        foreach ($hourlyCollection as $hourData) {
            $hour = $hourData['hour'] ?? null;
    
            // Validate hour format
            if (!$hour || strpos($hour, '-') === false) {
                \Log::warning("Invalid hour format encountered: " . json_encode($hourData));
                continue; // Skip invalid hours
            }
    
            [$startHour, $endHour] = explode('-', $hour);
            if (!is_numeric($startHour) || !is_numeric($endHour)) {
                \Log::warning("Invalid hour range encountered: " . json_encode($hourData));
                continue;
            }
    
            $shiftStart = Carbon::createFromTimeString($startHour . ':00');
            $shiftEnd = $shiftStart->copy()->addHour();
    
            $manPowerNeeded = ceil(($hourData['tc'] / 10) + ($hourData['sales'] / 5000));
            $manPowerNeeded = min($manPowerNeeded, $targetMH);
    
            // Sort employees by least assigned hours, then by current shift hours
            $employees = $employees->sortBy(['hours_assigned', 'current_shift_hours'])->values()->toArray();
    
            foreach ($employees as $employeeKey => $employee) {
                if ($manPowerNeeded <= 0) break;
    
                // Skip if the employee has already worked a full shift (6 hours)
                if ($employee['current_shift_hours'] >= 6) {
                    continue;
                }
    
                // Assign the shift
                $schedule->push([
                    'employee_name' => $employee['name'],
                    'role' => implode(', ', $employee['role']),
                    'station' => $employee['station'],
                    'shift_start' => $shiftStart->format('H:i'),
                    'shift_end' => $shiftEnd->format('H:i'),
                    'is_peak_hour' => in_array($hour, $peakHours),
                ]);
    
                // Update employee's working hours
                $employees[$employeeKey]['hours_assigned']++;
                $employees[$employeeKey]['current_shift_hours']++;
    
                $manPowerNeeded--;
    
                // Reset shift hours after 6 hours
                if ($employees[$employeeKey]['current_shift_hours'] >= 6) {
                    $employees[$employeeKey]['current_shift_hours'] = 0;
                }
            }
    
            // Convert back to collection
            $employees = collect($employees);
        }
    
        // Step 4: Compress schedule
        $compressedSchedule = $schedule->groupBy('employee_name')->map(function ($shifts, $employeeName) {
            $role = $shifts->first()['role'];
            $station = $shifts->first()['station'];
    
            $groupedShifts = $shifts->map(function ($shift) {
                return [
                    'shift_start' => $shift['shift_start'],
                    'shift_end' => $shift['shift_end'],
                    'is_peak_hour' => $shift['is_peak_hour'],
                ];
            });
    
            return [
                'employee_name' => $employeeName,
                'role' => $role,
                'station' => $station,
                'shifts' => $groupedShifts->toArray(),
            ];
        })->values();
    
        return response()->json([
            'message' => 'Labor schedule generated successfully!',
            'store_name' => Store::find($storeId)->name,
            'target_mh' => $targetMH,
            'peak_hours' => $peakHours,
            'schedule' => $compressedSchedule,
        ]);
    }
    
}
