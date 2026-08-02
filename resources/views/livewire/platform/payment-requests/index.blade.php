@php $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.'); @endphp

<div class="min-h-screen">
    @include('partials.platform-nav')

    <main class="mx-auto max-w-5xl p-6">
        <h1 class="text-2xl font-bold text-slate-900">Permintaan Pembayaran</h1>
        <p class="mt-1 text-sm text-slate-500">Konfirmasi klaim transfer manual dari toko sebelum langganan mereka diperpanjang.</p>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            @foreach ([
                'pending' => 'Menunggu',
                'confirmed' => 'Dikonfirmasi',
                'rejected' => 'Ditolak',
                'all' => 'Semua',
            ] as $value => $label)
                <button
                    wire:click="$set('statusFilter', '{{ $value }}')"
                    class="rounded-full px-4 py-1.5 text-sm font-medium transition {{ $statusFilter === $value ? 'bg-rose-600 text-white shadow-sm shadow-rose-600/25' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}"
                >
                    {{ $label }}
                    @if ($value === 'pending' && $this->pendingCount > 0)
                        <span class="ml-1 rounded-full px-1.5 text-xs {{ $statusFilter === $value ? 'bg-white/20' : 'bg-amber-100 text-amber-700' }}">{{ $this->pendingCount }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        <div class="mt-4 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-100">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Toko</th>
                        <th class="px-4 py-3 font-medium">Paket</th>
                        <th class="px-4 py-3 font-medium">Jumlah</th>
                        <th class="px-4 py-3 font-medium">Diajukan</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->requests as $request)
                        @php
                            $badge = match ($request->status->value) {
                                'pending' => 'bg-amber-100 text-amber-700',
                                'confirmed' => 'bg-emerald-100 text-emerald-700',
                                default => 'bg-red-100 text-red-700',
                            };
                        @endphp
                        <tr wire:key="request-{{ $request->id }}">
                            <td class="px-4 py-3 font-medium text-slate-800">
                                {{ $request->company->name }}
                                <p class="text-xs font-normal text-slate-400">{{ $request->requestedBy->name }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $request->plan->name }} &middot; {{ $request->months }} bln</td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $rp($request->amount) }}
                                @if ($request->notes)
                                    <p class="text-xs text-slate-400">{{ $request->notes }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $request->created_at->format('d M Y, H:i') }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $badge }}">{{ $request->status->label() }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($request->status->value === 'pending')
                                    <button
                                        wire:click="confirm({{ $request->id }})"
                                        wire:confirm="Konfirmasi pembayaran dari &quot;{{ $request->company->name }}&quot;? Langganan mereka akan langsung diperpanjang."
                                        class="font-medium text-emerald-600 hover:underline"
                                    >Konfirmasi</button>
                                    <button wire:click="startReject({{ $request->id }})" class="ml-3 font-medium text-red-600 hover:underline">Tolak</button>
                                @else
                                    <span class="text-xs text-slate-400">
                                        {{ $request->status->value === 'confirmed' ? 'Oleh '.$request->confirmedBy?->name : $request->rejection_reason }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Tidak ada permintaan pada status ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>

    @if ($rejectingId)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold text-slate-900">Tolak Permintaan</h2>
                <p class="mt-1 text-sm text-slate-500">Alasan ini akan terlihat oleh toko di riwayat permintaan mereka.</p>

                <div class="mt-4">
                    <textarea
                        wire:model="rejectionReason"
                        rows="4"
                        placeholder="mis. Bukti transfer tidak sesuai jumlah tagihan."
                        class="w-full rounded-lg border-slate-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"
                    ></textarea>
                    @error('rejectionReason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="mt-6 flex gap-2">
                    <button wire:click="cancelReject" class="flex-1 rounded-lg border border-slate-300 py-2 font-medium text-slate-600 hover:bg-slate-50">
                        Batal
                    </button>
                    <button wire:click="reject" class="flex-1 rounded-lg bg-red-600 py-2 font-bold text-white hover:bg-red-700">
                        Tolak Permintaan
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
