#!/bin/sh
set -e

echo "���� Starting PHP-FPM..."

# === RUNTIME VERIFICATION ONLY ===
# These operations are fast and idempotent

# Ensure runtime directories exist for Composer and git config.
export HOME=/tmp
export XDG_CONFIG_HOME=/tmp/.config
export GIT_CONFIG_GLOBAL=/tmp/.gitconfig
export COMPOSER_HOME=/tmp/composer-cache
export PSYSH_CONFIG_DIR=/tmp/.config/psysh
mkdir -p /tmp/composer-cache /tmp/.config /tmp/.config/psysh /tmp/.gitconfig bootstrap/cache
chmod -R 775 /tmp/composer-cache /tmp/.config /tmp/.config/psysh /tmp/.gitconfig bootstrap/cache 2>/dev/null || true

# Ensure Laravel directories exist and are writable (if we have permission)
if [ -d /var/www/bootstrap ]; then
    mkdir -p /var/www/bootstrap/cache
    chmod -R 775 /var/www/bootstrap/cache 2>/dev/null || true
fi
if [ -d /var/www/storage ]; then
    mkdir -p /var/www/storage/framework/cache /var/www/storage/framework/sessions /var/www/storage/framework/views /var/www/storage/logs
    chmod -R 775 /var/www/storage/framework /var/www/storage/logs 2>/dev/null || true
fi

# Bootstrap vendor dependencies in non-production bind-mounted workspaces.
if [ "${APP_ENV:-local}" != "production" ] && [ ! -f vendor/autoload.php ]; then
  echo "���� vendor not found, installing Composer dependencies..."
  git config --global --add safe.directory /var/www/html || true
  COMPOSER_ALLOW_SUPERUSER=1 HOME=/tmp COMPOSER_HOME=/tmp/composer-cache composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts
  chown -R www-data:www-data vendor composer.lock 2>/dev/null || true
fi

# Set environment (just in case)
export HOME=/var/www
export COMPOSER_HOME=/tmp/composer-cache
export PSYSH_CONFIG_DIR=/tmp/.config/psysh

exec "$@"