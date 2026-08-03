# POS Kasir

Aplikasi kasir (POS) multi-tenant untuk toko dan UMKM — dibangun dengan
Laravel 12, Livewire 3, Alpine.js, dan Tailwind CSS ("TALL stack"). Satu
instalasi bisa melayani banyak toko sekaligus (multi-tenant SaaS): setiap
toko punya data, outlet, produk, staf, dan langganannya sendiri yang
terpisah total dari toko lain.

## Fitur Utama

- **Kasir (POS Terminal)** — keranjang, diskon, pajak, pembayaran Tunai &
  QRIS statis, cetak struk, tahan/lanjutkan transaksi, multi-outlet.
- **Multi-tenant SaaS** — pendaftaran toko mandiri (`/register`), disetujui
  oleh Admin Platform sebelum bisa beroperasi, dengan masa trial.
- **Manajemen Produk & Stok** — kategori, produk, stok per-outlet, cetak
  label barcode, riwayat pergerakan stok yang bisa diaudit.
- **Supplier & Pembelian** — catat barang masuk dari supplier, stok dan
  harga modal produk ter-update otomatis.
- **Member & Loyalitas** — program poin yang bisa dikonfigurasi (nilai
  tukar poin, minimum penukaran, rasio perolehan poin).
- **Langganan (Billing)** — paket langganan dengan batas outlet/pengguna,
  pembayaran manual (transfer bank) yang dikonfirmasi Admin Platform.
- **Laporan** — dasbor penjualan, kinerja per kategori/kasir/metode
  pembayaran, perbandingan antar outlet, valuasi stok, ekspor Excel.
- **PWA** — bisa di-install ke layar utama, dan tetap memberi tahu kasir
  dengan jelas kalau koneksi internet terputus (bukan gagal diam-diam).

## Kebutuhan Sistem

- PHP 8.2 atau lebih baru, dengan ekstensi `pdo_mysql`, `gd`, `mbstring`,
  `fileinfo`, `intl`
- Composer
- MySQL 5.7+/MariaDB 10.4+ (atau kompatibel)
- Node.js 18+ & npm — opsional, hanya diperlukan untuk membangun aset
  Tailwind lewat Vite (lihat catatan di bagian bawah kalau tidak tersedia)
- Web server (Apache/Nginx, atau cukup `php artisan serve` untuk lokal)

## Instalasi

1. **Clone repo dan install dependency PHP**

   ```bash
   git clone https://github.com/pradapta3/POSkasir.git pos-kasir
   cd pos-kasir
   composer install
   ```

