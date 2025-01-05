# Use the official PHP-FPM image
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libonig-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Set permissions for storage and cache
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Optimize Laravel for API
RUN php artisan config:cache \
    && php artisan route:cache

# Expose PHP-FPM on port 9000
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]
