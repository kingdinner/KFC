<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            return;
        }

        foreach ($storeEmployees as $storeEmployee) {
            StarStatus::create([
                'store_employee_id' => $storeEmployee->id,
                'name' => 'Gold Star',
                'reason' => 'Outstanding performance in Q4',
                'status' => 'ACTIVE',
            ]);

            StarStatus::create([
                'store_employee_id' => $storeEmployee->id,
                'name' => 'Silver Star',
                'reason' => 'Consistent attendance',
                'status' => 'ACTIVE',
            ]);
        }
    }
}
