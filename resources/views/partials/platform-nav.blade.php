{{--
    Shared header for every Platform\* screen. Whichever component
    includes this must define its own logout(Logout $logout) method
    (see Platform\Companies\Index for the pattern).
--}}
<header class="flex items-center justify-between border-b border-slate-200 bg-white px-6 py-4">
    <div class="flex items-center gap-6">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-600 text-sm font-bold text-white shadow-md shadow-rose-600/25">
                P
            </div>
            <div>
                <h1 class="text-base font-bold text-slate-900">Admin Platform</h1>
                <p class="text-xs text-slate-400">Kelola toko, paket, dan pembayaran</p>
            </div>
        </div>

        <nav class="flex items-center gap-1">
            <a href="{{ route('platform.companies') }}" wire:navigate class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('platform.companies') ? 'bg-rose-50 text-rose-700' : 'text-slate-600 hover:bg-slate-100' }}">
                Toko
            </a>
            <a href="{{ route('platform.payment-requests') }}" wire:navigate class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('platform.payment-requests') ? 'bg-rose-50 text-rose-700' : 'text-slate-600 hover:bg-slate-100' }}">
                Pembayaran
            </a>
            <a href="{{ route('platform.plans') }}" wire:navigate class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('platform.plans') ? 'bg-rose-50 text-rose-700' : 'text-slate-600 hover:bg-slate-100' }}">
                Paket
            </a>
        </nav>
    </div>

    <button wire:click="logout" class="text-sm font-medium text-slate-500 hover:text-rose-600">Keluar</button>
</header>
