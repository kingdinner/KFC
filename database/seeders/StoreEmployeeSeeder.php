<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Store;
use App\Models\Employee;
use App\Models\StoreEmployee;
use Faker\Factory as Faker;

class StoreEmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Get all stores and employees
        $stores = Store::all();
        $employees = Employee::all();

        // Ensure there are stores and employees before seeding
        if ($stores->isEmpty() || $employees->isEmpty()) {
            $this->command->info('No stores or employees found. Please seed those first.');
            return;
        }

        // Create assignments
        foreach ($employees as $employee) {
            $store = $stores->random();

            StoreEmployee::create([
                'store_id' => 1,
                'employee_id' => $employee->id,
                'start_date' => $faker->dateTimeBetween('-2 years', 'now'),
                'end_date' => $faker->optional(0.3)->dateTimeBetween('now', '+1 year'), // 30% chance of having an end date
            ]);
        }

        $this->command->info('StoreEmployee records seeded successfully.');
    }
}
