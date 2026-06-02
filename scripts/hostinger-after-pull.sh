#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_DIR"

composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --force
php artisan optimize:clear
bash scripts/hostinger-sync-public-assets.sh
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Hostinger deployment steps completed."
