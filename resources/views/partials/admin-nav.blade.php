{{--
    Shared header nav — included from Terminal, Reports\Dashboard, and every
    Admin\* full-page component. Whichever component includes this must
    define its own logout(Logout $logout) method (see Terminal.php for the
    pattern); wire:click="logout" below binds to that including component.

    Dua susunan dari satu sumber tautan ($adminLinks): baris horizontal
    untuk layar lebar, dan satu tombol menu untuk layar sempit. Deretan
    horizontal ini sendirian butuh lebih dari 500px — di layar HP itu
    memaksa seluruh halaman melebar dan ter-zoom-out.
--}}
@php
    $user = Auth::user();
    $canAdmin = $user->isManager() || $user->isSuperadmin();
    $isSuper = $user->isSuperadmin();

    $adminLinks = collect([
        ['reports.dashboard', 'chart-bar', 'Laporan', $canAdmin],
        ['reports.inventory', 'scale', 'Laporan Inventaris', $canAdmin],
        ['reports.outlets', 'storefront', 'Perbandingan Outlet', $isSuper],
        ['admin.products', 'cube', 'Produk', $canAdmin],
        ['admin.labels', 'tag', 'Cetak Label', $canAdmin],
        ['admin.categories', 'tag', 'Kategori', $canAdmin],
        ['admin.suppliers', 'truck', 'Supplier', $canAdmin],
        ['admin.purchasing', 'clipboard-list', 'Pembelian', $canAdmin],
        ['admin.members', 'star', 'Member', $canAdmin],
        ['admin.users', 'users', 'Pengguna', $canAdmin],
        ['admin.outlets', 'storefront', 'Outlet', $isSuper],
        ['admin.settings', 'cog', 'Pengaturan', $isSuper],
        ['billing.index', 'wallet', 'Langganan Saya', $isSuper],
    ])->filter(fn ($link) => $link[3]);

    $itemClass = 'flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-rose-50 hover:text-rose-700';
@endphp

{{-- Layar lebar: baris horizontal seperti semula --}}
<div class="hidden items-center gap-4 lg:flex">
    @if ($canAdmin)
        <div x-data="{ open: false }" class="relative">
            <button
                @click="open = !open"
                @click.outside="open = false"
                type="button"
                class="flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100"
            >
                Admin
                <x-icon name="chevron-down" class="h-3.5 w-3.5 text-slate-400" />
            </button>
            <div
                x-show="open"
                x-cloak
                x-transition.origin.top.right
                class="absolute right-0 z-20 mt-1 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg"
            >
                @foreach ($adminLinks as [$route, $icon, $label, $show])
                    <a href="{{ route($route) }}" wire:navigate class="{{ $itemClass }}">
                        <x-icon :name="$icon" class="h-4 w-4 text-slate-400" /> {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <a href="{{ route('pos.terminal') }}" wire:navigate class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
        Kasir
    </a>
    <a href="{{ route('transactions.index') }}" wire:navigate class="flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
        <x-icon name="receipt" class="h-4 w-4 text-slate-400" /> Riwayat
    </a>

    <div class="mx-1 h-6 w-px bg-slate-200"></div>

    <div class="flex items-center gap-2">
        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-rose-100 text-sm font-bold text-rose-700">
            {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
        </span>
        <span class="text-sm font-medium text-slate-700">{{ $user->name }}</span>
    </div>

    <button wire:click="logout" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-700">
        <x-icon name="logout" class="h-4 w-4" /> Keluar
    </button>
</div>

{{-- Layar sempit: satu tombol menu --}}
<div x-data="{ open: false }" class="relative lg:hidden">
    <button
        @click="open = !open"
        @click.outside="open = false"
        type="button"
        aria-label="Menu"
        class="flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100"
    >
        <x-icon name="bars-3" class="h-6 w-6" />
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition.origin.top.right
        class="absolute right-0 z-30 mt-1 max-h-[75vh] w-60 overflow-y-auto overscroll-contain rounded-xl border border-slate-200 bg-white py-1 shadow-lg"
    >
        <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-3">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-rose-100 text-sm font-bold text-rose-700">
                {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
            </span>
            <span class="truncate text-sm font-medium text-slate-700">{{ $user->name }}</span>
        </div>

        <a href="{{ route('pos.terminal') }}" wire:navigate class="{{ $itemClass }}">
            <x-icon name="wallet" class="h-4 w-4 text-slate-400" /> Kasir
        </a>
        <a href="{{ route('transactions.index') }}" wire:navigate class="{{ $itemClass }}">
            <x-icon name="receipt" class="h-4 w-4 text-slate-400" /> Riwayat
        </a>

        @if ($adminLinks->isNotEmpty())
            <div class="mt-1 border-t border-slate-100 px-4 pb-1 pt-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
                Admin
            </div>
            @foreach ($adminLinks as [$route, $icon, $label, $show])
                <a href="{{ route($route) }}" wire:navigate class="{{ $itemClass }}">
                    <x-icon :name="$icon" class="h-4 w-4 text-slate-400" /> {{ $label }}
                </a>
            @endforeach
        @endif

        <div class="mt-1 border-t border-slate-100 pt-1">
            <button wire:click="logout" class="{{ $itemClass }} w-full">
                <x-icon name="logout" class="h-4 w-4 text-slate-400" /> Keluar
            </button>
        </div>
    </div>
</div>
