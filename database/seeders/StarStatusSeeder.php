<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StarStatus;

class StarStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'name' => 'Gold Star',
                'reason' => 'Outstanding performance in Q4',
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Silver Star',
                'reason' => 'Consistent attendance',
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bronze Star',
                'reason' => 'Achieved team targets',
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insert the data in bulk to optimize performance
        StarStatus::insert($data);

        $this->command->info('StarStatus seeding completed successfully.');
    }
}
