{{--
    Shared header nav — included from Terminal, Reports\Dashboard, and every
    Admin\* full-page component. Whichever component includes this must
    define its own logout(Logout $logout) method (see Terminal.php for the
    pattern); wire:click="logout" below binds to that including component.
--}}
<div class="flex items-center gap-4">
    @if (Auth::user()->isManager() || Auth::user()->isSuperadmin())
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
                <a href="{{ route('reports.dashboard') }}" wire:navigate class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-rose-50 hover:text-rose-700">
                    <x-icon name="chart-bar" class="h-4 w-4 text-slate-400" /> Laporan
                </a>
                <a href="{{ route('reports.inventory') }}" wire:navigate class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-rose-50 hover:text-rose-700">
                    <x-icon name="scale" class="h-4 w-4 text-slate-400" /> Laporan Inventaris
                </a>
                @if (Auth::user()->isSuperadmin())
                    <a href="{{ route('reports.outlets') }}" wire:navigate class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-rose-50 hover:text-rose-700">
                        <x-icon name="storefront" class="h-4 w-4 text-slate-400" /> Perbandingan Outlet
                    </a>
                @endif
                <a href="{{ route('admin.products') }}" wire:navigate class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-rose-50 hover:text-rose-700">
                    <x-icon name="cube" class="h-4 w-4 text-slate-400" /> Produk
                </a>
                <a href="{{ route('admin.labels') }}" wire:navigate class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-rose-50 hover:text-rose-700">
                    <x-icon name="tag" class="h-4 w-4 text-slate-400" /> Cetak Label
                </a>
                <a href="{{ route('admin.categories') }}" wire:navigate class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-rose-50 hover:text-rose-700">
                    <x-icon name="tag" class="h-4 w-4 text-slate-400" /> Kategori
                </a>
                <a href="{{ route('admin.suppliers') }}" wire:navigate class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-rose-50 hover:text-rose-700">
                    <x-icon name="truck" class="h-4 w-4 text-slate-400" /> Supplier
                </a>
                <a href="{{ route('admin.purchasing') }}" wire:navigate class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-rose-50 hover:text-rose-700">
                    <x-icon name="clipboard-list" class="h-4 w-4 text-slate-400" /> Pembelian
                </a>
                <a href="{{ route('admin.members') }}" wire:navigate class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-rose-50 hover:text-rose-700">
                    <x-icon name="star" class="h-4 w-4 text-slate-400" /> Member
                </a>
                <a href="{{ route('admin.users') }}" wire:navigate class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-rose-50 hover:text-rose-700">
                    <x-icon name="users" class="h-4 w-4 text-slate-400" /> Pengguna
                </a>
                @if (Auth::user()->isSuperadmin())
                    <a href="{{ route('admin.outlets') }}" wire:navigate class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-rose-50 hover:text-rose-700">
                        <x-icon name="storefront" class="h-4 w-4 text-slate-400" /> Outlet
                    </a>
                    <a href="{{ route('admin.settings') }}" wire:navigate class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-rose-50 hover:text-rose-700">
                        <x-icon name="cog" class="h-4 w-4 text-slate-400" /> Pengaturan
                    </a>
                    <a href="{{ route('billing.index') }}" wire:navigate class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-rose-50 hover:text-rose-700">
                        <x-icon name="wallet" class="h-4 w-4 text-slate-400" /> Langganan Saya
                    </a>
                @endif
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
            {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
        </span>
        <span class="hidden text-sm font-medium text-slate-700 sm:inline">{{ Auth::user()->name }}</span>
    </div>

    <button wire:click="logout" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-700">
        <x-icon name="logout" class="h-4 w-4" /> Keluar
    </button>
</div>
