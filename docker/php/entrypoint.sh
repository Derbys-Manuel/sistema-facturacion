#!/usr/bin/env sh
set -eu

cd /var/www/html

chown -R www-data:www-data storage bootstrap/cache || true

if [ ! -e public/storage ]; then
  php artisan storage:link || true
fi

if [ "${RUN_MIGRATIONS:-1}" = "1" ]; then
  php artisan migrate --force
fi

php artisan config:cache
php artisan route:cache || true
php artisan view:cache || true

exec "$@"
