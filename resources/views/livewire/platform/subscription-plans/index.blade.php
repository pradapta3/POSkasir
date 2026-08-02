@php $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.'); @endphp

<div class="min-h-screen">
    @include('partials.platform-nav')

    <main class="mx-auto max-w-4xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Paket Langganan</h1>
                <p class="mt-1 text-sm text-slate-500">Tiap toko memilih salah satu paket ini saat memperpanjang langganan.</p>
            </div>
            <button wire:click="create" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-rose-600/25 hover:bg-rose-700">
                + Tambah Paket
            </button>
        </div>

        <div class="mt-4 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-100">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nama Paket</th>
                        <th class="px-4 py-3 font-medium">Harga / Bulan</th>
                        <th class="px-4 py-3 font-medium">Batas Outlet</th>
                        <th class="px-4 py-3 font-medium">Batas Pengguna</th>
                        <th class="px-4 py-3 font-medium">Toko Memakai</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->plans as $plan)
                        <tr wire:key="plan-{{ $plan->id }}">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $plan->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $rp($plan->price_per_month) }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $plan->max_outlets ?? 'Tanpa batas' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $plan->max_users ?? 'Tanpa batas' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $plan->companies_count }} toko</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-semibold {{ $plan->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $plan->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $plan->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button wire:click="edit({{ $plan->id }})" class="font-medium text-rose-600 hover:underline">Ubah</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">Belum ada paket. Tambahkan satu untuk mulai menawarkan langganan berbayar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>

    @if ($showFormModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4">
            <div class="my-8 w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold text-slate-900">{{ $editingId ? 'Ubah Paket' : 'Tambah Paket' }}</h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="text-sm font-medium text-slate-600">Nama Paket</label>
                        <input type="text" wire:model="name" placeholder="mis. Pro" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Harga per Bulan (Rp)</label>
                        <input type="number" step="1000" min="0" wire:model="pricePerMonth" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                        @error('pricePerMonth') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-slate-600">Batas Outlet</label>
                            <input type="number" min="1" wire:model="maxOutlets" placeholder="Tanpa batas" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                            @error('maxOutlets') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-600">Batas Pengguna</label>
                            <input type="number" min="1" wire:model="maxUsers" placeholder="Tanpa batas" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                            @error('maxUsers') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <p class="text-xs text-slate-400">Kosongkan batas outlet/pengguna untuk paket tanpa batas.</p>

                    @if ($editingId)
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" wire:model="isActive" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                            Aktif (bisa dipilih toko)
                        </label>
                    @endif
                </div>

                <div class="mt-6 flex gap-2">
                    <button wire:click="$set('showFormModal', false)" class="flex-1 rounded-lg border border-slate-300 py-2 font-medium text-slate-600 hover:bg-slate-50">
                        Batal
                    </button>
                    <button wire:click="save" class="flex-1 rounded-lg bg-rose-600 py-2 font-bold text-white hover:bg-rose-700">
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
