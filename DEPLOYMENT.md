# Deploy ke VPS (Ubuntu 24.04 LTS)

Panduan ini mengasumsikan VPS baru/bersih (Ubuntu 24.04 LTS) dengan akses
root atau `sudo`. Semua perintah dijalankan lewat SSH.

Ganti `namadomain.com`, `pos_kasir`, dan password contoh di bawah dengan
milikmu sendiri — jangan pakai apa adanya di produksi.

## Cara cepat: pakai script

Kalau tidak mau menjalankan tiap perintah satu-satu, tiga script di folder
[`deploy/`](deploy/) melakukan semuanya secara otomatis — sudah diisi
dengan domain `mbayar.my.id` dan IP `202.155.14.70`. **Baca isinya dulu
sebelum dijalankan**, terutama karena semuanya pakai `sudo`.

1. **Sebagai root**, di VPS baru: jalankan
   [`deploy/01-server-setup.sh`](deploy/01-server-setup.sh) — buat user
   `deploy`, aktifkan firewall & fail2ban.
2. **Sebagai `deploy`** (setelah re-login lewat SSH key): jalankan
   [`deploy/02-install-app.sh`](deploy/02-install-app.sh) — install
   seluruh stack (PHP, MySQL, Nginx, dst.), clone aplikasi dari GitHub,
   migrasi database, konfigurasi Nginx, queue worker, backup harian.
   Password database dibuat acak otomatis dan ditampilkan di akhir —
   catat baik-baik.
3. **Setelah DNS domain sudah mengarah ke VPS** (lihat langkah 0 di
   bawah): `cd /var/www/pos-kasir && bash deploy/03-enable-https.sh` —
   pasang HTTPS lewat Let's Encrypt.

Cara mendapatkan file-file ini di VPS: copy-paste isinya langsung (paling
sederhana untuk yang pertama, karena repo belum ter-clone), atau begitu
`02-install-app.sh` selesai meng-clone repo, script ke-3 sudah otomatis
ada di `/var/www/pos-kasir/deploy/`.

Sisa dokumen ini menjelaskan setiap langkah yang dilakukan script-script
di atas secara manual, satu per satu — baca ini kalau mau paham detailnya
atau server-mu tidak persis Ubuntu 24.04.

## 0. Arahkan domain ke VPS

Lakukan ini duluan, sebelum instal apa pun — DNS butuh waktu untuk
merambat (propagasi), jadi lebih baik sudah berjalan di latar belakang
selagi kamu setup server.

**Cari tahu IP publik VPS kamu dulu** — biasanya ditampilkan di dashboard
provider VPS (DigitalOcean, Vultr, Contabo, Niagahoster Cloud VPS, dll.),
atau cek langsung dari VPS lewat SSH:

```bash
curl -4 ifconfig.me
```

Lalu pilih salah satu jalur di bawah — **A** kalau DNS dikelola langsung
di tempat kamu beli domain, atau **B** kalau mau pakai Cloudflare
(disarankan: gratis, dapat proteksi DDoS dasar, dan mempermudah HTTPS).

### Jalur A — DNS langsung dari registrar domain

1. Login ke tempat kamu beli domain (Niagahoster, Rumahweb, Domainesia,
   GoDaddy, Namecheap, dll.), buka menu "DNS Management" / "Kelola DNS".
2. Tambahkan dua DNS record bertipe **A**, keduanya mengarah ke IP VPS:

   | Type | Name/Host | Value / Points to | TTL |
   |---|---|---|---|
   | A | `@` (domain utama, mis. `namadomain.com`) | IP VPS kamu | Auto / 3600 |
   | A | `www` | IP VPS kamu | Auto / 3600 |

