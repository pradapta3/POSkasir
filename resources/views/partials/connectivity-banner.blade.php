{{--
    Global online/offline awareness for the operational app. This app is
    server-rendered Livewire — every action is an AJAX round trip — so
    losing connectivity mid-shift needs to be obvious to the cashier
    immediately, not discovered when a "Bayar" click silently does nothing.
    Reads Alpine.store('connectivity'), registered in layouts.pos.blade.php.

    No x-transition here on purpose — it leans on the browser firing a CSS
    transitionend event, and if that doesn't fire (reduced-motion settings,
    some automated/headless browsers) Alpine's show/hide can get stuck
    mid-transition and the banner never actually appears. A banner that's
    reliably instant beats one that's animated but occasionally invisible.
--}}
<div
    x-data
    x-show="!$store.connectivity.online"
    x-cloak
    class="fixed inset-x-0 top-0 z-[9999] flex items-center justify-center gap-2 bg-amber-500 px-4 py-2 text-center text-sm font-semibold text-white shadow-md"
>
    <x-icon name="exclamation-triangle" class="h-4 w-4 shrink-0" />
    Tidak ada koneksi internet — transaksi dan perubahan tidak bisa diproses sampai koneksi kembali.
</div>

<div
    x-data="{ show: false, wasOffline: false }"
    x-init="$watch('$store.connectivity.online', (online) => {
        if (! online) { wasOffline = true; return; }
        if (wasOffline) { show = true; wasOffline = false; setTimeout(() => show = false, 3000); }
    })"
    x-show="show"
    x-cloak
    class="fixed inset-x-0 top-0 z-[9999] flex items-center justify-center gap-2 bg-emerald-600 px-4 py-2 text-center text-sm font-semibold text-white shadow-md"
>
    <x-icon name="check-circle" class="h-4 w-4 shrink-0" />
    Koneksi internet tersambung kembali.
</div>
