<div class="flex h-full w-full flex-col">
    {{-- Content --}}
    <div class="flex-1 overflow-auto">
        <div class="mx-auto max-w-[90rem] px-6 py-4">
            @php
                $tz = auth()->user()->timezone ?? 'UTC';
                $formatTime = fn($min) => $min >= 60
                    ? intdiv($min, 60) . 'h' . ($min % 60 > 0 ? ' ' . ($min % 60) . 'm' : '')
                    : $min . 'm';
            @endphp

            <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-3">
                {{-- Left column --}}
                <div class="space-y-4">
                    {{-- Today's agenda --}}
                    <div class="rounded-2xl bg-white px-6 py-7 shadow-sm shadow-neutral-200/50 dark:bg-neutral-900 dark:shadow-neutral-950/30">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-xs font-semibold uppercase tracking-wide text-neutral-900 dark:text-neutral-100">Today</h2>
                            <span class="text-[11px] text-neutral-400 dark:text-neutral-500">
                                {{ $formatTime($scheduledMinutes) }} planned · {{ $formatTime($freeMinutes) }} free
                            </span>
                        </div>

                        @if ($todayBlocks->isEmpty() && $todayEvents->isEmpty())
                            <div class="py-4 text-center">
                                <p class="text-xs text-neutral-400 dark:text-neutral-500">Nothing scheduled for today</p>
                            </div>
                        @else
                            <div class="-mx-2 space-y-0.5">
                                @php
                                    $timeline = collect();
                                    foreach ($todayEvents as $event) {
                                        $timeline->push([
                                            'type' => 'event',
                                            'start' => $event->starts_at->copy()->setTimezone($tz),
                                            'end' => $event->ends_at->copy()->setTimezone($tz),
                                            'title' => $event->title,
                                            'item' => $event,
                                        ]);
                                    }
                                    foreach ($todayBlocks as $block) {
                                        $timeline->push([
                                            'type' => 'task',
                                            'start' => $block->scheduled_start->copy()->setTimezone($tz),
                                            'end' => $block->scheduled_end->copy()->setTimezone($tz),
                                            'title' => $block->task->title,
                                            'item' => $block,
                                        ]);
                                    }
                                    $timeline = $timeline->sortBy('start');
                                    $nowLocal = now()->setTimezone($tz);
                                @endphp

                                @foreach ($timeline as $entry)
                                    @php
                                        $isPast = $entry['end']->lt($nowLocal);
                                        $isCurrent = $entry['start']->lte($nowLocal) && $entry['end']->gte($nowLocal);
                                    @endphp
                                    <div class="flex items-center gap-3 rounded-lg px-2.5 py-2 {{ $isCurrent ? 'bg-accent-50/60 dark:bg-accent-950/15' : ($isPast ? 'opacity-40' : '') }}">
                                        <span class="w-11 shrink-0 text-right text-xs tabular-nums {{ $isCurrent ? 'font-semibold text-accent-600 dark:text-accent-400' : 'text-neutral-400 dark:text-neutral-500' }}">
                                            {{ $entry['start']->format('H:i') }}
                                        </span>
                                        @if ($entry['type'] === 'event')
                                            <span class="size-2 shrink-0 rounded-full bg-neutral-300 dark:bg-neutral-600"></span>
                                        @else
                                            @php $task = $entry['item']->task; @endphp
                                            <span class="size-2 shrink-0 rounded-full" style="background-color: {{ $task->project?->color ?? '#a3a3a3' }};"></span>
                                        @endif
                                        <p class="min-w-0 flex-1 truncate text-sm {{ $isCurrent ? 'font-semibold text-neutral-900 dark:text-neutral-100' : ($isPast ? 'text-neutral-500 dark:text-neutral-400' : 'font-medium text-neutral-800 dark:text-neutral-200') }}">
                                            {{ $entry['title'] }}
                                        </p>
                                        <span class="shrink-0 text-[11px] tabular-nums text-neutral-400 dark:text-neutral-500">
                                            {{ (int) $entry['start']->diffInMinutes($entry['end']) }}m
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Inbox --}}
                    <div class="rounded-2xl bg-white px-6 py-7 shadow-sm shadow-neutral-200/50 dark:bg-neutral-900 dark:shadow-neutral-950/30">
                        <div class="mb-3 flex items-center gap-2">
                            <h2 class="text-xs font-semibold uppercase tracking-wide text-neutral-900 dark:text-neutral-100">Inbox</h2>
                            @if ($inboxCount > 0)
                                <span class="rounded-full bg-accent-100 px-1.5 py-0.5 text-[11px] font-medium text-accent-700 dark:bg-accent-900/30 dark:text-accent-400">{{ $inboxCount }}</span>
                            @endif
                        </div>

                        @if ($inboxItems->isEmpty())
                            <div class="py-4 text-center">
                                <p class="text-xs text-neutral-400 dark:text-neutral-500">All caught up</p>
                            </div>
                        @else
                            <div class="-mx-2 space-y-0.5">
                                @foreach ($inboxItems as $item)
                                    <div class="group flex items-start gap-3 rounded-lg px-2.5 py-2 transition-colors hover:bg-neutral-50/60 dark:hover:bg-neutral-800/30">
                                        @if ($item->integration)
                                            <x-source-icon :type="$item->integration->type" size="sm" class="mt-0.5 shrink-0" />
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <p class="line-clamp-1 text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ $item->preview_text }}</p>
                                            <div class="mt-0.5 flex items-center gap-2">
                                                @if ($item->ai_suggested_priority)
                                                    <x-priority-badge :priority="\App\Enums\TaskPriority::from($item->ai_suggested_priority)" class="text-[11px]" />
                                                @endif
                                                @if ($item->ai_estimated_duration)
                                                    @php $dur = $item->ai_estimated_duration; @endphp
                                                    <span class="text-[11px] text-neutral-400 dark:text-neutral-500">~{{ $dur >= 60 ? intdiv($dur, 60) . 'h' : $dur . 'm' }}</span>
                                                @endif
                                                @if ($item->suggestedProject)
                                                    <span class="inline-flex items-center gap-1 text-[11px] font-medium" style="color: {{ $item->suggestedProject->color }};">
                                                        <span class="size-1.5 rounded-full" style="background-color: {{ $item->suggestedProject->color }};"></span>
                                                        {{ $item->suggestedProject->title }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-0.5 opacity-0 transition-opacity group-hover:opacity-100">
                                            <button wire:click="acceptInboxItem({{ $item->id }})" title="Accept"
                                                    class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-accent-600 transition-colors hover:bg-accent-50 dark:text-accent-400 dark:hover:bg-accent-900/20">
                                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                                Accept
                                            </button>
                                            <button wire:click="dismissInboxItem({{ $item->id }})" title="Dismiss"
                                                    class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs text-neutral-400 transition-colors hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20 dark:hover:text-red-400">
                                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Middle column --}}
                <div class="space-y-4">
                    {{-- Overdue --}}
                    @if ($overdueTasks->isNotEmpty())
                        <div class="rounded-2xl bg-white px-6 py-7 shadow-sm shadow-neutral-200/50 dark:bg-neutral-900 dark:shadow-neutral-950/30">
                            <div class="mb-3 flex items-center gap-2">
                                <span class="size-2 rounded-full bg-priority-urgent"></span>
                                <h2 class="text-xs font-semibold uppercase tracking-wide text-neutral-900 dark:text-neutral-100">Overdue</h2>
                                <span class="rounded-full bg-red-100 px-1.5 py-0.5 text-[11px] font-medium text-red-600 dark:bg-red-900/30 dark:text-red-400">{{ $overdueTasks->count() }}</span>
                            </div>

                            <div class="space-y-1">
                                @foreach ($overdueTasks as $task)
                                    <div class="group flex items-center gap-3 rounded-lg px-2.5 py-2 transition-colors hover:bg-red-50/40 dark:hover:bg-red-950/10">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <p class="min-w-0 flex-1 truncate text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ $task->title }}</p>
                                                <span class="shrink-0 text-[11px] text-red-400 dark:text-red-500">{{ $task->scheduled_start->copy()->setTimezone($tz)->format('M j, g:ia') }}</span>
                                            </div>
                                            @if ($task->project)
                                                <div class="mt-0.5">
                                                    <span class="inline-flex items-center gap-1 text-[11px] font-medium" style="color: {{ $task->project->color }};">
                                                        <span class="size-2 rounded-full" style="background-color: {{ $task->project->color }};"></span>
                                                        {{ $task->project->title }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="flex shrink-0 items-center gap-0.5 opacity-0 transition-opacity group-hover:opacity-100">
                                            <button wire:click="completeTask({{ $task->id }})" title="Mark done"
                                                    class="rounded-lg p-1.5 text-green-600 transition-colors hover:bg-green-100/60 dark:text-green-400 dark:hover:bg-green-900/20">
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                            </button>
                                            <button wire:click="rescheduleTask({{ $task->id }})" title="Reschedule"
                                                    class="rounded-lg p-1.5 text-accent-600 transition-colors hover:bg-accent-50 dark:text-accent-400 dark:hover:bg-accent-900/20">
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                            </button>
                                            <button wire:click="dismissTask({{ $task->id }})" title="Dismiss"
                                                    class="rounded-lg p-1.5 text-neutral-400 transition-colors hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20 dark:hover:text-red-400">
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Upcoming deadlines --}}
                    @if ($upcomingDeadlines->isNotEmpty())
                        <div class="rounded-2xl bg-white px-6 py-7 shadow-sm shadow-neutral-200/50 dark:bg-neutral-900 dark:shadow-neutral-950/30">
                            <div class="mb-3 flex items-center gap-2">
                                <h2 class="text-xs font-semibold uppercase tracking-wide text-neutral-900 dark:text-neutral-100">Upcoming Deadlines</h2>
                                <span class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[11px] font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">{{ $upcomingDeadlines->count() }}</span>
                            </div>
                            <div class="space-y-0.5" x-data>
                                @foreach ($upcomingDeadlines as $task)
                                    @php
                                        $deadlineLocal = $task->deadline->copy()->setTimezone($tz);
                                        $daysLeft = (int) now($tz)->startOfDay()->diffInDays($deadlineLocal->copy()->startOfDay(), false);
                                        $urgencyClass = match(true) {
                                            $daysLeft <= 0 => 'text-red-500 dark:text-red-400',
                                            $daysLeft <= 1 => 'text-amber-500 dark:text-amber-400',
                                            default => 'text-neutral-400 dark:text-neutral-500',
                                        };
                                        $urgencyLabel = match(true) {
                                            $daysLeft < 0 => abs($daysLeft) . 'd overdue',
                                            $daysLeft === 0 => 'Today',
                                            $daysLeft === 1 => 'Tomorrow',
                                            default => $daysLeft . 'd left',
                                        };
                                    @endphp
                                    <button @click="Livewire.dispatch('openModal', { component: 'task-detail-modal', arguments: { taskId: {{ $task->id }} } })"
                                            class="flex w-full items-center gap-3 rounded-lg px-2.5 py-2 text-left transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-800/30">
                                        @if ($task->project)
                                            <span class="size-2 shrink-0 rounded-full" style="background-color: {{ $task->project->color }};"></span>
                                        @else
                                            <span class="size-2 shrink-0 rounded-full bg-neutral-300 dark:bg-neutral-600"></span>
                                        @endif
                                        <span class="min-w-0 flex-1 truncate text-sm font-medium text-neutral-800 dark:text-neutral-200">{{ $task->title }}</span>
                                        <span class="shrink-0 text-xs tabular-nums text-neutral-400 dark:text-neutral-500">{{ $deadlineLocal->format('M j') }}</span>
                                        <span class="w-16 shrink-0 text-right text-xs font-medium tabular-nums {{ $urgencyClass }}">{{ $urgencyLabel }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Right column --}}
                <div class="space-y-4">
                    {{-- Overview --}}
                    <div class="rounded-2xl bg-white px-6 py-7 shadow-sm shadow-neutral-200/50 dark:bg-neutral-900 dark:shadow-neutral-950/30">
                        <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-neutral-900 dark:text-neutral-100">Overview</h2>
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-neutral-500 dark:text-neutral-400">Pending</span>
                                <span class="text-sm font-semibold tabular-nums text-neutral-900 dark:text-neutral-100">{{ $pendingCount }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-neutral-500 dark:text-neutral-400">Completed today</span>
                                <span class="text-sm font-semibold tabular-nums text-green-600 dark:text-green-400">{{ $completedTodayCount }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-neutral-500 dark:text-neutral-400">Overdue</span>
                                <span class="text-sm font-semibold tabular-nums {{ $overdueTasks->count() > 0 ? 'text-red-500' : 'text-neutral-900 dark:text-neutral-100' }}">{{ $overdueTasks->count() }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Capacity this week --}}
                    <div class="rounded-2xl bg-white px-6 py-7 shadow-sm shadow-neutral-200/50 dark:bg-neutral-900 dark:shadow-neutral-950/30">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-xs font-semibold uppercase tracking-wide text-neutral-900 dark:text-neutral-100">Capacity</h2>
                            @php
                                $weekFree = max(0, $weekTotalAvailable - $weekTotalScheduled);
                                $weekPct = $weekTotalAvailable > 0 ? round(($weekTotalScheduled / $weekTotalAvailable) * 100) : 0;
                            @endphp
                            <span class="text-[11px] text-neutral-400 dark:text-neutral-500">{{ $formatTime($weekFree) }} free</span>
                        </div>
                        <div class="mb-3">
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                                <div class="h-full rounded-full transition-all {{ $weekPct > 90 ? 'bg-red-400' : ($weekPct > 70 ? 'bg-amber-400' : 'bg-accent-500') }}"
                                     style="width: {{ min($weekPct, 100) }}%"></div>
                            </div>
                            <div class="mt-1 flex items-center justify-between">
                                <span class="text-[11px] text-neutral-400 dark:text-neutral-500">{{ $formatTime($weekTotalScheduled) }} scheduled</span>
                                <span class="text-[11px] text-neutral-400 dark:text-neutral-500">{{ $weekPct }}%</span>
                            </div>
                        </div>
                        <div class="flex items-end gap-1">
                            @foreach ($weekCapacity as $day)
                                @php
                                    $dayPct = $day['available'] > 0 ? min(100, round(($day['scheduled'] / $day['available']) * 100)) : 0;
                                    $barColor = $day['available'] === 0 ? 'bg-neutral-100 dark:bg-neutral-800' : ($dayPct > 90 ? 'bg-red-400' : ($dayPct > 70 ? 'bg-amber-400' : 'bg-accent-500'));
                                @endphp
                                <div class="flex flex-1 flex-col items-center gap-1">
                                    <div class="relative h-12 w-full overflow-hidden rounded bg-neutral-100 dark:bg-neutral-800">
                                        @if ($day['available'] > 0)
                                            <div class="absolute inset-x-0 bottom-0 rounded-sm transition-all {{ $barColor }}"
                                                 style="height: {{ $dayPct }}%"></div>
                                        @endif
                                    </div>
                                    <span class="text-[11px] {{ $day['isToday'] ? 'font-semibold text-accent-600 dark:text-accent-400' : 'text-neutral-400 dark:text-neutral-500' }}">{{ $day['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Projects --}}
                    @if ($projects->isNotEmpty())
                        <div class="rounded-2xl bg-white px-6 py-7 shadow-sm shadow-neutral-200/50 dark:bg-neutral-900 dark:shadow-neutral-950/30">
                            <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-neutral-900 dark:text-neutral-100">Projects</h2>
                            <div class="space-y-1">
                                @foreach ($projects as $project)
                                    <a href="{{ route('tasks') }}?project={{ $project->id }}" wire:navigate
                                       class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-800">
                                        <span class="size-2.5 shrink-0 rounded-full" style="background-color: {{ $project->color ?? '#6366f1' }};"></span>
                                        <span class="min-w-0 flex-1 truncate text-sm font-medium text-neutral-700 dark:text-neutral-300">{{ $project->title }}</span>
                                        <span class="text-xs tabular-nums text-neutral-400 dark:text-neutral-500">{{ $project->tasks_count }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
