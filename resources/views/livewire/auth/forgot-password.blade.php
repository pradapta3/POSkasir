<div class="rounded-2xl bg-white p-8 shadow-xl shadow-slate-200/60 ring-1 ring-slate-100">
    <div class="mb-8 text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-600 text-xl font-bold text-white shadow-lg shadow-rose-600/30">
            P
        </div>
        <h1 class="mt-4 text-2xl font-bold text-slate-900">Lupa Kata Sandi</h1>
        <p class="mt-1 text-sm text-slate-500">Masukkan email akunmu, kami kirimkan tautan atur ulang.</p>
    </div>

    @if ($status)
        <div class="mb-4 rounded-lg bg-emerald-50 px-4 py-2.5 text-sm text-emerald-700 ring-1 ring-emerald-200">
            {{ $status }}
        </div>
    @endif

    <form wire:submit="sendResetLink" class="space-y-4">
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
            @error('email') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="sendResetLink"
            class="w-full rounded-lg bg-rose-600 py-2.5 font-semibold text-white shadow-md shadow-rose-600/25 transition hover:bg-rose-700 disabled:opacity-60"
        >
            <span wire:loading.remove wire:target="sendResetLink">Kirim Tautan Atur Ulang</span>
            <span wire:loading wire:target="sendResetLink">Mengirim&hellip;</span>
        </button>
    </form>

    <p class="mt-6 text-center text-xs text-slate-400">
        <a href="{{ route('login') }}" wire:navigate class="font-medium text-rose-600 hover:underline">&larr; Kembali ke halaman masuk</a>
    </p>
</div>
