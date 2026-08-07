# Panduan Deploy ke VPS (Ubuntu 24.04 + Docker)

Panduan lengkap dari VPS kosong sampai aplikasi bisa diakses lewat HTTPS.

- IP VPS: `202.155.14.70`
- Domain: `www.mbayar.my.id`
- OS: Ubuntu 24.04 LTS
- Semua service jalan di container — tidak perlu install PHP/MySQL/Nginx
  manual di VPS

Yang akan berjalan lewat `docker-compose.yml`:

| Container | Fungsi |
|---|---|
| `app` | PHP-FPM + Nginx (aplikasi Laravel) + worker antrian WhatsApp |
| `db` | MySQL 8 |
| `nginx-proxy` | Reverse proxy di port 80/443, otomatis arahkan ke `app` |
| `acme-companion` | Otomatis terbitkan & perpanjang SSL Let's Encrypt |

Dengan setup ini **tidak perlu jalankan `certbot` manual** — sertifikat SSL
diterbitkan dan diperpanjang otomatis selama container jalan.

**Perkiraan waktu:** 20–30 menit, di luar waktu tunggu propagasi DNS.

---

## Yang perlu disiapkan

- Akses SSH ke VPS sebagai `root` (atau user dengan `sudo`)
- Domain `mbayar.my.id` yang DNS-nya bisa kamu atur di panel registrar
- VPS minimal **2 GB RAM** — kalau hanya 1 GB, langkah swap di bawah
  wajib, karena proses build bisa kehabisan memori

---

## Langkah 1 — Arahkan domain ke VPS

Lakukan ini paling awal, karena propagasi DNS butuh waktu.

Di panel DNS `mbayar.my.id`, buat A record:

| Type | Name/Host | Value | TTL |
|---|---|---|---|
| A | `www` | `202.155.14.70` | Auto / 3600 |
| A | `@` | `202.155.14.70` | Auto / 3600 |

Record `@` (root domain) opsional tapi disarankan supaya `mbayar.my.id`
tanpa `www` tidak error saat diakses.

Cek dari komputer kamu (bukan dari VPS):

```bash
dig +short www.mbayar.my.id
# harus menampilkan: 202.155.14.70
```

Biasanya beberapa menit sampai 1 jam. **Jangan lanjut ke langkah SSL
sebelum ini benar** — kalau DNS belum mengarah ke VPS, Let's Encrypt akan
gagal menerbitkan sertifikat.

---

## Langkah 2 — Login pertama & update sistem

```bash
ssh root@202.155.14.70

apt update && apt upgrade -y
```

Kalau muncul dialog biru soal restart service, pilih **Ok** saja. Kalau
diminta reboot setelah update kernel:

```bash
reboot
# tunggu ~30 detik, lalu ssh lagi
```

---

## Langkah 3 — Zona waktu & hostname

```bash
timedatectl set-timezone Asia/Jakarta
hostnamectl set-hostname poskasir

date        # cek: harus jam Indonesia (WIB)
```

Zona waktu penting supaya jam di struk dan laporan penjualan benar.

---

## Langkah 4 — Swap (wajib kalau RAM di bawah 2 GB)

Proses build (`npm run build` dan `composer install`) cukup rakus memori.
Tanpa swap, di VPS kecil build bisa mati di tengah jalan tanpa pesan
jelas — biasanya muncul sebagai `Killed` atau exit code 137.

Cek dulu apakah sudah ada swap:

```bash
free -h
```

Kalau baris `Swap` menunjukkan `0B`, buat 2 GB:

```bash
fallocate -l 2G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab

free -h     # sekarang baris Swap harus terisi 2Gi
```

Baris `/etc/fstab` membuat swap otomatis aktif lagi setiap VPS reboot.

---

## Langkah 5 — Firewall

```bash
apt install -y ufw

ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

ufw status
```

> **Penting:** `ufw allow OpenSSH` harus dijalankan sebelum `ufw enable`,
> kalau tidak koneksi SSH kamu sendiri akan terputus dan VPS jadi tidak
> bisa diakses kecuali lewat konsol web dari panel penyedia VPS.

Port 3306 (MySQL) sengaja **tidak** dibuka — database hanya diakses dari
dalam jaringan Docker, tidak perlu terekspos ke internet.

---

## Langkah 6 — Install Docker

```bash
curl -fsSL https://get.docker.com | sh

docker --version
docker compose version
```

