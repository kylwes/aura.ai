<header class="flex h-12 items-center justify-between border-b border-neutral-200 bg-white px-4 dark:border-neutral-800 dark:bg-neutral-900">
    {{-- Left: Navigation + Date label --}}
    <div class="flex items-center gap-3">
        <div class="flex items-center gap-0.5">
            <button wire:click="previous" class="rounded-md p-1 text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            </button>
            <button wire:click="goToToday" class="rounded-md px-2 py-0.5 text-[11px] font-medium text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-neutral-600 dark:text-neutral-500 dark:hover:bg-neutral-800 dark:hover:text-neutral-300" title="Go to today">
                Today
            </button>
            <button wire:click="next" class="rounded-md p-1 text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
            </button>
        </div>

        <span class="text-sm font-semibold text-neutral-900 dark:text-neutral-100"
              x-data="{ label: @js($dateLabel) }"
              @calendar-label-update.window="label = $event.detail.label"
              x-text="label"></span>
    </div>

    {{-- Center: View switcher + day count --}}
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
            <div class="flex items-center gap-px text-[11px] font-medium text-neutral-400 dark:text-neutral-500">
                @foreach ([3, 5, 7, 14] as $count)
                    <button wire:click="setWeekDaysCount({{ $count }})"
                            class="rounded px-1.5 py-0.5 transition-colors {{ $weekDaysCount === $count ? 'text-neutral-900 dark:text-neutral-100' : 'hover:text-neutral-600 dark:hover:text-neutral-300' }}">
                        {{ $count }}d
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Right: Summary + Actions --}}
    <div class="flex items-center gap-3">
        {{-- Today's summary --}}
        @php
            $formatTime = fn ($min) => $min >= 60
                ? intdiv($min, 60) . 'h' . ($min % 60 > 0 ? ' ' . ($min % 60) . 'm' : '')
                : $min . 'm';
        @endphp

        <div class="hidden items-center gap-3 text-[11px] text-neutral-400 dark:text-neutral-500 lg:flex">
            <span>{{ $formatTime($scheduledMinutes) }} planned</span>
            @if ($availableMinutes > 0)
                <span>{{ $formatTime($freeMinutes) }} free</span>
            @endif
        </div>

        <div class="flex items-center gap-1">
            <livewire:undo-timeline />

            <button x-data @click="Livewire.dispatch('toggle-event-panel')"
                    class="rounded-md p-1.5 text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300"
                    title="Toggle event panel">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75h6.5v16.5h-6.5zM13.75 3.75h6.5v16.5h-6.5z" /></svg>
            </button>
        </div>

        <button x-data @click="Livewire.dispatch('auto-schedule')"
                class="inline-flex items-center gap-1.5 rounded-lg bg-accent-600 px-3.5 py-1.5 text-xs font-medium text-white shadow-sm transition-colors hover:bg-accent-700">
            <x-icons.sparkle class="size-3.5" />
            Schedule
        </button>
    </div>
</header>
