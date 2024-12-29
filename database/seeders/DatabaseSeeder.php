<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

    
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            UsersTableSeeder::class,
            HRRulesAndFAQsSeeder::class,
            EmployeeSeeder::class,
            StoreSeeder::class,
            StoreEmployeeSeeder::class,
            BorrowTeamMemberSeeder::class,
            LeaveSeeder::class,
            PayRateSeeder::class,
            StarStatusSeeder::class,
            AvailabilitySeeder::class,
            TmarSummarySeeder::class,
            RatingSeeder::class,
        ]);
    }
}