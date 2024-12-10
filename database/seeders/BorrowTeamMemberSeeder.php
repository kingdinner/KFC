<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BorrowTeamMember;
use App\Models\Employee;
use App\Models\Store;
use Faker\Factory as Faker;

class BorrowTeamMemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Get all employees and stores
        $employees = Employee::all();
        $stores = Store::all();

        if ($employees->isEmpty() || $stores->count() < 2) {
            $this->command->info('Not enough data: Please seed employees and stores first.');
            return;
        }

        foreach ($employees as $employee) {
            $borrowedStore = $stores->random();
            $transferredStore = $stores->where('id', '!=', $borrowedStore->id)->random();

            BorrowTeamMember::create([
                'employee_id' => $employee->id,
                'borrowed_store_id' => $borrowedStore->id,
                'borrowed_date' => $faker->dateTimeBetween('-1 year', 'now'),
                'skill_level' => $faker->randomElement(['Beginner', 'Intermediate', 'Advanced']),
                'transferred_store_id' => $transferredStore->id,
                'transferred_date' => $faker->optional()->dateTimeBetween('now', '+6 months'),
                'transferred_time' => $faker->optional()->time(),
                'status' => $faker->randomElement(['Pending', 'Approved', 'Rejected']),
                'reason' => $faker->sentence,
            ]);
        }

        $this->command->info('Borrow Team Members table seeded successfully.');
    }
}
