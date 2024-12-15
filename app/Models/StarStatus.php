<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\StoreEmployee;

class StarStatus extends Model
{
    use HasFactory;

    protected $table = 'star_statuses';

    protected $fillable = [
        'store_employee_id', 
        'name', 
        'reason', 
        'status'
    ];

    public function storeEmployee()
    {
        return $this->belongsTo(StoreEmployee::class);
    }
}