<?php

namespace App\Traits;
use Illuminate\Support\Facades\Http;

trait HandlesExternalAPI
{
	// public function fetchExternalEmployeeData()
    // {
    //     $response = Http::get('https://api.example.com/employees'); 

    //     if ($response->successful()) {
    //         return $response->json();
    //     }

    //     // Handle API error
    //     return [
    //         "users" => []
    //     ];
    // }
    public function fetchExternalEmployeeData()
    {
        return [
            "users" => [
                [
                    "employee_id" => "2025-0006",
                    "email" => "user57@example.com",
                    "password" => "password123",
                    "role" => "admin",
                    "secret_question" => "What is your pet's name?",
                    "secret_answer" => "Charlie",
                    "employee" => [
                        "firstname" => "John",
                        "lastname" => "Doe",
                        "email_address" => "john.doe@example.com",
                        "dob" => "1990-01-01",
                        "nationality" => "American",
                        "address" => "123 Main St",
                        "city" => "New York",
                        "state" => "NY",
                        "zipcode" => "10001"
                    ]
                ],
                [
                    "employee_id" => "2025-0004",
                    "email" => "user85@example.com",
                    "password" => "password456",
                    "role" => "manager",
                    "secret_question" => "What is your mother's maiden name?",
                    "secret_answer" => "Smith",
                    "employee" => [
                        "firstname" => "Jane",
                        "lastname" => "Doe",
                        "email_address" => "jane.doe@example.com",
                        "dob" => "1992-05-10",
                        "nationality" => "Canadian",
                        "address" => "456 Elm St",
                        "city" => "Toronto",
                        "state" => "ON",
                        "zipcode" => "M5H 2N2"
                    ]
                ]
            ]
        ];
    }
}
