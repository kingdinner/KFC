<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StoreEmployee;
use App\Models\StarStatus;

class StarStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $storeEmployees = StoreEmployee::all();

        if ($storeEmployees->isEmpty()) {
            $this->command->info('No store employees found. Skipping StarStatus seeding.');
            return;
        }

        $data = [];
        foreach ($storeEmployees as $storeEmployee) {
            $data[] = [
                'store_employee_id' => $storeEmployee->id,
                'name' => 'Gold Star',
                'reason' => 'Outstanding performance in Q4',
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $data[] = [
                'store_employee_id' => $storeEmployee->id,
                'name' => 'Silver Star',
                'reason' => 'Consistent attendance',
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert the data in bulk to optimize performance
        StarStatus::insert($data);

        $this->command->info('StarStatus seeding completed successfully.');
    }
}
