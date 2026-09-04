#!/bin/sh
set -e

echo "🚀 Starting FrankenPHP + Octane (root setup)..."

# Fix gitconfig issue (ensure it's a file, not directory)
if [ -d /tmp/.gitconfig ]; then
    rm -rf /tmp/.gitconfig
fi

# Ensure runtime directories exist for Composer and git config.
export HOME=/tmp
export XDG_CONFIG_HOME=/tmp/.config
export GIT_CONFIG_GLOBAL=/tmp/.gitconfig
export COMPOSER_HOME=/tmp/composer-cache
export PSYSH_CONFIG_DIR=/tmp/.config/psysh
mkdir -p /tmp/composer-cache /tmp/.config /tmp/.config/psysh
chmod -R 775 /tmp/composer-cache /tmp/.config /tmp/.config/psysh 2>/dev/null || true

# Ensure bootstrap directory is writable by www-data
mkdir -p /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/bootstrap/cache

# Ensure storage directories are writable
mkdir -p /var/www/html/storage/framework/cache /var/www/html/storage/framework/sessions /var/www/html/storage/framework/views /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage/framework /var/www/html/storage/logs
chmod -R 775 /var/www/html/storage/framework /var/www/html/storage/logs

# Ensure vendor directory is writable (for composer)
if [ ! -f /var/www/html/vendor/autoload.php ]; then
    mkdir -p /var/www/html/vendor
    chown -R www-data:www-data /var/www/html/vendor
fi

# Ensure we're in the correct directory
cd /var/www/html

# Bootstrap vendor dependencies in non-production bind-mounted workspaces.
if [ "${APP_ENV:-local}" != "production" ] && [ ! -f /var/www/html/vendor/autoload.php ]; then
  echo "📦 vendor not found, installing Composer dependencies..."
  git config --global --add safe.directory /var/www/html || true
  COMPOSER_ALLOW_SUPERUSER=1 HOME=/tmp COMPOSER_HOME=/tmp/composer-cache composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts
  chown -R www-data:www-data /var/www/html/vendor /var/www/html/composer.lock 2>/dev/null || true
fi

# Run database migrations (non-production only, production should use deploy scripts)
if [ "${APP_ENV:-local}" != "production" ]; then
    if [ ! -f storage/database/.initialized ]; then
        echo "🗄️ Running database migrations..."
        php artisan migrate --force --no-interaction
        touch storage/database/.initialized
    fi
fi

# Generate app key if not set (non-production)
if [ "${APP_ENV:-local}" != "production" ]; then
    if ! grep -q '^APP_KEY=' .env 2>/dev/null || [ -z "$(grep '^APP_KEY=' .env | cut -d= -f2)" ]; then
        echo "🔑 Generating application key..."
        php artisan key:generate --force
    fi
fi

echo "🚀 Starting FrankenPHP + Octane..."
# Exec the command passed to container (octane:start)
exec "$@"