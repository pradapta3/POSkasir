<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Katalog contoh bergaya supermarket Indonesia untuk demo dan uji coba.
 *
 * Sengaja TIDAK dipanggil dari DatabaseSeeder — data ini tidak boleh ikut
 * terbuat otomatis saat deploy produksi. Jalankan manual:
 *
 *     php artisan db:seed --class=DummyProductSeeder
 *
 * Aman dijalankan berulang: produk dicocokkan lewat SKU, jadi menjalankan
 * ulang memperbarui data yang ada, bukan menggandakannya. Stok hanya diisi
 * saat baris stok pertama kali dibuat, supaya angka hasil transaksi atau
 * penyesuaian manual tidak tertimpa.
 */
class DummyProductSeeder extends Seeder
{
    /**
     * Warna kartu per kategori — dipakai untuk gambar produk yang
     * dibangkitkan sebagai SVG (lihat buildImage()).
     */
    private const CATALOG = [
        'Sembako' => ['color' => '#b45309', 'items' => [
            ['Beras Pandan Wangi 5kg', 'karung', 68000, 76000],
            ['Beras Setra Ramos 5kg', 'karung', 62000, 70000],
            ['Minyak Goreng Bimoli 1L', 'botol', 16500, 19000],
            ['Minyak Goreng Sania 2L', 'pouch', 32000, 36000],
            ['Gula Pasir Gulaku 1kg', 'bungkus', 15500, 17500],
            ['Tepung Terigu Segitiga Biru 1kg', 'bungkus', 11000, 13000],
            ['Garam Dapur Refina 500g', 'bungkus', 3500, 4500],
            ['Mie Instan Indomie Goreng', 'pcs', 2900, 3500],
            ['Mie Instan Indomie Soto', 'pcs', 2800, 3400],
            ['Mie Sedaap Goreng', 'pcs', 2800, 3400],
        ]],
        'Minuman' => ['color' => '#0369a1', 'items' => [
            ['Aqua Botol 600ml', 'botol', 3200, 4000],
            ['Aqua Galon 19L', 'galon', 20000, 23000],
            ['Le Minerale 600ml', 'botol', 3000, 3800],
            ['Teh Botol Sosro 450ml', 'botol', 4200, 5000],
            ['Coca-Cola 390ml', 'botol', 5000, 6000],
            ['Sprite 390ml', 'botol', 5000, 6000],
            ['Pocari Sweat 500ml', 'botol', 7500, 9000],
            ['Kopi Kapal Api Special 165g', 'bungkus', 12000, 14000],
            ['Teh Celup Sariwangi 25s', 'kotak', 6500, 8000],
            ['Ultra Milk Coklat 250ml', 'kotak', 5500, 6500],
        ]],
        'Makanan Ringan' => ['color' => '#c2410c', 'items' => [
            ['Chitato Sapi Panggang 68g', 'bungkus', 9500, 11500],
            ['Taro Net Seaweed 70g', 'bungkus', 8000, 9500],
            ['Oreo Original 133g', 'bungkus', 8500, 10000],
            ['Kacang Garuda 200g', 'bungkus', 9000, 11000],
            ['Beng Beng 20g', 'pcs', 1800, 2500],
            ['SilverQueen Chunky Bar 65g', 'pcs', 14000, 17000],
            ['Qtela Singkong 60g', 'bungkus', 7000, 8500],
        ]],
        'Bumbu Dapur' => ['color' => '#b91c1c', 'items' => [
            ['Kecap Manis ABC 275ml', 'botol', 12000, 14000],
            ['Saus Sambal ABC 335ml', 'botol', 11000, 13000],
            ['Royco Ayam 230g', 'bungkus', 9500, 11500],
            ['Masako Sapi 250g', 'bungkus', 9000, 11000],
            ['Bawang Merah 250g', 'bungkus', 9000, 11000],
            ['Bawang Putih 250g', 'bungkus', 10000, 12000],
        ]],
        'Susu & Telur' => ['color' => '#0e7490', 'items' => [
            ['Telur Ayam Negeri 1kg', 'kg', 27000, 30000],
            ['Susu UHT Ultra Full Cream 1L', 'kotak', 18000, 21000],
            ['SKM Frisian Flag 370g', 'kaleng', 11000, 13000],
            ['Susu Bubuk Dancow 400g', 'kotak', 42000, 47000],
            ['Keju Kraft Cheddar 170g', 'kotak', 22000, 25500],
            ['Yogurt Cimory 250ml', 'botol', 9000, 11000],
        ]],
        'Roti & Kue' => ['color' => '#a16207', 'items' => [
            ['Roti Tawar Sari Roti 380g', 'bungkus', 15500, 18000],
            ['Roti Sobek Cokelat', 'bungkus', 13000, 15500],
            ['Donat Gula 6pcs', 'kotak', 12000, 15000],
        ]],
        'Perawatan Diri' => ['color' => '#7e22ce', 'items' => [
            ['Sabun Lifebuoy 110g', 'pcs', 4200, 5200],
            ['Shampoo Pantene 170ml', 'botol', 22000, 26000],
            ['Pasta Gigi Pepsodent 190g', 'pcs', 14000, 16500],
            ['Sabun Cuci Muka Ponds 100g', 'pcs', 18000, 21000],
            ['Deodoran Rexona 45ml', 'pcs', 15000, 18000],
        ]],
        'Kebersihan Rumah' => ['color' => '#047857', 'items' => [
            ['Deterjen Rinso 770g', 'bungkus', 18000, 21500],
            ['Sabun Cuci Piring Sunlight 755ml', 'botol', 16000, 19000],
            ['Pewangi Molto 900ml', 'pouch', 17000, 20000],
            ['Pembersih Lantai Wipol 780ml', 'botol', 15000, 18000],
            ['Tisu Paseo 250s', 'pak', 13000, 15500],
        ]],
        'Makanan Beku' => ['color' => '#4338ca', 'items' => [
            ['Nugget Fiesta 500g', 'bungkus', 38000, 43000],
            ['Sosis So Nice 500g', 'bungkus', 28000, 32000],
            ['Bakso Sapi Kanzler 300g', 'bungkus', 32000, 37000],
        ]],
    ];

