<?php

namespace App\Http\Controllers\API\ScheduleManagement;

use App\Http\Controllers\Controller;
use App\Traits\HandlesAvailability;
use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\StoreEmployee;
use Carbon\Carbon;
use App\Models\LaborSchedule;

class LaborManagementController extends Controller
{
    use HandlesAvailability;
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
        $tradingHours = $request->input('trading_hours');

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $monthlySchedule = [];
        $totalEmployees = 0;

        for ($date = $startOfMonth; $date->lte($endOfMonth); $date->addDay()) {
            $today = $date->toDateString();

            // Process hourly data
            $hourlyCollection = [];
            foreach ($hourlyData as $hour => $data) {
                $hourlyCollection[] = [
                    'hour' => $hour,
                    'tc' => $data['tc'],
                    'sales' => $data['sales'],
                    'total' => $data['tc'] + $data['sales'],
                ];
            }

            usort($hourlyCollection, function ($a, $b) {
                [$aStart] = explode('-', $a['hour']);
                [$bStart] = explode('-', $b['hour']);
                return (int)$aStart - (int)$bStart;
            });

            $peakHours = array_slice(array_column($hourlyCollection, 'hour'), 0, 6);

            // Retrieve employees and group by station
            $storeEmployees = StoreEmployee::with(['employee.authenticationAccount.roles'])
                ->where('store_id', $storeId)
                ->get();

            $employeesByStation = [
                'kitchen_station' => [],
                'counter_station' => [],
                'dining_station' => [],
            ];

            foreach ($storeEmployees as $storeEmployee) {
                $availability = $this->getAvailabilityAndLeaves($storeEmployee->id, $date->format('Y-m'));

                $todayAvailability = $availability->where('date', $today);
                if ($todayAvailability->where('is_available', false)->count() === 0) {
                    if (!in_array('admin', $storeEmployee->employee->authenticationAccount->roles->pluck('name')->toArray())) {
                        $station = $storeEmployee->employee->station;
                        if (isset($employeesByStation[$station])) {
                            $employeesByStation[$station][] = [
                                'id' => $storeEmployee->id,
                                'name' => $storeEmployee->employee->firstname . ' ' . $storeEmployee->employee->lastname,
                                'role' => $storeEmployee->employee->authenticationAccount->roles->pluck('name')->toArray(),
                                'station' => $station,
                                'hours_assigned' => 0,
                            ];
                        }
                    }
                }
            }

            $totalEmployees = array_sum(array_map('count', $employeesByStation));

            // Distribute employees by shift (Rotation or Random)
            $shiftLength = 6; // Each shift is 6 hours
            $shiftsCount = ceil($tradingHours / $shiftLength);

            $shiftEmployees = [];
            foreach ($employeesByStation as $station => $employees) {
                shuffle($employees); // Randomize employee order for fairness
                $currentIndex = 0;

                for ($i = 0; $i < $shiftsCount; $i++) {
                    $shiftName = "Shift" . ($i + 1);
                    if (!isset($shiftEmployees[$shiftName])) {
                        $shiftEmployees[$shiftName] = [];
                    }

                    $employeesPerShift = floor(count($employees) / $shiftsCount);
                    $extraEmployee = ($currentIndex < count($employees)) ? 1 : 0;

                    $count = $employeesPerShift + $extraEmployee;
                    $shiftEmployees[$shiftName] = array_merge(
                        $shiftEmployees[$shiftName],
                        array_slice($employees, $currentIndex, $count)
                    );

                    $currentIndex += $count;
                }
            }

            // Define shifts dynamically based on trading hours
            $shifts = [];
            $currentStartHour = 6; // Assume trading starts at 6 AM
            foreach (array_keys($shiftEmployees) as $shiftName) {
                $shiftHours = [];
                for ($i = 0; $i < $shiftLength; $i++) {
                    $nextHour = ($currentStartHour % 12) + 1;
                    $hourKey = sprintf("%d-%d", $currentStartHour > 12 ? $currentStartHour - 12 : $currentStartHour, $nextHour);
                    $shiftHours[] = $hourKey;
                    $currentStartHour = ($currentStartHour + 1) % 24;
                }
                $shifts[$shiftName] = $shiftHours;
            }

            $schedule = [];

            // Assign shifts
            foreach ($shifts as $shiftName => $shiftHours) {
                foreach ($shiftHours as $hourRange) {
                    if (!isset($hourlyData[$hourRange])) continue;

                    [$startHour, $endHour] = explode('-', $hourRange);
                    $shiftStart = Carbon::createFromTimeString($startHour . ':00');
                    $shiftEnd = $shiftStart->copy()->addHour();

                    $manPowerNeeded = ceil(($hourlyData[$hourRange]['tc'] / 10) + ($hourlyData[$hourRange]['sales'] / 5000));

                    foreach ($shiftEmployees[$shiftName] as &$employee) {
                        if ($employee['hours_assigned'] < $shiftLength) {
                            $schedule[] = [
                                'employee_id' => $employee['id'],
                                'employee_name' => $employee['name'],
                                'role' => implode(', ', $employee['role']),
                                'station' => $employee['station'],
                                'shift_start' => $shiftStart->format('H:i'),
                                'shift_end' => $shiftEnd->format('H:i'),
                                'is_peak_hour' => in_array($hourRange, $peakHours),
                            ];

                            $employee['hours_assigned']++;
                            $manPowerNeeded--;

                            if ($manPowerNeeded <= 0) break;
                        }
                    }
                }
            }

            // Compress schedule
            $compressedSchedule = [];
            foreach ($schedule as $shift) {
                $key = $shift['employee_name'];
                if (!isset($compressedSchedule[$key])) {
                    $compressedSchedule[$key] = [
                        'employee_id' => $shift['employee_id'],
                        'employee_name' => $shift['employee_name'],
                        'role' => $shift['role'],
                        'station' => $shift['station'],
                        'shifts' => [],
                    ];
                }
                $compressedSchedule[$key]['shifts'][] = [
                    'shift_start' => $shift['shift_start'],
                    'shift_end' => $shift['shift_end'],
                    'is_peak_hour' => $shift['is_peak_hour'],
                ];
            }

            $monthlySchedule[$today] = array_values($compressedSchedule);
        }

