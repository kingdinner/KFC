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

        // Generate 10 fake stores
        for ($i = 0; $i < 10; $i++) {
            Store::create([
                'name' => $faker->company,
                'location' => $faker->address,
                'description' => $faker->catchPhrase,
                'cost_center' => 'CC-' . $faker->randomNumber(5, true),
                'asset_type' => $faker->randomElement(['Retail', 'Warehouse', 'Office']),
                'store_code' => strtoupper($faker->bothify('STR-####')),
            ]);
        }
    }
}
