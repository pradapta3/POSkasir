<div class="flex h-screen bg-slate-100">
    @include('partials.sidebar')

    <main class="flex-1 overflow-y-auto p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Supplier</h1>
                <p class="mt-1 text-sm text-slate-500">Kelola pemasok barang untuk dipilih saat mencatat pembelian.</p>
            </div>
            <button wire:click="create" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-rose-600/25 hover:bg-rose-700">
                + Tambah Supplier
            </button>
        </div>

        <div class="mt-4">
            <input
                type="text"
                wire:model.live.debounce.400ms="search"
                placeholder="Cari nama, kontak, atau telepon..."
                class="w-full max-w-sm rounded-lg border-slate-300 text-sm focus:border-rose-500 focus:ring-rose-500"
            >
        </div>

        <div class="mt-4 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-100">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nama Supplier</th>
                        <th class="px-4 py-3 font-medium">Kontak</th>
                        <th class="px-4 py-3 font-medium">Alamat</th>
                        <th class="px-4 py-3 font-medium">Pembelian</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->suppliers as $supplier)
                        <tr wire:key="supplier-{{ $supplier->id }}">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $supplier->name }}</td>
                            <td class="px-4 py-3 text-slate-600">
                                <p>{{ $supplier->contact_person ?: '-' }}</p>
                                @if ($supplier->phone)
                                    <p class="text-xs text-slate-400">{{ $supplier->phone }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $supplier->address ?: '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $supplier->purchase_orders_count }}x</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-semibold {{ $supplier->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $supplier->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $supplier->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button wire:click="edit({{ $supplier->id }})" class="font-medium text-rose-600 hover:underline">Ubah</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Belum ada supplier.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $this->suppliers->links() }}</div>
    </main>

    @if ($showFormModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4">
            <div class="my-8 w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold text-slate-900">{{ $editingId ? 'Ubah Supplier' : 'Tambah Supplier' }}</h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="text-sm font-medium text-slate-600">Nama Supplier</label>
                        <input type="text" wire:model="name" placeholder="mis. CV Sumber Rejeki" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Nama Kontak (opsional)</label>
                        <input type="text" wire:model="contactPerson" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                        @error('contactPerson') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
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
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Alamat (opsional)</label>
                        <textarea wire:model="address" rows="2" class="mt-1 w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500"></textarea>
                        @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    @if ($editingId)
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" wire:model="isActive" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                            Aktif (bisa dipilih saat mencatat pembelian)
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
