#!/bin/bash
# Database backup script for FuelStationOS
# Creates a backup of the SQLite database using PHP

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

DB_PATH="${1:-$PROJECT_ROOT/backend/storage/database/database.sqlite}"
BACKUP_DIR="${2:-$PROJECT_ROOT/backups}"

# Get timestamp for backup filename
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
DB_NAME=$(basename "$DB_PATH" .sqlite)
BACKUP_FILE="${BACKUP_DIR}/${DB_NAME}_${TIMESTAMP}.sqlite"
COMPRESSED_FILE="${BACKUP_FILE}.gz"

echo "🗄️  FuelStationOS Database Backup"
echo "=================================="
echo "Source DB: $DB_PATH"
echo "Backup to: $BACKUP_DIR"
echo ""

# Check if database exists
if [ ! -f "$DB_PATH" ]; then
    echo "❌ Database not found at: $DB_PATH"
    exit 1
fi

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Copy database using PHP (SQLite3 class)
echo "📦 Creating backup..."
php -r "
\$db = new SQLite3('$DB_PATH');
\$backup = new SQLite3('$BACKUP_FILE');
\$db->backup(\$backup);
echo 'Backup completed';
"

# Verify backup
if [ ! -f "$BACKUP_FILE" ]; then
    echo "❌ Backup failed - file not created"
    exit 1
fi

# Get file sizes
ORIGINAL_SIZE=$(stat -c%s "$DB_PATH" 2>/dev/null || stat -f%z "$DB_PATH")
BACKUP_SIZE=$(stat -c%s "$BACKUP_FILE" 2>/dev/null || stat -f%z "$BACKUP_FILE")

echo "✅ Backup created: $BACKUP_FILE"
echo "   Original: $(numfmt --to=iec $ORIGINAL_SIZE 2>/dev/null || echo "${ORIGINAL_SIZE} bytes")"
echo "   Backup:   $(numfmt --to=iec $BACKUP_SIZE 2>/dev/null || echo "${BACKUP_SIZE} bytes")"

# Compress if requested or if file is large
if [ "$3" = "compress" ] || [ "$ORIGINAL_SIZE" -gt 10485760 ]; then
    echo "🗜️  Compressing backup..."
    gzip -c "$BACKUP_FILE" > "$COMPRESSED_FILE"
    COMPRESSED_SIZE=$(stat -c%s "$COMPRESSED_FILE" 2>/dev/null || stat -f%z "$COMPRESSED_FILE")
    echo "✅ Compressed: $COMPRESSED_FILE"
    echo "   Compressed size: $(numfmt --to=iec $COMPRESSED_SIZE 2>/dev/null || echo "${COMPRESSED_SIZE} bytes")"
    echo "   Compression ratio: $(echo "scale=2; $COMPRESSED_SIZE * 100 / $ORIGINAL_SIZE" | bc)%"
    # Remove uncompressed if compression successful
    rm "$BACKUP_FILE"
    BACKUP_FILE="$COMPRESSED_FILE"
fi

# Create checksum
echo "🔐 Creating checksum..."
sha256sum "$BACKUP_FILE" > "${BACKUP_FILE}.sha256"
echo "✅ Checksum created: ${BACKUP_FILE}.sha256"

# Cleanup old backups (keep last 10)
echo "🧹 Cleaning old backups (keeping last 10)..."
cd "$BACKUP_DIR"
ls -t ${DB_NAME}_*.sqlite* 2>/dev/null | tail -n +11 | xargs -r rm -f
ls -t ${DB_NAME}_*.sqlite*.sha256 2>/dev/null | tail -n +11 | xargs -r rm -f

echo ""
echo "✅ Backup complete!"
echo "   File: $BACKUP_FILE"
echo "   Checksum: ${BACKUP_FILE}.sha256"