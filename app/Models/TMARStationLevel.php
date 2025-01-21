<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TMARStationLevel extends Model
{
    protected $table = 'tmar_station_levels';
    use HasFactory;

    protected $fillable = [
        'tmar_achievements_id',
        'station_type',
        'level',
        'date',
    ];

    public function tmarAchievement()
    {
        return $this->belongsTo(TMARAchievement::class);
    }
}
