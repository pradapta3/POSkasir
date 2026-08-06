#!/usr/bin/env bash
#
# POS Kasir — Langkah 2: install stack & aplikasi
# Jalankan sebagai user 'deploy' (BUKAN root — script ini pakai sudo
# sendiri untuk bagian yang butuh), SEKALI SAJA, setelah 01-server-setup.sh
# dan re-login sebagai 'deploy' berhasil.
#
# Domain : mbayar.my.id
# IP VPS : 202.155.14.70
#
set -euo pipefail

DOMAIN="mbayar.my.id"
VPS_IP="202.155.14.70"
APP_DIR="/var/www/pos-kasir"
DB_NAME="pos_kasir"
DB_USER="pos_kasir"
DB_PASS="$(openssl rand -base64 24 | tr -dc 'A-Za-z0-9' | cut -c1-24)"
REPO_URL="https://github.com/pradapta3/POSkasir.git"
RUN_AS="$(whoami)"

if [ "$RUN_AS" = "root" ]; then
    echo "Jangan jalankan ini sebagai root — login sebagai user 'deploy' dulu." >&2
    exit 1
fi

echo "==> Install PHP 8.3"
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y php8.3 php8.3-fpm php8.3-mysql php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath \
  php8.3-intl php8.3-cli

echo "==> Install MySQL"
sudo apt install -y mysql-server
# Hardening dasar non-interaktif (setara jawaban "Y" di mysql_secure_installation
# untuk anonymous user, remote root, dan test database). Jalankan
# `sudo mysql_secure_installation` manual kalau mau atur password root juga.
sudo mysql -e "DELETE FROM mysql.user WHERE User='';" 2>/dev/null || true
sudo mysql -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost','127.0.0.1','::1');" 2>/dev/null || true
sudo mysql -e "DROP DATABASE IF EXISTS test;" 2>/dev/null || true
sudo mysql -e "FLUSH PRIVILEGES;"

echo "==> Buat database & user aplikasi"
sudo mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
sudo mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

echo "==> Install Nginx, Composer, Node.js, Git"
sudo apt install -y nginx git unzip
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
sudo apt install -y nodejs

echo "==> Clone aplikasi dari GitHub"
sudo mkdir -p "${APP_DIR}"
sudo chown "${RUN_AS}:${RUN_AS}" "${APP_DIR}"
if [ -d "${APP_DIR}/.git" ]; then
    echo "    (sudah ada clone sebelumnya di ${APP_DIR}, skip clone)"
else
    git clone "${REPO_URL}" "${APP_DIR}"
fi
cd "${APP_DIR}"

composer install --optimize-autoloader --no-dev
npm install && npm run build

[ -f .env ] || cp .env.example .env
php artisan key:generate --force

echo "==> Konfigurasi .env untuk produksi"
sed -i "s|^APP_ENV=.*|APP_ENV=production|" .env
sed -i "s|^APP_DEBUG=.*|APP_DEBUG=false|" .env
sed -i "s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" .env
sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=mysql|" .env
sed -i "s|^DB_HOST=.*|DB_HOST=127.0.0.1|" .env
sed -i "s|^DB_PORT=.*|DB_PORT=3306|" .env
sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" .env
sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" .env
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" .env
if grep -q "^SESSION_SECURE_COOKIE=" .env; then
    sed -i "s|^SESSION_SECURE_COOKIE=.*|SESSION_SECURE_COOKIE=true|" .env
else
    echo "SESSION_SECURE_COOKIE=true" >> .env
fi

echo "==> Migrasi & seed database"
php artisan migrate --seed --force
php artisan storage:link

echo "==> Permission storage/ & bootstrap/cache/"
sudo chown -R "${RUN_AS}:www-data" "${APP_DIR}"
sudo find "${APP_DIR}/storage" -type d -exec chmod 775 {} \;
sudo find "${APP_DIR}/storage" -type f -exec chmod 664 {} \;
sudo chmod -R 775 "${APP_DIR}/bootstrap/cache"

echo "==> Cache konfigurasi produksi"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Konfigurasi Nginx"
sudo tee /etc/nginx/sites-available/pos-kasir > /dev/null <<NGINX
server {
    listen 80;
    server_name ${DOMAIN} www.${DOMAIN};
    root ${APP_DIR}/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php\$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 20M;
}
NGINX

sudo ln -sf /etc/nginx/sites-available/pos-kasir /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx

echo "==> Queue worker (systemd) — untuk notifikasi WhatsApp"
sudo tee /etc/systemd/system/pos-kasir-queue.service > /dev/null <<SERVICE
[Unit]
Description=POS Kasir queue worker
After=network.target mysql.service

[Service]
User=${RUN_AS}
Group=www-data
Restart=always
RestartSec=3
WorkingDirectory=${APP_DIR}
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
SERVICE

sudo systemctl daemon-reload
sudo systemctl enable --now pos-kasir-queue

echo "==> Backup database harian (cron jam 2 pagi)"
sudo mkdir -p /var/backups/pos-kasir
sudo tee /usr/local/bin/backup-pos-kasir.sh > /dev/null <<BACKUP
#!/bin/bash
TIMESTAMP=\$(date +%F_%H%M)
mysqldump -u ${DB_USER} -p'${DB_PASS}' ${DB_NAME} | gzip > /var/backups/pos-kasir/pos_kasir_\${TIMESTAMP}.sql.gz
find /var/backups/pos-kasir -name "*.sql.gz" -mtime +14 -delete
BACKUP
sudo chmod +x /usr/local/bin/backup-pos-kasir.sh
sudo chmod 600 /usr/local/bin/backup-pos-kasir.sh
(crontab -l 2>/dev/null | grep -v backup-pos-kasir.sh; echo "0 2 * * * /usr/local/bin/backup-pos-kasir.sh") | crontab -

cat <<EOF

==================================================================
 Selesai! CATAT baik-baik info database di bawah — tidak akan
 ditampilkan lagi setelah ini (tapi tersimpan juga di
 ${APP_DIR}/.env kalau perlu dicek ulang):

   Database    : ${DB_NAME}
   DB User     : ${DB_USER}
   DB Password : ${DB_PASS}

 Simpan salinannya di tempat aman (password manager, dsb).

 Langkah selanjutnya:
 1. Pastikan DNS domain '${DOMAIN}' sudah mengarah ke IP VPS ini
    (${VPS_IP}) — cek dengan:  dig ${DOMAIN} +short
 2. Kalau sudah mengarah dengan benar, jalankan:
      cd ${APP_DIR} && bash deploy/03-enable-https.sh
 3. Setelah aplikasi bisa diakses, GANTI password akun seed
    (admin@poskasir.test, platform@poskasir.test) lewat aplikasi.
==================================================================
EOF