        return response()->json([
            'message' => 'Monthly labor schedule generated successfully!',
            'store_name' => Store::find($storeId)->name,
            'total_employees' => $totalEmployees,
            'target_mh' => $targetMH,
            'monthly_schedule' => $monthlySchedule,
        ]);
    }


    public function saveLaborSchedule(Request $request)
    {
        $request->validate([
            'monthly_schedule' => 'required|array',
        ]);

        $monthlySchedule = $request->input('monthly_schedule');

        $firstDate = array_key_first($monthlySchedule);
        $monthYear = Carbon::parse($firstDate)->format('Y_m'); 
        $filename = $monthYear . '_' . round(microtime(true) * 1000 % 1000) . '_Monthly_Schedule';

        LaborSchedule::create([
            'filename' => $filename,
            'schedule_date' => $monthYear,
            'schedule_array' => $monthlySchedule,
        ]);

        return response()->json([
            'message' => 'Monthly labor schedule saved successfully!',
            'saved_schedule' => [
                'month' => $monthYear,
                'filename' => $filename,
            ],
        ], 201);
    }

    public function getLatestSchedules(Request $request)
    {
        $schedules = LaborSchedule::select( 'filename')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($schedules->isEmpty()) {
            return response()->json([
                'message' => 'No schedules found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Schedules retrieved successfully!',
            'schedules' => $schedules,
        ]);
    }

    public function displayLaborScheduleByFilename(Request $request, $filename)
{
    $laborSchedule = LaborSchedule::where('filename', $filename)->first();

    if (!$laborSchedule) {
        return response()->json([
            'success' => false,
            'message' => 'Labor schedule not found.',
        ], 404);
    }

    $scheduleArray = collect($laborSchedule->schedule_array); // Assuming schedule_array is an associative array
    $perPage = $request->get('per_page', 10); // Default to 10 items per page
    $currentPage = $request->get('page', 1);

    // Paginate the schedule array
    $paginatedSchedule = $scheduleArray
        ->slice(($currentPage - 1) * $perPage, $perPage)
        ->toArray();

    $pagination = [
        'current_page' => (int) $currentPage,
        'per_page' => (int) $perPage,
        'total' => $scheduleArray->count(),
        'last_page' => ceil($scheduleArray->count() / $perPage),
        'first_page_url' => url()->current() . '?page=1&per_page=' . $perPage,
        'last_page_url' => url()->current() . '?page=' . ceil($scheduleArray->count() / $perPage) . '&per_page=' . $perPage,
        'next_page_url' => $currentPage < ceil($scheduleArray->count() / $perPage)
            ? url()->current() . '?page=' . ($currentPage + 1) . '&per_page=' . $perPage
            : null,
        'prev_page_url' => $currentPage > 1
            ? url()->current() . '?page=' . ($currentPage - 1) . '&per_page=' . $perPage
            : null,
    ];

    return response()->json([
        'success' => true,
        'data' => $paginatedSchedule,
        'pagination' => $pagination,
    ]);
}



}
