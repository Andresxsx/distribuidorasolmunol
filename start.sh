#!/usr/bin/env bash
set -e

PORT="${PORT:-80}"

sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache public
chmod -R 775 storage bootstrap/cache public

php artisan optimize:clear || true

php artisan migrate --force

php artisan filament:assets || true

php artisan config:cache
php artisan view:cache

exec apache2-foreground