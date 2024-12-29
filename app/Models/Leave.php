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
        'date_ended',
        'reporting_manager',
        'reasons',
        'status',
    ];

    /**
     * Define relationship to Employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
