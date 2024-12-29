<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ratings extends Model
{
    use HasFactory;

    protected $table = 'ratings';

    protected $fillable = [
        'store_employee_id',
        'food_safety_certification_date',
        'champs_certification_date',
        'restaurant_basic_certification_date',
        'foh_certification_date',
        'moh_certification_date',
        'boh_certification_date',
        'kitchen_station_level',
        'kitchen_station_certification_date',
        'counter_station_level',
        'counter_station_certification_date',
        'dining_station_level',
        'dining_station_certification_date',
        'tenure_in_months',
        'retention_90_days',
        'remarks'
    ];

    public function storeEmployee()
    {
        return $this->belongsTo(StoreEmployee::class);
    }
}