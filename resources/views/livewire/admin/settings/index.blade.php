<div class="flex h-screen bg-slate-100">
    @include('partials.sidebar')

    <main class="flex-1 overflow-y-auto p-6">
        {{-- Toast --}}
        <div
            x-data="{ show: false }"
            x-on:settings-saved.window="show = true; setTimeout(() => show = false, 3000)"
            x-show="show"
            x-transition
            style="display: none;"
            class="fixed top-4 right-4 z-50 flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-medium text-white shadow-lg shadow-emerald-600/20"
        >
            <x-icon name="check-circle" class="h-5 w-5" />
            <span>Pengaturan disimpan.</span>
        </div>

        <div class="mx-auto max-w-2xl">
            <h1 class="text-2xl font-bold text-slate-900">Pengaturan</h1>
            <p class="mt-1 text-sm text-slate-500">Konfigurasi berlaku untuk seluruh toko.</p>

            {{-- Informasi Toko --}}
            <div class="mt-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-100">
                        <x-icon name="storefront" class="h-4 w-4 text-rose-600" />
                    </span>
                    <h2 class="text-sm font-semibold text-slate-700">Informasi Toko</h2>
                </div>
                <p class="mt-1 text-sm text-slate-500">
                    Tampil di invoice WhatsApp dan struk pelanggan.
                </p>

                <div class="mt-4 flex items-center gap-4">
                    <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-slate-100">
                        @if ($storeLogo)
                            <img src="{{ $storeLogo->temporaryUrl() }}" alt="Pratinjau logo" class="h-full w-full object-cover">
                        @elseif ($currentLogoUrl)
                            <img src="{{ $currentLogoUrl }}" alt="Logo toko" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center">
                                <x-icon name="photo" class="h-6 w-6 text-slate-300" />
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <label class="text-sm font-medium text-slate-600">Logo Toko</label>
                        <input type="file" wire:model="storeLogo" accept="image/*" class="mt-1 w-full text-sm">
                        @error('storeLogo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        <div wire:loading wire:target="storeLogo" class="mt-1 text-xs text-slate-400">Mengunggah&hellip;</div>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="text-sm font-medium text-slate-600">Nama Toko</label>
                    <input type="text" wire:model="storeName" placeholder="mis. Ubi Cilembu Bu Siti" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                    @error('storeName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="mt-4">
                    <label class="text-sm font-medium text-slate-600">Alamat Toko</label>
                    <textarea wire:model="storeAddress" rows="2" placeholder="Jl. Contoh No. 123, Kota" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500"></textarea>
                    @error('storeAddress') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="mt-4 max-w-xs">
                    <label class="text-sm font-medium text-slate-600">Nomor Telepon Toko</label>
                    <input type="text" wire:model="storePhone" placeholder="08xxxxxxxxxx" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                    @error('storePhone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Pembayaran --}}
            <div class="mt-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-100">
                        <x-icon name="wallet" class="h-4 w-4 text-rose-600" />
                    </span>
                    <h2 class="text-sm font-semibold text-slate-700">Pembayaran</h2>
                </div>
                <p class="mt-1 text-sm text-slate-500">
                    Metode bayar di kasir: Tunai dan QRIS. Kode QRIS di bawah ini statis (satu kode untuk semua transaksi) —
                    unggah kode QRIS toko kamu dari aplikasi bank/e-wallet, kasir akan menampilkannya saat pelanggan memilih QRIS.
                </p>

                <div class="mt-4 flex items-center gap-4">
                    <div class="h-24 w-24 shrink-0 overflow-hidden rounded-xl bg-slate-100">
                        @if ($qrisImage)
                            <img src="{{ $qrisImage->temporaryUrl() }}" alt="Pratinjau QRIS" class="h-full w-full object-contain">
                        @elseif ($currentQrisImageUrl)
                            <img src="{{ $currentQrisImageUrl }}" alt="Kode QRIS toko" class="h-full w-full object-contain">
                        @else
                            <div class="flex h-full w-full items-center justify-center">
                                <x-icon name="photo" class="h-6 w-6 text-slate-300" />
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <label class="text-sm font-medium text-slate-600">Kode QRIS Toko</label>
                        <input type="file" wire:model="qrisImage" accept="image/*" class="mt-1 w-full text-sm">
                        @error('qrisImage') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        <div wire:loading wire:target="qrisImage" class="mt-1 text-xs text-slate-400">Mengunggah&hellip;</div>
                    </div>
                </div>
            </div>

            {{-- Program Loyalitas --}}
            <div class="mt-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-100">
                            <x-icon name="star" class="h-4 w-4 text-rose-600" />
                        </span>
                        <h2 class="text-sm font-semibold text-slate-700">Program Loyalitas</h2>
                    </div>
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input type="checkbox" wire:model.live="loyaltyEnabled" class="peer sr-only">
                        <div class="h-6 w-11 rounded-full bg-slate-200 transition peer-checked:bg-rose-600 peer-focus:outline-none after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full"></div>
                    </label>
                </div>
                <p class="mt-1 text-sm text-slate-500">
                    Member mengumpulkan poin dari setiap transaksi dan bisa menukarnya sebagai diskon di transaksi berikutnya.
                    Kelola daftar member di menu <span class="font-medium text-slate-700">Member</span>.
                </p>

                @if ($loyaltyEnabled)
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-sm font-medium text-slate-600">Belanja (Rp) per 1 Poin</label>
                            <input type="number" step="1" min="0" wire:model="loyaltyEarnPerRupiah" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                            @error('loyaltyEarnPerRupiah') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            <p class="mt-1 text-xs text-slate-400">mis. 10.000 berarti member dapat 1 poin tiap kelipatan Rp 10.000 belanja.</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-600">Nilai 1 Poin Saat Ditukar (Rp)</label>
                            <input type="number" step="1" min="0" wire:model="loyaltyRedeemValue" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                            @error('loyaltyRedeemValue') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            <p class="mt-1 text-xs text-slate-400">mis. 100 berarti 50 poin = diskon Rp 5.000.</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-600">Minimum Poin untuk Ditukar</label>
                            <input type="number" step="1" min="0" wire:model="loyaltyMinRedeemPoints" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                            @error('loyaltyMinRedeemPoints') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            <p class="mt-1 text-xs text-slate-400">Isi 0 jika tidak ada minimum.</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Pajak & Struk --}}
            <div class="mt-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-100">
                        <x-icon name="receipt" class="h-4 w-4 text-rose-600" />
                    </span>
                    <h2 class="text-sm font-semibold text-slate-700">Pajak & Struk</h2>
                </div>

                <div class="mt-4 max-w-xs">
                    <label class="text-sm font-medium text-slate-600">Pajak Default (%)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        wire:model="taxPercentage"
                        class="mt-1 w-full rounded-lg border-slate-300 text-lg focus:border-rose-500 focus:ring-rose-500"
                    >
                    @error('taxPercentage')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1.5 text-xs text-slate-400">
                        Otomatis terisi di layar Kasir untuk setiap transaksi baru (kasir tetap bisa mengubahnya per transaksi).
                        Isi 0 jika tokomu belum berstatus PKP dan tidak memungut PPN.
                    </p>
                </div>

                <div class="mt-4">
                    <label class="text-sm font-medium text-slate-600">Catatan Kaki Struk</label>
                    <input type="text" wire:model="receiptFooter" placeholder="mis. Terima kasih sudah berbelanja!" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                    @error('receiptFooter') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="mt-4">
                    <label class="text-sm font-medium text-slate-600">Lebar Kertas Printer Struk</label>
                    <div class="mt-1.5 flex gap-3">
                        @foreach (['58' => '58mm', '80' => '80mm'] as $value => $label)
                            <label class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg border py-2.5 text-sm font-medium transition {{ $receiptPaperWidth === $value ? 'border-rose-600 bg-rose-50 text-rose-700' : 'border-slate-300 text-slate-600 hover:bg-slate-50' }}">
                                <input type="radio" wire:model="receiptPaperWidth" value="{{ $value }}" class="sr-only">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    @error('receiptPaperWidth') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    <p class="mt-1.5 text-xs text-slate-400">Sesuaikan dengan lebar kertas printer thermal kasir kamu.</p>
                </div>
            </div>

            <button
                wire:click="save"
                wire:loading.attr="disabled"
                wire:target="save, storeLogo, qrisImage"
                class="mt-6 rounded-lg bg-rose-600 px-5 py-2.5 text-sm font-bold text-white shadow-md shadow-rose-600/25 transition hover:bg-rose-700 disabled:opacity-60"
            >
                Simpan Pengaturan
            </button>
        </div>
    </main>
</div>
