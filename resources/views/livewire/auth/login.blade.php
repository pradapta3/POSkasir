<div class="rounded-2xl bg-white p-8 shadow-xl shadow-slate-200/60 ring-1 ring-slate-100">
    <div class="mb-8 text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-600 text-xl font-bold text-white shadow-lg shadow-rose-600/30">
            P
        </div>
        <h1 class="mt-4 text-2xl font-bold text-slate-900">POS Kasir</h1>
        <p class="mt-1 text-sm text-slate-500">Masuk untuk melanjutkan</p>
    </div>

    <form wire:submit="login" class="space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
            <input
                id="email"
                type="email"
                wire:model="email"
                autofocus
                autocomplete="username"
                placeholder="nama@tokokamu.com"
                class="mt-1.5 w-full rounded-lg border-slate-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"
            >
            @error('email')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Kata Sandi</label>
            <input
                id="password"
                type="password"
                wire:model="password"
                autocomplete="current-password"
                placeholder="••••••••"
                class="mt-1.5 w-full rounded-lg border-slate-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"
            >
            @error('password')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" wire:model="remember" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
            Ingat saya
        </label>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="login"
            class="w-full rounded-lg bg-rose-600 py-2.5 font-semibold text-white shadow-md shadow-rose-600/25 transition hover:bg-rose-700 disabled:opacity-60"
        >
            <span wire:loading.remove wire:target="login">Masuk</span>
            <span wire:loading wire:target="login">Memproses&hellip;</span>
        </button>
    </form>

    <p class="mt-4 text-center text-xs text-slate-400">
        <a href="{{ route('password.request') }}" wire:navigate class="font-medium text-rose-600 hover:underline">Lupa kata sandi?</a>
    </p>
    <p class="mt-2 text-center text-xs text-slate-400">
        Belum punya toko? <a href="{{ route('register') }}" wire:navigate class="font-medium text-rose-600 hover:underline">Daftar toko baru</a>
    </p>
</div>
