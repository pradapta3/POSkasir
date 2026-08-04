#!/bin/sh
set -e

cd /var/www/html

echo "Menunggu database ($DB_HOST:$DB_PORT) siap..."
# PHP CLI menulis fatal error ke stdout, bukan stderr — keduanya dibuang
# supaya percobaan koneksi yang gagal tidak membanjiri log saat menunggu.
waited=0
until php -r "new PDO('mysql:host=$DB_HOST;port=$DB_PORT', '$DB_USERNAME', '$DB_PASSWORD');" >/dev/null 2>&1; do
    sleep 2
    waited=$((waited + 2))
    if [ "$waited" -ge 60 ]; then
        echo "Masih belum bisa menyambung ke database setelah ${waited}s."
        echo "Cek DB_HOST/DB_USERNAME/DB_PASSWORD di .env (DB_HOST harus 'db',"
        echo "bukan 127.0.0.1), lalu cek juga: docker compose logs db"
        waited=0
    fi
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
