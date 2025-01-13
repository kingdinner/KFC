<?php

namespace App\Http\Controllers\API\DataManagement;

use App\Models\Store;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StoreController extends Controller
{
    public function index()
    {
        $stores = Store::all();
        return response()->json([
            'success' => true,
            'data' => $stores
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'cost_center' => 'required|string|max:255',
            'asset_type' => 'required|string|max:255',
            'store_code' => 'required|string|max:255|unique:stores',
        ]);

        $store = Store::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Store created successfully.',
            'data' => $store
        ], 201);
    }

    public function show(Store $store)
    {
        return response()->json([
            'success' => true,
            'data' => $store
        ], 200);
    }

    public function update(Request $request, Store $store)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'cost_center' => 'required|string|max:255',
            'asset_type' => 'required|string|max:255',
            'store_code' => 'required|string|max:255|unique:stores,store_code,' . ($store->id ?? 'null'), //Added Fallbacks
        ]);

        $store->update($request->all());

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

    public function search($storeName = null)
    {
        $storeName = trim($storeName);
    
        if (empty($storeName)) {
            // Return all stores if no storeName is provided
            $stores = Store::all();
            return response()->json([
                'success' => true,
                'data' => $stores,
            ], 200);
        }
    
        // Perform a search
        $stores = Store::where('name', 'LIKE', "%{$storeName}%")
                    ->orWhere('store_code', 'LIKE', "%{$storeName}%")
                    ->get();
    
        if ($stores->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No stores found for the given search term.',
            ], 404);
        }
    
        return response()->json([
            'success' => true,
            'data' => $stores,
        ], 200);
    }
    

}