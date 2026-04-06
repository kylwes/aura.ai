@props(['days', 'hours', 'events', 'taskBlocks', 'projectBlocks', 'eventsByCell' => [], 'taskBlocksByCell' => [], 'projectBlocksByCell' => [], 'anchorDate', 'weekDaysCount' => 7, 'selectedDate' => null, 'overrideDates' => []])

<div wire:key="cal-week-{{ $anchorDate }}"
     class="relative flex h-full flex-col"
     x-data="weekScroll()"
     x-init="init()">

    {{-- Single scroll container for both axes --}}
    <div class="flex-1 overflow-auto scroll-pl-[60px]"
         x-ref="scroller"
         @scroll.debounce.300ms="onScroll()"
         x-init="$nextTick(() => { $el.style.setProperty('--col-width', (($el.clientWidth - 60) / {{ $weekDaysCount }}) + 'px') })"
         @resize.window="$el.style.setProperty('--col-width', (($el.clientWidth - 60) / {{ $weekDaysCount }}) + 'px')">

        <div x-ref="grid"
             style="min-width: calc(60px + {{ $days->count() }} * var(--col-width, {{ 100 / $weekDaysCount }}vw))">

            {{-- Day headers (sticky top) --}}
            <div class="sticky top-0 z-20 flex border-b border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
                {{-- Corner spacer (sticky both top and left) --}}
                <div class="sticky left-0 z-30 w-[60px] shrink-0 border-r border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900"></div>

                @foreach ($days as $dayIndex => $day)
                    @php
                        $isGroupStart = $dayIndex > 0 && $dayIndex % $weekDaysCount === 0;
                        $isSelected = $selectedDate && $day->format('Y-m-d') === $selectedDate;
                    @endphp
                    <div wire:key="wh-{{ $day->format('Y-m-d') }}"
                         data-date="{{ $day->format('Y-m-d') }}"
                         @if ($day->format('Y-m-d') === $anchorDate) data-anchor @endif
                         style="width: var(--col-width, {{ 100 / $weekDaysCount }}vw)"
                         class="group/header relative flex shrink-0 items-center justify-center gap-1.5 border-r border-neutral-200 py-2 last:border-r-0 dark:border-neutral-800
                                snap-start
                                {{ $isSelected ? 'bg-accent-100/80 dark:bg-accent-900/30' : ($day->isToday() ? 'bg-accent-50/50 dark:bg-accent-950/20' : ($day->isWeekend() ? 'bg-neutral-200/60 dark:bg-neutral-950/60' : '')) }}
                                {{ $isGroupStart ? 'border-l border-neutral-300 dark:border-neutral-700' : '' }}">
                        <span class="text-xs font-medium {{ $isSelected ? 'text-accent-600 dark:text-accent-400' : ($day->isWeekend() ? 'text-neutral-300 dark:text-neutral-600' : 'text-neutral-400 dark:text-neutral-500') }}">{{ $day->format('D') }}</span>
                        <span class="text-sm font-semibold {{ $isSelected ? 'text-accent-700 dark:text-accent-300' : ($day->isToday() ? 'text-accent-600 dark:text-accent-400' : ($day->isWeekend() ? 'text-neutral-400 dark:text-neutral-600' : 'text-neutral-900 dark:text-neutral-100')) }}">{{ $day->format('j') }}</span>
                        @if (isset($overrideDates[$day->format('Y-m-d')]))
                            <span class="size-1 rounded-full bg-amber-400 dark:bg-amber-500" title="Custom schedule"></span>
                        @endif
                        <button x-data
                                @click.stop="Livewire.dispatch('openModal', { component: 'day-settings-modal', arguments: { date: '{{ $day->format('Y-m-d') }}' } })"
                                class="absolute right-0.5 top-1/2 -translate-y-1/2 rounded p-0.5 text-neutral-300 opacity-0 transition-all hover:bg-neutral-100 hover:text-neutral-500 group-hover/header:opacity-100 dark:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-400"
                                title="Day settings">
                            <svg class="size-3.5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/></svg>
                        </button>
                    </div>
                @endforeach
            </div>

            {{-- Time grid --}}
            <div class="relative flex"
                 x-data="calendarDrag({ view: 'week' })"
                 @mousedown="onMouseDown($event)"
                 @mousemove.window="onMouseMove($event)"
                 @mouseup.window="onMouseUp($event)"
                 :class="{ 'select-none': mode }">

                {{-- Time gutter (sticky left, scrolls vertically with content) --}}
                <div class="sticky left-0 z-10 w-[60px] shrink-0 border-r border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
                    @foreach ($hours as $hour)
                        <div wire:key="gutter-{{ $hour }}" class="relative h-[60px]">
                            <span class="absolute -top-2.5 right-2 text-[10px] font-medium text-neutral-400 dark:text-neutral-500">
                                {{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00
                            </span>
                        </div>
                    @endforeach
                </div>

                {{-- Day columns --}}
                <div class="relative flex-1" x-ref="dayColumns">
                    {{-- Drag selection highlight --}}
                    <div x-show="showPreview" x-cloak
                         class="pointer-events-none absolute z-[10] flex rounded-md bg-accent-500/20 ring-1 ring-accent-500/40 dark:bg-accent-400/15 dark:ring-accent-400/30"
                         :class="previewHeight <= 15 ? 'items-center' : 'items-start'"
                         :style="'top:' + previewTop + 'px; height:' + previewHeight + 'px; left:' + previewLeft + 'px; width:' + previewWidth + 'px;'">
                        <p class="px-2 text-[10px] font-medium text-accent-700 dark:text-accent-300" :class="previewHeight > 15 ? 'pt-1' : ''" x-text="formatTimeRange()"></p>
                    </div>

                    @foreach ($hours as $hour)
                        <div wire:key="wr-{{ $hour }}" class="flex">
                            @foreach ($days as $dayIndex => $day)
                                @php
                                    $isGroupStart = $dayIndex > 0 && $dayIndex % $weekDaysCount === 0;
                                    $isSelected = $selectedDate && $day->format('Y-m-d') === $selectedDate;
                                @endphp
                                <div wire:key="wc-{{ $day->format('Y-m-d') }}-{{ $hour }}"
                                     data-date="{{ $day->format('Y-m-d') }}"
                                     data-hour="{{ $hour }}"
                                     style="width: var(--col-width, {{ 100 / $weekDaysCount }}vw)"
                                     class="relative h-[60px] shrink-0 border-b border-r border-neutral-100 last:border-r-0 dark:border-neutral-800/50
                                            {{ $isSelected ? 'bg-accent-50/50 dark:bg-accent-950/15' : ($day->isToday() ? 'bg-accent-50/20 dark:bg-accent-950/5' : ($day->isWeekend() ? 'bg-neutral-200/40 dark:bg-neutral-950/40' : '')) }}
                                            {{ $isGroupStart ? 'border-l border-neutral-200 dark:border-neutral-700' : '' }}">

                                    @php $cellKey = $day->format('Y-m-d') . '-' . $hour; @endphp

                                    @foreach ($projectBlocksByCell[$cellKey] ?? [] as $pBlock)
                                        <x-calendar.project-block :block="$pBlock" />
                                    @endforeach

                                    @foreach ($eventsByCell[$cellKey] ?? [] as $event)
                                        <x-calendar.event-block :$event />
                                    @endforeach

                                    @foreach ($taskBlocksByCell[$cellKey] ?? [] as $block)
                                        <x-calendar.task-block-wrapper :block="$block" :task="$block->task" />
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @endforeach

                </div>

                @if (now()->between($days->first()->startOfDay(), $days->last()->endOfDay()))
                    <x-calendar.now-indicator />
                @endif
            </div>
        </div>
    </div>
</div>
