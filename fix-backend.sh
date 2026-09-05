#!/bin/bash
# =============================================================================
# FuelStationOS - Backend Directory Fix Script
# =============================================================================
# This script fixes the backend/ directory structure and permissions.
# Run this from the project root: /var/www/html/fuel_station_os/
#
# Usage: chmod +x fix-backend.sh && ./fix-backend.sh
# =============================================================================

set -e  # Exit on error

PROJECT_ROOT="/var/www/html/fuel_station_os"
BACKEND_DIR="$PROJECT_ROOT/backend"

echo "====================================================================="
echo "FuelStationOS - Backend Directory Fix Script"
echo "====================================================================="
echo ""

# Check if running from correct directory
if [ ! -f "$PROJECT_ROOT/makefile" ]; then
    echo "❌ Error: Please run this script from the project root directory"
    echo "   Expected: $PROJECT_ROOT"
    exit 1
fi

echo "📁 Project root: $PROJECT_ROOT"
echo "📁 Backend dir:  $BACKEND_DIR"
echo ""

# ---------------------------------------------------------------------------
# 1. Fix backend/ directory permissions (owned by nobody from Docker)
# ---------------------------------------------------------------------------
echo "🔧 Step 1: Fixing backend/ directory permissions..."
echo "   Current owner: $(stat -c '%U:%G' $BACKEND_DIR)"

# Change ownership to current user
sudo chown -R $(whoami):$(whoami) "$BACKEND_DIR" 2>/dev/null || {
    echo "   ⚠️  Could not change ownership (no sudo). Trying alternative..."
    # If no sudo, at least make group writable
    chmod -R g+w "$BACKEND_DIR" 2>/dev/null || true
}

echo "   New owner: $(stat -c '%U:%G' $BACKEND_DIR)"
echo "   ✅ Permissions fixed"
echo ""

# ---------------------------------------------------------------------------
# 2. Create backend/.gitignore
# ---------------------------------------------------------------------------
echo "📝 Step 2: Creating backend/.gitignore..."

cat > "$BACKEND_DIR/.gitignore" << 'GITIGNORE_EOF'
# Backend (Laravel) - backend/.gitignore

# Dependencies
/vendor/
/node_modules/

