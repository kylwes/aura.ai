<div class="relative" x-data>
    {{-- Toggle button --}}
    <button wire:click="toggle"
            class="rounded-md p-1.5 text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300"
            title="Undo timeline">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/></svg>
    </button>

    {{-- Dropdown panel --}}
    @if ($open)
        <div class="absolute right-0 top-full z-50 mt-1 w-80 rounded-xl bg-white p-3 shadow-lg ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800"
             @click.outside="$wire.toggle()">
            <h3 class="mb-2 text-xs font-semibold text-neutral-900 dark:text-neutral-100">Schedule History</h3>

            @if ($snapshots->isEmpty())
                <p class="py-4 text-center text-xs text-neutral-400 dark:text-neutral-500">No history yet</p>
            @else
                <div class="max-h-64 space-y-1 overflow-y-auto">
                    @foreach ($snapshots as $snapshot)
                        <div class="flex items-center justify-between rounded-lg px-2.5 py-2 hover:bg-neutral-50 dark:hover:bg-neutral-800/50">
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium text-neutral-700 dark:text-neutral-300">
                                    {{ str_replace('_', ' ', ucfirst($snapshot->trigger)) }}
                                </p>
                                <p class="text-[10px] text-neutral-400 dark:text-neutral-500">
                                    {{ $snapshot->created_at->diffForHumans() }} · {{ count($snapshot->task_states) }} tasks
                                </p>
                            </div>
                            <button wire:click="restore({{ $snapshot->id }})"
                                    wire:confirm="This will roll back to this snapshot. Continue?"
                                    class="shrink-0 rounded px-2 py-1 text-[10px] font-medium text-accent-600 hover:bg-accent-50 dark:text-accent-400 dark:hover:bg-accent-950/30">
                                Restore
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
