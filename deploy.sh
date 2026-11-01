#!/usr/bin/env bash
# Dijalankan DI VPS (oleh GitHub Actions atau manual): ./deploy.sh
set -euo pipefail
umask 002                                    # file baru bisa ditulis grup www-data

APP_DIR=/var/www/rph
cd "$APP_DIR"

php artisan down --retry=10 || true          # halaman maintenance selama update

git fetch origin main
git reset --hard origin/main                 # server tidak boleh punya edit lokal
                                             # (public/build & .env tidak ikut terhapus)

composer install --no-dev --optimize-autoloader --no-interaction

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan up
sudo systemctl reload php8.5-fpm             # reset OPcache
echo "Deployed $(git rev-parse --short HEAD)"