    public function run(): void
    {
        // CompanyScope tidak aktif di CLI (lihat komentarnya), jadi
        // company_id harus disebut eksplisit di setiap query di bawah.
        $company = Company::where('slug', 'toko-saya')->first() ?? Company::first();

        if (! $company) {
            $this->command?->error('Belum ada toko sama sekali. Jalankan dulu: php artisan db:seed');

            return;
        }

        $outlets = Outlet::where('company_id', $company->id)->get();

        if ($outlets->isEmpty()) {
            $this->command?->error("Toko \"{$company->name}\" belum punya outlet.");

            return;
        }

        $this->command?->info("Mengisi katalog contoh untuk: {$company->name}");

        $productCount = 0;
        $categoryIndex = 0;

        foreach (self::CATALOG as $categoryName => $group) {
            $categoryIndex++;

            $category = Category::firstOrCreate(
                ['company_id' => $company->id, 'slug' => Str::slug($categoryName)],
                ['name' => $categoryName, 'is_active' => true]
            );

            $itemIndex = 0;

            foreach ($group['items'] as [$name, $unit, $cost, $sell]) {
                $itemIndex++;
                $sku = sprintf('DMY-%02d-%03d', $categoryIndex, $itemIndex);

                $product = Product::updateOrCreate(
                    ['company_id' => $company->id, 'sku' => $sku],
                    [
                        'category_id' => $category->id,
                        'name' => $name,
                        'barcode' => $this->buildBarcode($sku),
                        'unit' => $unit,
                        'cost_price' => $cost,
                        'selling_price' => $sell,
                        'image_path' => $this->storeImage($sku, $name, $categoryName, $sell, $group['color']),
                        'is_active' => true,
                    ]
                );

                // Kuantitas hanya diisi saat baris stok pertama kali dibuat.
                // firstOrCreate (bukan updateOrCreate) supaya stok yang sudah
                // berubah karena penjualan tidak dikembalikan ke angka awal
                // setiap kali seeder ini dijalankan ulang.
                foreach ($outlets as $outlet) {
                    ProductStock::firstOrCreate(
                        ['product_id' => $product->id, 'outlet_id' => $outlet->id],
                        ['quantity' => $this->startingStock($sku), 'low_stock_threshold' => 10]
                    );
                }

                $productCount++;
            }

            $this->command?->line("  {$categoryName}: ".count($group['items']).' produk');
        }

        $this->command?->info(
            "Selesai — {$productCount} produk di ".count(self::CATALOG).' kategori, '.
            'stok terisi di '.$outlets->count().' outlet.'
        );
    }

