#!/bin/sh
set -e

cd /var/www/html

# Izin tulis untuk cache & log (volume Coolify bisa override uid)
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rwx storage bootstrap/cache || true

# Tanpa .env: Laravel membaca env dari proses (Coolify)
php artisan package:discover --ansi >/dev/null 2>&1 || true

exec /usr/bin/supervisord -c /etc/supervisord.conf
