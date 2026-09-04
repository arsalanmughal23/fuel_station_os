#!/bin/bash
# Build script for FuelStationOS sidecar and Tauri app
# Usage: ./build/scripts/build-sidecar.sh [clean|build|package]

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

echo "🔨 FuelStationOS Build Script"
echo "============================="

CLEAN=false
BUILD=true
PACKAGE=false

for arg in "$@"; do
    case $arg in
        clean)
            CLEAN=true
            ;;
        build)
            BUILD=true
            ;;
        package)
            PACKAGE=true
            ;;
        *)
            echo "Usage: $0 [clean|build|package]"
            exit 1
            ;;
    esac
done

cd "$PROJECT_ROOT"

# Clean build artifacts
if [ "$CLEAN" = true ]; then
    echo "🧹 Cleaning build artifacts..."
    rm -rf backend/vendor
    rm -rf backend/bootstrap/cache/*.php
    rm -rf backend/storage/logs/*
    rm -rf backend/storage/framework/cache/*
    rm -rf backend/storage/framework/sessions/*
    rm -rf backend/storage/framework/views/*
    rm -rf frontend/node_modules
    rm -rf frontend/.nuxt
    rm -rf frontend/.output
    rm -rf frontend/dist
    cd src-tauri && cargo clean 2>/dev/null || true
    echo "✅ Clean complete"
fi

# Build backend (Laravel sidecar for FrankenPHP)
if [ "$BUILD" = true ]; then
    echo ""
    echo "🔨 Building Laravel backend (FrankenPHP sidecar)..."
    cd "$PROJECT_ROOT/backend"

    echo "📦 Installing PHP dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

    echo "⚡ Optimizing Laravel..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache

    # Run octane:install if not already done
    if [ ! -f "config/octane.php" ]; then
        php artisan octane:install --server=frankenphp
    fi

    # Remove development files
    rm -rf tests
    rm -rf bootstrap/cache/*.php
    rm -rf storage/logs/*
    rm -rf storage/framework/cache/*
    rm -rf storage/framework/sessions/*
    rm -rf storage/framework/views/*
    rm -rf .git
    rm -rf .github
    rm -f phpunit.xml
    rm -f phpunit.xml.dist
    rm -f .env.example
    rm -f .gitignore
    rm -f .gitattributes
    rm -f composer.phar
    rm -f composer-setup.php

    echo "✅ Laravel FrankenPHP sidecar ready"

    # Build frontend
    echo ""
    echo "🔨 Building frontend..."
    cd "$PROJECT_ROOT/frontend"
    
    echo "📦 Installing frontend dependencies..."
    pnpm install --frozen-lockfile
    
    echo "🏗️ Building frontend..."
    pnpm run build
    
    echo "✅ Frontend built"
fi

# Build Tauri
if [ "$BUILD" = true ]; then
    echo ""
    echo "🔨 Building Tauri application..."
    cd "$PROJECT_ROOT/src-tauri"
    cargo build --release
    echo "✅ Tauri binary built"
fi

# Package installer
if [ "$PACKAGE" = true ]; then
    echo ""
    echo "📦 Packaging installer..."
    ./package-installer.sh
fi

echo ""
echo "✅ Build complete!"
echo ""
echo "Artifacts:"
echo "  - Backend: backend/ (ready for FrankenPHP sidecar)"
echo "  - Frontend: frontend/dist/"
echo "  - Tauri binary: src-tauri/target/release/fuel_station_os"
echo ""
echo "To run in development mode:"
echo "  1. Backend: make dev (Docker)"
echo "  2. Frontend: cd frontend && pnpm tauri dev"