<?php 

namespace Database\Seeders;  

use Illuminate\Database\Seeder; 
use App\Models\Employee; 
use App\Models\AuthenticationAccount; 
use Faker\Factory as Faker;  

class EmployeeSeeder extends Seeder 
{     
    /**      
     * Run the database seeds.      
     */     
    public function run(): void     
    {         
        $faker = Faker::create();         
        
        // Get all authentication accounts         
        $authenticationAccounts = AuthenticationAccount::all();          
        
        // For each AuthenticationAccount, create an employee entry         
        foreach ($authenticationAccounts as $account) {             
            Employee::create([                 
                'authentication_account_id' => $account->id,                 
                'firstname' => $faker->firstName,                 
                'lastname' => $faker->lastName,                 
                'email_address' => $faker->email,                 
                'dob' => $faker->date(),                
                'nationality' => $faker->country,           
                'address' => $faker->streetAddress,                 
                'city' => $faker->city,                 
                'state' => $faker->state,                 
                'zipcode' => $faker->postcode,             
            ]);         
        }     
    } 
}
