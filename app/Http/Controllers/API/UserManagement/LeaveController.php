<?php

namespace App\Http\Controllers\API\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    /**
     * Handle Leave Action (Approve or Reject)
     */
    public function handleLeaveAction(Request $request, Leave $leave)
    {
        // Ensure only managers can approve or reject leaves
        if (!auth()->user()->hasRole('manager')) {
            return response()->json([
                'message' => 'Unauthorized: Only managers can take action on leave requests.'
            ], 403);
        }

        // Validate the request input
        $validated = $request->validate([
            'action' => 'required|string|in:Approve,Reject',
        ]);

        // Update the leave status based on the action
        $leave->update([
            'status' => $validated['action'] === 'Approve' ? 'Approved' : 'Rejected',
        ]);

        return response()->json([
            'message' => "Leave request has been {$leave->status}.",
            'leave' => $leave
        ]);
    }
    public function createLeaveRequest(Request $request)
    {
        // Validate the leave request input
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date_applied' => 'required|date|after_or_equal:today',
            'duration' => 'required|integer|min:1|max:30',  // Max 30 days
            'reporting_manager' => 'required|string|max:255',
            'reasons' => 'required|string|max:500',
        ]);

        // Create a new leave request
        $leave = Leave::create([
            'employee_id' => $validated['employee_id'],
            'date_applied' => $validated['date_applied'],
            'duration' => $validated['duration'] . ' days',
            'reporting_manager' => $validated['reporting_manager'],
            'reasons' => $validated['reasons'],
            'status' => 'Pending', // Default status
        ]);

        return response()->json([
            'message' => 'Leave request submitted successfully.',
            'leave' => $leave
        ], 201);
    }
}
