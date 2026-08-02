<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>POS Kasir</title>

    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#e11d48">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <link rel="icon" href="/icons/icon-192.png">

    {{--
        Registered here, before Livewire's (synchronous, non-deferred)
        script loads and starts Alpine further down the page — alpine:init
        fires the moment Alpine boots, so if this listener were attached
        any later, Alpine would already have scanned the DOM and bound
        every x-show="!$store.connectivity.online" against a store that
        didn't exist yet, silently leaving it non-reactive forever after.
    --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('connectivity', { online: navigator.onLine });
        });
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="h-full font-sans antialiased text-slate-800">
    @auth
        <livewire:auth.verify-email-banner />
    @endauth

    @if (session('status'))
        <div class="bg-emerald-50 px-4 py-2 text-center text-sm text-emerald-700 ring-1 ring-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    @include('partials.connectivity-banner')

    {{ $slot }}

    @livewireScripts

    <script>
        window.addEventListener('online', () => Alpine.store('connectivity').online = true);
        window.addEventListener('offline', () => Alpine.store('connectivity').online = false);

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
    </script>
</body>
</html>
