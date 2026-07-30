@php $company = $this->company; @endphp

<div class="rounded-2xl bg-white p-8 shadow-xl shadow-slate-200/60 ring-1 ring-slate-100">
    <div class="mb-6 text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl {{ $company->status->value === 'rejected' ? 'bg-red-600 shadow-red-600/30' : 'bg-amber-500 shadow-amber-500/30' }} text-xl font-bold text-white shadow-lg">
            <x-icon name="{{ $company->status->value === 'rejected' ? 'exclamation-triangle' : 'storefront' }}" class="h-6 w-6" />
        </div>

        @if ($company->status->value === 'rejected')
            <h1 class="mt-4 text-xl font-bold text-slate-900">Pendaftaran Belum Disetujui</h1>
            <p class="mt-1 text-sm text-slate-500">Toko "{{ $company->name }}" belum dapat kami setujui.</p>
        @else
            <h1 class="mt-4 text-xl font-bold text-slate-900">Menunggu Persetujuan</h1>
            <p class="mt-1 text-sm text-slate-500">Toko "{{ $company->name }}" sedang ditinjau oleh Admin Platform.</p>
        @endif
    </div>

    @if ($company->status->value === 'rejected')
        <div class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">
            @if ($company->rejection_reason)
                <p class="font-medium">Alasan:</p>
                <p class="mt-1">{{ $company->rejection_reason }}</p>
            @else
                <p>Silakan hubungi tim kami untuk informasi lebih lanjut.</p>
            @endif
        </div>
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
