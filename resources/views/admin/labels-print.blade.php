<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Label Barcode</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        @media print {
            @page { margin: 8mm; }
            .no-print { display: none !important; }
        }
        .label {
            width: 50mm;
            height: 30mm;
            break-inside: avoid;
        }
    </style>
</head>
<body class="bg-slate-100 p-6 text-slate-900">
    <div class="no-print mb-4 flex items-center justify-between">
        <p class="text-sm text-slate-500">{{ collect($items)->sum('qty') }} label akan dicetak.</p>
        <div class="flex gap-2">
            <button onclick="window.print()" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-bold text-white">Cetak</button>
            <button onclick="window.close()" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600">Tutup</button>
        </div>
    </div>

    <div class="flex flex-wrap gap-2 bg-white p-2">
        @foreach ($items as $item)
            @php $barcodeValue = $item['barcode'] ?: $item['sku']; @endphp
            @for ($i = 0; $i < $item['qty']; $i++)
                <div class="label flex flex-col items-center justify-center overflow-hidden border border-dashed border-slate-300 px-1 text-center">
                    <p class="w-full truncate text-[9px] font-semibold leading-tight">{{ $item['name'] }}</p>
                    <svg class="barcode" data-barcode="{{ $barcodeValue }}"></svg>
                    <p class="text-[9px] font-medium">Rp {{ number_format((float) $item['price'], 0, ',', '.') }}</p>
                </div>
            @endfor
        @endforeach
    </div>

    <script>
        window.addEventListener('load', () => {
            document.querySelectorAll('.barcode').forEach((el) => {
                JsBarcode(el, el.dataset.barcode, {
                    format: 'CODE128',
                    width: 1.3,
                    height: 28,
                    fontSize: 10,
                    margin: 0,
                    displayValue: true,
                });
            });
            window.print();
        });
    </script>
</body>
</html>
