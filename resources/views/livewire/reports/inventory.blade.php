@php $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.'); @endphp

<div class="flex h-screen bg-slate-100">
    @include('partials.sidebar')

    <main class="flex-1 overflow-y-auto p-6">
        <div class="mx-auto max-w-5xl">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Laporan Inventaris</h1>
                <p class="mt-0.5 text-sm text-slate-500">Nilai stok yang tersedia saat ini, dihitung dari harga modal.</p>
            </div>

            {{-- KPI cards --}}
            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                    <div class="flex items-center gap-2 text-sm text-slate-500">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-100">
                            <x-icon name="currency" class="h-4 w-4 text-rose-600" />
                        </span>
                        Total Nilai Stok
                    </div>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ $rp($this->totalValue) }}</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                    <div class="flex items-center gap-2 text-sm text-slate-500">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-sky-100">
                            <x-icon name="cube" class="h-4 w-4 text-sky-600" />
                        </span>
                        Total Unit
                    </div>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($this->totalUnits) }}</p>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                {{-- Valuation by outlet --}}
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                    <h2 class="text-sm font-semibold text-slate-700">Nilai Stok per Outlet</h2>
                    <table class="mt-4 w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-500">
                                <th class="pb-2 font-medium">Outlet</th>
                                <th class="pb-2 font-medium">Unit</th>
                                <th class="pb-2 font-medium">Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->valuationByOutlet as $row)
                                <tr class="border-b border-slate-100">
                                    <td class="py-2 text-slate-800">{{ $row->outlet_name }}</td>
                                    <td class="py-2 text-slate-600">{{ number_format($row->total_units) }}</td>
                                    <td class="py-2 font-medium text-slate-700">{{ $rp($row->total_value) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="py-8 text-center text-sm text-slate-400">Belum ada stok produk aktif.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Low stock --}}
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                    <h2 class="text-sm font-semibold text-slate-700">Peringatan Stok Menipis</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($this->lowStockProducts as $stock)
                            <div class="flex items-center justify-between text-sm">
                                <div>
                                    <span class="text-slate-700">{{ $stock->product_name }}</span>
                                    <span class="block text-xs text-slate-400">{{ $stock->outlet_name }}</span>
                                </div>
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">
                                    sisa {{ $stock->quantity }}
                                </span>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <x-icon name="check-circle" class="h-10 w-10 text-emerald-300" />
                                <p class="mt-2 text-sm text-slate-400">Semua stok dalam kondisi aman.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Top value products --}}
            <div class="mt-6 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <h2 class="text-sm font-semibold text-slate-700">Produk dengan Nilai Stok Tertinggi</h2>
                <table class="mt-4 w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-500">
                            <th class="pb-2 font-medium">Produk</th>
                            <th class="pb-2 font-medium">Stok</th>
                            <th class="pb-2 font-medium">Harga Modal</th>
                            <th class="pb-2 font-medium">Total Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->topValueProducts as $product)
                            <tr class="border-b border-slate-100">
                                <td class="py-2 text-slate-800">
                                    {{ $product->product_name }}
                                    <span class="block text-xs text-slate-400">{{ $product->product_sku }}</span>
                                </td>
                                <td class="py-2 text-slate-600">{{ number_format($product->quantity) }}</td>
                                <td class="py-2 text-slate-600">{{ $rp($product->cost_price) }}</td>
                                <td class="py-2 font-medium text-slate-700">{{ $rp($product->total_value) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-8 text-center text-sm text-slate-400">Belum ada stok produk aktif.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
