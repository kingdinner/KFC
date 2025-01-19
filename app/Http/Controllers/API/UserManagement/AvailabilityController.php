<?php

namespace App\Http\Controllers\API\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\Availability;
use App\Models\StoreEmployee;
use App\Traits\HandlesHelperController;
use Illuminate\Http\Request;
use App\Traits\HandlesAvailability;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;


class AvailabilityController extends Controller
{
    use HandlesAvailability, HandlesHelperController;
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $storeId = $request->input('store_id');
        $search = $request->input('search');

        $storeEmployeeIdsQuery = StoreEmployee::with('employee:id,firstname,lastname');

        // Apply store_id filter if provided
        if ($storeId) {
            $storeEmployeeIdsQuery->where('store_id', $storeId);
        }

        // Apply search filter if provided
        if ($search) {
            $storeEmployeeIdsQuery->whereHas('employee', function ($query) use ($search) {
                $query->where('firstname', 'like', '%' . $search . '%')
                    ->orWhere('lastname', 'like', '%' . $search . '%');
            });
        }

        // Paginate the results
        $paginatedEmployeeIds = $storeEmployeeIdsQuery->paginate($perPage);

        // Process availability data
        $availability = collect($paginatedEmployeeIds->items())->map(function ($storeEmployee) use ($month) {
            $storeEmployeeId = $storeEmployee->id;
            $data = $this->getAvailabilityAndLeaves($storeEmployeeId, $month);

            $startOfMonth = Carbon::parse($month)->startOfMonth();
            $endOfMonth = Carbon::parse($month)->endOfMonth();

            $allDates = [];
            for ($date = $startOfMonth; $date->lte($endOfMonth); $date->addDay()) {
                $allDates[$date->toDateString()] = [
                    'is_available' => true,
                    'reason' => null,
                ];
            }

            if (!empty($data) && is_iterable($data)) {
                foreach ($data as $item) {
                    if (isset($item['date']) && array_key_exists($item['date'], $allDates)) {
                        $allDates[$item['date']] = [
                            'is_available' => false,
                            'reason' => $item['reason'] ?? 'No reason provided',
                        ];
                    }
                }
            }

            $availableDates = array_keys(array_filter($allDates, fn($entry) => $entry['is_available']));
            $unavailableDates = array_filter($allDates, fn($entry) => !$entry['is_available']);

            return [
                'store_employee_id' => $storeEmployeeId,
                'employee_name' => $storeEmployee->employee->firstname . ' ' . $storeEmployee->employee->lastname,
                'store_id' => $storeEmployee->store_id,
                'month' => $month,
                'available_dates' => $availableDates,
                'unavailable_dates' => array_map(fn($date, $info) => [
                    'date' => $date,
                    'reason' => $info['reason'],
                ], array_keys($unavailableDates), $unavailableDates),
            ];
        });

        // Return the response using paginateResponse from HandlesHelperController
        return $this->paginateResponse(new LengthAwarePaginator(
            $availability,
            $paginatedEmployeeIds->total(),
            $paginatedEmployeeIds->perPage(),
            $paginatedEmployeeIds->currentPage(),
            ['path' => $request->url(), 'query' => $request->query()]
        ));
    }


    /**
     * Store a newly created availability record.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_employee_id' => 'required|exists:store_employees,id',
            'date' => 'required|date',
            'is_available' => 'required|boolean',
            'reason' => 'nullable|string|max:255',
        ]);

        $availability = Availability::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Availability record created successfully.',
            'data' => $availability,
        ], 201);
    }

    /**
     * Display the specified availability record.
     */
    public function show(Request $request, $id)
    {
        $storeEmployeeId = $request->input('store_employee_id');
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        // Validate inputs
        $request->validate([
            'store_employee_id' => 'required|exists:store_employees,id',
            'month' => 'required|date_format:Y-m',
        ]);

        // Fetch availability and leave data for the store employee
        $data = $this->getAvailabilityAndLeaves($storeEmployeeId, $month, $perPage, $page);

        $startOfMonth = Carbon::parse($month)->startOfMonth();
        $endOfMonth = Carbon::parse($month)->endOfMonth();

        // Collect all dates in the month
        $allDates = [];
        for ($date = $startOfMonth; $date->lte($endOfMonth); $date->addDay()) {
            $allDates[$date->toDateString()] = [
                'is_available' => true,
                'reason' => null
            ]; // Default to available
        }

        // Process unavailable dates if $data is not empty
        if (!empty($data) && is_iterable($data)) {
            foreach ($data as $item) {
                if (isset($item['date']) && array_key_exists($item['date'], $allDates)) {
                    $allDates[$item['date']] = [
                        'is_available' => false,
                        'reason' => $item['reason'] ?? 'No reason provided' // Add reason if available
                    ];
                }
            }
        }

        // Separate available and unavailable dates
        $availableDates = array_keys(array_filter($allDates, fn($entry) => $entry['is_available']));
        $unavailableDates = array_filter($allDates, fn($entry) => !$entry['is_available']);

        // Format the response
        return response()->json([
            'success' => true,
            'data' => [
                'store_employee_id' => $storeEmployeeId,
                'month' => $month,
                'available_dates' => $availableDates,
                'unavailable_dates' => array_map(fn($date, $info) => [
                    'date' => $date,
                    'reason' => $info['reason']
                ], array_keys($unavailableDates), $unavailableDates),
            ],
        ]);
    }


    
    
    /**
     * Update the specified availability record.
     */
    public function update(Request $request, Availability $availability)
    {
        $validated = $request->validate([
            'is_available' => 'required|boolean',
            'reason' => 'nullable|string|max:255',
        ]);

        $availability->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Availability record updated successfully.',
            'data' => $availability,
        ]);
    }
}
