<div class="rounded-2xl bg-white p-8 shadow-xl shadow-slate-200/60 ring-1 ring-slate-100">
    <div class="mb-8 text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-600 text-xl font-bold text-white shadow-lg shadow-rose-600/30">
            P
        </div>
        <h1 class="mt-4 text-2xl font-bold text-slate-900">Daftar Toko Baru</h1>
        <p class="mt-1 text-sm text-slate-500">Gratis untuk mulai, tanpa kartu kredit.</p>
    </div>

    <form wire:submit="register" class="space-y-4">
        <div>
            <label for="storeName" class="block text-sm font-medium text-slate-700">Nama Toko</label>
            <input
                id="storeName"
                type="text"
                wire:model="storeName"
                autofocus
                placeholder="mis. Toko Artha Kumara"
                class="mt-1.5 w-full rounded-lg border-slate-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"
            >
            @error('storeName') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="ownerName" class="block text-sm font-medium text-slate-700">Nama Pemilik</label>
            <input
                id="ownerName"
                type="text"
                wire:model="ownerName"
                placeholder="Nama lengkap kamu"
                class="mt-1.5 w-full rounded-lg border-slate-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"
            >
            @error('ownerName') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
            <input
                id="email"
                type="email"
                wire:model="email"
                autocomplete="username"
                placeholder="nama@tokokamu.com"
                class="mt-1.5 w-full rounded-lg border-slate-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"
            >
            @error('email') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="phone" class="block text-sm font-medium text-slate-700">Nomor Telepon (opsional)</label>
            <input
                id="phone"
                type="text"
                wire:model="phone"
                placeholder="08xxxxxxxxxx"
                class="mt-1.5 w-full rounded-lg border-slate-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"
            >
            @error('phone') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Kata Sandi</label>
            <input
                id="password"
                type="password"
                wire:model="password"
                autocomplete="new-password"
                placeholder="Minimal 8 karakter"
                class="mt-1.5 w-full rounded-lg border-slate-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"
            >
            @error('password') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="passwordConfirmation" class="block text-sm font-medium text-slate-700">Konfirmasi Kata Sandi</label>
            <input
                id="passwordConfirmation"
                type="password"
                wire:model="passwordConfirmation"
                autocomplete="new-password"
                class="mt-1.5 w-full rounded-lg border-slate-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"
            >
            @error('passwordConfirmation') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="register"
            class="w-full rounded-lg bg-rose-600 py-2.5 font-semibold text-white shadow-md shadow-rose-600/25 transition hover:bg-rose-700 disabled:opacity-60"
        >
            <span wire:loading.remove wire:target="register">Buat Toko</span>
            <span wire:loading wire:target="register">Memproses&hellip;</span>
        </button>
    </form>

    <p class="mt-6 text-center text-xs text-slate-400">
        Sudah punya akun? <a href="{{ route('login') }}" wire:navigate class="font-medium text-rose-600 hover:underline">Masuk di sini</a>
    </p>
</div>
