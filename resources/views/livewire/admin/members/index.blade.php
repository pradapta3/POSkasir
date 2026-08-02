<div class="flex h-screen bg-slate-100">
    @include('partials.sidebar')

    <main class="flex-1 overflow-y-auto p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Member</h1>
                <p class="mt-1 text-sm text-slate-500">Kelola pelanggan dan program loyalitas toko.</p>
            </div>
            <button wire:click="create" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-rose-600/25 hover:bg-rose-700">
                + Tambah Pelanggan
            </button>
        </div>

        <div class="mt-4">
            <input
                type="text"
                wire:model.live.debounce.400ms="search"
                placeholder="Cari nama, telepon, atau kode member..."
                class="w-full max-w-sm rounded-lg border-slate-300 text-sm focus:border-rose-500 focus:ring-rose-500"
            >
        </div>

        <div class="mt-4 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-100">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nama</th>
                        <th class="px-4 py-3 font-medium">Kontak</th>
                        <th class="px-4 py-3 font-medium">Kode Member</th>
                        <th class="px-4 py-3 font-medium">Poin</th>
                        <th class="px-4 py-3 font-medium">Bergabung</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->customers as $customer)
                        <tr wire:key="customer-{{ $customer->id }}">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $customer->name }}</td>
                            <td class="px-4 py-3 text-slate-600">
                                <p>{{ $customer->phone ?: '-' }}</p>
                                @if ($customer->email)
                                    <p class="text-xs text-slate-400">{{ $customer->email }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($customer->is_member)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700">
                                        <x-icon name="star" class="h-3 w-3" /> {{ $customer->member_code }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400">Belum jadi member</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                @if ($customer->is_member)
                                    <button wire:click="openPointsModal({{ $customer->id }})" class="font-semibold text-slate-800 hover:text-rose-600 hover:underline">
                                        {{ number_format($customer->loyalty_points) }} poin
                                    </button>
                                @else
                                    <span class="text-slate-300">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $customer->member_since?->format('d M Y') ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button wire:click="edit({{ $customer->id }})" class="font-medium text-rose-600 hover:underline">Ubah</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Belum ada pelanggan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $this->customers->links() }}</div>
    </main>

    {{-- Create/edit form modal --}}
    @if ($showFormModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4">
            <div class="my-8 w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold text-slate-900">{{ $editingId ? 'Ubah Pelanggan' : 'Tambah Pelanggan' }}</h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="text-sm font-medium text-slate-600">Nama</label>
                        <input type="text" wire:model="name" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Nomor Telepon</label>
                        <input type="text" wire:model="phone" placeholder="08xxxxxxxxxx" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                        @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Email (opsional)</label>
                        <input type="email" wire:model="email" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                        @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Alamat (opsional)</label>
                        <textarea wire:model="address" rows="2" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500"></textarea>
                        @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    @if ($isMember)
                        <div class="flex items-center gap-2 rounded-lg bg-rose-50 px-3 py-2.5 text-sm text-rose-700">
                            <x-icon name="star" class="h-4 w-4 shrink-0" /> Sudah jadi member — poin dikelola dari tabel utama.
                        </div>
                    @else
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" wire:model="isMember" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                            Jadikan member (dapat kode member &amp; mulai kumpulkan poin)
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

    {{-- Points adjustment modal --}}
    @if ($showPointsModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold text-slate-900">Sesuaikan Poin</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $pointsCustomerName }}</p>
                <p class="mt-2 text-sm text-slate-600">Poin saat ini: <span class="font-semibold text-slate-800">{{ number_format((int) $pointsCustomerBalance) }}</span></p>

                <div class="mt-4">
                    <label class="text-sm font-medium text-slate-600">Perubahan Poin</label>
                    <input type="number" step="1" wire:model="pointsDelta" class="mt-1 w-full rounded-lg border-slate-300 text-lg focus:border-rose-500 focus:ring-rose-500">
                    @error('pointsDelta') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-slate-400">Masukkan angka positif untuk menambah, negatif untuk mengurangi.</p>
                </div>
                <div class="mt-4">
                    <label class="text-sm font-medium text-slate-600">Alasan</label>
                    <input type="text" wire:model="pointsNotes" placeholder="mis. Koreksi poin, kompensasi keluhan" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                    @error('pointsNotes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="mt-6 flex gap-2">
                    <button wire:click="$set('showPointsModal', false)" class="flex-1 rounded-lg border border-slate-300 py-2 font-medium text-slate-600 hover:bg-slate-50">
                        Batal
                    </button>
                    <button wire:click="adjustPoints" class="flex-1 rounded-lg bg-rose-600 py-2 font-bold text-white hover:bg-rose-700">
                        Terapkan
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
