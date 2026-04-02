@props(['task', 'approved' => false])

<div {{ $attributes->merge(['class' => 'rounded-lg bg-white p-4 ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800 transition-colors' . ($approved ? ' border-l-3 border-priority-low bg-green-50 dark:bg-green-950/20' : '')]) }}>
    <div class="flex items-center gap-4">
        <div class="flex items-center gap-2">
            @if ($task->integration)
                <x-source-icon :type="$task->integration->type" size="sm" />
            @endif
            <x-priority-badge :priority="$task->priority" />
        </div>

        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">{{ $task->title }}</p>
            <div class="mt-1 flex items-center gap-3 text-xs text-neutral-500 dark:text-neutral-400">
                @if ($task->scheduled_start)
                    <span>{{ $task->scheduled_start->format('D M j, H:i') }} – {{ $task->scheduled_end->format('H:i') }}</span>
                @endif
                @if ($task->estimated_duration)
                    <span class="rounded bg-neutral-100 px-1.5 py-0.5 dark:bg-neutral-800">{{ $task->formattedDuration() }}</span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button class="inline-flex items-center gap-1 rounded-lg border border-priority-low/30 px-3 py-1.5 text-xs font-medium text-priority-low hover:bg-green-50 dark:hover:bg-green-950/20">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                Approve
            </button>
            <button class="inline-flex items-center gap-1 rounded-lg border border-neutral-200 px-3 py-1.5 text-xs font-medium text-neutral-600 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                Reschedule
            </button>
            <button class="text-xs font-medium text-red-500 hover:text-red-600 dark:text-red-400">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>
</div>
