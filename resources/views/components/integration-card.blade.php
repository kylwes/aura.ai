@props(['type', 'status' => null, 'integration' => null])

@php
    $isConnected = $status === \App\Enums\IntegrationStatus::Connected || $status === \App\Enums\IntegrationStatus::Paused;
    $isPaused = $status === \App\Enums\IntegrationStatus::Paused;
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl bg-white p-6 ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800 text-center' . (! $isConnected ? ' opacity-60' : '')]) }}>
    <div class="mx-auto mb-3 flex size-12 items-center justify-center">
        <x-dynamic-component :component="$type->iconComponent()" class="size-8" />
    </div>

    <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">{{ $type->label() }}</h3>

    <div class="mt-2 flex items-center justify-center gap-1.5">
        <span class="size-2 rounded-full {{ $isConnected ? ($isPaused ? 'bg-priority-high' : 'bg-priority-low') : 'bg-neutral-300 dark:bg-neutral-600' }}"></span>
        <span class="text-xs text-neutral-500 dark:text-neutral-400">
            {{ $isConnected ? ($isPaused ? 'Paused' : 'Connected') : 'Disconnected' }}
        </span>
    </div>

    <div class="mt-4">
        @if ($isConnected)
            <label class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" class="peer sr-only" {{ ! $isPaused ? 'checked' : '' }}>
                <div class="h-5 w-9 rounded-full bg-neutral-200 after:absolute after:left-[2px] after:top-[2px] after:size-4 after:rounded-full after:bg-white after:transition-all peer-checked:bg-accent-600 peer-checked:after:translate-x-full dark:bg-neutral-700"></div>
            </label>
            <div class="mt-2">
                <button class="text-xs font-medium text-accent-600 hover:text-accent-700 dark:text-accent-400">Configure</button>
            </div>
        @else
            @if ($type === \App\Enums\IntegrationType::GoogleCalendar)
                <a href="{{ route('google.redirect') }}"
                   class="rounded-lg border border-accent-300 px-4 py-1.5 text-xs font-medium text-accent-600 hover:bg-accent-50 dark:border-accent-700 dark:text-accent-400 dark:hover:bg-accent-950/30">
                    Connect
                </a>
            @else
                <button class="rounded-lg border border-accent-300 px-4 py-1.5 text-xs font-medium text-accent-600 hover:bg-accent-50 dark:border-accent-700 dark:text-accent-400 dark:hover:bg-accent-950/30">
                    Connect
                </button>
            @endif
        @endif
    </div>
</div>
