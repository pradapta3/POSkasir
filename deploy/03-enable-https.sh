#!/usr/bin/env bash
#
# POS Kasir — Langkah 3: aktifkan HTTPS
# Jalankan sebagai user 'deploy' SETELAH DNS domain sudah mengarah ke IP
# VPS ini (cek dulu dengan: dig mbayar.my.id +short).
#
# Kalau kamu pakai Cloudflare: pastikan proxy masih "DNS only" (awan
# abu-abu) dulu sebelum menjalankan ini — lihat DEPLOYMENT.md.
#
set -euo pipefail

DOMAIN="mbayar.my.id"
VPS_IP="202.155.14.70"
APP_DIR="/var/www/pos-kasir"

echo "==> Cek DNS domain mengarah ke server ini"
RESOLVED_IP="$(dig +short "${DOMAIN}" @1.1.1.1 | tail -n1)"
echo "    ${DOMAIN} -> ${RESOLVED_IP:-<tidak ditemukan>}"
if [ "${RESOLVED_IP}" != "${VPS_IP}" ]; then
    echo "    PERINGATAN: DNS belum (atau belum sepenuhnya) mengarah ke ${VPS_IP}."
    echo "    Certbot kemungkinan besar akan gagal memverifikasi domain."
    read -rp "    Tetap lanjutkan? (y/N) " REPLY
    [[ "${REPLY}" =~ ^[Yy]$ ]] || exit 1
fi

echo "==> Install Certbot & pasang sertifikat HTTPS"
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d "${DOMAIN}" -d "www.${DOMAIN}"

echo "==> Perbarui APP_URL & cache ulang"
cd "${APP_DIR}"
sed -i "s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" .env
php artisan config:cache

cat <<EOF

==================================================================
 Selesai! Aplikasi sudah bisa diakses di:

   https://${DOMAIN}

 Kalau pakai Cloudflare, sekarang boleh aktifkan proxy-nya (awan
 jadi oranye), lalu di menu SSL/TLS Cloudflare set mode ke
 "Full (strict)" — bukan "Flexible". Lihat DEPLOYMENT.md untuk
 detail kenapa.

 Sertifikat akan diperpanjang otomatis lewat systemd timer bawaan
 certbot (cek: sudo systemctl status certbot.timer).
==================================================================
EOF
