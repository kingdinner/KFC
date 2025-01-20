<?php

namespace App\Http\Controllers\API\DataManagement;

use App\Traits\HandlesHelperController;
use Illuminate\Http\Request;
use App\Models\StarStatus;
use App\Http\Controllers\Controller;

class StarStatusController extends Controller
{
    use HandlesHelperController;
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $starStatusesQuery = StarStatus::query();

        if ($search) {
            $lowerSearchValue = strtolower($search);

            $starStatusesQuery->whereRaw('LOWER(name) LIKE ?', ["%{$lowerSearchValue}%"])
                ->orWhereRaw('LOWER(reason) LIKE ?', ["%{$lowerSearchValue}%"]);
        }

        $starStatuses = $starStatusesQuery->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $starStatuses->items(),
            'current_page' => $starStatuses->currentPage(),
            'from' => $starStatuses->firstItem(),
            'to' => $starStatuses->lastItem(),
            'per_page' => $starStatuses->perPage(),
            'total' => $starStatuses->total(),
            'last_page' => $starStatuses->lastPage(),
            'next_page_url' => $starStatuses->nextPageUrl(),
            'prev_page_url' => $starStatuses->previousPageUrl(),
            'links' => $starStatuses->linkCollection(),
        ], 200);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'reason' => 'nullable|string'
        ]);

        try {
            $starStatus = StarStatus::create($request->all());

            return response()->json([
                'success' => true,
                'data' => $starStatus
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add star status'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'reason' => 'nullable|string',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        try {
            $starStatus = StarStatus::findOrFail($id);
            $starStatus->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $starStatus
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update star status'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $starStatus = StarStatus::findOrFail($id);
            $starStatus->delete();

            return response()->json([
                'success' => true,
                'message' => 'Star status soft deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to soft delete star status'
            ], 500);
        }
    }
}
