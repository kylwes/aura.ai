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

</head>
<body class="flex h-screen flex-col bg-neutral-50 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100 antialiased {{ auth()->check() && app(\App\Settings\UserPreferences::class)->dark_mode ? 'dark' : '' }}">
    <x-app-header />

    <main class="flex flex-1 overflow-hidden">
        {{ $slot }}
    </main>

    <livewire:inbox-panel />
    <livewire:wire-elements-modal />
    <x-toast />
</body>
</html>
