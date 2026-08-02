<div class="flex h-screen bg-slate-100">
    @include('partials.sidebar')

    <main class="flex-1 overflow-y-auto p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Outlet</h1>
                <p class="mt-1 text-sm text-slate-500">Cabang toko kamu — kasir hanya melayani transaksi untuk satu outlet sekaligus.</p>
            </div>
            <button wire:click="create" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-rose-600/25 hover:bg-rose-700">
                + Tambah Outlet
            </button>
        </div>

        <div class="mt-4 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-100">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nama Outlet</th>
                        <th class="px-4 py-3 font-medium">Alamat</th>
                        <th class="px-4 py-3 font-medium">Telepon</th>
                        <th class="px-4 py-3 font-medium">Pengguna</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->outlets as $outlet)
                        <tr wire:key="outlet-{{ $outlet->id }}">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $outlet->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $outlet->address ?: '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $outlet->phone ?: '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $outlet->users_count }} orang</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-semibold {{ $outlet->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $outlet->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $outlet->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button wire:click="edit({{ $outlet->id }})" class="font-medium text-rose-600 hover:underline">Ubah</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Belum ada outlet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>

    @if ($showFormModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4">
            <div class="my-8 w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold text-slate-900">{{ $editingId ? 'Ubah Outlet' : 'Tambah Outlet' }}</h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="text-sm font-medium text-slate-600">Nama Outlet</label>
                        <input type="text" wire:model="name" placeholder="mis. Outlet Cabang Kemang" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Alamat</label>
                        <textarea wire:model="address" rows="2" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500"></textarea>
                        @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Nomor Telepon</label>
                        <input type="text" wire:model="phone" placeholder="08xxxxxxxxxx" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                        @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    @if ($editingId)
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" wire:model="isActive" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                            Aktif (bisa dipilih di kasir)
                        </label>
                        @error('isActive') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
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
