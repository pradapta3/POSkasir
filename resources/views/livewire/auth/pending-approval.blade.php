@php $company = $this->company; $state = $this->state; @endphp

<div class="rounded-2xl bg-white p-8 shadow-xl shadow-slate-200/60 ring-1 ring-slate-100">
    <div class="mb-6 text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl {{ in_array($state, ['pending', 'expired']) ? 'bg-amber-500 shadow-amber-500/30' : 'bg-red-600 shadow-red-600/30' }} text-xl font-bold text-white shadow-lg">
            <x-icon name="{{ $state === 'pending' ? 'storefront' : ($state === 'expired' ? 'wallet' : 'exclamation-triangle') }}" class="h-6 w-6" />
        </div>

        @if ($state === 'rejected')
            <h1 class="mt-4 text-xl font-bold text-slate-900">Pendaftaran Belum Disetujui</h1>
            <p class="mt-1 text-sm text-slate-500">Toko "{{ $company->name }}" belum dapat kami setujui.</p>
        @elseif ($state === 'suspended')
            <h1 class="mt-4 text-xl font-bold text-slate-900">Akun Toko Dinonaktifkan</h1>
            <p class="mt-1 text-sm text-slate-500">Toko "{{ $company->name }}" untuk sementara dinonaktifkan oleh Admin Platform.</p>
        @elseif ($state === 'expired')
            <h1 class="mt-4 text-xl font-bold text-slate-900">Masa Berlaku Habis</h1>
            <p class="mt-1 text-sm text-slate-500">Masa trial atau langganan toko "{{ $company->name }}" sudah berakhir.</p>
        @else
            <h1 class="mt-4 text-xl font-bold text-slate-900">Menunggu Persetujuan</h1>
            <p class="mt-1 text-sm text-slate-500">Toko "{{ $company->name }}" sedang ditinjau oleh Admin Platform.</p>
        @endif
    </div>

    @if ($state === 'rejected')
        <div class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">
            @if ($company->rejection_reason)
                <p class="font-medium">Alasan:</p>
                <p class="mt-1">{{ $company->rejection_reason }}</p>
            @else
                <p>Silakan hubungi tim kami untuk informasi lebih lanjut.</p>
            @endif
        </div>
    @elseif ($state === 'suspended')
        <div class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">
            <p>Hubungi tim kami untuk informasi lebih lanjut atau mengaktifkan kembali akun toko kamu.</p>
        </div>
    @elseif ($state === 'expired')
        <div class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-700 ring-1 ring-amber-200">
            <p>Perpanjang langganan untuk terus menggunakan POS Kasir tanpa gangguan.</p>
        </div>
        @if (Auth::user()->isSuperadmin())
            <a
                href="{{ route('billing.index') }}"
                wire:navigate
                class="mt-4 block w-full rounded-lg bg-rose-600 py-2.5 text-center font-semibold text-white transition hover:bg-rose-700"
            >
                Perpanjang Langganan
            </a>
        @endif
    @else
        <div class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-700 ring-1 ring-amber-200">
            Kami akan mengirimkan email begitu toko kamu disetujui dan siap digunakan. Biasanya proses ini tidak lama.
        </div>
    @endif

    <button
        wire:click="logout"
        class="mt-6 w-full rounded-lg border border-slate-300 py-2.5 font-semibold text-slate-600 transition hover:bg-slate-50"
    >
        Keluar
    </button>
</div>
