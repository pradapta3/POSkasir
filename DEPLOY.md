# Panduan Deploy ke VPS (Ubuntu 24.04 + Docker)

Panduan ini untuk deploy POS Kasir ke VPS dengan:

- IP VPS: `202.155.14.70`
- Domain: `www.mbayar.my.id`
- OS: Ubuntu 24.04 LTS
- Cara install: Docker (semua service jalan di container — tidak perlu
  install PHP/MySQL/Nginx manual di VPS)

Yang akan dijalankan lewat `docker-compose.yml`:

| Container | Fungsi |
|---|---|
| `app` | PHP-FPM + Nginx (aplikasi Laravel) + worker antrian WhatsApp |
| `db` | MySQL 8 |
| `nginx-proxy` | Reverse proxy di port 80/443, otomatis arahkan ke `app` |
| `acme-companion` | Otomatis terbitkan & perpanjang SSL Let's Encrypt |

Dengan setup ini kamu **tidak perlu jalankan `certbot` manual** — sertifikat
SSL diterbitkan dan diperpanjang otomatis selama container jalan.

---

## 0. Yang perlu disiapkan

- Akses SSH ke VPS (`root` atau user dengan `sudo`).
- Domain `mbayar.my.id` sudah kamu miliki dan bisa diatur DNS-nya (di
  panel registrar/DNS provider tempat kamu beli domain).

---

## 1. Arahkan domain ke VPS

Di panel DNS domain `mbayar.my.id`, buat **A record**:

| Type | Name/Host | Value | TTL |
|---|---|---|---|
| A | `www` | `202.155.14.70` | Auto / 3600 |

Opsional tapi disarankan, tambahkan juga record untuk root domain supaya
`mbayar.my.id` (tanpa `www`) tidak error saat diakses:

| Type | Name/Host | Value | TTL |
|---|---|---|---|
| A | `@` | `202.155.14.70` | Auto / 3600 |

Tunggu propagasi DNS (biasanya beberapa menit sampai 1 jam). Cek dari
komputer kamu:

```bash
dig +short www.mbayar.my.id
# harus menampilkan: 202.155.14.70
```

Jangan lanjut ke langkah SSL sebelum ini benar — kalau DNS belum
mengarah ke VPS, Let's Encrypt akan gagal menerbitkan sertifikat.

---

## 2. Login ke VPS & siapkan sistem

```bash
ssh root@202.155.14.70

apt update && apt upgrade -y
```

### Install Docker Engine + Docker Compose plugin

```bash
# Hapus paket docker lama kalau ada (aman walau belum pernah install)
for pkg in docker.io docker-doc docker-compose podman-docker containerd runc; do
  apt-get remove -y $pkg 2>/dev/null || true
done

apt-get install -y ca-certificates curl gnupg
install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
chmod a+r /etc/apt/keyrings/docker.asc

echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  tee /etc/apt/sources.list.d/docker.list > /dev/null

apt-get update
apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# Cek instalasi
docker --version
docker compose version
```

### Aktifkan firewall

```bash
apt-get install -y ufw
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable
ufw status
```

---

## 3. Clone project

```bash
mkdir -p /opt/poskasir
git clone https://github.com/pradapta3/POSkasir.git /opt/poskasir
cd /opt/poskasir
```

---

## 4. Konfigurasi environment (`.env`)

```bash
cp .env.example .env
nano .env
```

Ubah/isi baris-baris berikut di `.env`:

```env
APP_NAME="POS Kasir"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://www.mbayar.my.id

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=pos_kasir
DB_USERNAME=poskasir
DB_PASSWORD=isi-password-kuat-di-sini

# Khusus docker-compose (bukan dibaca Laravel):
DB_ROOT_PASSWORD=isi-password-root-mysql-yang-beda
LETSENCRYPT_EMAIL=pradapta3@gmail.com
```

Catatan:
- `DB_HOST=db` (bukan `127.0.0.1`) karena di dalam Docker, MySQL diakses
  lewat nama service `db`.
- `DB_PASSWORD` dan `DB_ROOT_PASSWORD` **harus diisi dan berbeda satu sama
  lain** — jangan dikosongkan untuk produksi.
