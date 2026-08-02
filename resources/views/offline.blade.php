<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Offline — POS Kasir</title>
    {{--
        No Tailwind CDN, no external fonts/scripts — this page is served
        from the service worker's cache while genuinely offline (see
        public/sw.js), so anything loaded over the network here would just
        fail to render. Every style is inline on purpose.
    --}}
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            color: #1e293b;
            padding: 1.5rem;
        }
        .card {
            width: 100%;
            max-width: 24rem;
            background: #fff;
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .badge {
            width: 3.5rem;
            height: 3.5rem;
            margin: 0 auto 1rem;
            border-radius: 9999px;
            background: #ffe4e6;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .badge svg { width: 1.75rem; height: 1.75rem; stroke: #e11d48; }
        h1 { font-size: 1.125rem; font-weight: 700; margin-bottom: 0.5rem; }
        p { font-size: 0.875rem; color: #64748b; line-height: 1.5; margin-bottom: 1.5rem; }
        button {
            width: 100%;
            padding: 0.625rem 1rem;
            border: none;
            border-radius: 0.5rem;
            background: #e11d48;
            color: #fff;
            font-size: 0.875rem;
            font-weight: 700;
            cursor: pointer;
        }
        button:active { background: #be123c; }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18.364 5.636a9 9 0 010 12.728m0 0L5.636 5.636m12.728 12.728L5.636 18.364a9 9 0 010-12.728" />
            </svg>
        </div>
        <h1>Tidak Ada Koneksi Internet</h1>
        <p>POS Kasir butuh koneksi internet untuk memproses transaksi dan memuat data toko. Periksa koneksi WiFi atau data seluler kamu, lalu coba lagi.</p>
        <button onclick="window.location.reload()">Coba Lagi</button>
    </div>
</body>
</html>
