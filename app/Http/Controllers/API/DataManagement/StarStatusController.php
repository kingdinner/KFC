<?php

namespace App\Http\Controllers\API\DataManagement;

use Illuminate\Http\Request;
use App\Models\StarStatus;
use App\Http\Controllers\Controller;

class StarStatusController extends Controller
{
    /**
     * Display a listing of star statuses.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            // Retrieve all star statuses
            $starStatuses = StarStatus::all();

            return response()->json([
                'success' => true,
                'data' => $starStatuses
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve star statuses'
            ], 500);
        }
    }

    /**
     * Add a new star status.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'store_employee_id' => 'required|exists:store_employees,id',
            'name' => 'required|string|max:255',
            'reason' => 'nullable|string',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        try {
            // Create a new star status
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

    /**
     * Update an existing star status.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'store_employee_id' => 'required|exists:store_employees,id',
            'name' => 'required|string|max:255',
            'reason' => 'nullable|string',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        try {
            // Find and update the star status
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

    /**
     * Delete a star status.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            // Find and delete the star status
            $starStatus = StarStatus::findOrFail($id);
            $starStatus->delete();

            return response()->json([
                'success' => true,
                'message' => 'Star status deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete star status'
            ], 500);
        }
    }

    /**
     * Search for star statuses.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function search($status)
    {
        $status = trim($status);

        if (empty($status)) {
            return response()->json([
                'success' => false,
                'message' => 'Status name parameter is required'
            ], 400);
        }

        try {
            $results = StarStatus::where('name', 'like', "%$status%")
                ->orWhere('reason', 'like', "%$status%")
                ->get();

            return response()->json([
                'success' => true,
                'data' => $results
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search star statuses'
            ], 500);
        }
    }
}