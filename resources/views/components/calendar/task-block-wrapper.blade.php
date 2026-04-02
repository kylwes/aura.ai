@props(['block', 'task', 'size' => 'sm'])

@php
    $tz = auth()->user()->timezone ?? 'UTC';
    $localStart = $block->scheduled_start->copy()->setTimezone($tz);
    $localEnd = $block->scheduled_end->copy()->setTimezone($tz);
    $durationMinutes = $localStart->diffInMinutes($localEnd);
    $heightPx = max(30, ($durationMinutes / 60) * 60);
    $topOffset = $localStart->minute;
    $startMin = $localStart->hour * 60 + $localStart->minute;
    $endMin = $localEnd->hour * 60 + $localEnd->minute;
@endphp

<div wire:key="wt-{{ $block->id }}"
     data-item-type="task"
     data-item-id="{{ $task->id }}"
     data-block-id="{{ $block->id }}"
     data-item-date="{{ $localStart->format('Y-m-d') }}"
     data-item-start="{{ $startMin }}"
     data-item-end="{{ $endMin }}"
     data-item-title="{{ $task->title }}"
     class="absolute z-[5] cursor-pointer overflow-hidden {{ $size === 'sm' ? 'left-2 right-1' : 'left-2.5 right-2 max-w-xl' }}"
     style="top: {{ $topOffset }}px; height: {{ $heightPx }}px;">
    <x-task-block :task="$task" :block="$block" />
    <div class="resize-handle absolute inset-x-0 bottom-0 h-2 cursor-s-resize rounded-b-lg"></div>
</div>
