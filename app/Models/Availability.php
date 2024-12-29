<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Availability extends Model
{
    use HasFactory;

    protected $table = 'availability';

    protected $fillable = [
        'store_employee_id',
        'date',
        'is_available',
        'reason',
    ];

    public function storeEmployee()
    {
        return $this->belongsTo(StoreEmployee::class);
    }
}