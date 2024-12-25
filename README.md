# Laravel Project Setup Guide

# System Requirements
# - PHP 8.3.10
# - Composer
# - PostgreSQL
# - Laravel Framework 10.x or higher
# - Docker (optional, for containerized development)

# Installation Steps

# 1. Clone the Repository
git clone <repository_url>
cd <project_directory>

# 2. Install Dependencies
composer install

# 3. Environment Configuration
cp .env.example .env
# Edit the .env file for PostgreSQL settings:
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=your_database_name
# DB_USERNAME=your_database_user
# DB_PASSWORD=your_database_password

# Running Migrations and Seeding Data

# 1. Migrate the Database
php artisan migrate

# 2. Seed the Database
php artisan db:seed

# Setting Up Laravel Passport

# 1. Install Passport
php artisan passport:install

# Add the following generated client_id and client_secret values to the .env file:
# PASSPORT_PERSONAL_ACCESS_CLIENT_ID=1
# PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=your_client_secret

# For Docker users, append or overwrite these values inside the Laravel container:
docker exec -it laravel-app bash -c "echo 'PASSPORT_PERSONAL_ACCESS_CLIENT_ID=1' >> .env"
docker exec -it laravel-app bash -c "echo 'PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=your_client_secret' >> .env"

# Verify the changes:
docker exec -it laravel-app bash -c "cat .env | grep PASSPORT_PERSONAL_ACCESS_CLIENT"

# Clear and cache configuration
docker exec -it laravel-app php artisan config:clear
docker exec -it laravel-app php artisan config:cache

# Running the Application

# 1. Using Laravel's Built-in Server
php artisan serve
# Visit http://localhost:8000

# 2. Using Docker
docker-compose up -d
# Access the application at http://localhost:8000

# Testing APIs with Postman

# 1. Import the provided Postman collection (<Postman_Collection_File.json>).
# 2. Use the environment variables for dynamic testing.
# 3. Run API requests according to the collection routes.

# Useful Commands

# Run Development Server
php artisan serve

# Clear Cache and Config
php artisan config:cache
php artisan route:cache
php artisan cache:clear

# Rollback Migrations
php artisan migrate:rollback

# Troubleshooting Tips

# Ensure the .env file is correctly configured and has appropriate permissions.
# Verify the database connection using PostgreSQL client tools.
# If using Docker, check container logs:
docker logs laravel-app
docker logs postgres