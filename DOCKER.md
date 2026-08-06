# Deploy pakai Docker

Alternatif dari [DEPLOYMENT.md](DEPLOYMENT.md) — daripada install PHP,
MySQL, dan Nginx langsung di VPS, semuanya jalan di dalam container.
Lebih sedikit yang perlu diinstal manual di server, dan lebih mudah
dipindah ke VPS lain kalau perlu (tinggal `git clone` + `docker compose up`).

Panduan ini pakai domain `mbayar.my.id` dan IP `202.155.14.70` sebagai
contoh — file konfigurasinya sudah diisi dengan nilai ini. Ganti kalau
domain/IP-mu beda.

> Domain harus sudah diarahkan ke IP VPS-mu sebelum langkah HTTPS di
> bawah — kalau belum, lihat [DEPLOYMENT.md bagian "Arahkan domain ke
> VPS"](DEPLOYMENT.md#0-arahkan-domain-ke-vps) dulu (langkah itu sama
> saja, tidak terkait Docker).

## 1. Install Docker

```bash
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker $USER
```

Logout lalu login lagi supaya keanggotaan grup `docker` berlaku (atau
jalankan `newgrp docker`) — setelah ini perintah `docker` tidak perlu
`sudo` lagi.

> Kalau VPS ini sebelumnya sudah dipakai dengan setup native
> ([DEPLOYMENT.md](DEPLOYMENT.md)), matikan dulu servis yang lama supaya
> tidak rebutan port 80/443/3306:
> ```bash
> sudo systemctl stop nginx mysql "php8.3-fpm" 2>/dev/null
> sudo systemctl disable nginx mysql "php8.3-fpm" 2>/dev/null
> ```

## 2. Clone aplikasi

```bash
git clone https://github.com/pradapta3/POSkasir.git pos-kasir
cd pos-kasir
```

## 3. Konfigurasi environment

```bash
cp .env.example .env
```

Edit `.env` — beberapa nilai wajib beda dari default karena jalan di
Docker (host database bukan `127.0.0.1` lagi, tapi nama service `db`):

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://mbayar.my.id

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=pos_kasir
DB_USERNAME=pos_kasir
DB_PASSWORD=GANTI_DENGAN_PASSWORD_KUAT

SESSION_SECURE_COOKIE=true
```

Tambahkan juga satu baris baru khusus untuk password root MySQL di dalam
container (dipakai `docker-compose.yml`, bukan oleh aplikasi Laravel):

```env
DB_ROOT_PASSWORD=GANTI_DENGAN_PASSWORD_LAIN_YANG_KUAT
```

> `docker compose` otomatis membaca file `.env` di folder yang sama
> dengan `docker-compose.yml` untuk mengisi `${DB_DATABASE}` dkk. di
> dalamnya — satu file `.env` ini dipakai bersama oleh Laravel dan Docker
> Compose, tidak perlu file terpisah.

## 4. Build image & install dependency

```bash
docker compose build

docker compose run --rm app composer install --optimize-autoloader --no-dev

# Build aset Tailwind/Vite — pakai container Node sekali pakai, tidak
# perlu install Node.js langsung di VPS
docker run --rm -v "$(pwd)":/app -w /app node:20 sh -c "npm ci && npm run build"

docker compose run --rm app php artisan key:generate
```

## 5. Siapkan database

```bash
docker compose up -d db

# Tunggu sampai statusnya "healthy" (beberapa detik)
docker compose ps

docker compose run --rm app php artisan migrate --seed --force
docker compose run --rm app php artisan storage:link
```

## 6. Jalankan semua service

```bash
docker compose up -d
docker compose ps
```

Empat service akan jalan: `app` (PHP-FPM), `queue` (worker notifikasi
WhatsApp), `nginx` (web server), `db` (MySQL). Di titik ini aplikasi
sudah bisa diakses lewat `http://mbayar.my.id` (belum HTTPS).

## 7. Firewall

Sama seperti setup native — Docker mengelola port-forwarding-nya sendiri,
tapi `ufw` tetap perlu mengizinkan trafik masuk ke server:

