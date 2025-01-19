<?php

namespace App\Http\Controllers\API\DataManagement;

use App\Models\Store;
use App\Traits\HandlesHelperController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StoreController extends Controller
{
    use HandlesHelperController;
    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));
        $perPage = (int) $request->input('per_page', 10);

        $storesQuery = Store::query();

        if (!empty($search)) {
            $storesQuery->where(function ($query) use ($search) {
                $query->where('name', 'ILIKE', "%{$search}%")
                      ->orWhere('store_code', 'ILIKE', "%{$search}%")
                      ->orWhere('cost_center', 'ILIKE', "%{$search}%")
                      ->orWhere('asset_type', 'ILIKE', "%{$search}%");
            });
        }

        $stores = $storesQuery->paginate($perPage);

        return $this->paginateResponse($stores);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'cost_center' => 'required|string|max:255',
            'asset_type' => 'required|string|max:255',
            'store_code' => 'required|string|max:255|unique:stores,store_code',
        ]);

        try {
            // Create the store using only validated data
            $store = Store::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Store created successfully.',
                'data' => $store
            ], 201);
        } catch (\Exception $e) {
            // Handle unexpected errors
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the store.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $store_code)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'cost_center' => 'required|string|max:255',
            'asset_type' => 'required|string|max:255',
        ]);

        // Find the store by store_code
        $store = Store::where('store_code', $store_code)->firstOrFail();

        $store->update($request->only(['name', 'cost_center', 'asset_type']));

        return response()->json([
            'success' => true,
            'message' => 'Store updated successfully.',
            'data' => $store
        ], 200);
    }


    public function destroy(Store $store)
    {
        $store->delete();

        return response()->json([
            'success' => true,
            'message' => 'Store deleted successfully.'
        ], 200);
    }
}