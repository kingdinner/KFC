<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'position',
        'rate_label',
        'hourly_rate',
        'store_employee_id',
    ];

    /**
     * Relationship with StoreEmployee
     */
    public function storeEmployee()
    {
        return $this->belongsTo(StoreEmployee::class);
    }
}
