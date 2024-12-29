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
                $dateApplied = $faker->dateTimeBetween('-1 year', 'now');
                $dateEnded = $faker->dateTimeBetween($dateApplied, '+30 days'); // Ensure date_ended is after date_applied
                
                Leave::create([
                    'employee_id' => $employee->id,
                    'type' => $faker->randomElement(['VL', 'SL']), // Random leave type
                    'date_applied' => $dateApplied,
                    'date_ended' => $dateEnded,
                    'reporting_manager' => $faker->name,
                    'reasons' => $faker->sentence,
                    'status' => $faker->randomElement(['Pending', 'Approved', 'Rejected']),
                ]);
            }
        }

        $this->command->info('Leaves table seeded successfully.');
    }
}
