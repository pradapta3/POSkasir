<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>POS Kasir</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
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

    {{ $slot }}

    @livewireScripts
</body>
</html>
