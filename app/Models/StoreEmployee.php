<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PayRate;
use App\Models\StarStatus;

class StoreEmployee extends Model
{
    use HasFactory;

    protected $table = 'store_employees';

    protected $fillable = [
        'store_id',
        'employee_id',
        'start_date',
        'end_date',
    ];

    public function payRate()
    {
        return $this->hasOne(PayRate::class);
    }

    public function starStatuses()
    {
        return $this->hasMany(StarStatus::class);
    }
}