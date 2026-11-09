#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

if [ ! -f ".env" ]; then
    echo "Missing .env. Copy .env.production.example to .env and fill database/mail secrets first."
    exit 1
fi

echo "Installing PHP dependencies..."
# Hostinger shared hosting disables proc_open, which Composer needs to run Laravel's
# post-install scripts ("The Process class relies on proc_open"). Install without
# scripts, then run package discovery in-process through artisan.
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts
php artisan package:discover --ansi

# Frontend assets are built locally and committed in public/build. Building on the
# server rewrites tracked files and can make the next `git pull` fail, so it is opt-in:
#   BUILD_ASSETS=1 bash scripts/hostinger-deploy.sh
if [ "${BUILD_ASSETS:-0}" = "1" ]; then
    echo "Building frontend assets..."
    npm ci --include=dev
    npm run build
fi

if [ ! -f "public/build/manifest.json" ]; then
    echo "Missing public/build/manifest.json. Build locally with npm run build and commit/upload public/build."
    exit 1
fi

# When the domain does not serve the project's public/ directory, /build/... is read
# from the web root, which must receive a copy of public/build on every deploy
# (otherwise new hashed assets 404 and pages render blank).
#   - project inside public_html (web root = project root) -> ./build
#   - project beside public_html                            -> ../public_html/build
#   - override with PUBLIC_HTML_DIR=/path/to/web/root
if [ -n "${PUBLIC_HTML_DIR:-}" ]; then
    WEB_ROOT="$PUBLIC_HTML_DIR"
elif [ "$(basename "$PWD")" = "public_html" ]; then
    WEB_ROOT="."
elif [ -d "../public_html" ]; then
    WEB_ROOT="../public_html"
else
    WEB_ROOT=""
fi

if [ -n "$WEB_ROOT" ]; then
    echo "Syncing public build assets to $WEB_ROOT/build..."
    mkdir -p "$WEB_ROOT/build"
    cp -R public/build/. "$WEB_ROOT/build/"
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
