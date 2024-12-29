<?php

namespace App\Http\Controllers\API\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\Availability;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $availability = Availability::with('storeEmployee')->paginate($perPage);

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
    public function show(Availability $availability)
    {
        $availability->load('storeEmployee');

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
