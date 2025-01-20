<?php

namespace App\Http\Controllers\API\TMAR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StoreEmployee;
use App\Models\Rating;

class RatingController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $ratingsQuery = Rating::with(['storeEmployee.employee'])->withTrashed();

        if ($search) {
            $lowerSearchValue = strtolower($search);

            $ratingsQuery->where(function ($query) use ($lowerSearchValue) {
                $query->orWhereRaw('CAST(food_safety_certification_date AS TEXT) LIKE ?', ["%{$lowerSearchValue}%"])
                    ->orWhereRaw('CAST(champs_certification_date AS TEXT) LIKE ?', ["%{$lowerSearchValue}%"])
                    ->orWhereRaw('CAST(restaurant_basic_certification_date AS TEXT) LIKE ?', ["%{$lowerSearchValue}%"])
                    ->orWhereRaw('CAST(foh_certification_date AS TEXT) LIKE ?', ["%{$lowerSearchValue}%"])
                    ->orWhereRaw('CAST(moh_certification_date AS TEXT) LIKE ?', ["%{$lowerSearchValue}%"])
                    ->orWhereRaw('CAST(boh_certification_date AS TEXT) LIKE ?', ["%{$lowerSearchValue}%"])
                    ->orWhereRaw('LOWER(kitchen_station_level) LIKE ?', ["%{$lowerSearchValue}%"])
                    ->orWhereRaw('LOWER(counter_station_level) LIKE ?', ["%{$lowerSearchValue}%"])
                    ->orWhereRaw('LOWER(dining_station_level) LIKE ?', ["%{$lowerSearchValue}%"])
                    ->orWhereRaw('CAST(tenure_in_months AS TEXT) LIKE ?', ["%{$lowerSearchValue}%"])
                    ->orWhereRaw('LOWER(remarks) LIKE ?', ["%{$lowerSearchValue}%"])
                    ->orWhereRaw('CAST(store_employee_id AS TEXT) LIKE ?', ["%{$lowerSearchValue}%"])
                    ->orWhereHas('storeEmployee', function ($q) use ($lowerSearchValue) {
                        $q->whereHas('employee', function ($subQuery) use ($lowerSearchValue) {
                            $subQuery->whereRaw('LOWER(firstname) LIKE ?', ["%{$lowerSearchValue}%"])
                                ->orWhereRaw('LOWER(lastname) LIKE ?', ["%{$lowerSearchValue}%"]);
                        });
                    });
            });
        }

        $ratings = $ratingsQuery->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $ratings->items(),
            'current_page' => $ratings->currentPage(),
            'total' => $ratings->total(),
            'per_page' => $ratings->perPage(),
            'last_page' => $ratings->lastPage(),
            'next_page_url' => $ratings->nextPageUrl(),
            'prev_page_url' => $ratings->previousPageUrl(),
        ], 200);
    }


    public function show($storeEmployeeId, $ratingId)
    {
        $storeEmployee = StoreEmployee::find($storeEmployeeId);

        if (!$storeEmployee) {
            return response()->json(['message' => 'Store employee not found'], 404);
        }

        $rating = $storeEmployee->ratings()->withTrashed()->find($ratingId);

        if (!$rating) {
            return response()->json(['message' => 'Rating not found'], 404);
        }

        return response()->json(['data' => $rating], 200);
    }

    public function store(Request $request, $storeEmployeeId)
    {
        $storeEmployee = StoreEmployee::find($storeEmployeeId);

        if (!$storeEmployee) {
            return response()->json(['message' => 'Store employee not found'], 404);
        }

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

        $rating = $storeEmployee->ratings()->create($validatedData);

        return response()->json(['message' => 'Rating created successfully', 'data' => $rating], 201);
    }

    public function update(Request $request, $storeEmployeeId, $ratingId)
    {
        $storeEmployee = StoreEmployee::find($storeEmployeeId);

        if (!$storeEmployee) {
            return response()->json(['message' => 'Store employee not found'], 404);
        }

        $rating = $storeEmployee->ratings()->withTrashed()->find($ratingId);

        if (!$rating) {
            return response()->json(['message' => 'Rating not found'], 404);
        }

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

        $rating->update($validatedData);

        return response()->json(['message' => 'Rating updated successfully', 'data' => $rating]);
    }

    public function destroy($storeEmployeeId, $ratingId)
    {
        $storeEmployee = StoreEmployee::find($storeEmployeeId);

        if (!$storeEmployee) {
            return response()->json(['message' => 'Store employee not found'], 404);
        }

        $rating = $storeEmployee->ratings()->find($ratingId);

        if (!$rating) {
            return response()->json(['message' => 'Rating not found'], 404);
        }

        $rating->delete();

        return response()->json(['message' => 'Rating soft deleted successfully'], 200);
    }

    public function restore($storeEmployeeId, $ratingId)
    {
        $storeEmployee = StoreEmployee::find($storeEmployeeId);

        if (!$storeEmployee) {
            return response()->json(['message' => 'Store employee not found'], 404);
        }

        $rating = $storeEmployee->ratings()->onlyTrashed()->find($ratingId);

        if (!$rating) {
            return response()->json(['message' => 'Rating not found'], 404);
        }

        $rating->restore();

        return response()->json(['message' => 'Rating restored successfully', 'data' => $rating], 200);
    }
}
