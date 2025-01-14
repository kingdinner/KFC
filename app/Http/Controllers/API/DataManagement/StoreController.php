<?php

namespace App\Http\Controllers\API\DataManagement;

use App\Models\Store;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StoreController extends Controller
{
    public function index()     
    {         
        $stores = Store::paginate(10); // Adjust the number as needed
        return response()->json([             
            'success' => true,             
            'data' => $stores         
        ], 200);     
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


    public function show(Store $store)
    {
        return response()->json([
            'success' => true,
            'data' => $store
        ], 200);
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

        // Update the store with the provided data
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

    public function search($storeName = null)     
    {         
        $storeName = trim($storeName);              

        if (empty($storeName)) {             
            $stores = Store::paginate(10); // Return paginated stores if no search term is provided             
            return response()->json([                 
                'success' => true,                 
                'data' => $stores,             
            ], 200);         
        }              

        $stores = Store::where('name', 'LIKE', "%{$storeName}%")                         
                      ->orWhere('store_code', 'LIKE', "%{$storeName}%")                         
                      ->paginate(10); // Paginate search results              

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