# Laravel specific
/storage/*.key
/storage/logs/*.log
!/storage/logs/.gitkeep
/storage/app/public/
/storage/framework/cache/
/storage/framework/sessions/
/storage/framework/views/
/bootstrap/cache/*.php
/public/hot
/public/storage

# Environment
/.env
/.env.backup
/.env.production
/.env.*.local

# Homestead
Homestead.json
Homestead.yaml

# Auth
auth.json

# Database
*.sqlite
*.sqlite-journal
*.sqlite-wal
*.sqlite-shm

# Composer
composer.phar

# PHPUnit
.phpunit.result.cache
.phpunit.cache/
.coverage

# IDE
.idea/
.vscode/
*.swp
*.swo
*~
.DS_Store
Thumbs.db

# OS
*.log
GITIGNORE_EOF

echo "   ✅ backend/.gitignore created"
echo ""

# ---------------------------------------------------------------------------
# 3. Ensure backend/.env exists and has correct values
# ---------------------------------------------------------------------------
echo "📝 Step 3: Ensuring backend/.env exists with correct config..."

if [ ! -f "$BACKEND_DIR/.env" ] || [ ! -s "$BACKEND_DIR/.env" ]; then
    cat > "$BACKEND_DIR/.env" << 'ENV_EOF'
# Application
APP_NAME="Fuel Station OS"
APP_ENV=local
APP_KEY=base64:KpC6PWYi8dqNboRhJvjlVGz756c2CGqO5Q8cw4duGDM=
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_PORT=8000

# Database
DB_CONNECTION=sqlite
DB_DATABASE=./storage/database/database.sqlite

# Cache & Session
CACHE_DRIVER=file
QUEUE_CONNECTION=database
SESSION_DRIVER=file

# Queue
QUEUE_TABLE=jobs
QUEUE_FAILED_TABLE=failed_jobs

# Frontend
NUXT_PUBLIC_API_BASE_URL=http://localhost:8000/api/v1

# Mail (optional)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
ENV_EOF
    echo "   ✅ backend/.env created"
else
    echo "   ℹ️  backend/.env already exists, skipping"
fi
echo ""

# ---------------------------------------------------------------------------
# 4. Ensure backend/.env.example exists
# ---------------------------------------------------------------------------
echo "📝 Step 4: Ensuring backend/.env.example exists..."

if [ ! -f "$BACKEND_DIR/.env.example" ] || [ ! -s "$BACKEND_DIR/.env.example" ]; then
    cat > "$BACKEND_DIR/.env.example" << 'ENV_EXAMPLE_EOF'
# Application
APP_NAME="Fuel Station OS"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_PORT=8000

# Database
DB_CONNECTION=sqlite
DB_DATABASE=./storage/database/database.sqlite

# Cache & Session
CACHE_DRIVER=file
QUEUE_CONNECTION=database
SESSION_DRIVER=file

# Queue
QUEUE_TABLE=jobs
QUEUE_FAILED_TABLE=failed_jobs

# Frontend
NUXT_PUBLIC_API_BASE_URL=http://localhost:8000/api/v1

# Mail (optional)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
ENV_EXAMPLE_EOF
    echo "   ✅ backend/.env.example created"
else
    echo "   ℹ️  backend/.env.example already exists, skipping"
fi
echo ""

# ---------------------------------------------------------------------------
# 5. Remove duplicate root bootstrap/ directory
# ---------------------------------------------------------------------------
echo "🗑️  Step 5: Removing duplicate root bootstrap/ directory..."

if [ -d "$PROJECT_ROOT/bootstrap" ]; then
    # Check if it's identical to backend/bootstrap
    if diff -r "$PROJECT_ROOT/bootstrap" "$BACKEND_DIR/bootstrap" >/dev/null 2>&1; then
        rm -rf "$PROJECT_ROOT/bootstrap"
        echo "   ✅ Removed duplicate root bootstrap/ (identical to backend/)"
    else
        echo "   ⚠️  Root bootstrap/ differs from backend/bootstrap/ - keeping both for manual review"
        echo "      Compare with: diff -r $PROJECT_ROOT/bootstrap $BACKEND_DIR/bootstrap"
    fi
else
    echo "   ℹ️  No root bootstrap/ directory found"
fi
echo ""

# ---------------------------------------------------------------------------
# 6. Handle root storage/ directory (merge logs if needed)
# ---------------------------------------------------------------------------
echo "🗑️  Step 6: Handling duplicate root storage/ directory..."

if [ -d "$PROJECT_ROOT/storage" ]; then
    # Check if backend/storage has framework directory (it should)
    if [ -d "$BACKEND_DIR/storage/framework" ]; then
        # Merge any unique logs from root storage to backend storage
        if [ -d "$PROJECT_ROOT/storage/logs" ]; then
            echo "   📋 Merging logs from root storage/logs to backend/storage/logs..."
            cp -n "$PROJECT_ROOT/storage/logs/"*.log "$BACKEND_DIR/storage/logs/" 2>/dev/null || true
        fi
        rm -rf "$PROJECT_ROOT/storage"
        echo "   ✅ Removed duplicate root storage/ (merged logs, backend has framework/)"
    else
        echo "   ⚠️  backend/storage/framework missing - keeping root storage/ for safety"
    fi
else
    echo "   ℹ️  No root storage/ directory found"
fi
echo ""

# ---------------------------------------------------------------------------
# 7. Remove root composer-setup.php (should be in backend/)
# ---------------------------------------------------------------------------
echo "🗑️  Step 7: Removing root composer-setup.php..."

if [ -f "$PROJECT_ROOT/composer-setup.php" ]; then
    if [ -f "$BACKEND_DIR/composer-setup.php" ]; then
        rm -f "$PROJECT_ROOT/composer-setup.php"
        echo "   ✅ Removed root composer-setup.php (exists in backend/)"
    else
        mv "$PROJECT_ROOT/composer-setup.php" "$BACKEND_DIR/composer-setup.php"
        echo "   ✅ Moved root composer-setup.php to backend/"
    fi
else
    echo "   ℹ️  No root composer-setup.php found"
fi
echo ""

# ---------------------------------------------------------------------------
# 8. Remove root .env and .env.example (now in backend/)
# ---------------------------------------------------------------------------
echo "🗑️  Step 8: Removing root .env and .env.example (now in backend/)..."

if [ -f "$PROJECT_ROOT/.env" ]; then
    rm -f "$PROJECT_ROOT/.env"
    echo "   ✅ Removed root .env"
fi

if [ -f "$PROJECT_ROOT/.env.example" ]; then
    rm -f "$PROJECT_ROOT/.env.example"
    echo "   ✅ Removed root .env.example"
fi
echo ""

# ---------------------------------------------------------------------------
# 9. Verify backend structure
# ---------------------------------------------------------------------------
echo "✅ Step 9: Verifying backend/ structure..."
echo ""
echo "   Backend directory contents:"
ls -la "$BACKEND_DIR/" | head -20
echo ""

# Check key files
for file in ".env" ".env.example" ".gitignore" "artisan" "composer.json" "Caddyfile" "frankenphp-worker.php"; do
    if [ -f "$BACKEND_DIR/$file" ]; then
        echo "   ✅ $file"
    else
        echo "   ❌ $file (MISSING)"
    fi
done

for dir in "app" "bootstrap" "config" "database" "public" "resources" "routes" "storage" "tests" "vendor"; do
    if [ -d "$BACKEND_DIR/$dir" ]; then
        echo "   ✅ $dir/"
    else
        echo "   ❌ $dir/ (MISSING)"
    fi
done

echo ""
echo "====================================================================="
echo "✅ Backend directory fix complete!"
echo "====================================================================="
echo ""
echo "Next steps:"
echo "  1. Run 'make dev' to start Docker backend with FrankenPHP"
echo "  2. Run 'make tauri-dev' in another terminal for frontend"
echo "  3. Or run 'make setup-docker' if first time setup needed"
echo ""