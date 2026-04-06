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

<div wire:key="wt-{{ $block->id }}-{{ $localStart->format('Y-m-d-Hi') }}"
     data-item-type="task"
     data-item-id="{{ $task->id }}"
     data-block-id="{{ $block->id }}"
     data-item-date="{{ $localStart->format('Y-m-d') }}"
     data-item-start="{{ $startMin }}"
     data-item-end="{{ $endMin }}"
     data-item-title="{{ $task->title }}"
     class="group/task absolute z-[5] cursor-pointer overflow-visible {{ $size === 'sm' ? 'left-1.5 right-3' : 'left-2 right-4 max-w-xl' }}"
     style="top: {{ $topOffset }}px; height: {{ $heightPx }}px;">
    <x-task-block :task="$task" :block="$block" />
    <div class="resize-handle absolute inset-x-0 bottom-0 h-2 cursor-s-resize rounded-b-lg"></div>

    {{-- Quick actions on hover --}}
    <div class="pointer-events-none absolute -right-0.5 -top-0.5 flex gap-0.5 opacity-0 transition-opacity group-hover/task:pointer-events-auto group-hover/task:opacity-100">
        <button wire:click="completeTask({{ $task->id }})"
                @click.stop
                title="Complete"
                class="flex size-5 items-center justify-center rounded-full bg-white shadow ring-1 ring-neutral-200 transition-colors hover:bg-green-50 hover:text-green-600 dark:bg-neutral-800 dark:ring-neutral-700 dark:hover:bg-green-900/30 dark:hover:text-green-400">
            <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
        </button>
        <button wire:click="togglePin({{ $task->id }})"
                @click.stop
                title="{{ $task->is_pinned ? 'Unpin' : 'Pin' }}"
                class="flex size-5 items-center justify-center rounded-full bg-white shadow ring-1 ring-neutral-200 transition-colors hover:bg-amber-50 hover:text-amber-600 dark:bg-neutral-800 dark:ring-neutral-700 dark:hover:bg-amber-900/30 dark:hover:text-amber-400 {{ $task->is_pinned ? 'text-amber-500' : 'text-neutral-400' }}">
            <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
        </button>
    </div>
</div>
