<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RatingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('ratings')->insert([
            [
                'store_employee_id' => 1,
                'food_safety_certification_date' => Carbon::now()->subMonths(6),
                'champs_certification_date' => Carbon::now()->subMonths(12),
                'restaurant_basic_certification_date' => Carbon::now()->subMonths(3),
                'foh_certification_date' => Carbon::now()->subMonths(9),
                'moh_certification_date' => Carbon::now()->subMonths(8),
                'boh_certification_date' => Carbon::now()->subMonths(2),
                'kitchen_station_level' => 'Advanced',
                'kitchen_station_certification_date' => Carbon::now()->subMonths(4),
                'counter_station_level' => 'Intermediate',
                'counter_station_certification_date' => Carbon::now()->subMonths(5),
                'dining_station_level' => 'Beginner',
                'dining_station_certification_date' => Carbon::now()->subMonths(10),
                'tenure_in_months' => 24.5,
                'retention_90_days' => true,
                'remarks' => 'Excellent performance, ready for promotion.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'store_employee_id' => 2,
                'food_safety_certification_date' => Carbon::now()->subMonths(3),
                'champs_certification_date' => Carbon::now()->subMonths(6),
                'restaurant_basic_certification_date' => Carbon::now()->subMonths(1),
                'foh_certification_date' => Carbon::now()->subMonths(4),
                'moh_certification_date' => Carbon::now()->subMonths(3),
                'boh_certification_date' => Carbon::now()->subMonths(5),
                'kitchen_station_level' => 'Intermediate',
                'kitchen_station_certification_date' => Carbon::now()->subMonths(7),
                'counter_station_level' => 'Advanced',
                'counter_station_certification_date' => Carbon::now()->subMonths(9),
                'dining_station_level' => 'Intermediate',
                'dining_station_certification_date' => Carbon::now()->subMonths(11),
                'tenure_in_months' => 18.0,
                'retention_90_days' => false,
                'remarks' => 'Needs improvement in FOH area.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}