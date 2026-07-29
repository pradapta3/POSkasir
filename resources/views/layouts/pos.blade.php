<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>POS Kasir</title>
    {{--
        Tailwind CDN — no Node.js is installed on this machine so the Vite
        asset pipeline (@vite(...)) can't build resources/css/app.css. Once
        Node is available, run `npm install && npm run build` and swap this
        back for @vite(['resources/css/app.css', 'resources/js/app.js']).
    --}}
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
</head>
<body class="h-full font-sans antialiased text-slate-800">
    {{ $slot }}

    @livewireScripts
</body>
</html>
