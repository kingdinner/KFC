<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\AuthenticationAccount;
use Faker\Factory as Faker;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $jobPositions = ['crew', 'manager', 'hr', 'admin'];
        // Assuming you have some authentication accounts already in the database
        $authenticationAccounts = AuthenticationAccount::all();

        // For each AuthenticationAccount, create an employee entry
        foreach ($authenticationAccounts as $account) {
            Employee::create([
                'authentication_account_id' => $account->id,
                'fullname' => $faker->name,
                'address' => $faker->address,
                'contact_number' => $faker->phoneNumber,
                'job_position' => $faker->randomElement($jobPositions),
            ]);
        }
    }
}
