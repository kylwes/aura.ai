@props(['level'])

@php
    $level = min(3, max(1, (int) $level));
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5']) }} title="AI confidence: {{ $level }}/3">
    @for ($i = 1; $i <= 3; $i++)
        <span class="h-1.5 w-3 rounded-full {{ $i <= $level ? 'bg-accent-500' : 'bg-neutral-200 dark:bg-neutral-700' }}"></span>
    @endfor
</span>
