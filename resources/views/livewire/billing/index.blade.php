@php
    $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
    $company = $this->company;
    $expiresAt = $company->accessExpiresAt();
    $daysLeft = $expiresAt ? now()->diffInDays($expiresAt, false) : null;
@endphp

<div class="flex h-screen bg-slate-100">
    @include('partials.sidebar')

    <main class="flex-1 overflow-y-auto p-6">
        {{-- Toast --}}
        <div
            x-data="{ show: false }"
            x-on:request-submitted.window="show = true; setTimeout(() => show = false, 5000)"
            x-show="show"
            x-transition
            style="display: none;"
            class="fixed top-4 right-4 z-50 flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-medium text-white shadow-lg shadow-emerald-600/20"
        >
            <x-icon name="check-circle" class="h-5 w-5" />
            <span>Permintaan konfirmasi pembayaran terkirim. Admin Platform akan meninjaunya.</span>
        </div>

        <div class="mx-auto max-w-3xl">
            <h1 class="text-2xl font-bold text-slate-900">Langganan Saya</h1>
            <p class="mt-1 text-sm text-slate-500">Status paket dan masa aktif toko kamu.</p>

            {{-- Current status --}}
            <div class="mt-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-sm text-slate-500">Paket saat ini</p>
                        <p class="mt-0.5 text-xl font-bold text-slate-900">{{ $company->subscriptionPlan?->name ?? 'Trial' }}</p>
                    </div>
                    <div class="text-right">
                        @if (! $expiresAt)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Aktif tanpa batas waktu
                            </span>
                        @elseif ($daysLeft < 0)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span> Kedaluwarsa {{ $expiresAt->format('d M Y') }}
                            </span>
                        @elseif ($daysLeft <= 3)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Berakhir dalam {{ $daysLeft }} hari
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Aktif sampai {{ $expiresAt->format('d M Y') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Plan picker --}}
            <div class="mt-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                <h2 class="text-sm font-semibold text-slate-700">Perpanjang atau Ganti Paket</h2>

                @if ($this->hasPendingRequest)
                    <div class="mt-3 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-700 ring-1 ring-amber-200">
                        Ada permintaan konfirmasi pembayaran yang masih menunggu ditinjau Admin Platform. Kamu tetap bisa mengajukan permintaan baru jika perlu.
                    </div>
                @endif

                @if ($this->plans->isEmpty())
                    <p class="mt-3 text-sm text-slate-400">Belum ada paket langganan yang tersedia. Hubungi tim kami.</p>
                @else
                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-{{ min(3, $this->plans->count()) }}">
                        @foreach ($this->plans as $plan)
                            <button
                                type="button"
                                wire:click="selectPlan({{ $plan->id }})"
                                class="rounded-xl border-2 p-4 text-left transition {{ $selectedPlanId === $plan->id ? 'border-rose-600 bg-rose-50' : 'border-slate-200 hover:border-rose-300' }}"
                            >
                                <p class="font-semibold text-slate-800">{{ $plan->name }}</p>
                                <p class="mt-1 text-lg font-bold text-slate-900">{{ $rp($plan->price_per_month) }}<span class="text-xs font-normal text-slate-400">/bulan</span></p>
                                <p class="mt-2 text-xs text-slate-500">
                                    {{ $plan->max_outlets ? $plan->max_outlets.' outlet' : 'Outlet tanpa batas' }}
                                    &middot;
                                    {{ $plan->max_users ? $plan->max_users.' pengguna' : 'Pengguna tanpa batas' }}
                                </p>
                            </button>
                        @endforeach
                    </div>

                    <div class="mt-4 flex items-end gap-4">
                        <div>
                            <label class="text-sm font-medium text-slate-600">Jumlah Bulan</label>
                            <div class="mt-1 flex gap-2">
                                @foreach ([1, 3, 6, 12] as $option)
                                    <button
                                        type="button"
                                        wire:click="$set('months', {{ $option }})"
                                        class="rounded-lg border px-3 py-1.5 text-sm font-medium transition {{ $months === $option ? 'border-rose-600 bg-rose-50 text-rose-700' : 'border-slate-300 text-slate-600 hover:bg-slate-50' }}"
                                    >{{ $option }} bln</button>
                                @endforeach
                            </div>
                        </div>
                        <div class="ml-auto text-right">
                            <p class="text-xs text-slate-500">Total</p>
                            <p class="text-xl font-bold text-slate-900">{{ $rp($this->totalAmount) }}</p>
                        </div>
                    </div>

                    <button
                        wire:click="openRequestModal"
                        class="mt-4 w-full rounded-lg bg-rose-600 py-2.5 font-bold text-white shadow-md shadow-rose-600/25 transition hover:bg-rose-700 sm:w-auto sm:px-6"
                    >
                        Lanjutkan Pembayaran
                    </button>
                @endif
            </div>

            {{-- History --}}
            @if ($this->requestHistory->isNotEmpty())
                <div class="mt-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <h2 class="text-sm font-semibold text-slate-700">Riwayat Permintaan</h2>
                    <div class="mt-3 divide-y divide-slate-100">
                        @foreach ($this->requestHistory as $request)
                            @php
                                $badge = match ($request->status->value) {
                                    'pending' => 'bg-amber-100 text-amber-700',
                                    'confirmed' => 'bg-emerald-100 text-emerald-700',
                                    default => 'bg-red-100 text-red-700',
                                };
                            @endphp
                            <div class="flex items-center justify-between py-3 text-sm">
                                <div>
                                    <p class="font-medium text-slate-800">{{ $request->plan->name }} &middot; {{ $request->months }} bulan</p>
                                    <p class="text-xs text-slate-400">{{ $request->created_at->format('d M Y, H:i') }} &middot; {{ $rp($request->amount) }}</p>
                                    @if ($request->status->value === 'rejected' && $request->rejection_reason)
                                        <p class="mt-1 text-xs text-red-600">{{ $request->rejection_reason }}</p>
                                    @endif
                                </div>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $badge }}">{{ $request->status->label() }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </main>

    @if ($showRequestModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold text-slate-900">Konfirmasi Pembayaran</h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $this->selectedPlan?->name }} &middot; {{ $months }} bulan &middot; <span class="font-semibold text-slate-800">{{ $rp($this->totalAmount) }}</span>
                </p>

                <div class="mt-4 rounded-lg bg-slate-50 p-4 text-sm text-slate-600 ring-1 ring-slate-200">
                    <p class="font-medium text-slate-700">Transfer ke:</p>
                    {{--
                        Placeholder bank details — no live payment gateway is
                        wired up yet (see Billing\Index docblock). Update this
                        with the platform operator's real account before going
                        live, or replace this whole block once a gateway is
                        integrated.
                    --}}
                    <p class="mt-1">Bank BCA — 1234567890</p>
                    <p>a.n. PT POS Kasir Indonesia</p>
                </div>

                <div class="mt-4">
                    <label class="text-sm font-medium text-slate-600">Catatan (opsional)</label>
                    <textarea wire:model="notes" rows="2" placeholder="mis. Sudah transfer jam 14.00 dari BCA" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500"></textarea>
                </div>

                <div class="mt-6 flex gap-2">
                    <button wire:click="$set('showRequestModal', false)" class="flex-1 rounded-lg border border-slate-300 py-2 font-medium text-slate-600 hover:bg-slate-50">
                        Batal
                    </button>
                    <button wire:click="submitRequest" class="flex-1 rounded-lg bg-rose-600 py-2 font-bold text-white hover:bg-rose-700">
                        Saya Sudah Transfer
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
