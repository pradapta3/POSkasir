<div class="min-h-screen">
    <header class="flex items-center justify-between border-b border-slate-200 bg-white px-6 py-4">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-600 text-sm font-bold text-white shadow-md shadow-rose-600/25">
                P
            </div>
            <div>
                <h1 class="text-base font-bold text-slate-900">Admin Platform</h1>
                <p class="text-xs text-slate-400">Tinjau pendaftaran toko baru</p>
            </div>
        </div>
        <button wire:click="logout" class="text-sm font-medium text-slate-500 hover:text-rose-600">Keluar</button>
    </header>

    <main class="mx-auto max-w-5xl p-6">
        <div class="flex flex-wrap items-center gap-2">
            @foreach ([
                'pending' => 'Menunggu',
                'approved' => 'Disetujui',
                'rejected' => 'Ditolak',
                'all' => 'Semua',
            ] as $value => $label)
                <button
                    wire:click="$set('statusFilter', '{{ $value }}')"
                    class="rounded-full px-4 py-1.5 text-sm font-medium transition {{ $statusFilter === $value ? 'bg-rose-600 text-white shadow-sm shadow-rose-600/25' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}"
                >
                    {{ $label }}
                    @if ($value === 'pending' && $this->pendingCount > 0)
                        <span class="ml-1 rounded-full bg-white/20 px-1.5 text-xs {{ $statusFilter === $value ? '' : 'bg-amber-100 text-amber-700' }}">{{ $this->pendingCount }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        <div class="mt-4 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-100">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nama Toko</th>
                        <th class="px-4 py-3 font-medium">Pemilik</th>
                        <th class="px-4 py-3 font-medium">Tanggal Daftar</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->companies as $company)
                        @php $owner = $company->users->first(); @endphp
                        <tr wire:key="company-{{ $company->id }}">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $company->name }}</td>
                            <td class="px-4 py-3 text-slate-600">
                                <p>{{ $owner?->name ?? '-' }}</p>
                                <p class="text-xs text-slate-400">{{ $owner?->email ?? $company->owner_email }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $company->created_at->format('d M Y, H:i') }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $badge = match ($company->status->value) {
                                        'pending' => 'bg-amber-100 text-amber-700',
                                        'approved' => 'bg-emerald-100 text-emerald-700',
                                        'rejected' => 'bg-red-100 text-red-700',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $badge }}">
                                    {{ $company->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($company->status->value === 'pending')
                                    <button
                                        wire:click="approve({{ $company->id }})"
                                        wire:confirm="Setujui toko &quot;{{ $company->name }}&quot;?"
                                        class="font-medium text-emerald-600 hover:underline"
                                    >Setujui</button>
                                    <button wire:click="startReject({{ $company->id }})" class="ml-3 font-medium text-red-600 hover:underline">Tolak</button>
                                @else
                                    <span class="text-xs text-slate-400">
                                        {{ $company->status->value === 'approved' ? 'Disetujui '.$company->approved_at?->format('d M Y') : 'Sudah ditinjau' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">Tidak ada toko pada status ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>

    @if ($rejectingId)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold text-slate-900">Tolak Pendaftaran</h2>
                <p class="mt-1 text-sm text-slate-500">Alasan ini akan dikirimkan ke pemilik toko lewat email.</p>

                <div class="mt-4">
                    <textarea
                        wire:model="rejectionReason"
                        rows="4"
                        placeholder="mis. Data toko tidak lengkap, silakan daftar ulang dengan informasi yang valid."
                        class="w-full rounded-lg border-slate-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"
                    ></textarea>
                    @error('rejectionReason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="mt-6 flex gap-2">
                    <button wire:click="cancelReject" class="flex-1 rounded-lg border border-slate-300 py-2 font-medium text-slate-600 hover:bg-slate-50">
                        Batal
                    </button>
                    <button wire:click="reject" class="flex-1 rounded-lg bg-red-600 py-2 font-bold text-white hover:bg-red-700">
                        Tolak Pendaftaran
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
