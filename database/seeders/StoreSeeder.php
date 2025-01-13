<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Store;
use Faker\Factory as Faker;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Generate store codes from '101' to '111'
        $storeCodes = range(101, 111);

        foreach ($storeCodes as $storeCode) {
            Store::create([
                'name' => $faker->company,
                'cost_center' => 'CC-' . $faker->unique()->randomNumber(5, true),
                'asset_type' => $faker->randomElement(['Retail', 'Warehouse', 'Office']),
                'store_code' => (string) $storeCode, // Convert to string if needed
            ]);
        }

        $this->command->info('Stores seeded successfully with store codes from 101 to 111.');
    }
}
