<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TMARAchievement extends Model
{
    protected $table = 'tmar_achievements';

    use HasFactory;

    protected $fillable = [
        'employee',
        'service_provider',
        'tenure_months',
        'ninety_day_retention',
        'all_star',
        'team_leader',
        'sletp',
        'resigned',
        'rtm',
        'remarks',
        'basic_certification',
        'food_safety',
        'champs_certification',
        'restaurant_basic',
        'fod',
        'mod',
        'boh',
        'basic',
        'certification',
        'sldc',
    ];

    public function stationLevels()
    {
        return $this->hasMany(TMARStationLevel::class, 'tmar_achievement_id');
    }
}
