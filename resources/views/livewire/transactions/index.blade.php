@php
    $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
@endphp

<div class="min-h-screen bg-slate-100">
    <header class="flex items-center justify-between border-b border-slate-200 bg-white px-6 py-3">
        <div class="flex items-center gap-2">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-600 text-sm font-bold text-white">P</span>
            <h1 class="text-lg font-bold text-slate-900">Riwayat Transaksi</h1>
        </div>
        @include('partials.admin-nav')
    </header>

    <div class="mx-auto max-w-5xl p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari nomor invoice..."
                class="w-64 rounded-lg border-slate-300 text-sm focus:border-rose-500 focus:ring-rose-500"
            >
            <div class="flex rounded-lg border border-slate-300 bg-white p-1">
                @foreach (['today' => 'Hari Ini', 'week' => 'Minggu Ini', 'month' => 'Bulan Ini', 'all' => 'Semua'] as $value => $label)
                    <button
                        wire:click="$set('range', '{{ $value }}')"
                        class="rounded-md px-3 py-1.5 text-sm font-medium transition {{ $range === $value ? 'bg-rose-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}"
                    >{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <div class="mt-4 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-100">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Invoice</th>
                        <th class="px-4 py-3 font-medium">Tanggal</th>
                        <th class="px-4 py-3 font-medium">Kasir</th>
                        <th class="px-4 py-3 font-medium">Total</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->transactions as $transaction)
                        <tr wire:key="transaction-{{ $transaction->id }}">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $transaction->invoice_number }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $transaction->user->name }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $rp($transaction->grand_total) }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $isPaid = $transaction->payment_status->value === 'paid';
                                @endphp
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-semibold {{ $isPaid ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $isPaid ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                    {{ $transaction->payment_status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a
                                    href="{{ route('pos.receipt', $transaction) }}"
                                    target="_blank"
                                    class="inline-flex items-center gap-1.5 font-medium text-rose-600 hover:underline"
                                >
                                    <x-icon name="receipt" class="h-4 w-4" /> Cetak Struk
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-slate-400">
                                Tidak ada transaksi pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $this->transactions->links() }}</div>
    </div>
</div>
