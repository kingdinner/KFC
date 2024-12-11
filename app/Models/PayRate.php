<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayRate extends Model
{
    use HasFactory;

    protected $table = 'pay_rates';

    protected $fillable = [
        'position',
        'hourly_rate',
        'rate_label',
    ];

    public function storeEmployee()
    {
        return $this->belongsTo(StoreEmployee::class);
    }
}