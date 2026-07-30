@php
    $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
@endphp

<div class="flex h-screen bg-slate-100">
    @include('partials.sidebar')

    <main class="flex-1 overflow-y-auto p-6">
    <div class="mx-auto max-w-6xl">
        <h1 class="text-2xl font-bold text-slate-900">Produk</h1>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex gap-3">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari nama / SKU / barcode..."
                    class="w-64 rounded-lg border-slate-300 text-sm focus:border-rose-500 focus:ring-rose-500"
                >
                <select wire:model.live="categoryFilter" class="rounded-lg border-slate-300 text-sm focus:border-rose-500 focus:ring-rose-500">
                    <option value="">Semua Kategori</option>
                    @foreach ($this->categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <button wire:click="create" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-rose-600/25 hover:bg-rose-700">
                + Tambah Produk
            </button>
        </div>

        <div class="mt-4 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-100">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Produk</th>
                        <th class="px-4 py-3 font-medium">Kategori</th>
                        <th class="px-4 py-3 font-medium">Modal / Jual</th>
                        <th class="px-4 py-3 font-medium">Stok</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->products as $product)
                        <tr wire:key="product-{{ $product->id }}">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                                        @if ($product->image_url)
                                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                                        @else
                                            <div class="flex h-full w-full items-center justify-center">
                                                <x-icon name="photo" class="h-5 w-5 text-slate-300" />
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-800">{{ $product->name }}</p>
                                        <p class="text-xs text-slate-400">
                                            {{ $product->sku }}@if ($product->barcode) &middot; {{ $product->barcode }} @endif
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $product->category?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $rp($product->cost_price) }} / {{ $rp($product->selling_price) }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $totalStock = $product->productStocks->sum('quantity');
                                    $anyLow = $product->productStocks->contains(fn ($s) => $s->isLowStock());
                                @endphp
                                <button
                                    wire:click="openStockModal({{ $product->id }})"
                                    class="rounded-full px-2 py-0.5 text-xs font-semibold hover:opacity-80 {{ $anyLow ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600' }}"
                                >
                                    {{ $totalStock }} {{ $product->unit }}
                                    @if ($this->outlets->count() > 1)
                                        <span class="text-[10px] opacity-70">({{ $product->productStocks->count() }} outlet)</span>
                                    @endif
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-semibold {{ $product->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $product->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button wire:click="edit({{ $product->id }})" class="font-medium text-rose-600 hover:underline">Ubah</button>
                                <button
                                    wire:click="delete({{ $product->id }})"
                                    wire:confirm="Hapus {{ $product->name }}? Produk tidak benar-benar hilang dari database, tapi tidak akan tampil lagi di aplikasi."
                                    class="ml-3 text-red-600 hover:underline"
                                >Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Produk tidak ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $this->products->links() }}</div>
    </div>
    </main>

    {{-- Create/edit modal --}}
    @if ($showFormModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4">
            <div class="my-8 w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold text-slate-900">{{ $editingId ? 'Ubah Produk' : 'Tambah Produk' }}</h2>

                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="text-sm font-medium text-slate-600">Nama</label>
                        <input type="text" wire:model="name" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">SKU</label>
                        <input type="text" wire:model="sku" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                        @error('sku') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Barcode</label>
                        <input type="text" wire:model="barcode" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                        @error('barcode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-2">
                        <label class="text-sm font-medium text-slate-600">Kategori</label>
                        <select wire:model="categoryId" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                            <option value="">Tanpa Kategori</option>
                            @foreach ($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Harga Modal</label>
                        <input type="number" step="0.01" wire:model="costPrice" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                        @error('costPrice') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Harga Jual</label>
                        <input type="number" step="0.01" wire:model="sellingPrice" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                        @error('sellingPrice') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Satuan</label>
                        <input type="text" wire:model="unit" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Batas Stok Menipis</label>
                        <input type="number" wire:model="lowStockThreshold" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                    </div>

                    @if (! $editingId)
                        <div class="col-span-2">
                            <label class="text-sm font-medium text-slate-600">Stok Awal</label>
                            <input type="number" wire:model="initialStock" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                            @error('initialStock') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <div class="col-span-2 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">
                            Jumlah stok tidak bisa diubah di sini — gunakan badge stok pada daftar produk untuk mencatat penyesuaian yang benar.
                        </div>
                    @endif

                    <div class="col-span-2">
                        <label class="text-sm font-medium text-slate-600">Gambar</label>
                        <div class="mt-1 flex items-center gap-3">
                            <div class="h-16 w-16 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                                @if ($image)
                                    <img src="{{ $image->temporaryUrl() }}" alt="Pratinjau" class="h-full w-full object-cover">
                                @elseif ($editingImageUrl)
                                    <img src="{{ $editingImageUrl }}" alt="Gambar saat ini" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full w-full items-center justify-center">
                                        <x-icon name="photo" class="h-6 w-6 text-slate-300" />
                                    </div>
                                @endif
                            </div>
                            <input type="file" wire:model="image" accept="image/*" class="w-full text-sm">
                        </div>
                        @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        <div wire:loading wire:target="image" class="mt-1 text-xs text-slate-400">Mengunggah&hellip;</div>
                    </div>

                    <div class="col-span-2">
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" wire:model="isActive" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                            Aktif (tampil di kasir)
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex gap-2">
                    <button wire:click="$set('showFormModal', false)" class="flex-1 rounded-lg border border-slate-300 py-2 font-medium text-slate-600 hover:bg-slate-50">
                        Batal
                    </button>
                    <button wire:click="save" wire:loading.attr="disabled" wire:target="save" class="flex-1 rounded-lg bg-rose-600 py-2 font-bold text-white hover:bg-rose-700 disabled:opacity-60">
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Stock adjustment modal --}}
    @if ($showStockModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold text-slate-900">Sesuaikan Stok</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $stockProductName }}</p>

                @if ($this->outlets->count() > 1)
                    <label class="mt-4 block text-sm font-medium text-slate-600">Outlet</label>
                    <select wire:model.live="stockOutletId" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                        @foreach ($this->outlets as $outlet)
                            <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                @endif

                <p class="mt-3 text-sm text-slate-500">
                    Stok saat ini: <span class="font-semibold text-slate-800">{{ $this->stockRows->get($stockOutletId)?->quantity ?? 0 }}</span>
                </p>
                <p class="mt-1 text-xs text-slate-400">Masukkan angka positif untuk menambah stok, negatif untuk mengurangi.</p>

                <label class="mt-4 block text-sm font-medium text-slate-600">Jumlah Perubahan</label>
                <input type="number" wire:model="stockDelta" class="mt-1 w-full rounded-lg border-slate-300 text-lg focus:border-rose-500 focus:ring-rose-500">
                @error('stockDelta') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                @error('stockOutletId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                <label class="mt-3 block text-sm font-medium text-slate-600">Alasan (opsional)</label>
                <input type="text" wire:model="stockNotes" placeholder="mis. Restock dari supplier, barang rusak" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">

                <div class="mt-6 flex gap-2">
                    <button wire:click="$set('showStockModal', false)" class="flex-1 rounded-lg border border-slate-300 py-2 font-medium text-slate-600 hover:bg-slate-50">
                        Batal
                    </button>
                    <button wire:click="adjustStock" class="flex-1 rounded-lg bg-rose-600 py-2 font-bold text-white hover:bg-rose-700">
                        Terapkan
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
