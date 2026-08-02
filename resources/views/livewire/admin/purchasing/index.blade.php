@php $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.'); @endphp

<div class="flex h-screen bg-slate-100">
    @include('partials.sidebar')

    <main class="flex-1 overflow-y-auto p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Pembelian</h1>
                <p class="mt-1 text-sm text-slate-500">Catat stok yang masuk dari supplier — stok dan harga modal produk diperbarui otomatis.</p>
            </div>
            <button wire:click="create" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-rose-600/25 hover:bg-rose-700">
                + Catat Pembelian
            </button>
        </div>

        <div class="mt-4 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-100">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">No. Pembelian</th>
                        <th class="px-4 py-3 font-medium">Supplier</th>
                        <th class="px-4 py-3 font-medium">Outlet</th>
                        <th class="px-4 py-3 font-medium">Item</th>
                        <th class="px-4 py-3 font-medium">Total</th>
                        <th class="px-4 py-3 font-medium">Tanggal</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->purchaseOrders as $po)
                        <tr wire:key="po-{{ $po->id }}">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $po->po_number }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $po->supplier?->name ?? 'Tanpa supplier' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $po->outlet->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $po->items_count }} produk</td>
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ $rp($po->total_amount) }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $po->created_at->format('d M Y, H:i') }}</td>
                            <td class="px-4 py-3 text-right">
                                <button wire:click="viewDetail({{ $po->id }})" class="font-medium text-rose-600 hover:underline">Lihat Detail</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">Belum ada pembelian tercatat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $this->purchaseOrders->links() }}</div>
    </main>

    {{-- Record purchase modal --}}
    @if ($showFormModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4">
            <div class="my-8 w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold text-slate-900">Catat Pembelian</h2>

                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-slate-600">Outlet Penerima</label>
                        <select wire:model.live="outletId" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                            @foreach ($this->outlets as $outlet)
                                <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                        @error('outletId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Supplier (opsional)</label>
                        <select wire:model="supplierId" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                            <option value="">Tanpa supplier</option>
                            @foreach ($this->suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                        @error('supplierId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                @if ($this->lowStockAlerts->isNotEmpty())
                    <div class="mt-4 rounded-lg bg-amber-50 p-3 ring-1 ring-amber-200">
                        <p class="flex items-center gap-1.5 text-xs font-semibold text-amber-700">
                            <x-icon name="exclamation-triangle" class="h-3.5 w-3.5" /> Stok menipis di outlet ini
                        </p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($this->lowStockAlerts as $stock)
                                <button
                                    type="button"
                                    wire:click="addItem({{ $stock->product->id }})"
                                    class="rounded-full border border-amber-300 bg-white px-3 py-1 text-xs font-medium text-amber-700 hover:bg-amber-100"
                                >
                                    + {{ $stock->product->name }} (sisa {{ $stock->quantity }})
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="relative mt-4">
                    <label class="text-sm font-medium text-slate-600">Tambah Produk</label>
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
                @error('items') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                @if (! empty($items))
                    <div class="mt-4 overflow-hidden rounded-lg border border-slate-200">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-slate-500">
                                <tr>
                                    <th class="px-3 py-2 font-medium">Produk</th>
                                    <th class="px-3 py-2 font-medium">Jumlah</th>
                                    <th class="px-3 py-2 font-medium">Harga Beli</th>
                                    <th class="px-3 py-2 font-medium">Subtotal</th>
                                    <th class="px-3 py-2 font-medium"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($items as $key => $item)
                                    <tr wire:key="item-{{ $key }}">
                                        <td class="px-3 py-2">
                                            <p class="font-medium text-slate-800">{{ $item['name'] }}</p>
                                            <p class="text-xs text-slate-400">{{ $item['sku'] }}</p>
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" min="1" wire:model="items.{{ $key }}.quantity" class="w-20 rounded-lg border-slate-300 text-sm focus:border-rose-500 focus:ring-rose-500">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" min="0" step="100" wire:model="items.{{ $key }}.unit_cost" class="w-28 rounded-lg border-slate-300 text-sm focus:border-rose-500 focus:ring-rose-500">
                                        </td>
                                        <td class="px-3 py-2 font-medium text-slate-700">
                                            {{ $rp(($item['quantity'] ?: 0) * ($item['unit_cost'] ?: 0)) }}
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <button type="button" wire:click="removeItem('{{ $key }}')" class="text-xs font-medium text-slate-400 hover:text-red-600">Hapus</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-2 flex justify-end text-sm">
                        <p class="text-slate-500">Total: <span class="ml-1 text-base font-bold text-slate-900">{{ $rp($this->itemsTotal) }}</span></p>
                    </div>
                @endif

                <div class="mt-4">
                    <label class="text-sm font-medium text-slate-600">Catatan (opsional)</label>
                    <textarea wire:model="notes" rows="2" placeholder="mis. No. faktur supplier, metode pembayaran" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500"></textarea>
                </div>

                <div class="mt-6 flex gap-2">
                    <button wire:click="$set('showFormModal', false)" class="flex-1 rounded-lg border border-slate-300 py-2 font-medium text-slate-600 hover:bg-slate-50">
                        Batal
                    </button>
                    <button wire:click="save" class="flex-1 rounded-lg bg-rose-600 py-2 font-bold text-white hover:bg-rose-700">
                        Simpan Pembelian
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Detail modal (read-only) --}}
    @if ($showDetailModal && $this->viewingPurchaseOrder)
        @php $po = $this->viewingPurchaseOrder; @endphp
        <div class="fixed inset-0 z-40 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4">
            <div class="my-8 w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">{{ $po->po_number }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500">{{ $po->created_at->format('d M Y, H:i') }} &middot; dicatat oleh {{ $po->user->name }}</p>
                    </div>
                    <button wire:click="$set('showDetailModal', false)" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                    <p class="text-slate-500">Supplier <span class="block font-medium text-slate-800">{{ $po->supplier?->name ?? 'Tanpa supplier' }}</span></p>
                    <p class="text-slate-500">Outlet <span class="block font-medium text-slate-800">{{ $po->outlet->name }}</span></p>
                </div>

                @if ($po->notes)
                    <p class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600 ring-1 ring-slate-200">{{ $po->notes }}</p>
                @endif

                <div class="mt-4 overflow-hidden rounded-lg border border-slate-200">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="px-3 py-2 font-medium">Produk</th>
                                <th class="px-3 py-2 font-medium">Jumlah</th>
                                <th class="px-3 py-2 font-medium">Harga</th>
                                <th class="px-3 py-2 font-medium">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($po->items as $item)
                                <tr>
                                    <td class="px-3 py-2">
                                        <p class="font-medium text-slate-800">{{ $item->product_name }}</p>
                                        <p class="text-xs text-slate-400">{{ $item->product_sku }}</p>
                                    </td>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->quantity }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $rp($item->unit_cost) }}</td>
                                    <td class="px-3 py-2 font-medium text-slate-700">{{ $rp($item->subtotal) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 flex justify-end text-sm">
                    <p class="text-slate-500">Total: <span class="ml-1 text-base font-bold text-slate-900">{{ $rp($po->total_amount) }}</span></p>
                </div>

                <button wire:click="$set('showDetailModal', false)" class="mt-4 w-full rounded-lg border border-slate-300 py-2 font-medium text-slate-600 hover:bg-slate-50">
                    Tutup
                </button>
            </div>
        </div>
    @endif
</div>
