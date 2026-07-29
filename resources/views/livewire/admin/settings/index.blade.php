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
            <span>✓</span>
            <span>Pengaturan disimpan.</span>
        </div>

        <div class="mx-auto max-w-2xl">
            <h1 class="text-2xl font-bold text-slate-900">Pengaturan</h1>
            <p class="mt-1 text-sm text-slate-500">Konfigurasi berlaku untuk seluruh toko.</p>

            <div class="mt-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                <h2 class="text-sm font-semibold text-slate-700">Pajak Transaksi</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Persentase pajak default yang otomatis terisi di layar Kasir untuk setiap transaksi baru.
                    Kasir tetap bisa mengubahnya per transaksi jika perlu. Kosongkan/isi 0 jika tokomu belum
                    berstatus PKP (Pengusaha Kena Pajak) dan tidak memungut PPN.
                </p>

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
                </div>

                <button
                    wire:click="save"
                    wire:loading.attr="disabled"
                    class="mt-5 rounded-lg bg-rose-600 px-5 py-2.5 text-sm font-bold text-white shadow-md shadow-rose-600/25 transition hover:bg-rose-700 disabled:opacity-60"
                >
                    Simpan Pengaturan
                </button>
            </div>
        </div>
    </main>
</div>