```bash
sudo apt install -y ufw
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

## 8. Aktifkan HTTPS

Container `nginx` sekarang jalan pakai `docker/nginx/http.conf` (HTTP
saja) — ini sengaja, karena Certbot butuh server HTTP yang sudah aktif
untuk memverifikasi kepemilikan domain sebelum bisa menerbitkan
sertifikat.

**Pastikan dulu domain sudah mengarah ke server ini:**

```bash
dig mbayar.my.id +short
# harus menampilkan 202.155.14.70
```

Kalau sudah benar, minta sertifikat:

```bash
docker compose run --rm certbot certonly \
  --webroot -w /var/www/certbot \
  -d mbayar.my.id -d www.mbayar.my.id \
  --email EMAIL_KAMU@example.com --agree-tos --no-eff-email
```

Ganti nginx ke konfigurasi HTTPS dan reload:

```bash
cp docker/nginx/https.conf docker/nginx/active.conf
docker compose restart nginx
```

Aplikasi sekarang bisa diakses di `https://mbayar.my.id`. Sertifikat
diperpanjang otomatis oleh container `certbot` (cek tiap 12 jam, hanya
benar-benar memperbarui kalau sudah dekat kadaluwarsa).

## 9. Backup database

```bash
sudo mkdir -p /var/backups/pos-kasir
sudo tee /usr/local/bin/backup-pos-kasir.sh > /dev/null <<'EOF'
#!/bin/bash
cd /home/deploy/pos-kasir   # sesuaikan path clone-mu
TIMESTAMP=$(date +%F_%H%M)
docker compose exec -T db mysqldump -u pos_kasir -pGANTI_DENGAN_PASSWORD_KUAT pos_kasir \
  | gzip > /var/backups/pos-kasir/pos_kasir_$TIMESTAMP.sql.gz
find /var/backups/pos-kasir -name "*.sql.gz" -mtime +14 -delete
EOF
sudo chmod +x /usr/local/bin/backup-pos-kasir.sh
sudo chmod 600 /usr/local/bin/backup-pos-kasir.sh   # berisi password DB

(crontab -l 2>/dev/null; echo "0 2 * * * /usr/local/bin/backup-pos-kasir.sh") | crontab -
```

Simpan salinan backup ini di luar VPS juga (S3, Google Drive, dll.).

## 10. Update aplikasi di kemudian hari

```bash
cd pos-kasir
git pull origin main
docker compose build
docker compose run --rm app composer install --optimize-autoloader --no-dev
docker run --rm -v "$(pwd)":/app -w /app node:20 sh -c "npm ci && npm run build"
docker compose run --rm app php artisan migrate --force
docker compose run --rm app php artisan config:cache
docker compose up -d
```

## Perintah yang berguna

```bash
docker compose logs -f app          # log aplikasi
docker compose logs -f queue        # log queue worker
docker compose exec app bash        # masuk ke container app
docker compose exec app php artisan tinker
docker compose restart nginx        # setelah ganti konfigurasi nginx
docker compose down                 # matikan semua (data tetap aman di volume)
```

## Checklist sebelum benar-benar dipakai pelanggan

- [ ] `APP_DEBUG=false` dan `APP_ENV=production` di `.env`
- [ ] `DB_PASSWORD` dan `DB_ROOT_PASSWORD` bukan default, dan beda satu
      sama lain
- [ ] HTTPS aktif (`docker/nginx/active.conf` sudah isi `https.conf`),
      `APP_URL` pakai `https://`
- [ ] Password akun seed (`admin@poskasir.test`, `platform@poskasir.test`)
      sudah diganti — lihat [README](README.md#akun-awal-setelah-seeding)
- [ ] Firewall (`ufw`) aktif, hanya port 22/80/443 terbuka
- [ ] Backup database terjadwal dan sudah dites bisa di-restore
- [ ] `https://mbayar.my.id/.env` harus 403/404, bukan menampilkan isi
      file (nginx sudah memblokirnya lewat aturan dotfile — tapi cek
      langsung sekali untuk memastikan)
