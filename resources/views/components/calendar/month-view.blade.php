@props(['monthGroups', 'events', 'taskBlocks', 'anchorDate', 'selectedDate' => null])

<div wire:key="cal-month-{{ $anchorDate }}"
     class="flex h-full flex-col"
     x-data="monthScroll()"
     x-init="init()">

    {{-- Day-of-week header --}}
    <div class="sticky top-0 z-20 grid grid-cols-7 border-b border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
            <div class="border-r border-neutral-100 py-2.5 text-center text-[11px] font-semibold uppercase tracking-wider last:border-r-0 dark:border-neutral-800/50
                        {{ in_array($dayName, ['Sat', 'Sun']) ? 'bg-neutral-100/40 text-neutral-300 dark:bg-neutral-800/30 dark:text-neutral-600' : 'text-neutral-400 dark:text-neutral-500' }}">
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
                                $userTz = auth()->user()->timezone ?? 'UTC';
                                $dayEvents = $events->filter(fn ($e) => $e->starts_at->copy()->setTimezone($userTz)->isSameDay($monthDay));
                                $dayTasks = $taskBlocks->filter(fn ($b) => $b->scheduled_start->copy()->setTimezone($userTz)->isSameDay($monthDay));
                                $totalItems = $dayEvents->count() + $dayTasks->count();
                            @endphp
                            <div wire:key="md-{{ $monthDay->format('Y-m-d') }}"
                                 class="border-b border-r border-neutral-100 p-2 last:border-r-0 dark:border-neutral-800/50
                                        {{ $isSelected ? 'bg-accent-100/60 dark:bg-accent-900/20' : ($isToday ? 'bg-accent-50/30 dark:bg-accent-950/10' : ($monthDay->isWeekend() ? 'bg-neutral-100/40 dark:bg-neutral-800/30' : '')) }}">

                                {{-- Date label --}}
                                <div class="mb-1">
                                    @if ($isFirstOfMonth)
                                        <span class="text-[11px] font-semibold text-neutral-400 dark:text-neutral-500">{{ $monthDay->format('M') }}</span>
                                    @endif
                                    <span class="inline-flex size-7 items-center justify-center rounded-full text-sm
                                                 {{ $isToday ? 'bg-accent-600 font-bold text-white' : ($isSelected ? 'bg-accent-200 font-semibold text-accent-800 dark:bg-accent-800 dark:text-accent-200' : 'font-medium text-neutral-800 dark:text-neutral-200') }}">
                                        {{ $monthDay->format('j') }}
                                    </span>
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
