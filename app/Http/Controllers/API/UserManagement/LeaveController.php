<?php

namespace App\Http\Controllers\API\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\Leave;
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
        return $this->handleApproval($request, $leave, 'Leave');
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

    public function index(Request $request)
    {
        // Set pagination with default values
        $perPage = $request->input('per_page', 10);  // Default to 10 if not provided
    
        // Fetch leave records with employee relationships
        $leaves = Leave::with([
            'employee:id,firstname,lastname,email_address',
        ])->paginate($perPage);
    
        // Format and return the response
        return response()->json([
            'success' => true,
            'current_page' => $leaves->currentPage(),
            'total_pages' => $leaves->lastPage(),
            'total_records' => $leaves->total(),
            'data' => $leaves->map(fn($leave) => $this->formatLeaveData($leave)),
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
            'date_applied' => $leave->date_applied,
            'duration' => $leave->duration,
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
