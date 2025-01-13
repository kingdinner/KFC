<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TmarReport;
use Faker\Factory as Faker;

class TmarSummarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Prepare the data
        for ($i = 0; $i < 20; $i++) {
            $storeNumber = $faker->randomElement([101]);

            TmarReport::create([
                'pc' => $faker->lexify('???'),
                'area' => 'Area ' . $faker->numberBetween(1, 5),
                'count_per_area' => $faker->numberBetween(10, 50),
                'store_number' => $storeNumber,
                'sas_name' => $faker->word . ' SAS',
                'other_name' => $faker->word . ' Other',
                'star_0' => $faker->numberBetween(0, 5),
                'star_1' => $faker->numberBetween(0, 5),
                'star_2' => $faker->numberBetween(0, 5),
                'star_3' => $faker->numberBetween(0, 5),
                'star_4' => $faker->numberBetween(0, 5),
                'all_star' => $faker->numberBetween(10, 30),
                'team_leader' => $faker->name,
                'sldc' => 'SLDC ' . $faker->numberBetween(1, 5),
                'sletp' => 'SLETP ' . $faker->numberBetween(1, 5),
                'total_team_member' => $faker->numberBetween(20, 50),
                'average_tenure' => $faker->randomFloat(1, 1, 5),
                'retention_90_days' => $faker->numberBetween(15, 45),
                'restaurant_basics' => 'Basics ' . $faker->word,
                'foh' => 'FOH ' . $faker->word,
            ]);
        }

        $this->command->info('TMAR Summary data seeded successfully with 20 records.');
    }
}
