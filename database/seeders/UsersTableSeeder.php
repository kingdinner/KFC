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

        // Create additional users as necessary
        $user2 = AuthenticationAccount::create([
            'employee_id' => '2024-1023',
            'email' => 'user4@example.com',
            'password' => Hash::make('password'),
            'secret_question' => 'What is your mother\'s maiden name?',
            'secret_answer' => Hash::make('Smith'),
        ]);

        $user2->assignRole('manager');

        // Create additional users as necessary
        $user3 = AuthenticationAccount::create([
            'employee_id' => '2024-1024',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'secret_question' => 'What is your mother\'s maiden name?',
            'secret_answer' => Hash::make('Smith'),
        ]);

        $user3->assignRole('team-member');
    }
}