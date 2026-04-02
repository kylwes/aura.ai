<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 — Aura</title>
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
<body class="flex min-h-screen items-center justify-center bg-neutral-50 dark:bg-neutral-950">
    <div class="text-center">
        <p class="text-7xl font-bold text-neutral-200 dark:text-neutral-800">404</p>
        <h1 class="mt-4 text-lg font-semibold text-neutral-900 dark:text-neutral-100">Page not found</h1>
        <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">The page you're looking for doesn't exist.</p>
        <a href="/" class="mt-6 inline-flex items-center gap-1 text-sm font-medium text-accent-600 hover:text-accent-700 dark:text-accent-400">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            Back to calendar
        </a>
    </div>
</body>
</html>