- `LETSENCRYPT_EMAIL` dipakai untuk registrasi sertifikat SSL & notifikasi
  kalau ada masalah perpanjangan.

Simpan file (`Ctrl+O`, `Enter`, `Ctrl+X` di nano).

### Generate `APP_KEY`

Build dulu image aplikasinya (tanpa menjalankan), lalu minta artisan
mencetak key baru:

```bash
docker compose build app
docker compose run --rm app php artisan key:generate --show
```

Salin output-nya (format `base64:xxxxxxxx...`), lalu tempel ke `.env`:

```bash
nano .env
# isi: APP_KEY=base64:xxxxxxxx...
```

---

## 5. Build & jalankan semua container

```bash
docker compose up -d --build
```

Perintah ini akan:
1. Build image aplikasi (composer install, npm build, dst).
2. Menjalankan MySQL, aplikasi, dan reverse proxy.
3. Container `app` otomatis menjalankan migrasi database + seed data awal
   saat pertama kali start (lihat `docker/entrypoint.sh`).
4. `acme-companion` otomatis meminta sertifikat SSL Let's Encrypt untuk
   `www.mbayar.my.id` — proses ini butuh **1-2 menit** setelah container
   aktif dan DNS sudah mengarah dengan benar.

Cek status semua container:

```bash
docker compose ps
```

Semua harus berstatus `Up`/`running`. Lihat log kalau ada yang aneh:

```bash
docker compose logs -f app            # log aplikasi Laravel/Nginx
docker compose logs -f acme-companion # log proses penerbitan SSL
```

---

## 6. Cek aplikasi

Buka di browser: **https://www.mbayar.my.id**

Kamu akan diarahkan ke halaman `/login`. Masuk pakai akun contoh hasil
seeding:

| Email | Password | Peran |
|---|---|---|
| `admin@poskasir.test` | `password` | Superadmin |
| `platform@poskasir.test` | `password` | Admin Platform |

**Segera ganti kedua password ini** (menu profil/akun) sebelum dipakai
untuk transaksi sungguhan — kredensial di atas publik ada di README.

---

## 7. Perintah operasional sehari-hari

```bash
# Lihat log aplikasi real-time
docker compose logs -f app

# Restart aplikasi (tanpa hilang data)
docker compose restart app

# Deploy update kode terbaru
cd /opt/poskasir
git pull
docker compose up -d --build

# Jalankan perintah artisan apa pun di dalam container
docker compose exec app php artisan <perintah>

# Backup database
docker compose exec db mysqldump -u root -p"$(grep DB_ROOT_PASSWORD .env | cut -d= -f2)" pos_kasir > backup-$(date +%F).sql

# Matikan semua container (data tetap aman di volume)
docker compose down

# Matikan sekaligus HAPUS data (hindari kecuali memang ingin reset total)
docker compose down -v
```

---

## 8. Troubleshooting singkat

**Situs muncul "502 Bad Gateway"**
Container `app` biasanya masih menjalankan migrasi/build cache saat start
pertama. Tunggu ~30 detik lalu refresh. Kalau masih 502, cek:
```bash
docker compose logs -f app
```

**Belum ada SSL / browser bilang "Not Secure"**
- Pastikan DNS `www.mbayar.my.id` sudah benar mengarah ke `202.155.14.70`
  (`dig +short www.mbayar.my.id`).
- Cek log `acme-companion`: `docker compose logs -f acme-companion`.
- Port 80 dan 443 harus terbuka ke internet (cek `ufw status`, dan cek
  juga firewall dari provider VPS kalau ada, di luar `ufw`).

**Lupa password akun admin**
```bash
docker compose exec app php artisan tinker
>>> $u = App\Models\User::where('email', 'admin@poskasir.test')->first();
>>> $u->password = bcrypt('password-baru');
>>> $u->save();
```

**Container `db` gagal start / data korup**
Jangan hapus volume `db_data` sembarangan — itu satu-satunya tempat data
tersimpan. Cek log dulu: `docker compose logs db`.
