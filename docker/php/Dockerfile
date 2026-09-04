# FrankenPHP + Laravel Octane Development Image
# Based on official FrankenPHP image with PHP 8.4

FROM dunglas/frankenphp:php8.4

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    pdo_sqlite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for better layer caching
COPY backend/composer.json backend/composer.lock ./

# Install dependencies (will be cached if composer files don't change)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Copy application files
COPY backend/ .

# Generate optimized autoloader
RUN composer dump-autoload --optimize

# Create Laravel runtime directories and fix permissions
RUN mkdir -p /var/www/html/bootstrap/cache \
    /var/www/html/storage/framework/cache \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    && chown -R www-data:www-data /var/www/html/bootstrap/cache \
    /var/www/html/storage/framework \
    /var/www/html/storage/logs \
    && chmod -R 755 /var/www/html/app \
    /var/www/html/bootstrap \
    /var/www/html/config \
    /var/www/html/database \
    /var/www/html/public \
    /var/www/html/resources \
    /var/www/html/routes \
    /var/www/html/storage \
    /var/www/html/tests

RUN mkdir -p /var/www/.config && chown -R www-data:www-data /var/www/.config

# Copy entrypoint
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set environment variables
ENV HOME=/var/www \
    COMPOSER_HOME=/tmp/composer-cache \
    APP_ENV=local \
    APP_DEBUG=true

# Expose FrankenPHP port
EXPOSE 8000

# Use entrypoint for runtime setup (runs as root, drops to www-data)
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Default command runs FrankenPHP + Octane HTTP server
CMD ["php", "artisan", "octane:start", "--server=frankenphp", "--host=0.0.0.0", "--port=8000"]