    /**
     * Stok awal 25–150 unit, ditentukan dari SKU supaya angkanya bervariasi
     * antar produk tapi tetap sama setiap kali seeder dijalankan.
     */
    private function startingStock(string $sku): int
    {
        return 25 + (crc32($sku) % 126);
    }

    /**
     * EAN-13 dengan awalan 899 (kode negara Indonesia) dan digit periksa
     * yang benar, supaya barcode-nya bisa dipindai dan divalidasi seperti
     * barcode produk sungguhan.
     */
    private function buildBarcode(string $sku): string
    {
        $body = '899'.substr(str_pad((string) crc32($sku), 9, '0', STR_PAD_LEFT), 0, 9);

        $sum = 0;
        foreach (str_split($body) as $i => $digit) {
            $sum += (int) $digit * ($i % 2 === 0 ? 1 : 3);
        }

        return $body.((10 - $sum % 10) % 10);
    }

    /**
     * Gambar produk dibangkitkan sebagai SVG, bukan diunduh dari internet:
     * server produksi tidak selalu punya akses keluar, dan SVG tetap tajam
     * di ukuran berapa pun sambil hanya memakan ~1 KB per file.
     */
    private function storeImage(string $sku, string $name, string $category, int $price, string $color): string
    {
        $path = 'products/dummy-'.Str::lower($sku).'.svg';

        Storage::disk('public')->put($path, $this->buildImage($name, $category, $price, $color));

        return $path;
    }

    private function buildImage(string $name, string $category, int $price, string $color): string
    {
        $lines = array_slice(explode("\n", wordwrap($name, 18, "\n", true)), 0, 4);
        $startY = 190 - (count($lines) - 1) * 21;

        $nameSvg = '';
        foreach ($lines as $i => $line) {
            $y = $startY + $i * 42;
            $nameSvg .= sprintf(
                '<text x="200" y="%d" text-anchor="middle" font-size="30" font-weight="600" fill="#ffffff">%s</text>',
                $y,
                htmlspecialchars($line, ENT_XML1)
            );
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400" width="400" height="400" '.
            'font-family="Segoe UI, Roboto, Helvetica, Arial, sans-serif">'.
            '<rect width="400" height="400" fill="%s"/>'.
            '<rect y="330" width="400" height="70" fill="#000000" fill-opacity="0.22"/>'.
            '<text x="200" y="58" text-anchor="middle" font-size="19" fill="#ffffff" fill-opacity="0.72" '.
            'letter-spacing="1.5">%s</text>'.
            '%s'.
            '<text x="200" y="375" text-anchor="middle" font-size="34" font-weight="700" fill="#ffffff">%s</text>'.
            '</svg>',
            $color,
            htmlspecialchars(Str::upper($category), ENT_XML1),
            $nameSvg,
            'Rp '.number_format($price, 0, ',', '.')
        );
    }
}
