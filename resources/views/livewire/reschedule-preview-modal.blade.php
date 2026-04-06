<div>
    {{-- Header --}}
    <div class="flex items-center justify-between px-8 pt-6">
        <div class="flex items-center gap-2">
            <div class="h-6 w-1 rounded-full bg-amber-500"></div>
            <h2 class="text-xl font-semibold text-neutral-900 dark:text-neutral-100">Reschedule Preview</h2>
        </div>
        <button wire:click="$dispatch('closeModal')" class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300">
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
        </button>
    </div>

    {{-- Body --}}
    <div class="px-8 py-6">
        @if ($proposal?->trigger_description)
            <p class="mb-4 text-sm text-neutral-500 dark:text-neutral-400">{{ $proposal->trigger_description }}</p>
        @endif

        <div class="max-h-80 space-y-2 overflow-y-auto">
            @foreach ($changes as $change)
                @php
                    $task = auth()->user()->tasks()->find($change['task_id']);
                    $tz = auth()->user()->timezone ?? 'UTC';
                @endphp
                @if ($task)
                    <div class="flex items-center justify-between rounded-lg bg-neutral-50 px-3 py-2.5 dark:bg-neutral-800/50">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ $task->title }}</p>
                            <div class="mt-0.5 flex items-center gap-2 text-xs text-neutral-500 dark:text-neutral-400">
                                @if ($change['old_start'])
                                    <span>{{ \Carbon\Carbon::parse($change['old_start'])->setTimezone($tz)->format('H:i') }}</span>
                                    <svg class="size-3 text-neutral-300 dark:text-neutral-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                @endif
                                <span class="font-medium text-accent-600 dark:text-accent-400">{{ \Carbon\Carbon::parse($change['new_start'])->setTimezone($tz)->format('D H:i') }} - {{ \Carbon\Carbon::parse($change['new_end'])->setTimezone($tz)->format('H:i') }}</span>
                            </div>
                        </div>
                        <span class="ml-3 shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium {{ $change['action'] === 'move' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' }}">
                            {{ $change['action'] === 'move' ? 'Moved' : 'New' }}
                        </span>
                    </div>
                @endif
            @endforeach
        </div>

        @if (empty($changes))
            <p class="py-4 text-center text-sm text-neutral-400 dark:text-neutral-500">No changes proposed</p>
        @endif
    </div>

    {{-- Footer --}}
    <div class="flex items-center justify-end gap-3 border-t border-neutral-200 px-8 py-4 dark:border-neutral-800">
        <button wire:click="reject" class="px-4 py-2 text-sm font-medium text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-neutral-200">
            Reject
        </button>
        <button wire:click="accept" class="rounded-lg bg-accent-600 px-5 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-accent-700">
            Accept changes
        </button>
    </div>
</div>
