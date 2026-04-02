<header class="flex h-14 items-center justify-between border-b border-neutral-200 bg-white px-4 dark:border-neutral-800 dark:bg-neutral-900">
    {{-- Left: Calendar navigation --}}
    <div class="flex items-center gap-4">
        <div class="flex items-center gap-1">
            <button wire:click="previous" class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            </button>

            <button wire:click="goToToday" class="rounded-full p-1 text-neutral-300 hover:text-accent-500 dark:text-neutral-600 dark:hover:text-accent-400" title="Go to today">
                <svg class="size-1.5" viewBox="0 0 10 10" fill="currentColor"><circle cx="5" cy="5" r="5"/></svg>
            </button>

            <button wire:click="next" class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
            </button>

            <span class="ml-2 text-sm font-medium text-neutral-700 dark:text-neutral-300"
                  x-data="{ label: @js($dateLabel) }"
                  @calendar-label-update.window="label = $event.detail.label"
                  x-text="label"></span>
        </div>
    </div>

    {{-- Center: View switcher --}}
    <div class="flex items-center gap-2">
        <div class="flex items-center rounded-lg bg-neutral-100 p-0.5 dark:bg-neutral-800">
            @foreach (['day' => 'Day', 'week' => 'Week', 'month' => 'Month'] as $value => $label)
                <button wire:click="setView('{{ $value }}')"
                        class="rounded-md px-3 py-1 text-xs font-medium transition-colors {{ $currentView === $value ? 'bg-white text-neutral-900 shadow-sm dark:bg-neutral-700 dark:text-neutral-100' : 'text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @if ($currentView === 'week')
            <div class="flex items-center rounded-lg bg-neutral-100 p-0.5 dark:bg-neutral-800">
                @foreach ([3, 5, 7, 14] as $count)
                    <button wire:click="setWeekDaysCount({{ $count }})"
                            class="rounded-md px-2.5 py-1 text-xs font-medium transition-colors {{ $weekDaysCount === $count ? 'bg-white text-neutral-900 shadow-sm dark:bg-neutral-700 dark:text-neutral-100' : 'text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200' }}">
                        {{ $count }}d
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Right: Actions --}}
    <div class="flex items-center gap-2">
        <button x-data @click="Livewire.dispatch('auto-schedule')"
                class="inline-flex items-center gap-1.5 rounded-lg bg-accent-600 px-4 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-accent-700 transition-colors">
            <x-icons.sparkle class="size-3.5" />
            Auto-schedule
        </button>

        {{-- Event panel toggle --}}
        <button x-data @click="Livewire.dispatch('toggle-event-panel')"
                class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300"
                title="Toggle event panel">
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75h6.5v16.5h-6.5zM13.75 3.75h6.5v16.5h-6.5z" /></svg>
        </button>
    </div>
</header>
