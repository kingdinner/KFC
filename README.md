# Laravel Project Setup Guide
This guide will help you set up the Laravel project using PHP 8.3.10, including migrations, seeding the database, and configuring API authentication using Laravel Passport. A Postman collection is also included for API testing.

## System Requirements
PHP 8.3.10
Composer
MySQL (or another compatible database)
Laravel Framework 10.x or higher

## Installation Steps
1. Clone the Repository
git clone <repository_url>
cd <project_directory>
2. Install Dependencies
composer install
3. Environment Configuration
Copy the .env.example file and rename it to .env.
Configure database details in the .env file:
dotenv
Copy code
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
Running Migrations and Seeding Data
1. Migrate the Database
php artisan migrate
2. Seed the Database
php artisan db:seed
Setting Up Laravel Passport
1. Install Passport
php artisan passport:install
This will generate client_id and client_secret values. Add these to your .env file:
.env
PASSPORT_CLIENT_ID=your_client_id
PASSPORT_CLIENT_SECRET=your_client_secret

## Running the Application
php artisan serve
Visit http://localhost:8000 in your browser.

## Testing APIs with Postman
Import the provided Postman collection (<Postman_Collection_File.json>).
Use the environment variables for dynamic testing.
Run API requests according to the collection routes.
Useful Commands

### Run Development Server:
php artisan serve

### Clear Cache and Config:
php artisan config:cache
php artisan route:cache
php artisan cache:clear

### Rollback Migrations:
php artisan migrate:rollback
Troubleshooting Tips


### Setting Up Proxy in React (For Development Only)
Parameters:
route: The specific route in the Laravel backend that you want to call (e.g., users/register, stores).
payload: The data (usually a JSON object) to send to the route.

Ensure your .env file has the correct APP_URL value for your Laravel backend:
- APP_URL=http://127.0.0.1:8000

## Configure Proxy in React
To make requests to the Laravel backend in development, add the following to your package.json file:
```
"proxy": "http://127.0.0.1:8000"
```

## Create a Proxy Request Utility
In your React project, create a new utility function to send requests through the /api/proxy endpoint.
```
const proxyRequest = async (route, payload) => {
    const response = await fetch('/api/proxy', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ route, payload }),
    });

    if (!response.ok) {
        throw new Error('Proxy request failed');
    }

    return response.json();
};

export default proxyRequest;
```

## Usage Example
```
import proxyRequest from './utils/proxyRequest';

const loginUser = async () => {
    try {
        const data = await proxyRequest('login', {
            employee_id: '2024-1023'
            password: 'password',
        });
        console.log('Login:', data);
    } catch (error) {
        console.error('Error:', error);
    }
};

createUser();
```

## Example Request
```
{
  "route": "login",
  "payload": {
    employee_id: '2024-1023'
    password: 'password',
  }
}
```
License
This project is licensed under the MIT License. See the LICENSE file for more details.