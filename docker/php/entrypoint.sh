#!/bin/sh
set -e

echo "🚀 Starting PHP-FPM..."

# === RUNTIME VERIFICATION ONLY ===
# These operations are fast and idempotent

# Ensure directories exist (may be removed by volume mounts)
mkdir -p bootstrap/cache /tmp/composer-cache /var/www/.config

# Ensure permissions are correct (may be changed by volume mounts)
chown -R www-data:www-data bootstrap/cache /tmp/composer-cache /var/www/.config 2>/dev/null || true
chmod -R 775 bootstrap/cache /tmp/composer-cache /var/www/.config 2>/dev/null || true

# Set environment (just in case)
export HOME=/var/www
export COMPOSER_HOME=/tmp/composer-cache
export PSYSH_CONFIG_DIR=/var/www/.config/psysh

exec "$@"