3. Lanjut ke bagian [Tunggu propagasi](#tunggu-propagasi) di bawah.

### Jalur B — Pakai Cloudflare

Beda dengan Jalur A, di Cloudflare kamu **tidak** menambah DNS record di
registrar — sebaliknya, pengelolaan DNS domainmu dipindah sepenuhnya ke
Cloudflare dengan mengganti *nameserver* domain.

1. **Daftar/masuk ke Cloudflare** di
   [dash.cloudflare.com](https://dash.cloudflare.com) kalau belum punya
   akun.
2. Klik **Add a domain** (atau **Add site**), masukkan nama domainmu
   (tanpa `http://` atau `www`, misal `namadomain.com`), lalu **Continue**.
3. Pilih paket **Free** — sudah cukup untuk kebutuhan ini — lalu
   **Continue**.
4. Cloudflare akan otomatis memindai (scan) DNS record yang sudah ada di
   domainmu. Biarkan saja, lanjut **Continue**.
5. Di halaman DNS record, tambahkan (atau edit kalau sudah ada dari hasil
   scan) dua record bertipe **A**:

   | Type | Name | IPv4 address | Proxy status |
   |---|---|---|---|
   | A | `@` | IP VPS kamu | **DNS only** (awan abu-abu) |
   | A | `www` | IP VPS kamu | **DNS only** (awan abu-abu) |

   Klik ikon awan di kolom **Proxy status** kalau masih oranye, sampai
   berubah jadi abu-abu ("DNS only") — biarkan seperti ini dulu untuk
   sementara, jangan langsung diaktifkan (dijelaskan kenapa di langkah 8).

6. **Continue to activation.** Cloudflare akan menampilkan **dua alamat
   nameserver** khusus untuk domainmu, contohnya:

   ```
   ada.ns.cloudflare.com
   ben.ns.cloudflare.com
   ```

   (Nama sebenarnya beda-beda per domain — pakai persis yang ditampilkan
   di dashboard-mu, bukan contoh di atas.)

7. **Pindah ke situs tempat kamu beli domain** (registrar — bukan
   Cloudflare), cari menu **Nameserver** / **DNS Settings** / **Change
   Nameservers**, lalu ganti nameserver yang ada dengan **dua nameserver
   dari Cloudflare** di langkah 6. Hapus nameserver default/lama, ganti
   total dengan yang dari Cloudflare.
8. Kembali ke Cloudflare, klik **Done, check nameservers**. Cloudflare
   akan memberi tahu lewat email begitu perubahan terdeteksi aktif —
   biasanya beberapa menit sampai beberapa jam, kadang sampai 24 jam.
   Status domain di dashboard Cloudflare akan berubah dari "Pending" jadi
   "Active".
9. Setelah statusnya **Active**, domainmu resmi dikelola Cloudflare dan
   dua A record dari langkah 5 sudah berlaku.

### Tunggu propagasi

Baik Jalur A maupun B, cek dulu sudah nyambung atau belum sebelum lanjut:

```bash
dig namadomain.com +short
```

Kalau hasilnya sudah menampilkan IP VPS kamu, domain sudah siap. Bisa
juga dicek lewat [dnschecker.org](https://dnschecker.org) untuk melihat
status propagasinya dari banyak lokasi sekaligus.

**Jangan lanjut ke langkah 8 (HTTPS/Certbot) sebelum ini benar-benar
selesai** — Certbot perlu bisa mengakses domain tersebut secara publik
untuk memverifikasi kepemilikannya. Kalau pakai Cloudflare, proxy-nya
juga harus masih **DNS only** (awan abu-abu) saat Certbot jalan — lihat
catatan di langkah 8.

## 1. Setup awal server & keamanan dasar

```bash
# Login pertama sebagai root, lalu update sistem
apt update && apt upgrade -y

# Buat user non-root untuk kerja sehari-hari (jangan pakai root terus)
adduser deploy
usermod -aG sudo deploy

# Salin SSH key kamu ke user baru (dari komputer lokal, BUKAN di VPS):
#   ssh-copy-id deploy@IP_VPS_KAMU
# lalu login ulang sebagai deploy, dan lanjutkan sisa panduan ini dari situ

# Firewall dasar — hanya buka SSH, HTTP, HTTPS
sudo apt install -y ufw
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# (Opsional tapi disarankan) fail2ban — blokir IP yang brute-force SSH
sudo apt install -y fail2ban
sudo systemctl enable --now fail2ban
```

> Setelah user `deploy` bisa login lewat SSH key, matikan login root dan
> login password lewat SSH: edit `/etc/ssh/sshd_config`, set
> `PermitRootLogin no` dan `PasswordAuthentication no`, lalu
> `sudo systemctl restart ssh`.

## 2. Install PHP

Ubuntu 24.04 sudah menyediakan PHP lewat apt, tapi memakai PPA Ondřej Surý
memberi kontrol versi yang lebih pasti dan update keamanan yang cepat —
ini pendekatan standar untuk deployment Laravel:

```bash
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update

sudo apt install -y php8.3 php8.3-fpm php8.3-mysql php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath \
  php8.3-intl php8.3-cli

php -v   # pastikan 8.3.x
```

## 3. Install MySQL

```bash
sudo apt install -y mysql-server
sudo mysql_secure_installation
```

Jawab prompt-nya: set password root, hapus anonymous user, tolak remote
root login, hapus test database — jawab "Y" untuk semuanya.

Buat database dan user khusus aplikasi (jangan pakai `root` di `.env`):

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE pos_kasir CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pos_kasir'@'localhost' IDENTIFIED BY 'GANTI_DENGAN_PASSWORD_KUAT';
GRANT ALL PRIVILEGES ON pos_kasir.* TO 'pos_kasir'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 4. Install Nginx, Composer, Node.js, Git

```bash
sudo apt install -y nginx git unzip

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js (LTS) — hanya dipakai sekali untuk build aset Tailwind/Vite
curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
sudo apt install -y nodejs
```

## 5. Clone & setup aplikasi

```bash
sudo mkdir -p /var/www/pos-kasir
sudo chown deploy:deploy /var/www/pos-kasir
git clone https://github.com/pradapta3/POSkasir.git /var/www/pos-kasir
cd /var/www/pos-kasir

composer install --optimize-autoloader --no-dev
npm install && npm run build

cp .env.example .env
php artisan key:generate
```

Edit `.env` untuk produksi:

```env
APP_NAME="POS Kasir"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://namadomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pos_kasir
DB_USERNAME=pos_kasir
DB_PASSWORD=GANTI_DENGAN_PASSWORD_KUAT

SESSION_SECURE_COOKIE=true

QUEUE_CONNECTION=database
MAIL_MAILER=log
FONNTE_TOKEN=
```

> `APP_DEBUG=false` wajib di produksi — kalau tetap `true`, error apa pun
> akan menampilkan stack trace lengkap (termasuk isi `.env`) ke siapa saja
> yang mengaksesnya. `SESSION_SECURE_COOKIE=true` memastikan cookie sesi
> hanya dikirim lewat HTTPS, setelah SSL aktif di langkah 8.

Migrasi, seed, dan siapkan storage:

```bash
php artisan migrate --seed --force
php artisan storage:link

sudo chown -R deploy:www-data /var/www/pos-kasir
sudo find /var/www/pos-kasir/storage -type d -exec chmod 775 {} \;
sudo find /var/www/pos-kasir/storage -type f -exec chmod 664 {} \;
sudo chmod -R 775 /var/www/pos-kasir/bootstrap/cache
```

Cache konfigurasi untuk performa produksi (ulangi 3 baris ini setiap kali
`.env` atau kode berubah — lihat [bagian update](#10-update-aplikasi-di-kemudian-hari)):

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 6. Konfigurasi Nginx

Buat `/etc/nginx/sites-available/pos-kasir`:

```nginx
server {
    listen 80;
    server_name namadomain.com www.namadomain.com;
    root /var/www/pos-kasir/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 20M;
}
```

Aktifkan dan reload:

```bash
sudo ln -s /etc/nginx/sites-available/pos-kasir /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

## 7. Queue worker (untuk notifikasi WhatsApp)

Jalankan sebagai systemd service supaya otomatis restart kalau crash atau
server reboot. Buat `/etc/systemd/system/pos-kasir-queue.service`:

```ini
[Unit]
Description=POS Kasir queue worker
After=network.target mysql.service

[Service]
User=deploy
Group=www-data
Restart=always
RestartSec=3
WorkingDirectory=/var/www/pos-kasir
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now pos-kasir-queue
sudo systemctl status pos-kasir-queue
```

Kalau tidak memakai notifikasi WhatsApp (`FONNTE_TOKEN` kosong), langkah
ini boleh dilewati — sisa aplikasi tetap berjalan normal tanpanya.

## 8. HTTPS lewat Let's Encrypt

> Kalau kamu pakai Cloudflare (Jalur B di langkah 0): pastikan proxy
> masih **DNS only** (awan abu-abu) di dashboard Cloudflare sebelum
> menjalankan Certbot di bawah ini. Kalau sudah diaktifkan (awan oranye),
> permintaan verifikasi Certbot akan lewat Cloudflare dulu, bukan
> langsung ke VPS-mu, dan biasanya gagal.

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d namadomain.com -d www.namadomain.com
```

Certbot otomatis mengubah config Nginx untuk redirect HTTP → HTTPS dan
memasang sertifikat. Renewal otomatis sudah terjadwal lewat systemd timer
bawaan certbot — cek dengan `sudo systemctl status certbot.timer`.

Setelah HTTPS aktif, pastikan `.env` sudah `APP_URL=https://namadomain.com`
dan `SESSION_SECURE_COOKIE=true` (sudah diset di langkah 5), lalu:

```bash
php artisan config:cache
```

**Kalau pakai Cloudflare**, sekarang baru boleh aktifkan proxy-nya (klik
ikon awan di dashboard Cloudflare sampai jadi oranye) untuk mendapat
proteksi DDoS dan caching tambahan. Setelah itu, buka **SSL/TLS** di menu
Cloudflare dan pastikan mode-nya di-set ke **Full (strict)** — bukan
"Flexible". Sertifikat dari Certbot di VPS sudah valid, jadi Cloudflare
bisa memverifikasinya penuh; mode "Flexible" malah bisa menyebabkan
redirect loop karena Cloudflare akan mengira koneksi ke origin server
tidak terenkripsi.

## 9. Backup database (jadwal harian)

```bash
sudo mkdir -p /var/backups/pos-kasir
sudo tee /usr/local/bin/backup-pos-kasir.sh > /dev/null <<'EOF'
#!/bin/bash
TIMESTAMP=$(date +%F_%H%M)
mysqldump -u pos_kasir -p'GANTI_DENGAN_PASSWORD_KUAT' pos_kasir \
  | gzip > /var/backups/pos-kasir/pos_kasir_$TIMESTAMP.sql.gz
find /var/backups/pos-kasir -name "*.sql.gz" -mtime +14 -delete
EOF
sudo chmod +x /usr/local/bin/backup-pos-kasir.sh
sudo chmod 600 /usr/local/bin/backup-pos-kasir.sh   # berisi password DB

# Jadwalkan tiap jam 2 pagi
(crontab -l 2>/dev/null; echo "0 2 * * * /usr/local/bin/backup-pos-kasir.sh") | crontab -
```

Simpan salinan backup ini di luar VPS juga (S3, Google Drive, dll.) —
backup yang hanya ada di server yang sama tidak menyelamatkan apa-apa
kalau VPS-nya hilang/rusak.

## 10. Update aplikasi di kemudian hari

```bash
cd /var/www/pos-kasir
sudo systemctl stop pos-kasir-queue

git pull origin main
composer install --optimize-autoloader --no-dev
npm install && npm run build

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

sudo systemctl start pos-kasir-queue
sudo systemctl reload nginx
```

## Checklist sebelum benar-benar dipakai pelanggan

- [ ] `APP_DEBUG=false` dan `APP_ENV=production` di `.env`
- [ ] Password database bukan default, dan bukan `root`
- [ ] HTTPS aktif, `APP_URL` pakai `https://`
- [ ] Password akun seed (`admin@poskasir.test`, `platform@poskasir.test`)
      sudah diganti — lihat [README](README.md#akun-awal-setelah-seeding)
- [ ] Login root SSH & password auth SSH sudah dimatikan
- [ ] Firewall (`ufw`) aktif, hanya port 22/80/443 terbuka
- [ ] Backup database terjadwal dan sudah dites bisa di-restore
- [ ] `storage/` dan `bootstrap/cache/` writable oleh user web server,
      tapi `.env` **tidak** bisa diakses lewat browser (cek langsung:
      `https://namadomain.com/.env` harus 403/404, bukan menampilkan isi file)
