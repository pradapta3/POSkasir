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

    {{--
        Cetak lewat printer termal Bluetooth — hanya muncul di dalam aplikasi
        Android (Capacitor). Di browser biasa blok ini tetap tersembunyi dan
        struk dicetak lewat dialog cetak seperti sebelumnya.
    --}}
    <div id="bt-panel" class="no-print mt-3 hidden">
        <button id="bt-print" class="w-full rounded-lg bg-slate-900 py-2.5 text-sm font-bold text-white">
            Cetak ke Printer Bluetooth
        </button>
        <p id="bt-status" class="mt-2 text-center text-xs text-slate-500"></p>
        <div id="bt-devices" class="mt-2 space-y-1"></div>
    </div>

    @php
        // Data struk disajikan terstruktur supaya perakit ESC/POS di bawah
        // tidak perlu mengurai DOM — tata letak layar dan tata letak cetak
        // termal berbeda sama sekali.
        $receiptPayload = [
            'paperWidth' => (int) $paperWidth,
            'store' => array_filter([
                'name' => $storeName,
                'address' => $storeAddress,
                'phone' => $storePhone,
            ]),
            'meta' => array_filter([
                'No. Invoice' => $transaction->invoice_number,
                'Tanggal' => $transaction->created_at->format('d/m/Y H:i'),
                'Kasir' => $transaction->user->name,
                'Pelanggan' => $transaction->customer?->name,
            ]),
            'items' => $transaction->items->map(fn ($item) => [
                'name' => $item->product_name,
                'qty' => (int) $item->quantity,
                'price' => $rp($item->price),
                'subtotal' => $rp($item->subtotal),
            ])->all(),
            'totals' => array_filter([
                'Subtotal' => $rp($transaction->subtotal),
                'Diskon' => $transaction->discount_amount > 0 ? '-'.$rp($transaction->discount_amount) : null,
                'Diskon Poin' => $transaction->loyalty_discount_amount > 0 ? '-'.$rp($transaction->loyalty_discount_amount) : null,
                'Pajak' => $rp($transaction->tax_amount),
            ]),
            'grandTotal' => $rp($transaction->grand_total),
            'payment' => array_filter([
                'Metode Bayar' => \App\Enums\PaymentMethod::tryFrom($transaction->payment_method)?->label() ?? ucfirst($transaction->payment_method),
                'Dibayar' => $transaction->payment_method === 'cash' ? $rp($transaction->paid_amount) : null,
                'Kembalian' => $transaction->payment_method === 'cash' ? $rp($transaction->change_amount) : null,
                'Poin Didapat' => $transaction->loyalty_points_earned > 0 ? '+'.$transaction->loyalty_points_earned : null,
            ]),
            'footer' => $receiptFooter,
        ];
    @endphp
    <script type="application/json" id="receipt-payload">@json($receiptPayload)</script>

    <script>
        (function () {
            const isNative = !!(window.Capacitor && window.Capacitor.isNativePlatform && window.Capacitor.isNativePlatform());

            // Di browser biasa perilakunya tidak berubah: dialog cetak
            // terbuka otomatis. Di dalam aplikasi Android hal itu justru
            // mengganggu — kasir mencetak ke printer termal, bukan ke
            // dialog cetak Android.
            if (! isNative) {
                window.addEventListener('load', () => window.print());
                return;
            }

            const printer = window.Capacitor.Plugins.CapacitorThermalPrinter;
            const panel = document.getElementById('bt-panel');
            const status = document.getElementById('bt-status');
            const list = document.getElementById('bt-devices');
            const button = document.getElementById('bt-print');

            if (! printer) {
                return;
            }

            panel.classList.remove('hidden');

            const data = JSON.parse(document.getElementById('receipt-payload').textContent);
            // 58mm memuat 32 karakter per baris pada font A, 80mm memuat 48.
            const COLS = data.paperWidth === 58 ? 32 : 48;
            const LAST_DEVICE_KEY = 'poskasir.printer';

            const say = (message) => { status.textContent = message; };

            /** Satu baris dua kolom: label di kiri, nilai rata kanan. */
            const pair = (left, right) => {
                const gap = COLS - left.length - right.length;
                return gap > 0 ? left + ' '.repeat(gap) + right : (left + ' ' + right);
            };

            async function printTo(address) {
                say('Menyambung ke printer…');
                await printer.connect({ address });

                say('Mencetak…');
                await printer.begin({});
                await printer.align({ alignment: 'center' });
                await printer.bold({ enabled: true });
                await printer.doubleHeight({ enabled: true });
                await printer.text({ text: data.store.name + '\n' });
                await printer.doubleHeight({ enabled: false });
                await printer.bold({ enabled: false });

                for (const key of ['address', 'phone']) {
                    if (data.store[key]) await printer.text({ text: data.store[key] + '\n' });
                }

                await printer.align({ alignment: 'left' });
                await printer.text({ text: '-'.repeat(COLS) + '\n' });

                for (const [label, value] of Object.entries(data.meta)) {
                    await printer.text({ text: pair(label, String(value)) + '\n' });
                }

                await printer.text({ text: '-'.repeat(COLS) + '\n' });

                for (const item of data.items) {
                    await printer.text({ text: item.name + '\n' });
                    await printer.text({ text: pair('  ' + item.qty + ' x ' + item.price, item.subtotal) + '\n' });
                }

                await printer.text({ text: '-'.repeat(COLS) + '\n' });

                for (const [label, value] of Object.entries(data.totals)) {
                    await printer.text({ text: pair(label, String(value)) + '\n' });
                }

                await printer.bold({ enabled: true });
                await printer.text({ text: pair('TOTAL', data.grandTotal) + '\n' });
                await printer.bold({ enabled: false });
                await printer.text({ text: '-'.repeat(COLS) + '\n' });

                for (const [label, value] of Object.entries(data.payment)) {
                    await printer.text({ text: pair(label, String(value)) + '\n' });
                }

                if (data.footer) {
                    await printer.text({ text: '\n' });
                    await printer.align({ alignment: 'center' });
                    await printer.text({ text: data.footer + '\n' });
                }

                await printer.text({ text: '\n\n\n' });
                await printer.write({});

                say('Struk terkirim ke printer.');
                localStorage.setItem(LAST_DEVICE_KEY, address);
            }

            function renderDevices(devices) {
                list.innerHTML = '';
                devices.forEach((device) => {
                    const item = document.createElement('button');
                    item.className = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-left text-xs';
                    item.textContent = (device.name || 'Tanpa nama') + ' — ' + device.address;
                    item.onclick = async () => {
                        try {
                            await printer.stopScan();
                            await printTo(device.address);
                            list.innerHTML = '';
                        } catch (error) {
                            say('Gagal: ' + (error && error.message ? error.message : error));
                        }
                    };
                    list.appendChild(item);
                });
            }

            button.onclick = async () => {
                list.innerHTML = '';
                try {
                    // Printer yang terakhir dipakai dicoba lebih dulu supaya
                    // kasir tidak perlu memilih perangkat tiap transaksi.
                    const last = localStorage.getItem(LAST_DEVICE_KEY);
                    if (last) {
                        try {
                            await printTo(last);
                            return;
                        } catch (error) {
                            say('Printer terakhir tidak terjangkau, memindai…');
                        }
                    }

                    say('Memindai printer…');
                    await printer.addListener('discoverDevices', ({ devices }) => renderDevices(devices));
                    await printer.addListener('discoveryFinish', () => {
                        if (! list.children.length) say('Tidak ada printer ditemukan. Pastikan printer menyala dan sudah dipasangkan di Pengaturan Bluetooth.');
                    });
                    await printer.startScan();
                } catch (error) {
                    say('Gagal: ' + (error && error.message ? error.message : error));
                }
            };
        })();
    </script>
</body>
</html>
