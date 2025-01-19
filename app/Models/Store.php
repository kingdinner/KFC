<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'cost_center',
        'asset_type',
        'store_code',
        'level',
        'costcenter_text',
        'Area',
        'operating_hours',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function employeesThroughStore()
{
    return $this->hasManyThrough(Employee::class, StoreEmployee::class, 'store_id', 'id', 'id', 'employee_id');
}

}
