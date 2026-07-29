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
                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div
                x-show="open"
                x-cloak
                x-transition.origin.top.right
                class="absolute right-0 z-20 mt-1 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg"
            >
                <a href="{{ route('reports.dashboard') }}" wire:navigate class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-rose-50 hover:text-rose-700">
                    <span>📊</span> Laporan
                </a>
                <a href="{{ route('admin.products') }}" wire:navigate class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-rose-50 hover:text-rose-700">
                    <span>📦</span> Produk
                </a>
                <a href="{{ route('admin.categories') }}" wire:navigate class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-rose-50 hover:text-rose-700">
                    <span>🏷️</span> Kategori
                </a>
                <a href="{{ route('admin.users') }}" wire:navigate class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-rose-50 hover:text-rose-700">
                    <span>👥</span> Pengguna
                </a>
                @if (Auth::user()->isSuperadmin())
                    <a href="{{ route('admin.settings') }}" wire:navigate class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-rose-50 hover:text-rose-700">
                        <span>⚙️</span> Pengaturan
                    </a>
                @endif
            </div>
        </div>
    @endif

    <a href="{{ route('pos.terminal') }}" wire:navigate class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
        Kasir
    </a>

    <div class="mx-1 h-6 w-px bg-slate-200"></div>

    <div class="flex items-center gap-2">
        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-rose-100 text-sm font-bold text-rose-700">
            {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
        </span>
        <span class="hidden text-sm font-medium text-slate-700 sm:inline">{{ Auth::user()->name }}</span>
    </div>

    <button wire:click="logout" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-700">
        Keluar
    </button>
</div>
