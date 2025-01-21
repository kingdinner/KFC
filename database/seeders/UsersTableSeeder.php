<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AuthenticationAccount;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        // Create default user
        $user = AuthenticationAccount::create([
            'employee_id' => '2024-1025',
            'email' => 'admin3@example.com',
            'password' => Hash::make('password'),
            'secret_question' => 'What is your favorite color?',
            'secret_answer' => Hash::make('blue'),
        ]);

        // Assign roles
        $user->assignRole('admin');

        // Create additional users
        $user2 = AuthenticationAccount::create([
            'employee_id' => '2024-1023',
            'email' => 'user4@example.com',
            'password' => Hash::make('password'),
            'secret_question' => 'What is your mother\'s maiden name?',
            'secret_answer' => Hash::make('Smith'),
        ]);

        $user2->assignRole('manager');

        // Create 10 team members
        for ($i = 1; $i <= 10; $i++) {
            $teamMember = AuthenticationAccount::create([
                'employee_id' => '2024-TM-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'email' => 'teammember' . $i . '@example.com',
                'password' => Hash::make('password'),
                'secret_question' => 'What is your favorite animal?',
                'secret_answer' => Hash::make('dog'),
            ]);

            $teamMember->assignRole('team-member');
        }
        for ($i = 1; $i <= 1; $i++) {
            $teamLeader = AuthenticationAccount::create([
                'employee_id' => '2024-TL-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'email' => 'teamleader' . $i . '@example.com',
                'password' => Hash::make('password'),
                'secret_question' => 'What is your favorite animal?',
                'secret_answer' => Hash::make('dog'),
            ]);

            $teamLeader->assignRole('team-leader');
        }
    }
}
