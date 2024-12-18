<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TmarSummary;

class TmarSummarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TmarSummary::create([
            'pc' => 'NLU',
            'area' => 'Area 1',
            'count_per_area' => 15,
            'store_number' => 101,
            'sas_name' => 'SAS One',
            'other_name' => 'Other One',
            'star_0' => 2,
            'star_1' => 5,
            'star_2' => 3,
            'star_3' => 1,
            'star_4' => 0,
            'all_star' => 11,
            'team_leader' => 'John Doe',
            'sldc' => 'SLDC 1',
            'sletp' => 'SLETP 1',
            'total_team_member' => 25,
            'average_tenure' => 2.5,
            'retention_90_days' => 20,
            'restaurant_basics' => 'Basics A',
            'foh' => 'FOH A',
        ]);

        // Second TMAR summary record
        TmarSummary::create([
            'pc' => 'SLU',
            'area' => 'Area 2',
            'count_per_area' => 20,
            'store_number' => 102,
            'sas_name' => 'SAS Two',
            'other_name' => 'Other Two',
            'star_0' => 0,
            'star_1' => 4,
            'star_2' => 6,
            'star_3' => 5,
            'star_4' => 3,
            'all_star' => 18,
            'team_leader' => 'Jane Smith',
            'sldc' => 'SLDC 2',
            'sletp' => 'SLETP 2',
            'total_team_member' => 30,
            'average_tenure' => 3.0,
            'retention_90_days' => 28,
            'restaurant_basics' => 'Basics B',
            'foh' => 'FOH B',
        ]);
    }
}
