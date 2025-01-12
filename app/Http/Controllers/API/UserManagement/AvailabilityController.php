<?php

namespace App\Http\Controllers\API\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\Availability;
use Illuminate\Http\Request;
use App\Traits\HandlesAvailability;
use Carbon\Carbon;

class AvailabilityController extends Controller
{
    use HandlesAvailability;
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        // Fetch availability and leave data for all employees in the current page
        $storeEmployeeIds = Availability::pluck('store_employee_id')->unique();

        $availability = collect();
        foreach ($storeEmployeeIds as $storeEmployeeId) {
            $availability = $availability->merge(
                $this->getAvailabilityAndLeaves($storeEmployeeId, Carbon::now()->format('Y-m'), $perPage, $page)
            );
        }

        return response()->json([
            'success' => true,
            'data' => $availability,
        ]);
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
        $month = $request->input('month'); // Format: YYYY-MM
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        // Validate inputs
        $validated = $request->validate([
            'store_employee_id' => 'required|exists:store_employees,id',
            'month' => 'required|date_format:Y-m',
        ]);

        // Get paginated availability and leave data
        $availability = $this->getAvailabilityAndLeaves($storeEmployeeId, $month, $perPage, $page);

        // If no availability or leave records exist for the month
        if ($availability->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'store_employee_id' => $storeEmployeeId,
                    'month' => $month,
                    'is_available' => true,
                    'reason' => 'Entire month is available.',
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $availability,
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
