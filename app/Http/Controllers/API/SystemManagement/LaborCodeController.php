<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LaborCode;

class LaborCodeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return LaborCode::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255',
            'rate' => 'required|numeric'
        ]);

        $laborCode = LaborCode::create($validated);
        return response()->json($laborCode, 201);
    }

    public function show(LaborCode $laborCode)
    {
        return $laborCode;
    }

    public function update(Request $request, LaborCode $laborCode)
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:255',
            'rate' => 'sometimes|numeric'
        ]);

        $laborCode->update($validated);
        return response()->json($laborCode);
    }

    public function destroy(LaborCode $laborCode)
    {
        $laborCode->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }

}
