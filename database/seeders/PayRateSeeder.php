<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PayRate;
use App\Models\StoreEmployee;

class PayRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $storeEmployees = StoreEmployee::all();

        if ($storeEmployees->isEmpty()) {
            $this->command->info('No store employees found. Please seed the StoreEmployee table first.');
            return;
        }

        PayRate::create([
            'id' => 1,
            'position' => 'Team Member',
            'rate_label' => 'per hour',
            'hourly_rate' => 500,
            'store_employee_id' => $storeEmployees->random()->id,
        ]);

        PayRate::create([
            'id' => 2,
            'position' => 'Supervisor',
            'rate_label' => 'per hour',
            'hourly_rate' => 700,
            'store_employee_id' => $storeEmployees->random()->id,
        ]);

        $this->command->info('PayRate records seeded successfully.');
    }
}
