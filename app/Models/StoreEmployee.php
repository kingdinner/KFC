<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreEmployee extends Model
{
    use HasFactory;

    protected $table = 'store_employees';

    protected $fillable = [
        'store_id',
        'employee_id',
        'start_date',
        'end_date',
        'status'
    ];

    public function payRate()
    {
        return $this->hasOne(PayRate::class);
    }

    public function starStatuses()
    {
        return $this->hasMany(StarStatus::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