2. **Siapkan file environment**

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Buat database MySQL**, lalu isi kredensialnya di `.env`:

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=pos_kasir
   DB_USERNAME=root
   DB_PASSWORD=
   ```

4. **Jalankan migrasi + seeder**

   ```bash
   php artisan migrate --seed
   php artisan storage:link
   ```

   Ini membuat semua tabel, 4 role dasar (Superadmin, Manajer Toko, Kasir,
   Admin Platform), 3 paket langganan awal (Basic/Pro/Enterprise), satu
   toko contoh ("Toko Saya") beserta outlet dan akun login-nya (lihat
   [Akun Awal](#akun-awal-setelah-seeding) di bawah). `storage:link` wajib
   dijalankan agar gambar produk/logo yang di-upload bisa diakses.

5. **Build aset front-end** (opsional tapi direkomendasikan):

   ```bash
   npm install
   npm run build
   ```

   > Kalau Node.js tidak tersedia di server kamu, aplikasi tetap bisa
   > jalan tanpa langkah ini — ganti saja `@vite(...)` di tiga file
   > `resources/views/layouts/{pos,guest,platform}.blade.php` dengan
   > `<script src="https://cdn.tailwindcss.com"></script>`. Ini cara yang
   > dipakai selama pengembangan aplikasi ini di mesin tanpa Node — cukup
   > untuk development/demo, tapi untuk produksi tetap disarankan pakai
   > `npm run build` yang sebenarnya.

6. **Jalankan aplikasi**

   Kalau Node.js tersedia, satu perintah ini menjalankan server, queue
   worker, log viewer, dan Vite sekaligus:

   ```bash
   composer run dev
   ```

   Tanpa Node (memakai CDN Tailwind seperti catatan di atas), cukup:

   ```bash
   php artisan serve
   ```

   dan jalankan `php artisan queue:work` di terminal terpisah kalau ingin
   mengaktifkan notifikasi WhatsApp (lihat [Konfigurasi
   Opsional](#konfigurasi-opsional)). Atau jalankan lewat Apache/Nginx
   (arahkan document root ke folder `public/`).

## Akun Awal (setelah seeding)

| Email | Password | Peran | Keterangan |
|---|---|---|---|
| `admin@poskasir.test` | `password` | Superadmin | Sudah aktif, siap dipakai — pemilik toko contoh "Toko Saya" |
| `platform@poskasir.test` | `password` | Admin Platform | Operator SaaS — menyetujui pendaftaran toko baru, mengelola paket & pembayaran langganan |

**Ganti kedua password ini segera** kalau aplikasi akan dipakai sungguhan,
bukan hanya untuk development lokal.

## Langkah Pertama Sampai Bisa Akses Aplikasi

### Jalur cepat — pakai akun contoh yang sudah disetujui

1. Buka aplikasi di browser, kamu akan diarahkan ke `/login`.
2. Masuk dengan `admin@poskasir.test` / `password`.
3. Kamu langsung masuk ke `/pos` (Kasir). Buka menu **Admin → Kategori**
   untuk menambah minimal satu kategori, lalu **Admin → Produk** untuk
   menambah produk beserta stok awalnya.
4. Kembali ke Kasir, klik **Mulai Shift** (isi modal awal kas), lalu mulai
   tambahkan produk ke keranjang dan coba **Bayar**.

### Jalur lengkap — mendaftarkan toko baru sendiri

Ini alur yang akan dipakai pelanggan sungguhan saat mendaftar:

1. Buka `/register`, isi nama toko, nama pemilik, email, dan password.
   Sistem otomatis membuat perusahaan (status **Menunggu**, masa trial 14
   hari), satu outlet ("Outlet Utama"), dan akun Superadmin — lalu
   langsung login-kan pemiliknya.
2. Pemilik akan melihat halaman **Menunggu Persetujuan** — toko belum bisa
   dipakai sampai disetujui oleh Admin Platform.
3. Login sebagai `platform@poskasir.test`, buka **Platform → Toko**
   (`/platform/companies`), cari toko yang baru daftar, klik **Setujui**.
4. Login kembali sebagai pemilik toko tadi — sekarang bisa masuk ke
   `/pos` seperti alur cepat di atas (tambah kategori/produk, buka shift,
   mulai jualan).
5. (Opsional) Verifikasi email: link verifikasi dikirim lewat mailer yang
   dikonfigurasi di `.env` (`MAIL_MAILER`). Default `MAIL_MAILER=log`
   menulis email ke `storage/logs/laravel.log`, jadi untuk mencobanya
   secara lokal, buka file log itu dan cari link verifikasinya. Akun
   tetap bisa dipakai sebelum verifikasi — hanya muncul banner pengingat.

## Konfigurasi Opsional

- **QRIS statis** — Superadmin toko upload gambar QRIS lewat
  **Admin → Pengaturan**; kasir tinggal menunjukkan QR itu ke pembeli dan
  konfirmasi manual di layar. Tidak ada payment gateway yang tersambung.
- **Program loyalitas** — aktifkan dan atur nilai tukar poin di
  **Admin → Pengaturan**.
- **Notifikasi WhatsApp** — isi `FONNTE_TOKEN` di `.env` (dari
  [fonnte.com](https://fonnte.com) setelah menyambungkan perangkat
  WhatsApp), set `QUEUE_CONNECTION=database` di `.env`, lalu jalankan
  `php artisan queue:work` (atau pakai `composer run dev` yang sudah
  menjalankannya otomatis). Struk akan terkirim ke nomor pembeli yang
  diisi saat checkout.
- **Langganan (Billing)** — Admin Platform mengatur paket di
  **Platform → Paket** (`/platform/plans`); pemilik toko mengajukan
  perpanjangan/upgrade di **Langganan Saya**, lalu Admin Platform
  mengonfirmasinya secara manual di **Platform → Pembayaran** setelah
  transfer diverifikasi.

## Arsitektur Singkat

- **Multi-tenancy**: setiap tabel milik sebuah toko punya kolom
  `company_id`, otomatis di-scope lewat `CompanyScope` (global scope) yang
  dipasang lewat trait `BelongsToCompany` — hampir semua model tidak perlu
  filter manual per-company.
- **Outlet**: satu toko bisa punya banyak outlet/cabang; stok, transaksi,
  dan laporan semuanya sadar-outlet (`outlet_id`).
- **Peran (role)**: Kasir → Manajer Toko → Superadmin (dalam satu toko),
  plus Admin Platform (lintas-toko, operator SaaS).
- Struktur folder mengikuti konvensi Laravel standar; logika bisnis yang
  menyentuh database (checkout, penyesuaian stok, poin loyalitas,
  pembelian) dipisah ke `app/Services/`, bukan ditulis langsung di
  komponen Livewire.

## Deploy ke VPS (Docker)

Untuk hosting produksi di VPS (Ubuntu + Docker, lengkap dengan SSL
otomatis), ikuti langkah demi langkah di [`DEPLOY.md`](DEPLOY.md).

## Kontribusi & Lisensi

Proyek internal — belum ada panduan kontribusi publik. Dibangun di atas
framework [Laravel](https://laravel.com) yang berlisensi MIT.
