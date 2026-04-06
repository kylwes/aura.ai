@props(['block', 'size' => 'sm'])

@php
    $tz = auth()->user()->timezone ?? 'UTC';
    $localStart = $block->scheduled_start->copy()->setTimezone($tz);
    $localEnd = $block->scheduled_end->copy()->setTimezone($tz);
    $durationMinutes = $localStart->diffInMinutes($localEnd);
    $heightPx = max(30, ($durationMinutes / 60) * 60);
    $topOffset = $localStart->minute;
    $project = $block->project;
    $color = $project->color ?? '#6366f1';
@endphp

<div wire:key="wpb-{{ $block->id }}-{{ $localStart->format('Y-m-d-Hi') }}"
     class="pointer-events-none absolute z-[1]"
     style="top: {{ $topOffset }}px; height: {{ $heightPx }}px; left: 0; right: 0;">

    {{-- Hover zone for label --}}
    <a href="{{ route('tasks') }}?project={{ $project->id }}"
       wire:navigate
       class="group/proj pointer-events-auto absolute left-0 top-0 bottom-0 w-3 cursor-pointer">
        <div class="absolute left-0 top-0 bottom-0 w-[2px] transition-all group-hover/proj:w-[3px]"
             style="background-color: {{ $color }}40;"></div>

        <div class="absolute left-0 -top-3.5 flex items-center gap-1 rounded-r-md px-1.5 py-0.5 opacity-0 transition-opacity group-hover/proj:opacity-100"
             style="background-color: {{ $color }}18;">
            <span class="size-1.5 shrink-0 rounded-full" style="background-color: {{ $color }};"></span>
            <p class="whitespace-nowrap text-[9px] font-semibold" style="color: {{ $color }};">{{ $project->title }}</p>
        </div>
    </a>
</div>
