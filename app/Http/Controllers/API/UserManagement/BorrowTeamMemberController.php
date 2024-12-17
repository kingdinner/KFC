<?php

namespace App\Http\Controllers\API\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\BorrowTeamMember;
use Illuminate\Http\Request;
use App\Traits\HandlesApprovals;

class BorrowTeamMemberController extends Controller
{
    use HandlesApprovals;

    /**
     * Handle Borrow Request Action
     */
    public function handleBorrowRequestAction(Request $request, BorrowTeamMember $borrowTeamMember)
    {
        return $this->handleApproval($request, $borrowTeamMember, 'Borrow Team Member');
    }

    public function handleSwapRequestAction(Request $request, BorrowTeamMember $swapTeamMember)
    {
        return $this->handleApproval($request, $swapTeamMember, 'Swap Team Member');
    }

    public function swapIndex(Request $request)
    {
        return $this->index($request, 'swap');
    }

    public function borrowIndex(Request $request)
    {
        return $this->index($request, 'borrow');
    }
    /**
     * Display all borrowed team members with relationships based on endpoint.
     */
    public function index(Request $request, $borrowType)
    {
        // Fetch borrowed team members with pagination
        $perPage = $request->input('per_page', 10);  // Default to 10 records per page
        $borrowedMembers = BorrowTeamMember::with([
            'employee:id,firstname,lastname',
            'borrowedStore:id,name',
            'transferredStore:id,name',
        ])
        ->where('borrow_type', $borrowType)
        ->paginate($perPage);

        // Format and return paginated response
        return response()->json([
            'success' => true,
            'current_page' => $borrowedMembers->currentPage(),
            'total_pages' => $borrowedMembers->lastPage(),
            'total_records' => $borrowedMembers->total(),
            'data' => $borrowedMembers->map(fn($member) => $this->formatBorrowData($member)),
        ]);
    }

    /**
     * Create a Borrow Team Member Request
     */
    public function createBorrowRequest(Request $request)
    {
        // Validate incoming request
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'borrowed_store_id' => 'required|exists:stores,id',
            'borrowed_date' => 'required|date|after_or_equal:today',
            'borrowed_time' => 'required|date_format:H:i',
            'borrow_type' => 'required|in:swap,borrow',
            'skill_level' => 'required|string',
            'reason' => 'required|string|max:255',
        ]);

        // Create the borrow request
        $borrowRequest = BorrowTeamMember::create([
            'employee_id' => $validated['employee_id'],
            'borrowed_store_id' => $validated['borrowed_store_id'],
            'borrowed_date' => $validated['borrowed_date'],
            'borrowed_time' => $validated['borrowed_time'],
            'borrow_type' => $validated['borrow_type'],
            'skill_level' => $validated['skill_level'],
            'status' => 'Pending', 
            'reason' => $validated['reason'],
        ]);

        return response()->json([
            'message' => 'Borrow request submitted successfully.',
            'borrow_request' => $borrowRequest,
        ], 201);
    }

    /**
     * Format Borrow Team Member Data
     */
    private function formatBorrowData($member)
    {
        return [
            'id' => $member->id,
            'employee' => $this->getEmployeeFullName($member),
            'borrowed_store' => optional($member->borrowedStore)->name,
            'borrowed_date' => $member->borrowed_date,
            'borrowed_time' => $member->borrowed_time,
            'borrow_type' => $member->borrow_type,
            'skill_level' => $member->skill_level,
            'transferred_store' => optional($member->transferredStore)->name,
            'transferred_date' => $member->transferred_date,
            'transferred_time' => $member->transferred_time,
            'status' => $member->status,
            'reason' => $member->reason,
        ];
    }

    /**
     * Generate Employee Full Name
     */
    private function getEmployeeFullName($member)
    {
        return optional($member->employee)->firstname . ' ' . optional($member->employee)->lastname;
    }
}
