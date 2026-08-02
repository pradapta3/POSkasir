@php $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.'); @endphp

<div class="flex h-screen bg-slate-100">
    @include('partials.sidebar')

    <main class="flex-1 overflow-y-auto p-6">
        <div class="mx-auto max-w-2xl">
            <h1 class="text-2xl font-bold text-slate-900">Cetak Label Barcode</h1>
            <p class="mt-1 text-sm text-slate-500">Pilih produk dan jumlah label, lalu cetak ke stiker label atau kertas biasa.</p>

            <div class="mt-6 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <div class="relative">
                    <label class="text-sm font-medium text-slate-600">Cari Produk</label>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="productSearch"
                        placeholder="Cari nama, SKU, atau barcode produk..."
                        class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500"
                    >
                    @if ($this->productResults->isNotEmpty())
                        <div class="absolute z-10 mt-1 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                            @foreach ($this->productResults as $product)
                                <button
                                    type="button"
                                    wire:click="addItem({{ $product->id }})"
                                    class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-rose-50"
                                >
                                    <span class="text-slate-700">{{ $product->name }}</span>
                                    <span class="text-xs text-slate-400">{{ $product->sku }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
                @error('items') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror

                @if (! empty($items))
                    <div class="mt-4 overflow-hidden rounded-lg border border-slate-200">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-slate-500">
                                <tr>
                                    <th class="px-3 py-2 font-medium">Produk</th>
                                    <th class="px-3 py-2 font-medium">Kode</th>
                                    <th class="px-3 py-2 font-medium">Jumlah Label</th>
                                    <th class="px-3 py-2 font-medium"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($items as $key => $item)
                                    <tr wire:key="label-item-{{ $key }}">
                                        <td class="px-3 py-2 font-medium text-slate-800">{{ $item['name'] }}</td>
                                        <td class="px-3 py-2 text-slate-500">{{ $item['barcode'] ?: $item['sku'] }}</td>
                                        <td class="px-3 py-2">
                                            <input type="number" min="1" wire:model="items.{{ $key }}.qty" class="w-20 rounded-lg border-slate-300 text-sm focus:border-rose-500 focus:ring-rose-500">
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <button type="button" wire:click="removeItem('{{ $key }}')" class="text-xs font-medium text-slate-400 hover:text-red-600">Hapus</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex items-center justify-between">
                        <p class="text-sm text-slate-500">Total <span class="font-semibold text-slate-800">{{ $this->totalLabels }}</span> label akan dicetak.</p>
                        <button wire:click="printLabels" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-rose-600/25 hover:bg-rose-700">
                            Cetak Label
                        </button>
                    </div>
                @else
                    <div class="mt-6 flex flex-col items-center justify-center py-8 text-center">
                        <x-icon name="tag" class="h-10 w-10 text-slate-300" />
                        <p class="mt-2 text-sm text-slate-400">Cari dan pilih produk di atas untuk mulai.</p>
                    </div>
                @endif
            </div>

            <p class="mt-3 text-xs text-slate-400">
                Produk tanpa barcode dari pabrik akan dicetak menggunakan SKU-nya sendiri sebagai kode barcode — tempel label ini di kemasan lalu pindai seperti biasa di kasir.
            </p>
        </div>
    </main>
</div>
