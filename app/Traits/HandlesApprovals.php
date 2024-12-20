<?php

namespace App\Traits;

use Illuminate\Http\Request;
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

        return response()->json([
            'message' => "{$modelName} request has been {$model->status}.",
            'data' => $model
        ]);
    }
}
