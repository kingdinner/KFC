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

        // Get the start and end dates for this month
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        // Generate leave records
        foreach ($employees as $employee) {
            // Decide randomly if the employee is not on leave
            if ($faker->boolean(30)) { // 30% chance of no leave records
                continue;
            }

            // Generate random leave records for this employee
            for ($i = 0; $i < rand(1, 10); $i++) { // Each employee gets 1-10 leave records
                $dateApplied = $faker->dateTimeBetween($startOfMonth, $endOfMonth);
                $dateEnded = (clone $dateApplied)->modify('+' . rand(1, 2) . ' days'); // 1 or 2 days after date_applied
                
                // Ensure dateEnded does not go beyond the end of the month
                if ($dateEnded > $endOfMonth) {
                    $dateEnded = $endOfMonth;
                }

                Leave::create([
                    'employee_id' => $employee->id,
                    'type' => $faker->randomElement(['VL', 'SL']), // Random leave type
                    'date_applied' => $dateApplied,
                    'date_ended' => $dateEnded,
                    'reporting_manager' => $faker->name,
                    'reasons' => $faker->sentence,
                    'status' => $faker->randomElement(['Approved']), // Always 'Approved'
                ]);
            }
        }

        $this->command->info('Leaves table seeded successfully.');
    }
}
