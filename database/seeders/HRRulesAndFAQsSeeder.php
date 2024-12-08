<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\HRRule;
use App\Models\FAQ;

class HRRulesAndFAQsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        HRRule::create(['rule' => 'JoeDOE']);
        FAQ::create([
            'question' => 'JoeDOE?',
            'answer' => 'JoeDOE'
        ]);
    }
}
