<div>
    {{-- Header --}}
    <div class="flex items-center justify-between px-8 pt-6">
        <div class="flex items-center gap-2">
            <div class="h-6 w-1 rounded-full bg-violet-500"></div>
            <h2 class="text-xl font-semibold text-neutral-900 dark:text-neutral-100">What If...</h2>
        </div>
        <button wire:click="$dispatch('closeModal')" class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300">
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
        </button>
    </div>

    {{-- Body --}}
    <div class="space-y-5 px-8 py-6">
        @if (! $hasResults)
            {{-- Scenario selection --}}
            <div>
                <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Scenario</label>
                <div class="mt-2 flex items-center gap-2">
                    <button wire:click="$set('scenarioType', 'day_off')" type="button"
                            class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition-colors
                                {{ $scenarioType === 'day_off' ? 'border-neutral-300 bg-neutral-100 text-neutral-900 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100' : 'border-transparent text-neutral-500 hover:bg-neutral-50 dark:text-neutral-400 dark:hover:bg-neutral-800' }}">
                        Take a day off
                    </button>
                    <button wire:click="$set('scenarioType', 'change_hours')" type="button"
                            class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition-colors
                                {{ $scenarioType === 'change_hours' ? 'border-neutral-300 bg-neutral-100 text-neutral-900 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100' : 'border-transparent text-neutral-500 hover:bg-neutral-50 dark:text-neutral-400 dark:hover:bg-neutral-800' }}">
                        Change work hours
                    </button>
                </div>
            </div>

            @if ($scenarioType === 'day_off')
                <div>
                    <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Date</label>
                    <input type="date" wire:model="dayOffDate"
                           class="mt-1 w-full rounded-lg border-0 bg-neutral-100 px-3 py-2.5 text-sm font-medium text-neutral-900 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-800 dark:text-neutral-100">
                </div>
            @elseif ($scenarioType === 'change_hours')
                <div>
                    <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Date</label>
                    <input type="date" wire:model="changeDate"
                           class="mt-1 w-full rounded-lg border-0 bg-neutral-100 px-3 py-2.5 text-sm font-medium text-neutral-900 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-800 dark:text-neutral-100">
                </div>
                <div class="flex items-start gap-6">
                    <div class="flex-1">
                        <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Start</label>
                        <input type="time" wire:model="changeStart"
                               class="mt-1 w-full rounded-lg border-0 bg-neutral-100 px-3 py-2.5 text-sm font-medium text-neutral-900 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-800 dark:text-neutral-100">
                    </div>
                    <div class="flex-1">
                        <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">End</label>
                        <input type="time" wire:model="changeEnd"
                               class="mt-1 w-full rounded-lg border-0 bg-neutral-100 px-3 py-2.5 text-sm font-medium text-neutral-900 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-800 dark:text-neutral-100">
                    </div>
                </div>
            @endif
        @else
            {{-- Results --}}
            <div>
                <p class="mb-3 text-sm text-neutral-500 dark:text-neutral-400">
                    {{ count($simulatedSchedule) }} task(s) would be affected
                </p>

                @if (empty($simulatedSchedule))
                    <div class="rounded-lg bg-green-50 px-4 py-3 dark:bg-green-950/20">
                        <p class="text-sm text-green-700 dark:text-green-400">No tasks would be affected by this change.</p>
                    </div>
                @else
                    <div class="max-h-72 space-y-2 overflow-y-auto">
                        @foreach ($simulatedSchedule as $change)
                            <div class="flex items-center justify-between rounded-lg bg-neutral-50 px-3 py-2.5 dark:bg-neutral-800/50">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ $change['title'] }}</p>
                                    <div class="mt-0.5 flex items-center gap-2 text-xs text-neutral-500 dark:text-neutral-400">
                                        @if ($change['old_start'])
                                            <span>{{ $change['old_start'] }}</span>
                                            <svg class="size-3 text-neutral-300 dark:text-neutral-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                        @endif
                                        <span class="font-medium text-accent-600 dark:text-accent-400">{{ $change['new_start'] }} - {{ $change['new_end'] }}</span>
                                    </div>
                                </div>
                                <span class="ml-3 shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium {{ $change['action'] === 'move' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' }}">
                                    {{ $change['action'] === 'move' ? 'Moved' : 'New' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Footer --}}
    <div class="flex items-center justify-between border-t border-neutral-200 px-8 py-4 dark:border-neutral-800">
        <div>
            @if ($hasResults)
                <button wire:click="$set('hasResults', false)" class="text-xs font-medium text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">
                    &larr; Back
                </button>
            @endif
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="$dispatch('closeModal')" class="px-4 py-2 text-sm font-medium text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-neutral-200">
                Cancel
            </button>
            @if (! $hasResults)
                <button wire:click="simulate" wire:loading.attr="disabled" wire:target="simulate"
                        class="rounded-lg bg-violet-600 px-5 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-violet-700 disabled:opacity-60">
                    <span wire:loading.remove wire:target="simulate">Simulate</span>
                    <span wire:loading wire:target="simulate">Simulating...</span>
                </button>
            @else
                <button wire:click="apply" wire:loading.attr="disabled" wire:target="apply"
                        class="rounded-lg bg-accent-600 px-5 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-accent-700 disabled:opacity-60">
                    <span wire:loading.remove wire:target="apply">Apply scenario</span>
                    <span wire:loading wire:target="apply">Applying...</span>
                </button>
            @endif
        </div>
    </div>
</div>
