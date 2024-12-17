<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BorrowTeamMember extends Model
  {
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'borrowed_store_id',
        'borrowed_date',
        'borrowed_time',
        'borrow_type',
        'skill_level',
        'transferred_store_id',
        'transferred_date',
        'transferred_time',
        'status',
        'reason',
    ];

    /**
     * Relationships
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function borrowedStore()
    {
        return $this->belongsTo(Store::class, 'borrowed_store_id');
    }

    public function transferredStore()
    {
        return $this->belongsTo(Store::class, 'transferred_store_id');
    }
}