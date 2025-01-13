<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TmarReport extends Model
{
    use HasFactory;

    protected $table = 'tmar_reports';

    protected $fillable = [
        'pc',
        'area',
        'count_per_area',
        'store_number',
        'sas_name',
        'other_name',
        'star_0',
        'star_1',
        'star_2',
        'star_3',
        'star_4',
        'all_star',
        'team_leader',
        'sldc',
        'sletp',
        'total_team_member',
        'average_tenure',
        'retention_90_days',
        'restaurant_basics',
        'foh'
    ];

    // Optionally, specify the data types for casting
    protected $casts = [
        'star_0' => 'integer',
        'star_1' => 'integer',
        'star_2' => 'integer',
        'star_3' => 'integer',
        'star_4' => 'integer',
        'all_star' => 'integer',
        'count_per_area' => 'integer',
        'store_number' => 'integer',
        'total_team_member' => 'integer',
        'average_tenure' => 'decimal:2',
        'retention_90_days' => 'integer',
    ];
}
