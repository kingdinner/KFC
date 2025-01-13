<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Rating;
use App\Models\StoreEmployee;
use Faker\Factory as Faker;

class RatingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Get all existing store employees
        $storeEmployees = StoreEmployee::all();

        if ($storeEmployees->isEmpty()) {
            $this->command->info('No store employees found. Please seed StoreEmployee records first.');
            return;
        }

        foreach ($storeEmployees as $storeEmployee) {
            Rating::create([
                'store_employee_id' => $storeEmployee->id,
                'food_safety_certification_date' => $faker->dateTimeBetween('-2 years', 'now'),
                'champs_certification_date' => $faker->dateTimeBetween('-2 years', 'now'),
                'restaurant_basic_certification_date' => $faker->dateTimeBetween('-2 years', 'now'),
                'foh_certification_date' => $faker->dateTimeBetween('-2 years', 'now'),
                'moh_certification_date' => $faker->dateTimeBetween('-2 years', 'now'),
                'boh_certification_date' => $faker->dateTimeBetween('-2 years', 'now'),
                'kitchen_station_level' => $faker->randomElement(['Beginner', 'Intermediate', 'Advanced']),
                'kitchen_station_certification_date' => $faker->dateTimeBetween('-2 years', 'now'),
                'counter_station_level' => $faker->randomElement(['Beginner', 'Intermediate', 'Advanced']),
                'counter_station_certification_date' => $faker->dateTimeBetween('-2 years', 'now'),
                'dining_station_level' => $faker->randomElement(['Beginner', 'Intermediate', 'Advanced']),
                'dining_station_certification_date' => $faker->dateTimeBetween('-2 years', 'now'),
                'tenure_in_months' => $faker->numberBetween(1, 60),
                'retention_90_days' => $faker->boolean,
                'remarks' => $faker->sentence,
            ]);
        }

        $this->command->info('Ratings seeded successfully.');
    }
}
