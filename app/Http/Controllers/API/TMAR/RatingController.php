<?php

namespace App\Http\Controllers\API\TMAR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StoreEmployee;

class RatingController extends Controller
{
    public function index($storeEmployeeId)
    {
        $storeEmployee = StoreEmployee::find($storeEmployeeId);

        if (!$storeEmployee) {
            return response()->json(['message' => 'Store employee not found'], 404);
        }

        $ratings = $storeEmployee->ratings;

        if ($ratings->isEmpty()) {
            return response()->json(['message' => 'No ratings found for this store employee'], 404);
        }
        
        return response()->json([
            'data' => $ratings
        ], 200);
    }

    /**
     * Display a specific rating for a store employee.
     *
     * @param int $storeEmployeeId
     * @param int $ratingId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($storeEmployeeId, $ratingId)
    {
        $storeEmployee = StoreEmployee::find($storeEmployeeId);

        if (!$storeEmployee) {
            return response()->json(['message' => 'Store employee not found'], 404);
        }

        $rating = $storeEmployee->ratings()->find($ratingId);

        if (!$rating) {
            return response()->json(['message' => 'Rating not found'], 404);
        }

        return response()->json([
            'data' => $rating
        ], 200);
    }

    /**
     * Store a newly created rating for a store employee.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $storeEmployeeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $storeEmployeeId)
    {
        $storeEmployee = StoreEmployee::find($storeEmployeeId);

        if (!$storeEmployee) {
            return response()->json(['message' => 'Store employee not found'], 404);
        }

        // Validate incoming request
        $validatedData = $request->validate([
            'food_safety_certification_date' => 'nullable|date',
            'champs_certification_date' => 'nullable|date',
            'restaurant_basic_certification_date' => 'nullable|date',
            'foh_certification_date' => 'nullable|date',
            'moh_certification_date' => 'nullable|date',
            'boh_certification_date' => 'nullable|date',
            'kitchen_station_level' => 'nullable|string|max:50',
            'kitchen_station_certification_date' => 'nullable|date',
            'counter_station_level' => 'nullable|string|max:50',
            'counter_station_certification_date' => 'nullable|date',
            'dining_station_level' => 'nullable|string|max:50',
            'dining_station_certification_date' => 'nullable|date',
            'tenure_in_months' => 'nullable|numeric',
            'retention_90_days' => 'nullable|boolean',
            'remarks' => 'nullable|string|max:255',
        ]);

        // Create the new rating for the employee
        $rating = $storeEmployee->ratings()->create($validatedData);

        return response()->json(['message' => 'Rating created successfully', 'data' => $rating], 201);
    }

    /**
     * Update the specified rating for a store employee.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $storeEmployeeId
     * @param int $ratingId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $storeEmployeeId, $ratingId)
    {
        $storeEmployee = StoreEmployee::find($storeEmployeeId);

        if (!$storeEmployee) {
            return response()->json(['message' => 'Store employee not found'], 404);
        }

        $rating = $storeEmployee->ratings()->find($ratingId);

        if (!$rating) {
            return response()->json(['message' => 'Rating not found'], 404);
        }

        // Validate incoming request
        $validatedData = $request->validate([
            'food_safety_certification_date' => 'nullable|date',
            'champs_certification_date' => 'nullable|date',
            'restaurant_basic_certification_date' => 'nullable|date',
            'foh_certification_date' => 'nullable|date',
            'moh_certification_date' => 'nullable|date',
            'boh_certification_date' => 'nullable|date',
            'kitchen_station_level' => 'nullable|string|max:50',
            'kitchen_station_certification_date' => 'nullable|date',
            'counter_station_level' => 'nullable|string|max:50',
            'counter_station_certification_date' => 'nullable|date',
            'dining_station_level' => 'nullable|string|max:50',
            'dining_station_certification_date' => 'nullable|date',
            'tenure_in_months' => 'nullable|numeric',
            'retention_90_days' => 'nullable|boolean',
            'remarks' => 'nullable|string|max:255',
        ]);

        // Update the rating
        $rating->update($validatedData);

        return response()->json(['message' => 'Rating updated successfully', 'data' => $rating]);
    }
}
