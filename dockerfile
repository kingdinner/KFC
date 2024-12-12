# Use the official PHP 8.2 FPM image as base
FROM php:8.2-fpm

# Set the working directory
WORKDIR /var/www

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    zip \
    nano \
    libonig-dev \
    libxml2-dev \
    mariadb-client \
    libpq-dev \
    supervisor

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd pdo pdo_mysql pdo_pgsql mbstring xml zip bcmath

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy the application source code
COPY . .

# Trust the application directory recursively to prevent Git "dubious ownership" errors
RUN git config --global --add safe.directory '*'

# Create necessary directories
RUN mkdir -p storage bootstrap/cache

# Set permissions
RUN chown -R www-data:www-data /var/www && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Set environment variables
ENV APP_ENV=local \
    APP_DEBUG=true \
    DB_CONNECTION=mysql \
    DB_HOST=mysql \
    DB_PORT=3306

# Install Laravel dependencies
RUN composer install --optimize-autoloader --no-dev

# Expose port 9000 and start PHP-FPM
EXPOSE 9000

# Set the default command
CMD ["php-fpm"]
