<?php

namespace App\Http\Controllers\API\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\Availability;
use Illuminate\Http\Request;
use App\Traits\HandlesApprovals;

class LeaveController extends Controller
{
    use HandlesApprovals;

    /**
     * Handle Leave Action
     */
    public function handleLeaveAction(Request $request, Leave $leave)
    {
        // Validate the JSON payload
        $validated = $request->validate([
            'action' => 'required|string|in:Approve,Reject',
        ]);
    
        // Perform the approval or rejection
        $response = $this->handleApproval($request, $leave, 'Leave');
    
        // Update availability if the action is "Approve"
        if ($validated['action'] === 'Approve') {
            Availability::updateOrCreate(
                [
                    'store_employee_id' => $leave->employee_id,
                    'date' => $leave->date_applied,
                ],
                [
                    'is_available' => false,
                    'reason' => 'On Leave',
                ]
            );
        }
    
        return $response;
    }    

    public function createLeaveRequest(Request $request)
    {
        // Validate the leave request input
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|in:VL,SL,EL', // Adding the type of leave condition
            'date_applied' => 'required|date|after_or_equal:today',
            'date_ended' => 'required|date',  // Max 30 days
            'reporting_manager' => 'required|string|max:255',
            'reasons' => 'required|string|max:500',
        ]);

        // Create a new leave request
        $leave = Leave::create([
            'employee_id' => $validated['employee_id'],
            'type' => $validated['type'],
            'date_applied' => $validated['date_applied'],
            'date_ended' => $validated['date_ended'],
            'reporting_manager' => $validated['reporting_manager'],
            'reasons' => $validated['reasons'],
            'status' => 'Pending', // Default status
        ]);

        return response()->json([
            'message' => 'Leave request submitted successfully.',
            'leave' => $leave
        ], 201);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10); 
        $search = $request->input('search');

        $leavesQuery = Leave::with([
            'employee:id,firstname,lastname,email_address',
        ]);

        if ($search) {
            $leavesQuery->whereHas('employee', function ($query) use ($search) {
                $query->where('firstname', 'like', '%' . $search . '%')
                    ->orWhere('lastname', 'like', '%' . $search . '%')
                    ->orWhere('email_address', 'like', '%' . $search . '%');
            });
        }

        $leaves = $leavesQuery->paginate($perPage);

        return response()->json([
            'success' => true,
            'current_page' => $leaves->currentPage(),
            'total_pages' => $leaves->lastPage(),
            'total_records' => $leaves->total(),
            'data' => $leaves->items(),
            'pagination' => [
                'per_page' => $leaves->perPage(),
                'next_page_url' => $leaves->nextPageUrl(),
                'prev_page_url' => $leaves->previousPageUrl(),
            ],
        ]);
    }


    /**
     * Format Leave Member Data
     */
    private function formatLeaveData($leave)
    {
        return [
            'id' => $leave->id,
            'employee' => $this->getEmployeeFullName($leave),
            'type' => $leave->type, // Include the type of leave
            'date_applied' => $leave->date_applied,
            'date_ended' => $leave->date_ended,
            'reporting_manager' => $leave->reporting_manager,
            'reasons' => $leave->reasons,
            'status' => $leave->status,
        ];
    }

    /**
     * Generate Employee Full Name
     */
    private function getEmployeeFullName($leave)
    {
        $employee = optional($leave->employee);
        return "{$employee->firstname} {$employee->lastname}";
    }
}
