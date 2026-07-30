<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Masuk' }} — POS Kasir</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex h-full items-center justify-center bg-gradient-to-br from-rose-50 via-slate-50 to-slate-100 font-sans antialiased">
    <div class="w-full max-w-sm px-4">
        {{ $slot }}
    </div>

    @livewireScripts
</body>
</html>
