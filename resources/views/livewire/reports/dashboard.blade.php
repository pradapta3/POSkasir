@php
    $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
    [$from, $to] = $this->period;
@endphp

<div class="flex h-screen bg-slate-100">
    @include('partials.sidebar')

    <main class="flex-1 overflow-y-auto p-6">
        <div class="mx-auto max-w-6xl">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Dasbor Penjualan</h1>
                    <p class="mt-0.5 text-sm text-slate-500">{{ $from->format('d M Y') }} &ndash; {{ $to->format('d M Y') }}</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex rounded-lg border border-slate-300 bg-white p-1">
                        @foreach (['today' => 'Hari Ini', 'week' => 'Minggu Ini', 'month' => 'Bulan Ini', 'custom' => 'Kustom'] as $value => $label)
                            <button
                                wire:click="setRange('{{ $value }}')"
                                class="rounded-md px-3 py-1.5 text-sm font-medium transition {{ $range === $value ? 'bg-rose-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}"
                            >{{ $label }}</button>
                        @endforeach
                    </div>

                    @if ($range === 'custom')
                        <div class="flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2 py-1">
                            <input type="date" wire:model.live="customFrom" class="rounded-md border-0 p-1 text-sm text-slate-700 focus:ring-0">
                            <span class="text-slate-400">&ndash;</span>
                            <input type="date" wire:model.live="customTo" class="rounded-md border-0 p-1 text-sm text-slate-700 focus:ring-0">
                        </div>
                    @endif

                    <a
                        href="{{ route('reports.export.transactions', ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'outlet_id' => $this->outletId]) }}"
                        class="flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-emerald-600/20 transition hover:bg-emerald-700"
                    >
                        <x-icon name="download" class="h-4 w-4" /> Ekspor ke Excel
                    </a>
                </div>
            </div>

            {{-- KPI cards --}}
            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                    <div class="flex items-center gap-2 text-sm text-slate-500">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-100">
                            <x-icon name="currency" class="h-4 w-4 text-rose-600" />
                        </span>
                        Pendapatan
                    </div>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ $rp($this->summary['revenue']) }}</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                    <div class="flex items-center gap-2 text-sm text-slate-500">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100">
                            <x-icon name="trending-up" class="h-4 w-4 text-emerald-600" />
                        </span>
                        Laba Kotor
                    </div>
                    <p class="mt-2 text-2xl font-bold text-emerald-600">{{ $rp($this->summary['grossProfit']) }}</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                    <div class="flex items-center gap-2 text-sm text-slate-500">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-sky-100">
                            <x-icon name="receipt" class="h-4 w-4 text-sky-600" />
                        </span>
                        Transaksi
                    </div>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ $this->summary['transactionCount'] }}</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                    <div class="flex items-center gap-2 text-sm text-slate-500">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100">
                            <x-icon name="scale" class="h-4 w-4 text-amber-600" />
                        </span>
                        Rata-rata Transaksi
                    </div>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ $rp($this->summary['averageOrderValue']) }}</p>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
                {{-- Daily sales bar chart (pure CSS — no chart JS dependency) --}}
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100 lg:col-span-2">
                    <h2 class="text-sm font-semibold text-slate-700">Penjualan per Hari</h2>
                    @php $maxRevenue = $this->dailySales->max('revenue') ?: 1; @endphp
                    <div class="mt-4 space-y-2">
                        @forelse ($this->dailySales as $day)
                            <div class="flex items-center gap-3">
                                <span class="w-20 shrink-0 text-xs text-slate-500">{{ \Carbon\Carbon::parse($day->date)->format('d M') }}</span>
                                <div class="h-4 flex-1 overflow-hidden rounded bg-slate-100">
                                    <div class="h-4 rounded bg-gradient-to-r from-rose-400 to-rose-600" style="width: {{ max(4, round($day->revenue / $maxRevenue * 100)) }}%"></div>
                                </div>
                                <span class="w-28 shrink-0 text-right text-xs font-medium text-slate-700">{{ $rp($day->revenue) }}</span>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <x-icon name="chart-bar" class="h-10 w-10 text-slate-300" />
                                <p class="mt-2 text-sm text-slate-400">Belum ada penjualan lunas pada periode ini.</p>
                            </div>
                        @endforelse
                    </div>
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

            {{-- Top products --}}
            <div class="mt-6 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <h2 class="text-sm font-semibold text-slate-700">Produk Terlaris</h2>
                <table class="mt-4 w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-500">
                            <th class="pb-2 font-medium">Produk</th>
                            <th class="pb-2 font-medium">Jumlah Terjual</th>
                            <th class="pb-2 font-medium">Pendapatan</th>
                            <th class="pb-2 font-medium">Laba Kotor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->topProducts as $product)
                            <tr class="border-b border-slate-100">
                                <td class="py-2 text-slate-800">{{ $product->product_name }}</td>
                                <td class="py-2 text-slate-600">{{ $product->quantity_sold }}</td>
                                <td class="py-2 text-slate-600">{{ $rp($product->revenue) }}</td>
                                <td class="py-2 text-emerald-600">{{ $rp($product->profit) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-8 text-center text-sm text-slate-400">Belum ada penjualan pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
                {{-- Category performance --}}
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100 lg:col-span-2">
                    <h2 class="text-sm font-semibold text-slate-700">Kinerja per Kategori</h2>
                    <table class="mt-4 w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-500">
                                <th class="pb-2 font-medium">Kategori</th>
                                <th class="pb-2 font-medium">Terjual</th>
                                <th class="pb-2 font-medium">Pendapatan</th>
                                <th class="pb-2 font-medium">Laba Kotor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->categoryPerformance as $category)
                                <tr class="border-b border-slate-100">
                                    <td class="py-2 text-slate-800">{{ $category->category_name }}</td>
                                    <td class="py-2 text-slate-600">{{ $category->quantity_sold }}</td>
                                    <td class="py-2 text-slate-600">{{ $rp($category->revenue) }}</td>
                                    <td class="py-2 text-emerald-600">{{ $rp($category->profit) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-8 text-center text-sm text-slate-400">Belum ada penjualan pada periode ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Payment method breakdown --}}
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                    <h2 class="text-sm font-semibold text-slate-700">Metode Pembayaran</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($this->paymentMethodBreakdown as $method)
                            @php
                                $label = \App\Enums\PaymentMethod::tryFrom($method->payment_method)?->label() ?? $method->payment_method;
                                $share = $this->summary['revenue'] > 0 ? round($method->revenue / $this->summary['revenue'] * 100) : 0;
                            @endphp
                            <div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium text-slate-700">{{ $label }}</span>
                                    <span class="text-slate-500">{{ $rp($method->revenue) }} &middot; {{ $method->transaction_count }}x</span>
                                </div>
                                <div class="mt-1 h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-2 rounded-full bg-rose-500" style="width: {{ max(4, $share) }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="py-8 text-center text-sm text-slate-400">Belum ada penjualan pada periode ini.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Cashier performance --}}
            <div class="mt-6 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <h2 class="text-sm font-semibold text-slate-700">Kinerja Kasir</h2>
                <table class="mt-4 w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-500">
                            <th class="pb-2 font-medium">Kasir</th>
                            <th class="pb-2 font-medium">Transaksi</th>
                            <th class="pb-2 font-medium">Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->salesByCashier as $cashier)
                            <tr class="border-b border-slate-100">
                                <td class="py-2 text-slate-800">{{ $cashier->cashier_name }}</td>
                                <td class="py-2 text-slate-600">{{ $cashier->transaction_count }}</td>
                                <td class="py-2 text-slate-600">{{ $rp($cashier->revenue) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-8 text-center text-sm text-slate-400">Belum ada penjualan pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