Itu script resmi dari Docker. Kalau lebih suka cara manual lewat apt
repository, langkahnya ada di
[dokumentasi resmi Docker](https://docs.docker.com/engine/install/ubuntu/).

Pastikan Docker otomatis jalan setelah reboot:

```bash
systemctl enable docker
systemctl is-enabled docker    # harus: enabled
```

---

## Langkah 7 — Ambil kode aplikasi

```bash
git clone https://github.com/pradapta3/POSkasir.git /opt/poskasir
cd /opt/poskasir
```

> Kalau file Docker belum ada di branch `main`, clone branch-nya langsung:
>
> ```bash
> git clone -b claude/vps-ubuntu-docker-setup-hfmmur \
>   https://github.com/pradapta3/POSkasir.git /opt/poskasir
> ```

---

## Langkah 8 — Konfigurasi `.env`

Ini langkah yang paling sering bikin gagal, jadi kerjakan pelan-pelan.

```bash
cd /opt/poskasir
cp .env.example .env
```

Isi nilainya (password digenerate acak otomatis):

```bash
sed -i 's|^APP_ENV=.*|APP_ENV=production|'                                .env
sed -i 's|^APP_DEBUG=.*|APP_DEBUG=false|'                                 .env
sed -i 's|^APP_URL=.*|APP_URL=https://www.mbayar.my.id|'                  .env
sed -i 's|^DB_HOST=.*|DB_HOST=db|'                                        .env
sed -i 's|^DB_DATABASE=.*|DB_DATABASE=pos_kasir|'                         .env
sed -i 's|^DB_USERNAME=.*|DB_USERNAME=poskasir|'                          .env
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$(openssl rand -hex 16)|"           .env
sed -i "s|^DB_ROOT_PASSWORD=.*|DB_ROOT_PASSWORD=$(openssl rand -hex 16)|" .env
sed -i 's|^LETSENCRYPT_EMAIL=.*|LETSENCRYPT_EMAIL=emailkamu@gmail.com|'   .env
sed -i "s|^APP_KEY=.*|APP_KEY=base64:$(openssl rand -base64 32)|"         .env
```

Ganti `emailkamu@gmail.com` dengan email kamu yang sebenarnya — dipakai
Let's Encrypt untuk notifikasi kalau perpanjangan sertifikat bermasalah.

Verifikasi, **tidak boleh ada yang kosong**:

```bash
grep -E '^(APP_ENV|APP_KEY|APP_URL|DB_|LETSENCRYPT_EMAIL)=' .env
```

Dua hal yang paling sering keliru:

- `DB_HOST` **harus** `db`, bukan `127.0.0.1`. Di dalam Docker,
  `127.0.0.1` menunjuk ke container itu sendiri — bukan ke MySQL. Kalau
  salah, log akan penuh `SQLSTATE[HY000] [2002] Connection refused`.
- `DB_ROOT_PASSWORD` tidak boleh kosong — MySQL menolak start tanpa itu.

---

## Langkah 9 — Build & jalankan

```bash
docker compose up -d --build
```

Build pertama butuh **5–15 menit** (kompilasi ekstensi PHP, composer
install, npm build). Setelah itu:

1. MySQL, aplikasi, dan reverse proxy dinyalakan
2. Container `app` otomatis menjalankan migrasi + seed data awal
   (lihat `docker/entrypoint.sh`)
3. `acme-companion` meminta sertifikat SSL — butuh 1–2 menit setelah
   container aktif dan DNS sudah benar

Cek statusnya:

```bash
docker compose ps
```

Keempat container harus `Up`/`running`. Pantau prosesnya:

```bash
docker compose logs -f app             # migrasi & seeding
docker compose logs -f acme-companion  # penerbitan SSL
```

Di log `app` yang kamu cari: `Menunggu database...` → `Database siap.` →
daftar migrasi jalan → seeding selesai.

---

## Langkah 10 — Cek aplikasi

Buka **https://www.mbayar.my.id**

Kamu akan diarahkan ke `/login`. Masuk dengan akun hasil seeding:

| Email | Password | Peran |
|---|---|---|
| `admin@poskasir.test` | `password` | Superadmin |
| `platform@poskasir.test` | `password` | Admin Platform |

**Segera ganti kedua password ini** sebelum dipakai untuk transaksi
sungguhan — kredensial di atas tertulis publik di README.

---

## Mengelola lewat GUI (opsional)

Semua langkah di atas bisa dijalankan lewat terminal saja. Tapi kalau
lebih nyaman pakai antarmuka grafis, ada beberapa pilihan.

### Portainer — GUI untuk Docker (paling cocok)

Kelola container lewat browser: lihat status, baca log, restart, buka
terminal ke dalam container, cek pemakaian resource. Ini yang paling
berguna untuk setup ini karena semuanya berjalan di Docker.

Repo ini sudah menyertakan `docker-compose.portainer.yml`. Cara pakainya:

1. Tambahkan A record DNS `panel` → `202.155.14.70` (seperti Langkah 1)
2. Jalankan bersama compose utama:

```bash
cd /opt/poskasir
docker compose -f docker-compose.yml -f docker-compose.portainer.yml up -d
```

3. Buka **https://panel.mbayar.my.id** — saat pertama kali dibuka, kamu
   diminta membuat user admin. **Buat password yang kuat.**

> Portainer punya akses penuh ke Docker daemon, yang setara dengan akses
> root di VPS. Buat password yang kuat, dan jangan pernah menjalankannya
> tanpa HTTPS. Kalau tidak sedang dipakai, matikan saja:
> `docker compose stop portainer`

Supaya perintah panjang itu tidak perlu diketik ulang tiap kali:

```bash
echo 'COMPOSE_FILE=docker-compose.yml:docker-compose.portainer.yml' >> .env
```

Setelah itu `docker compose up -d` biasa sudah termasuk Portainer.

### Cockpit — GUI untuk sistem Ubuntu-nya

Untuk hal di luar Docker: pemakaian CPU/RAM, storage, layanan systemd,
log sistem, update paket, dan terminal web.

```bash
apt install -y cockpit
ufw allow 9090/tcp
```

Buka `https://202.155.14.70:9090`, login dengan user Linux kamu (misal
`root`). Browser akan memperingatkan soal sertifikat self-signed — wajar,
karena diakses lewat IP, bukan domain.

### Desktop penuh (GNOME/XFCE + Remote Desktop)

Secara teknis bisa, tapi **tidak disarankan** untuk server produksi:

- Menghabiskan 1–2 GB RAM yang seharusnya untuk aplikasi
- Menambah luas permukaan serangan (butuh port RDP/VNC terbuka)
- Lambat diakses lewat jaringan

Untuk mengelola server web, Portainer + Cockpit sudah menutup hampir
semua kebutuhan tanpa kekurangan di atas.

### Panel hosting (aaPanel, CloudPanel, dsb)

Tidak cocok dipakai bersamaan dengan setup ini. Panel semacam itu memasang
Nginx/Apache, PHP, dan MySQL-nya sendiri di VPS, lalu berebut port 80/443
dengan `nginx-proxy`. Pilih salah satu: Docker (panduan ini) **atau** panel
hosting — jangan keduanya.

---

## Perintah operasional sehari-hari

```bash
cd /opt/poskasir

# Lihat log aplikasi real-time
docker compose logs -f app

# Restart aplikasi (data tetap aman)
docker compose restart app

# Deploy update kode terbaru
git pull
docker compose up -d --build

# Jalankan perintah artisan
docker compose exec app php artisan <perintah>

# Masuk ke shell container
docker compose exec app bash

# Backup database
docker compose exec db mysqldump -u root \
  -p"$(grep '^DB_ROOT_PASSWORD=' .env | cut -d= -f2)" pos_kasir \
  > backup-$(date +%F).sql

# Matikan semua container (data tetap aman di volume)
docker compose down

# Matikan sekaligus HAPUS SEMUA DATA (hanya untuk reset total)
docker compose down -v
```

Backup rutin otomatis tiap malam jam 2 pagi:

```bash
crontab -e
# tambahkan baris berikut:
0 2 * * * cd /opt/poskasir && docker compose exec -T db mysqldump -u root -p"$(grep '^DB_ROOT_PASSWORD=' .env | cut -d= -f2)" pos_kasir > /root/backup-$(date +\%F).sql
```

---

## Troubleshooting

### Log penuh `SQLSTATE[HY000] [2002] Connection refused`

`DB_HOST` di `.env` masih `127.0.0.1`. Harus `db`:

```bash
grep DB_HOST .env          # cek
sed -i 's|^DB_HOST=.*|DB_HOST=db|' .env
docker compose restart app
```

### Container `db` tidak mau start

Biasanya `DB_ROOT_PASSWORD` kosong. Isi dulu, lalu buang volume MySQL yang
terlanjur dibuat dengan kredensial kosong:

```bash
docker compose down
docker volume ls | grep db_data
docker volume rm poskasir_db_data
docker compose up -d
```

> Perintah `docker volume rm` menghapus seluruh isi database. Aman
> dijalankan saat instalasi baru (belum ada data), **tapi jangan pernah**
> dijalankan setelah aplikasi dipakai berjualan.

### Build gagal / berhenti dengan `Killed` atau exit code 137

VPS kehabisan memori. Aktifkan swap (Langkah 4), lalu build ulang.

### Situs menampilkan "502 Bad Gateway"

Container `app` masih menjalankan migrasi saat start pertama. Tunggu ~30
detik lalu refresh. Kalau tetap 502: `docker compose logs -f app`.

### Belum ada SSL / browser bilang "Not Secure"

- Pastikan DNS sudah benar: `dig +short www.mbayar.my.id`
- Cek log: `docker compose logs -f acme-companion`
- Pastikan port 80 & 443 terbuka: `ufw status` — dan cek juga firewall
  dari panel penyedia VPS, yang terpisah dari `ufw`
- Let's Encrypt membatasi 5 kegagalan per jam untuk domain yang sama.
  Kalau kena limit, tunggu satu jam sebelum mencoba lagi.

### Lupa password akun admin

```bash
docker compose exec app php artisan tinker
>>> $u = App\Models\User::where('email', 'admin@poskasir.test')->first();
>>> $u->password = bcrypt('password-baru');
>>> $u->save();
```

### Cek pemakaian resource

```bash
docker stats          # per container, real-time
free -h               # RAM & swap
df -h                 # kapasitas disk
```
