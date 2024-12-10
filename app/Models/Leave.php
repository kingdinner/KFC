<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date_applied',
        'duration',
        'reporting_manager',
        'reasons',
        'status',
    ];

    /**
     * Relationship to Employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}