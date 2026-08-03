#!/bin/sh
set -e

cd /var/www/html

echo "Menunggu database ($DB_HOST:$DB_PORT) siap..."
until php -r "new PDO('mysql:host=$DB_HOST;port=$DB_PORT', '$DB_USERNAME', '$DB_PASSWORD');" 2>/dev/null; do
    sleep 2
done
echo "Database siap."

php artisan migrate --force --seed

if [ ! -L public/storage ]; then
    php artisan storage:link
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
