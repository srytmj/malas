<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Cegah FOUC dark mode: apply class "dark" sinkron sebelum first paint, sebelum CSS
             sempat kepasang. Kelas dark di useTheme.ts baru di-apply di useEffect (setelah paint
             pertama), jadi tanpa ini user dark-mode bakal kelihatan sekilas layar putih tiap full
             page load. Baca localStorage dulu (sumber kebenaran per useTheme.ts), fallback ke tema
             user dari server buat login pertama kali di device baru sebelum localStorage keisi. --}}
        <script>
            (function () {
                try {
                    var theme = localStorage.getItem('theme') || @json(auth()->user()->theme ?? 'system');
                    var isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                    if (isDark) document.documentElement.classList.add('dark');
                } catch (e) {}
            })();
        </script>

        <title inertia>{{ config('app.name', 'MALAS') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon/favicon.svg') }}" media="(prefers-color-scheme: light)">
        <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon/favicon-dark.svg') }}" media="(prefers-color-scheme: dark)">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon/favicon-32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon/favicon-16.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon/favicon-180.png') }}">
        <link rel="manifest" href="{{ asset('site.webmanifest') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/Pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
