<?php

namespace App\Http\Controllers\API\DataManagement;

use App\Traits\HandlesHelperController;
use Illuminate\Http\Request;
use App\Models\StarStatus;
use App\Http\Controllers\Controller;

use Illuminate\Pagination\LengthAwarePaginator;
class StarStatusController extends Controller
{
    use HandlesHelperController;
    public function index(Request $request)
    {
        $search = $request->query('search', '');
        $perPage = (int) $request->query('per_page', 10);

        $starStatusesQuery = StarStatus::query();

        if (!empty($search)) {
            $starStatusesQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%$search%")
                    ->orWhere('reason', 'like', "%$search%");

                if (is_numeric($search)) {
                    $query->orWhere('id', $search);
                }
            });
        }

        $starStatuses = $starStatusesQuery->paginate($perPage);

        $starStatusesData = $starStatuses->getCollection()->map(function ($starStatus) {
            return [
                'id' => $starStatus->id,
                'name' => $starStatus->name,
                'reason' => $starStatus->reason,
                'status' => $starStatus->status,
                'created_at' => $starStatus->created_at ? $starStatus->created_at->toDateTimeString() : null,
                'updated_at' => $starStatus->updated_at ? $starStatus->updated_at->toDateTimeString() : null,
                'deleted_at' => $starStatus->deleted_at,
            ];
        });

        $formattedPaginator = new LengthAwarePaginator(
            $starStatusesData,
            $starStatuses->total(),
            $starStatuses->perPage(),
            $starStatuses->currentPage(),
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json([
            'success' => true,
            'data' => $formattedPaginator->items(),
            'pagination' => [
                'current_page' => $formattedPaginator->currentPage(),
                'from' => $formattedPaginator->firstItem(),
                'to' => $formattedPaginator->lastItem(),
                'per_page' => $formattedPaginator->perPage(),
                'total' => $formattedPaginator->total(),
                'last_page' => $formattedPaginator->lastPage(),
                'next_page_url' => $formattedPaginator->nextPageUrl(),
                'prev_page_url' => $formattedPaginator->previousPageUrl(),
            ]
        ]);
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
