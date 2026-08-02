{{--
    Persistent sidebar for back-office screens (Reports, Admin\*). The POS
    Terminal deliberately does NOT use this — a cashier terminal wants
    maximum screen real estate for the product grid/cart, not permanent nav
    chrome, so Terminal keeps its own compact header + dropdown instead.
    Whichever component includes this must define logout(Logout $logout).
--}}
<aside class="flex w-60 shrink-0 flex-col border-r border-slate-200 bg-white">
    <div class="flex items-center gap-2 border-b border-slate-200 px-5 py-4">
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-600 text-sm font-bold text-white">P</span>
        <span class="text-lg font-bold text-slate-900">POS Kasir</span>
    </div>

    <livewire:partials.outlet-switcher />

    <nav class="flex-1 space-y-1 overflow-y-auto p-3">
        <a
            href="{{ route('pos.terminal') }}"
            wire:navigate
            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100"
        >
            <x-icon name="cart" class="h-5 w-5 text-slate-400" /> Kasir
        </a>
        <a
            href="{{ route('transactions.index') }}"
            wire:navigate
            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('transactions.index') ? 'bg-rose-50 text-rose-700' : 'text-slate-600 hover:bg-slate-100' }}"
        >
            <x-icon name="receipt" class="h-5 w-5 {{ request()->routeIs('transactions.index') ? 'text-rose-600' : 'text-slate-400' }}" /> Riwayat Transaksi
        </a>
        <a
            href="{{ route('reports.dashboard') }}"
            wire:navigate
            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('reports.dashboard') ? 'bg-rose-50 text-rose-700' : 'text-slate-600 hover:bg-slate-100' }}"
        >
            <x-icon name="chart-bar" class="h-5 w-5 {{ request()->routeIs('reports.dashboard') ? 'text-rose-600' : 'text-slate-400' }}" /> Laporan
        </a>
        <a
            href="{{ route('reports.inventory') }}"
            wire:navigate
            class="ml-8 flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('reports.inventory') ? 'bg-rose-50 text-rose-700' : 'text-slate-500 hover:bg-slate-100' }}"
        >
            Inventaris
        </a>
        @if (Auth::user()->isSuperadmin())
            <a
                href="{{ route('reports.outlets') }}"
                wire:navigate
                class="ml-8 flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('reports.outlets') ? 'bg-rose-50 text-rose-700' : 'text-slate-500 hover:bg-slate-100' }}"
            >
                Perbandingan Outlet
            </a>
        @endif

        <p class="px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wide text-slate-400">Manajemen</p>

        <a
            href="{{ route('admin.products') }}"
            wire:navigate
            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.products') ? 'bg-rose-50 text-rose-700' : 'text-slate-600 hover:bg-slate-100' }}"
        >
            <x-icon name="cube" class="h-5 w-5 {{ request()->routeIs('admin.products') ? 'text-rose-600' : 'text-slate-400' }}" /> Produk
        </a>
        <a
            href="{{ route('admin.labels') }}"
            wire:navigate
            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.labels') ? 'bg-rose-50 text-rose-700' : 'text-slate-600 hover:bg-slate-100' }}"
        >
            <x-icon name="tag" class="h-5 w-5 {{ request()->routeIs('admin.labels') ? 'text-rose-600' : 'text-slate-400' }}" /> Cetak Label
        </a>
        <a
            href="{{ route('admin.categories') }}"
            wire:navigate
            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.categories') ? 'bg-rose-50 text-rose-700' : 'text-slate-600 hover:bg-slate-100' }}"
        >
            <x-icon name="tag" class="h-5 w-5 {{ request()->routeIs('admin.categories') ? 'text-rose-600' : 'text-slate-400' }}" /> Kategori
        </a>
        <a
            href="{{ route('admin.suppliers') }}"
            wire:navigate
            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.suppliers') ? 'bg-rose-50 text-rose-700' : 'text-slate-600 hover:bg-slate-100' }}"
        >
            <x-icon name="truck" class="h-5 w-5 {{ request()->routeIs('admin.suppliers') ? 'text-rose-600' : 'text-slate-400' }}" /> Supplier
        </a>
        <a
            href="{{ route('admin.purchasing') }}"
            wire:navigate
            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.purchasing') ? 'bg-rose-50 text-rose-700' : 'text-slate-600 hover:bg-slate-100' }}"
        >
            <x-icon name="clipboard-list" class="h-5 w-5 {{ request()->routeIs('admin.purchasing') ? 'text-rose-600' : 'text-slate-400' }}" /> Pembelian
        </a>
        <a
            href="{{ route('admin.members') }}"
            wire:navigate
            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.members') ? 'bg-rose-50 text-rose-700' : 'text-slate-600 hover:bg-slate-100' }}"
        >
            <x-icon name="star" class="h-5 w-5 {{ request()->routeIs('admin.members') ? 'text-rose-600' : 'text-slate-400' }}" /> Member
        </a>
        <a
            href="{{ route('admin.users') }}"
            wire:navigate
            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.users') ? 'bg-rose-50 text-rose-700' : 'text-slate-600 hover:bg-slate-100' }}"
        >
            <x-icon name="users" class="h-5 w-5 {{ request()->routeIs('admin.users') ? 'text-rose-600' : 'text-slate-400' }}" /> Pengguna
        </a>
        @if (Auth::user()->isSuperadmin())
            <a
                href="{{ route('admin.outlets') }}"
                wire:navigate
                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.outlets') ? 'bg-rose-50 text-rose-700' : 'text-slate-600 hover:bg-slate-100' }}"
            >
                <x-icon name="storefront" class="h-5 w-5 {{ request()->routeIs('admin.outlets') ? 'text-rose-600' : 'text-slate-400' }}" /> Outlet
            </a>
            <a
                href="{{ route('admin.settings') }}"
                wire:navigate
                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.settings') ? 'bg-rose-50 text-rose-700' : 'text-slate-600 hover:bg-slate-100' }}"
            >
                <x-icon name="cog" class="h-5 w-5 {{ request()->routeIs('admin.settings') ? 'text-rose-600' : 'text-slate-400' }}" /> Pengaturan
            </a>
            <a
                href="{{ route('billing.index') }}"
                wire:navigate
                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('billing.index') ? 'bg-rose-50 text-rose-700' : 'text-slate-600 hover:bg-slate-100' }}"
            >
                <x-icon name="wallet" class="h-5 w-5 {{ request()->routeIs('billing.index') ? 'text-rose-600' : 'text-slate-400' }}" /> Langganan Saya
            </a>
        @endif
    </nav>

    <div class="border-t border-slate-200 p-3">
        <div class="flex items-center gap-2 rounded-lg px-2 py-2">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-rose-100 text-sm font-bold text-rose-700">
                {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-slate-700">{{ Auth::user()->name }}</p>
                <p class="truncate text-xs text-slate-400">{{ Auth::user()->role?->name }}</p>
            </div>
        </div>
        <button
            wire:click="logout"
            class="mt-1 flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
        >
            <x-icon name="logout" class="h-5 w-5 text-slate-400" /> Keluar
        </button>
    </div>
</aside>
