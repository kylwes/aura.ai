@props(['event', 'size' => 'sm'])

@php
    $tz = auth()->user()->timezone ?? 'UTC';
    $localStart = $event->starts_at->copy()->setTimezone($tz);
    $localEnd = $event->ends_at->copy()->setTimezone($tz);
    $durationMinutes = $localStart->diffInMinutes($localEnd);
    $heightPx = max(26, ($durationMinutes / 60) * 60);
    $topOffset = $localStart->minute;
    $startMin = $localStart->hour * 60 + $localStart->minute;
    $endMin = $localEnd->hour * 60 + $localEnd->minute;
    $isCompact = $durationMinutes <= 30;
@endphp

<div wire:key="we-{{ $event->id }}"
     data-item-type="event"
     data-item-id="{{ $event->id }}"
     data-item-date="{{ $localStart->format('Y-m-d') }}"
     data-item-start="{{ $startMin }}"
     data-item-end="{{ $endMin }}"
     data-item-title="{{ $event->title }}"
     class="absolute z-[5] cursor-pointer overflow-hidden rounded-lg bg-neutral-200/80 shadow-sm transition-all hover:shadow-md hover:bg-neutral-300/80 dark:bg-neutral-700/80 dark:hover:bg-neutral-600/80
            {{ $size === 'sm' ? 'left-2 right-2' : 'left-2.5 right-2 max-w-xl' }}"
     style="top: {{ $topOffset }}px; height: {{ $heightPx }}px;">
    <div class="resize-handle resize-top absolute inset-x-0 top-0 h-2 cursor-n-resize rounded-t-lg"></div>

    <div class="flex h-full {{ $isCompact ? 'items-center gap-1.5 px-2.5' : 'flex-col justify-start px-2.5 py-1.5' }}">
        <p class="truncate font-semibold text-neutral-800 dark:text-neutral-100 {{ $isCompact ? 'text-xs' : ($size === 'sm' ? 'text-xs' : 'text-sm') }}">{{ $event->title }}</p>
        <p class="time-label shrink-0 {{ $isCompact ? 'text-[10px]' : ($size === 'sm' ? 'text-[10px]' : 'text-xs') }} text-neutral-500 dark:text-neutral-400">{{ $localStart->format('H:i') }}{{ !$isCompact ? ' – ' . $localEnd->format('H:i') : '' }}</p>
    </div>

    <div class="resize-handle resize-bottom absolute inset-x-0 bottom-0 h-2 cursor-s-resize rounded-b-lg"></div>
</div>
