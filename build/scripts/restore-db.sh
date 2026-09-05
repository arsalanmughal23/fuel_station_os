#!/bin/bash
# Database restore script for FuelStationOS
# Restores a SQLite database from backup

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

DB_PATH="${1:-$PROJECT_ROOT/backend/storage/database/database.sqlite}"
BACKUP_FILE="${2:-}"

echo "🔄 FuelStationOS Database Restore"
echo "=================================="
echo "Target DB: $DB_PATH"
echo ""

# Check if backup file is provided
if [ -z "$BACKUP_FILE" ]; then
    echo "Usage: $0 [target_db_path] <backup_file>"
    echo ""
    echo "Available backups:"
    ls -la "$PROJECT_ROOT/backups/" 2>/dev/null || echo "No backups directory found"
    exit 1
fi

# Check if backup file exists
if [ ! -f "$BACKUP_FILE" ]; then
    echo "❌ Backup file not found: $BACKUP_FILE"
    exit 1
fi

# Verify checksum if available
if [ -f "${BACKUP_FILE}.sha256" ]; then
    echo "🔐 Verifying checksum..."
    if sha256sum -c "${BACKUP_FILE}.sha256" --quiet; then
        echo "✅ Checksum verified"
    else
        echo "❌ Checksum verification failed!"
        exit 1
    fi
fi

# Decompress if needed
RESTORE_FILE="$BACKUP_FILE"
if [[ "$BACKUP_FILE" == *.gz ]]; then
    echo "🗜️  Decompressing backup..."
    RESTORE_FILE="${BACKUP_FILE%.gz}"
    gunzip -c "$BACKUP_FILE" > "$RESTORE_FILE"
    echo "✅ Decompressed to: $RESTORE_FILE"
fi

# Verify it's a valid SQLite database using PHP
echo "🔍 Verifying backup integrity..."
if ! php -r "
\$db = new SQLite3('$RESTORE_FILE');
\$result = \$db->querySingle('PRAGMA integrity_check;');
if (\$result !== 'ok') {
    exit(1);
}
"; then
    echo "❌ Backup integrity check failed!"
    rm -f "$RESTORE_FILE" 2>/dev/null || true
    exit 1
fi
echo "✅ Integrity check passed"

# Create a backup of current database before restoring
if [ -f "$DB_PATH" ]; then
    echo "📦 Backing up current database..."
    CURRENT_BACKUP="${DB_PATH}.pre_restore_$(date +%Y%m%d_%H%M%S)"
    cp "$DB_PATH" "$CURRENT_BACKUP"
    echo "✅ Current DB backed up to: $CURRENT_BACKUP"
fi

# Ensure target directory exists
mkdir -p "$(dirname "$DB_PATH")"

# Restore the database
echo "📥 Restoring database..."
cp "$RESTORE_FILE" "$DB_PATH"

# Clean up decompressed file if we created it
if [[ "$BACKUP_FILE" == *.gz ]] && [ -f "$RESTORE_FILE" ]; then
    rm -f "$RESTORE_FILE"
fi

# Verify restored database using PHP
echo "🔍 Verifying restored database..."
if php -r "
\$db = new SQLite3('$DB_PATH');
\$result = \$db->querySingle('PRAGMA integrity_check;');
if (\$result !== 'ok') {
    exit(1);
}
"; then
    echo "✅ Restored database integrity check passed"
else
    echo "❌ Restored database integrity check failed!"
    exit 1
fi

# Run migrations to ensure schema is up to date
if [ -f "$PROJECT_ROOT/backend/artisan" ]; then
    echo "🔧 Running migrations..."
    cd "$PROJECT_ROOT/backend"
    php artisan migrate --force --no-interaction 2>/dev/null || echo "⚠️  Migration failed or not needed"
fi

echo ""
echo "✅ Restore complete!"
echo "   Database restored to: $DB_PATH"