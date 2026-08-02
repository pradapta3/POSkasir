<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $transaction->invoice_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @php
        $paperWidth = \App\Models\Setting::get('receipt_paper_width', '80');
        // 58mm printers have ~48mm of printable width after margins, 80mm
        // printers ~72mm — matches the physical paper roll a thermal
        // printer actually feeds, not just a smaller version of the same
        // layout, so text wraps the way it will on the real receipt.
        $contentWidthMm = $paperWidth === '58' ? 48 : 72;
        $baseFontPx = $paperWidth === '58' ? 10 : 12;
    @endphp
    <style>
        /* Comfortable width for on-screen viewing/printing preview before the print dialog opens. */
        body { font-family: 'Courier New', Courier, monospace; max-width: 320px; }
        @media print {
            @page { margin: 4mm; size: {{ $paperWidth }}mm auto; }
            .no-print { display: none !important; }
            /* Switched to the printer's actual paper width only for the printed
               output — matches the physical roll so text wraps the way it will
               on the real receipt, not just a smaller version of the screen layout. */
            body { max-width: {{ $contentWidthMm }}mm; font-size: {{ $baseFontPx }}px; }
        }
    </style>
</head>
<body class="mx-auto bg-white p-4 text-slate-900">
    @php
        $rp = fn ($n) => 'Rp'.number_format((float) $n, 0, ',', '.');
        $storeName = \App\Models\Setting::get('store_name') ?: 'POS Kasir';
        $storeAddress = \App\Models\Setting::get('store_address');
        $storePhone = \App\Models\Setting::get('store_phone');
        $storeLogoPath = \App\Models\Setting::get('store_logo_path');
        $receiptFooter = \App\Models\Setting::get('receipt_footer');
    @endphp

    <div class="text-center">
        @if ($storeLogoPath)
            <img
                src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($storeLogoPath) }}"
                alt="Logo"
                class="mx-auto mb-2 h-12 w-12 object-contain"
            >
        @endif
        <p class="text-base font-bold">{{ $storeName }}</p>
        @if ($storeAddress)
            <p class="text-xs">{{ $storeAddress }}</p>
        @endif
        @if ($storePhone)
            <p class="text-xs">{{ $storePhone }}</p>
        @endif
    </div>

    <div class="my-3 border-t border-dashed border-slate-400"></div>

    <div class="space-y-0.5 text-xs">
        <div class="flex justify-between"><span>No. Invoice</span><span>{{ $transaction->invoice_number }}</span></div>
        <div class="flex justify-between"><span>Tanggal</span><span>{{ $transaction->created_at->format('d/m/Y H:i') }}</span></div>
        <div class="flex justify-between"><span>Kasir</span><span>{{ $transaction->user->name }}</span></div>
        @if ($transaction->customer)
            <div class="flex justify-between"><span>Pelanggan</span><span>{{ $transaction->customer->name }}</span></div>
        @endif
    </div>

    <div class="my-3 border-t border-dashed border-slate-400"></div>

    <div class="space-y-1.5 text-xs">
        @foreach ($transaction->items as $item)
            <div>
                <p class="font-medium">{{ $item->product_name }}</p>
                <div class="flex justify-between text-slate-600">
                    <span>{{ $item->quantity }} x {{ $rp($item->price) }}</span>
                    <span>{{ $rp($item->subtotal) }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="my-3 border-t border-dashed border-slate-400"></div>

    <div class="space-y-0.5 text-xs">
        <div class="flex justify-between"><span>Subtotal</span><span>{{ $rp($transaction->subtotal) }}</span></div>
        @if ($transaction->discount_amount > 0)
            <div class="flex justify-between"><span>Diskon</span><span>-{{ $rp($transaction->discount_amount) }}</span></div>
        @endif
        @if ($transaction->loyalty_discount_amount > 0)
            <div class="flex justify-between"><span>Diskon Poin ({{ $transaction->loyalty_points_redeemed }} poin)</span><span>-{{ $rp($transaction->loyalty_discount_amount) }}</span></div>
        @endif
        <div class="flex justify-between"><span>Pajak ({{ rtrim(rtrim(number_format((float) $transaction->tax_percentage, 2), '0'), '.') }}%)</span><span>{{ $rp($transaction->tax_amount) }}</span></div>
        <div class="mt-1 flex justify-between border-t border-slate-300 pt-1 text-sm font-bold">
            <span>TOTAL</span><span>{{ $rp($transaction->grand_total) }}</span>
        </div>
    </div>

    <div class="my-3 border-t border-dashed border-slate-400"></div>

    <div class="space-y-0.5 text-xs">
        <div class="flex justify-between">
            <span>Metode Bayar</span>
            {{-- tryFrom, not from: PaymentMethod has shrunk since older
                 transactions were recorded (GoPay/Kartu/Lainnya removed),
                 so a historical receipt can't assume its value still exists. --}}
            <span>{{ \App\Enums\PaymentMethod::tryFrom($transaction->payment_method)?->label() ?? ucfirst($transaction->payment_method) }}</span>
        </div>
        <div class="flex justify-between"><span>Status</span><span>{{ $transaction->payment_status->label() }}</span></div>
        @if ($transaction->payment_method === 'cash')
            <div class="flex justify-between"><span>Dibayar</span><span>{{ $rp($transaction->paid_amount) }}</span></div>
            <div class="flex justify-between"><span>Kembalian</span><span>{{ $rp($transaction->change_amount) }}</span></div>
        @endif
        @if ($transaction->loyalty_points_earned > 0)
            <div class="flex justify-between"><span>Poin Didapat</span><span>+{{ $transaction->loyalty_points_earned }}</span></div>
        @endif
    </div>

    @if ($receiptFooter)
        <div class="my-3 border-t border-dashed border-slate-400"></div>
        <p class="text-center text-xs">{{ $receiptFooter }}</p>
    @endif

    <div class="no-print mt-6 flex gap-2">
        <button onclick="window.print()" class="flex-1 rounded-lg bg-rose-600 py-2 text-sm font-bold text-white">
            Cetak Ulang
        </button>
        <button onclick="window.close()" class="flex-1 rounded-lg border border-slate-300 py-2 text-sm font-medium text-slate-600">
            Tutup
        </button>
    </div>

    <script>
        window.addEventListener('load', () => window.print());
    </script>
</body>
</html>
