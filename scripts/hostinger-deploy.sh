#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

if [ ! -f ".env" ]; then
    echo "Missing .env. Copy .env.production.example to .env and fill database/mail secrets first."
    exit 1
fi

echo "Installing PHP dependencies..."
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

if command -v npm >/dev/null 2>&1; then
    echo "Building frontend assets..."
    npm ci --include=dev
    npm run build
else
    echo "npm is not available on this server. Checking for prebuilt assets..."
    if [ ! -f "public/build/manifest.json" ]; then
        echo "Missing public/build/manifest.json. Build locally with npm run build and upload public/build."
        exit 1
    fi
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    echo "Generating Laravel app key..."
    php artisan key:generate --force
fi

echo "Running database migrations..."
php artisan migrate --force

echo "Creating storage symlink if possible..."
php artisan storage:link || true

echo "Optimizing Laravel..."
php artisan optimize:clear
php artisan optimize

echo "Deployment complete. Check /up and the homepage."
