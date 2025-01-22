<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LaborSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'filename',
        'schedule_array',
    ];

    // Ensure schedule_array is cast to and from JSON automatically
    protected $casts = [
        'schedule_array' => 'array',
    ];
}
