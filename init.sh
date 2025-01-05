#!/bin/sh

set -e

# Wait for the database to be ready
echo "Waiting for database connection..."
until nc -z -v -w30 $DB_HOST $DB_PORT; do
  echo "Waiting for database..."
  sleep 1
done
echo "Database is ready!"

# Run migrations and seed the database
if [ ! -f /var/www/storage/initialized ]; then
  echo "Running migrations and seeding..."
  php artisan migrate:refresh --force
  php artisan db:seed --force
  touch /var/www/storage/initialized
  echo "Initialization complete!"
fi

# Start PHP-FPM
echo "Starting PHP-FPM..."
exec php-fpm
