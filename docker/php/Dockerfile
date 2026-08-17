FROM php:8.4-fpm

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
COPY composer.json composer.lock ./

# Install dependencies (will be cached if composer files don't change)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Copy application files
COPY . .

RUN composer dump-autoload --optimize

RUN mkdir -p /var/www/.config && chown -R www-data:www-data /var/www/.config

# Copy entrypoint (runtime verification only) with executable permissions
COPY --chmod=+x docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh

# Set environment variables
ENV HOME=/var/www \
    COMPOSER_HOME=/tmp/composer-cache

# Change current user to www
USER www-data

EXPOSE 9000

# Use entrypoint for runtime setup only
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
