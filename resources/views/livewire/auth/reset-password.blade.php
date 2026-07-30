<div class="rounded-2xl bg-white p-8 shadow-xl shadow-slate-200/60 ring-1 ring-slate-100">
    <div class="mb-8 text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-600 text-xl font-bold text-white shadow-lg shadow-rose-600/30">
            P
        </div>
        <h1 class="mt-4 text-2xl font-bold text-slate-900">Atur Ulang Kata Sandi</h1>
        <p class="mt-1 text-sm text-slate-500">Buat kata sandi baru untuk akunmu.</p>
    </div>

    <form wire:submit="resetPassword" class="space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
            <input
                id="email"
                type="email"
                wire:model="email"
                autofocus
                autocomplete="username"
                class="mt-1.5 w-full rounded-lg border-slate-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"
            >
            @error('email') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Kata Sandi Baru</label>
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
            <label for="passwordConfirmation" class="block text-sm font-medium text-slate-700">Konfirmasi Kata Sandi Baru</label>
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
            wire:target="resetPassword"
            class="w-full rounded-lg bg-rose-600 py-2.5 font-semibold text-white shadow-md shadow-rose-600/25 transition hover:bg-rose-700 disabled:opacity-60"
        >
            <span wire:loading.remove wire:target="resetPassword">Simpan Kata Sandi Baru</span>
            <span wire:loading wire:target="resetPassword">Menyimpan&hellip;</span>
        </button>
    </form>
</div>
