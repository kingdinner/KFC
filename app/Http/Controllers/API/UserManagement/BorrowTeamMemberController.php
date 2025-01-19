<?php

namespace App\Http\Controllers\API\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\BorrowTeamMember;
use Illuminate\Http\Request;
use App\Traits\HandlesApprovals;
use App\Traits\HandlesHelperController;

class BorrowTeamMemberController extends Controller
{
    use HandlesApprovals, HandlesHelperController;

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

    public function index(Request $request, $borrowType)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $borrowedMembersQuery = BorrowTeamMember::with([
            'employee:id,firstname,lastname',
            'borrowedStore:id,name',
            'transferredStore:id,name',
        ])->where('borrow_type', $borrowType);

        if ($search) {
            $borrowedMembersQuery->whereHas('employee', function ($query) use ($search) {
                $query->where('firstname', 'like', '%' . $search . '%')
                      ->orWhere('lastname', 'like', '%' . $search . '%');
            })
            ->orWhereHas('borrowedStore', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->orWhereHas('transferredStore', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            });
        }

        $borrowedMembers = $borrowedMembersQuery->paginate($perPage);

        return $this->paginateResponse($borrowedMembers);
    }

    public function createBorrowRequest(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'borrowed_store_id' => 'required|exists:stores,id',
            'borrowed_date' => 'required|date|after_or_equal:today',
            'borrowed_time' => 'required|date_format:H:i',
            'borrow_type' => 'required|in:swap,borrow',
            'skill_level' => 'required|string',
            'reason' => 'required|string|max:255',
        ]);

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

    private function formatBorrowData($member)
    {
        return [
            'id' => $member->id,
            'employee' => $this->getEmployeeFullName($member),
            'original_store' => optional($member->employee->stores->first())->only(['id', 'name']),
            'borrowed_store' => [
                'id' => optional($member->borrowedStore)->id,
                'name' => optional($member->borrowedStore)->name
            ],
            'borrowed_date' => $member->borrowed_date,
            'borrowed_time' => $member->borrowed_time,
            'borrow_type' => $member->borrow_type,
            'skill_level' => $member->skill_level,
            'transferred_store' => [
                'id' => optional($member->transferredStore)->id,
                'name' => optional($member->transferredStore)->name
            ],
            'transferred_date' => $member->transferred_date,
            'transferred_time' => $member->transferred_time,
            'status' => $member->status,
            'reason' => $member->reason,
        ];
    }

    private function getEmployeeFullName($member)
    {
        return optional($member->employee)->firstname . ' ' . optional($member->employee)->lastname;
    }
}
