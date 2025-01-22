<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Models\Availability;
use App\Models\Leave;
use App\Models\StoreEmployee;
use Illuminate\Pagination\LengthAwarePaginator;

trait HandlesAvailability
{
    /**
     * Merge Availability and Leave data for a specified month and storeEmployeeId.
     *
     * @param int $storeEmployeeId
     * @param string $month
     * @param int $perPage
     * @param int $page
     * @return LengthAwarePaginator
     */
    public function getAvailabilityAndLeaves($storeEmployeeId, $month, $perPage = 10, $page = 1)
    {
        $startOfMonth = Carbon::parse($month)->startOfMonth();
        $endOfMonth = Carbon::parse($month)->endOfMonth();

        // Query availability for the specified month
        $availability = Availability::with('storeEmployee')
        ->where('store_employee_id', $storeEmployeeId)
        ->where('status', 'Approved') // Only fetch approved availability records
        ->whereBetween('date', [$startOfMonth, $endOfMonth])
        ->get();
    

        // Fetch leaves for the employee during the specified month
        $leaves = Leave::where('employee_id', function ($query) use ($storeEmployeeId) {
                $query->select('employee_id')
                    ->from('store_employees')
                    ->where('id', $storeEmployeeId);
            })
            ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('date_applied', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('date_ended', [$startOfMonth, $endOfMonth]);
            })
            ->get();

        // Add leave dates to availability
        foreach ($leaves as $leave) {
            $leaveStart = Carbon::parse($leave->date_applied)->startOfDay();
            $leaveEnd = Carbon::parse($leave->date_ended)->endOfDay();

            // Ensure leave dates are within the specified month
            $leaveStart = $leaveStart->greaterThanOrEqualTo($startOfMonth) ? $leaveStart : $startOfMonth;
            $leaveEnd = $leaveEnd->lessThanOrEqualTo($endOfMonth) ? $leaveEnd : $endOfMonth;

            // Generate leave period entries
            $period = Carbon::parse($leaveStart);
            while ($period->lessThanOrEqualTo($leaveEnd)) {
                // Avoid duplication in availability data
                if (!$availability->contains('date', $period->toDateString())) {
                    $availability->push([
                        'id' => $leave->id, // Use Leave model's ID
                        'store_employee_id' => $storeEmployeeId,
                        'date' => $period->toDateString(),
                        'is_available' => false,
                        'reason' => 'On Leave',
                        'status' => $leave->status,
                        'created_at' => null,
                        'updated_at' => null,
                        'store_employee' => StoreEmployee::find($storeEmployeeId),
                    ]);
                }
                $period->addDay();
            }
        }

        // Sort by date
        $sortedAvailability = $availability->sortBy('date');

        // Paginate the merged collection
        $paginatedAvailability = new LengthAwarePaginator(
            $sortedAvailability->forPage($page, $perPage),
            $sortedAvailability->count(),
            $perPage,
            $page
        );

        return $paginatedAvailability;
    }
}
