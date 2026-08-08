# Aplikasi Android (Capacitor)

Pembungkus Android untuk POS Kasir. Isinya bukan salinan aplikasi — WebView
menampilkan situs live (`https://www.mbayar.my.id`), sehingga **perubahan
kode web tetap tersebar otomatis lewat deploy biasa, tanpa perlu
menerbitkan ulang APK**.

Yang ditambahkan lapisan native ini cuma satu, tapi itu yang tidak bisa
dilakukan browser: **mencetak struk langsung ke printer termal Bluetooth**
(protokol ESC/POS lewat Bluetooth Classic/SPP — jenis yang dipakai
RPP02N, Panda, Eppos, dan sejenisnya).

## Kapan perlu build ulang APK

| Perubahan | Perlu APK baru? |
|---|---|
| Kode PHP, Blade, CSS, JS aplikasi web | **Tidak** — cukup deploy seperti biasa |
| Isi struk, produk, harga, laporan | **Tidak** |
| Nama aplikasi, ikon, alamat server | Ya |
| Versi plugin printer / kode native | Ya |
| Naik target SDK (tuntutan Play Store, ~setahun sekali) | Ya |

## Kebutuhan build

- **JDK 21**
- **Android SDK** — paling mudah lewat [Android Studio](https://developer.android.com/studio).
  Kalau tanpa Android Studio, pasang command line tools lalu:
  `sdkmanager "platform-tools" "platforms;android-35" "build-tools;35.0.0"`
- **Node.js 20+**
- Variabel `ANDROID_HOME` mengarah ke folder SDK

## Build APK debug (untuk uji coba)

```bash
cd mobile
npm install
npx cap sync android
cd android && ./gradlew assembleDebug
```

APK-nya ada di:
`mobile/android/app/build/outputs/apk/debug/app-debug.apk`

Kirim file itu ke HP (WhatsApp/USB), buka, lalu izinkan "install dari
sumber tidak dikenal" saat diminta.

> APK debug tidak boleh dipakai untuk produksi jangka panjang — kuncinya
> kunci debug bawaan yang sama untuk semua orang, dan tidak bisa diunggah
> ke Play Store.

## Build APK release (untuk dibagikan sungguhan)

1. **Buat keystore** — sekali seumur hidup aplikasi. Simpan baik-baik dan
   backup: kalau file ini hilang, kamu **tidak bisa** lagi menerbitkan
   pembaruan untuk aplikasi yang sama.

   ```bash
   keytool -genkey -v -keystore poskasir.keystore \
     -alias poskasir -keyalg RSA -keysize 2048 -validity 10000
   ```

2. **Daftarkan keystore** di `mobile/android/key.properties` (file ini
   sudah masuk `.gitignore` — jangan pernah di-commit):

   ```properties
   storeFile=/path/absolut/ke/poskasir.keystore
   storePassword=passwordmu
   keyAlias=poskasir
   keyPassword=passwordmu
   ```

3. **Build:**

   ```bash
   cd mobile && npx cap sync android
   cd android && ./gradlew assembleRelease
   ```

   Hasilnya: `mobile/android/app/build/outputs/apk/release/app-release.apk`

Untuk Play Store, pakai `bundleRelease` (menghasilkan `.aab`) dan biaya
pendaftaran developer $25 sekali bayar.

## Mengubah alamat server

Kalau domainmu berubah, sunting `capacitor.config.json`:

```json
"server": { "url": "https://domain-barumu.com" }
```

lalu `npx cap sync android` dan build ulang.

## Cara kerja cetak Bluetooth

Kode cetaknya **tidak** ada di proyek ini — semuanya di
`resources/views/pos/receipt.blade.php` di aplikasi Laravel. Halaman struk
mendeteksi apakah sedang berjalan di dalam aplikasi:

```js
window.Capacitor?.isNativePlatform?.()
```

- **Di browser biasa** — tidak ada yang berubah: dialog cetak terbuka
  otomatis seperti sebelumnya.
- **Di dalam aplikasi** — dialog cetak ditahan, muncul tombol
  **Cetak ke Printer Bluetooth**.

Plugin dipanggil lewat `window.Capacitor.Plugins.CapacitorThermalPrinter`,
bukan lewat `import`, supaya build Vite aplikasi web tidak perlu tahu apa
pun soal Capacitor.

Alamat printer yang berhasil dipakai disimpan di `localStorage`, jadi
transaksi berikutnya langsung mencetak tanpa memilih perangkat lagi. Kalau
printer itu tidak terjangkau, aplikasi otomatis memindai ulang.

### Menyiapkan printer

1. Nyalakan printer, pasangkan lewat **Pengaturan → Bluetooth** Android
   (biasanya PIN `0000` atau `1234`)
2. Buka aplikasi POS Kasir, selesaikan satu transaksi
3. Di halaman struk, ketuk **Cetak ke Printer Bluetooth**
4. Pilih printer dari daftar — sekali saja, seterusnya otomatis

Atur lebar kertas (58mm atau 80mm) di **Admin → Pengaturan**. Struk ESC/POS
menyesuaikan jumlah karakter per baris: 32 untuk 58mm, 48 untuk 80mm.

## Batasan yang perlu diketahui

- **Tetap butuh internet.** Setiap aksi adalah round-trip ke server, jadi
  aplikasi ini tidak membuat kasir bisa berjualan saat koneksi putus.
- **Hanya Android.** Untuk iOS perlu `npx cap add ios`, Mac, dan akun
  Apple Developer ($99/tahun).
- Kalau server tidak terjangkau saat aplikasi dibuka, yang tampil halaman
  kesalahan dari `www/index.html`.
