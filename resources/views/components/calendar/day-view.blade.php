@props(['day', 'hours', 'events', 'taskBlocks', 'projectBlocks', 'eventsByCell' => [], 'taskBlocksByCell' => [], 'projectBlocksByCell' => [], 'overrideDates' => []])

<div class="h-full overflow-auto"
     x-data
     x-init="$nextTick(() => { $el.scrollTop = 8 * 60 })">
    {{-- Day header --}}
    <div class="sticky top-0 z-10 grid grid-cols-[60px_1fr] border-b border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <div class="border-r border-neutral-200 dark:border-neutral-800"></div>
        <div class="flex items-center justify-between px-4 py-3 {{ $day->isToday() ? 'bg-accent-50/50 dark:bg-accent-950/20' : '' }}">
            <div>
                <p class="text-xs font-medium text-neutral-400 dark:text-neutral-500">{{ $day->format('l') }}</p>
                <p class="mt-0.5 flex items-center gap-1.5 text-lg font-semibold {{ $day->isToday() ? 'text-accent-600 dark:text-accent-400' : 'text-neutral-900 dark:text-neutral-100' }}">
                    {{ $day->format('F j, Y') }}
                    @if (isset($overrideDates[$day->format('Y-m-d')]))
                        <span class="size-1.5 rounded-full bg-amber-400 dark:bg-amber-500" title="Custom schedule"></span>
                    @endif
                </p>
            </div>
            <button x-data
                    @click="Livewire.dispatch('openModal', { component: 'day-settings-modal', arguments: { date: '{{ $day->format('Y-m-d') }}' } })"
                    class="rounded-lg p-1.5 text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300"
                    title="Day settings">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
            </button>
        </div>
    </div>

    {{-- Time grid --}}
    <div class="relative grid grid-cols-[60px_1fr]"
         x-data="calendarDrag({ view: 'day' })"
         x-ref="gridContent"
         @mousedown="onMouseDown($event)"
         @mousemove.window="onMouseMove($event)"
         @mouseup.window="onMouseUp($event)"
         :class="{ 'select-none': mode }">

        {{-- Hour labels --}}
        @foreach ($hours as $hour)
            <div wire:key="day-hour-{{ $hour }}"
                 class="relative border-r border-neutral-200 dark:border-neutral-800" style="grid-row: {{ $loop->iteration }};">
                <span class="absolute -top-2.5 right-2 text-[10px] font-medium text-neutral-400 dark:text-neutral-500">
                    {{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00
                </span>
            </div>

            {{-- Hour cell --}}
            <div wire:key="day-cell-{{ $hour }}"
                 data-date="{{ $day->format('Y-m-d') }}"
                 data-hour="{{ $hour }}"
                 class="relative min-h-[60px] border-b border-neutral-100 dark:border-neutral-800/50 {{ $day->isToday() ? 'bg-accent-50/30 dark:bg-accent-950/10' : '' }}"
                 style="grid-row: {{ $loop->iteration }};">

                @php $cellKey = $day->format('Y-m-d') . '-' . $hour; @endphp

                @foreach ($projectBlocksByCell[$cellKey] ?? [] as $pBlock)
                    <x-calendar.project-block :block="$pBlock" size="md" />
                @endforeach

                @foreach ($eventsByCell[$cellKey] ?? [] as $event)
                    <x-calendar.event-block :$event size="md" />
                @endforeach

                @foreach ($taskBlocksByCell[$cellKey] ?? [] as $block)
                    <x-calendar.task-block-wrapper :block="$block" :task="$block->task" size="md" />
                @endforeach
            </div>
        @endforeach

        {{-- Drag selection highlight --}}
        <div x-show="showPreview" x-cloak
             class="pointer-events-none absolute right-0 left-[60px] z-[10] flex rounded-md bg-accent-500/20 ring-1 ring-accent-500/40 dark:bg-accent-400/15 dark:ring-accent-400/30"
             :class="previewHeight <= 15 ? 'items-center' : 'items-start'"
             :style="'top:' + previewTop + 'px; height:' + previewHeight + 'px;'">
            <p class="px-2 text-[10px] font-medium text-accent-700 dark:text-accent-300" :class="previewHeight > 15 ? 'pt-1' : ''" x-text="formatTimeRange()"></p>
        </div>

        @if ($day->isToday())
            <div class="pointer-events-none absolute left-0 right-0 z-[15]"
                 x-data="{ top: 0, timeLabel: '' }"
                 x-init="
                    const updatePosition = () => {
                        const now = new Date();
                        top = (now.getHours() * 60) + now.getMinutes();
                        const h = String(now.getHours()).padStart(2, '0');
                        const m = String(now.getMinutes()).padStart(2, '0');
                        timeLabel = h + ':' + m;
                    };
                    updatePosition();
                    setInterval(updatePosition, 60000);
                 "
                 :style="'top: ' + top + 'px'">
                <div class="flex items-center">
                    <div class="w-[60px] pr-1.5 text-right">
                        <span class="text-[10px] font-medium text-red-400/80 dark:text-red-500/70" x-text="timeLabel"></span>
                    </div>
                    <div class="h-px flex-1 bg-red-400/40 dark:bg-red-500/30"></div>
                </div>
            </div>
        @endif
    </div>
</div>
