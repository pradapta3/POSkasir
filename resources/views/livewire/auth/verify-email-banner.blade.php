{{--
    Livewire needs a single static root element to attach wire:id to — a
    top-level @unless/@if here (instead of inside the div) leaves the real
    content outside Livewire's tracked DOM, silently breaking wire:click.
--}}
<div>
    @unless (Auth::user()->hasVerifiedEmail())
        <div class="flex flex-wrap items-center justify-between gap-2 bg-amber-50 px-4 py-2 text-sm text-amber-800 ring-1 ring-amber-200">
            <span class="flex items-center gap-2">
                <x-icon name="exclamation-triangle" class="h-4 w-4 shrink-0 text-amber-500" />
                Verifikasi email <strong>{{ Auth::user()->email }}</strong> untuk mengamankan akunmu.
            </span>

            @if ($sent)
                <span class="font-medium text-emerald-600">Email verifikasi terkirim, cek kotak masukmu.</span>
            @else
                <button
                    wire:click="resend"
                    wire:loading.attr="disabled"
                    wire:target="resend"
                    class="shrink-0 font-semibold text-amber-900 underline hover:no-underline disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="resend">Kirim ulang email verifikasi</span>
                    <span wire:loading wire:target="resend">Mengirim&hellip;</span>
                </button>
            @endif
        </div>
    @endunless
</div>
