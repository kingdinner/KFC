<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Leave;
use App\Models\Employee;
use Faker\Factory as Faker;

class LeaveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Get all employees
        $employees = Employee::all();

        // Ensure employees exist before seeding
        if ($employees->isEmpty()) {
            $this->command->info('No employees found. Please seed the employees first.');
            return;
        }

        // Generate leave records
        foreach ($employees as $employee) {
            for ($i = 0; $i < rand(1, 3); $i++) {  // Each employee gets 1-3 leave records
                Leave::create([
                    'employee_id' => $employee->id,
                    'date_applied' => $faker->dateTimeBetween('-1 year', 'now'),
                    'duration' => rand(1, 14) . ' days',
                    'reporting_manager' => $faker->name,
                    'reasons' => $faker->sentence,
                    'status' => $faker->randomElement(['Approved', 'Rejected']), // Random status
                ]);
            }
        }

        $this->command->info('Leaves table seeded successfully.');
    }
}
