@props(['monthGroups', 'events', 'taskBlocks', 'eventsByDate' => [], 'taskBlocksByDate' => [], 'anchorDate', 'selectedDate' => null, 'overrideDates' => []])

<div wire:key="cal-month-{{ $anchorDate }}"
     class="flex h-full flex-col"
     x-data="monthScroll()"
     x-init="init()">

    {{-- Day-of-week header --}}
    <div class="sticky top-0 z-20 grid grid-cols-7 border-b border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
            <div class="border-r border-neutral-100 py-2.5 text-center text-[11px] font-semibold uppercase tracking-wider last:border-r-0 dark:border-neutral-800/50
                        {{ in_array($dayName, ['Sat', 'Sun']) ? 'bg-neutral-200/50 text-neutral-300 dark:bg-neutral-950/40 dark:text-neutral-600' : 'text-neutral-400 dark:text-neutral-500' }}">
                {{ $dayName }}
            </div>
        @endforeach
    </div>

    {{-- Scrollable month groups --}}
    <div class="flex-1 overflow-y-auto"
         x-ref="scroller"
         @scroll.throttle.150ms="onScroll()">

        @foreach ($monthGroups as $group)
            <div wire:key="mg-{{ $group['key'] }}"
                 data-month-label="{{ $group['label'] }}"
                 @if ($group['weeks']->contains(fn ($w) => $w['days']->contains(fn ($d) => $d->format('Y-m-d') === $anchorDate))) data-anchor @endif
                 class="flex min-h-full flex-col border-t-2 border-neutral-200 first:border-t-0 dark:border-neutral-700">
                @foreach ($group['weeks'] as $weekData)
                    @php $week = $weekData['days']; $weekIndex = $weekData['index']; @endphp
                    <div wire:key="mw-{{ $weekIndex }}"
                         class="grid flex-1 grid-cols-7">
                        @foreach ($week as $monthDay)
                            @php
                                $isToday = $monthDay->isToday();
                                $isSelected = $selectedDate && $monthDay->format('Y-m-d') === $selectedDate;
                                $isFirstOfMonth = $monthDay->day === 1;
                                $dateKey = $monthDay->format('Y-m-d');
                                $dayEvents = collect($eventsByDate[$dateKey] ?? []);
                                $dayTasks = collect($taskBlocksByDate[$dateKey] ?? []);
                                $totalItems = $dayEvents->count() + $dayTasks->count();
                            @endphp
                            <div wire:key="md-{{ $monthDay->format('Y-m-d') }}"
                                 class="group/day border-b border-r border-neutral-100 p-2 last:border-r-0 dark:border-neutral-800/50
                                        {{ $isSelected ? 'bg-accent-100/60 dark:bg-accent-900/20' : ($isToday ? 'bg-accent-50/30 dark:bg-accent-950/10' : ($monthDay->isWeekend() ? 'bg-neutral-200/40 dark:bg-neutral-950/40' : '')) }}">

                                {{-- Date label --}}
                                <div class="mb-1 flex items-center justify-between">
                                    <div class="flex items-center gap-0.5">
                                        @if ($isFirstOfMonth)
                                            <span class="text-[11px] font-semibold text-neutral-400 dark:text-neutral-500">{{ $monthDay->format('M') }}</span>
                                        @endif
                                        <span class="inline-flex size-7 items-center justify-center rounded-full text-sm
                                                     {{ $isToday ? 'bg-accent-600 font-bold text-white' : ($isSelected ? 'bg-accent-200 font-semibold text-accent-800 dark:bg-accent-800 dark:text-accent-200' : 'font-medium text-neutral-800 dark:text-neutral-200') }}">
                                            {{ $monthDay->format('j') }}
                                        </span>
                                        @if (isset($overrideDates[$monthDay->format('Y-m-d')]))
                                            <span class="size-1 rounded-full bg-amber-400 dark:bg-amber-500" title="Custom schedule"></span>
                                        @endif
                                    </div>
                                    <button x-data
                                            @click.stop="Livewire.dispatch('openModal', { component: 'day-settings-modal', arguments: { date: '{{ $monthDay->format('Y-m-d') }}' } })"
                                            class="rounded p-0.5 text-neutral-300 opacity-0 transition-all hover:bg-neutral-100 hover:text-neutral-500 group-hover/day:opacity-100 dark:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-400"
                                            title="Day settings">
                                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                    </button>
                                </div>

                                {{-- Events & tasks --}}
                                <div class="space-y-0.5">
                                    @foreach ($dayEvents->take(3) as $event)
                                        <div wire:key="me-{{ $event->id }}"
                                             class="flex items-center gap-1 rounded px-1.5 py-0.5 text-[11px] leading-snug">
                                            <span class="size-1.5 shrink-0 rounded-full bg-neutral-400 dark:bg-neutral-500"></span>
                                            <span class="truncate font-medium text-neutral-700 dark:text-neutral-300">{{ $event->title }}</span>
                                        </div>
                                    @endforeach

                                    @foreach ($dayTasks->take(max(0, 3 - $dayEvents->count())) as $block)
                                        <div wire:key="mt-{{ $block->id }}"
                                             class="flex cursor-pointer items-center gap-1 rounded px-1.5 py-0.5 text-[11px] leading-snug"
                                             x-data @click="Livewire.dispatch('openModal', { component: 'task-detail-modal', arguments: { taskId: {{ $block->task_id }} } })">
                                            <span class="size-1.5 shrink-0 rounded-full bg-accent-400 dark:bg-accent-500"></span>
                                            <span class="truncate font-medium text-accent-700 dark:text-accent-300">{{ $block->task->title }}</span>
                                        </div>
                                    @endforeach

                                    @if ($totalItems > 3)
                                        <p class="pl-1 text-[10px] font-medium text-neutral-400 dark:text-neutral-500">
                                            +{{ $totalItems - 3 }} more
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
