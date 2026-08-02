@php
    $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
    [$from, $to] = $this->period;
    $maxRevenue = $this->outlets->max('revenue') ?: 1;
@endphp

<div class="flex h-screen bg-slate-100">
    @include('partials.sidebar')

    <main class="flex-1 overflow-y-auto p-6">
        <div class="mx-auto max-w-5xl">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Perbandingan Outlet</h1>
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
                </div>
            </div>

            @if ($this->outlets->count() <= 1)
                <div class="mt-6 flex flex-col items-center justify-center rounded-xl bg-white p-12 text-center shadow-sm ring-1 ring-slate-100">
                    <x-icon name="storefront" class="h-10 w-10 text-slate-300" />
                    <p class="mt-3 text-sm text-slate-500">Perbandingan baru berguna kalau toko kamu punya lebih dari satu outlet.</p>
                    <a href="{{ route('admin.outlets') }}" wire:navigate class="mt-3 text-sm font-medium text-rose-600 hover:underline">Tambah outlet baru &rarr;</a>
                </div>
            @else
                <div class="mt-6 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                    <div class="space-y-4">
                        @foreach ($this->outlets as $outlet)
                            <div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-semibold text-slate-800">{{ $outlet->outlet_name }}</span>
                                    <span class="text-slate-500">{{ $rp($outlet->revenue) }} &middot; {{ $outlet->transaction_count }} transaksi</span>
                                </div>
                                <div class="mt-1.5 h-3 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-3 rounded-full bg-gradient-to-r from-rose-400 to-rose-600" style="width: {{ $outlet->revenue > 0 ? max(4, round($outlet->revenue / $maxRevenue * 100)) : 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-100">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-medium">Outlet</th>
                                <th class="px-4 py-3 font-medium">Transaksi</th>
                                <th class="px-4 py-3 font-medium">Pendapatan</th>
                                <th class="px-4 py-3 font-medium">Laba Kotor</th>
                                <th class="px-4 py-3 font-medium">Rata-rata / Transaksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($this->outlets as $outlet)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-800">{{ $outlet->outlet_name }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $outlet->transaction_count }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $rp($outlet->revenue) }}</td>
                                    <td class="px-4 py-3 text-emerald-600">{{ $rp($outlet->profit) }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $rp($outlet->transaction_count > 0 ? $outlet->revenue / $outlet->transaction_count : 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t-2 border-slate-200 bg-slate-50 font-semibold text-slate-800">
                            <tr>
                                <td class="px-4 py-3">Total</td>
                                <td class="px-4 py-3">{{ $this->outlets->sum('transaction_count') }}</td>
                                <td class="px-4 py-3">{{ $rp($this->outlets->sum('revenue')) }}</td>
                                <td class="px-4 py-3 text-emerald-600">{{ $rp($this->outlets->sum('profit')) }}</td>
                                <td class="px-4 py-3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </main>
</div>
