<div>
    @if ($staleTasks->isNotEmpty())
        <div class="space-y-2 px-4 py-2">
            @foreach ($staleTasks as $task)
                <div class="flex items-center justify-between rounded-lg bg-amber-50 px-3 py-2 ring-1 ring-amber-200 dark:bg-amber-950/20 dark:ring-amber-800">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-medium text-amber-900 dark:text-amber-200">{{ $task->title }}</p>
                        <p class="text-[10px] text-amber-600 dark:text-amber-400">Rescheduled {{ $task->reschedule_count }}x</p>
                    </div>
                    <div class="ml-3 flex shrink-0 items-center gap-1.5">
                        <button wire:click="escalate({{ $task->id }})" class="rounded px-2 py-1 text-[10px] font-medium text-amber-700 hover:bg-amber-100 dark:text-amber-300 dark:hover:bg-amber-900/40" title="Set to urgent priority">Escalate</button>
                        <button wire:click="dismiss({{ $task->id }})" class="rounded px-2 py-1 text-[10px] font-medium text-neutral-500 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800" title="Dismiss task">Dismiss</button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
