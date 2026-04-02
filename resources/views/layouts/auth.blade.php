<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Aura' }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        (function () {
            const stored = localStorage.getItem('darkMode');
            const prefersDark = stored !== null
                ? stored === 'true'
                : window.matchMedia('(prefers-color-scheme: dark)').matches;

            if (prefersDark) document.documentElement.classList.add('dark');
        })();
    </script>
</head>
<body class="min-h-screen bg-neutral-50 dark:bg-neutral-950 antialiased">
    <div class="flex min-h-screen items-center justify-center px-4 py-12"
         style="background-image: radial-gradient(circle at 1px 1px, rgb(0 0 0 / 0.05) 1px, transparent 0); background-size: 24px 24px;">

        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <a href="/" class="inline-flex items-center gap-2 text-2xl font-bold text-neutral-900 dark:text-neutral-100">
                    <x-icons.sparkle class="size-8 text-accent-600" />
                    Aura
                </a>
            </div>

            <div class="rounded-xl bg-white p-8 shadow-lg ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
