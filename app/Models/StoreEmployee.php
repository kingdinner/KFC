<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PayRate;
use App\Models\StarStatus;
use App\Models\Employee;
use App\Models\Rating;
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

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }
}
