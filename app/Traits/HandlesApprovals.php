<?php

namespace App\Traits;

use Illuminate\Http\Request;
use App\Models\StoreEmployee;
use App\Models\BorrowTeamMember;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

trait HandlesApprovals
{
    /**
     * Handle Approve/Reject Action
     *
     * @param Request $request
     * @param Model $model
     * @param string $modelName
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleApproval(Request $request, Model $model, string $modelName)
    {
        // Ensure only managers can approve or reject requests
        if (!auth()->user()->hasRole('manager')) {
            return response()->json([
                'message' => "Unauthorized: Only managers can take action on {$modelName} requests."
            ], 403);
        }

        // Validate the action input
        $validated = $request->validate([
            'action' => 'required|string|in:Approve,Reject',
            'reason' => 'required_if:action,Reject|string|max:255'
        ]);

        // Update the model status and reason if rejected
        $model->update([
            'status' => $validated['action'] === 'Approve' ? 'Approved' : 'Rejected',
            'reason' => $validated['action'] === 'Reject' ? $validated['reason'] : null,
        ]);

        if ($validated['action'] === 'Approve' && $model instanceof BorrowTeamMember) {
            try {
                $storeEmployee = StoreEmployee::create([
                    'store_id' => $model->borrowed_store_id,
                    'employee_id' => $model->employee_id,
                    'start_date' => $model->borrowed_date,
                    'end_date' => $model->borrowed_date,
                    'status' => 'temporary',
                ]);
                Log::info('Created StoreEmployee:', $storeEmployee->toArray());
            } catch (\Exception $e) {
                Log::error('Error creating StoreEmployee:', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Failed to create StoreEmployee entry.'], 500);
            }
        }

        return response()->json([
            'message' => "{$modelName} request has been {$model->status}.",
            'data' => $model
        ]);
    }

}
