<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Availability extends Model
{
    use HasFactory;

    protected $table = 'availability'; // Ensure this matches your actual table name

    public $timestamps = false; // Disable timestamps if not present in the table

    protected $fillable = [
        'store_employee_id',
        'date',
        'is_available',
        'reason',
        'additional_shift', // Indicates if the shift is additional
        'swap_shift_from',  // Date of the shift being swapped from
        'swap_shift_to',    // Preferred date for the swapped shift
        'swap_reason',      // Reason for the swap
        'status',
        'approval_reason', // Reason for approval/rejection
    ];

    /**
     * Define the relationship with the StoreEmployee model.
     */
    public function storeEmployee()
    {
        return $this->belongsTo(StoreEmployee::class, 'store_employee_id');
    }
}
