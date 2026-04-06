<?php

namespace App\Livewire\Pages;

use App\Enums\CalendarView;
use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use App\Enums\TaskStatus;
use App\Jobs\ResolveOverlapsJob;
use App\Jobs\ScheduleTasksJob;
use App\Jobs\SyncGoogleCalendarJob;
use App\Models\ProjectBlock;
use App\Models\TaskBlock;
use App\Settings\UserPreferences;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.planner')]
#[Title('Planner — Aura')]
class PlannerPage extends Component
{
    public string $currentView = 'week';

    public Carbon $currentDate;

    public ?string $selectedDate = null;

    public int $weekDaysCount = 7;

    public int $pastBuffer = 2;

    public int $futureBuffer = 3;

    /** @return array<string, string> */
    public function getListeners(): array
    {
        return [
            'echo-private:App.Models.User.'.auth()->id().',OverlapsResolved' => '$refresh',
            'echo-private:App.Models.User.'.auth()->id().',ScheduleCompleted' => 'onScheduleCompleted',
            'echo-private:App.Models.User.'.auth()->id().',RescheduleProposed' => 'onRescheduleProposed',
        ];
    }

    public function onScheduleCompleted(): void
    {
        $this->dispatch('toast', type: 'success', title: 'Schedule updated', body: 'Your tasks have been rescheduled.');
    }

    /** @param array<string, mixed> $event */
    public function onRescheduleProposed(array $event): void
    {
        $this->dispatch('openModal', component: 'reschedule-preview-modal', arguments: ['proposalId' => $event['proposal_id']]);
    }

    public function mount(UserPreferences $preferences, ?string $date = null): void
    {
        $this->currentView = $preferences->calendar_view->value;
        $this->weekDaysCount = $preferences->week_days_count;
        $this->currentDate = $date ? Carbon::parse($date) : now();
        $this->triggerSyncIfNeeded();
    }

    #[On('calendar-go-to-date')]
    public function goToDate(string $date): void
    {
        $this->currentDate = Carbon::parse($date);
        $this->selectedDate = $date;
    }

    #[On('calendar-navigate')]
    public function onCalendarNavigate(string $view, string $date): void
    {
        $this->currentView = $view;
        $this->currentDate = Carbon::parse($date);
        $this->pastBuffer = match ($view) {
            'month' => 2,
            default => 4,
        };
        $this->futureBuffer = match ($view) {
            'month' => 2,
            default => 4,
        };

        $preferences = app(UserPreferences::class);
        $this->weekDaysCount = $preferences->week_days_count;
        $preferences->calendar_view = CalendarView::from($view);
        $preferences->save();
    }

    #[On('calendar-event-created')]
    public function onEventCreated(): void
    {
        // Re-render is triggered automatically by the event listener
    }

    #[On('task-scheduled')]
    public function onTaskScheduled(): void {}

    #[On('project-layers-changed')]
    public function onProjectLayersChanged(): void {}

    #[On('day-override-saved')]
    public function onDayOverrideSaved(): void {}

    #[On('project-saved')]
    public function onProjectSaved(): void
    {
        $userId = auth()->id();
        cache()->forget("user.{$userId}.projects_with_schedules");
        cache()->forget("user.{$userId}.work_schedules");
    }

    #[On('auto-schedule')]
    public function autoSchedule(): void
    {
        ScheduleTasksJob::debounce(auth()->user());
        $this->dispatch('toast', type: 'info', title: 'Scheduling...', body: 'Rescheduling your tasks.');
    }

    public function scheduleTask(int $taskId, string $date, int $startMinutes, int $duration): void
    {
        $task = auth()->user()->tasks()->where('status', TaskStatus::Pending)->findOrFail($taskId);
        $tz = auth()->user()->timezone ?? 'UTC';

        $scheduledStart = Carbon::parse($date, $tz)->startOfDay()->addMinutes($startMinutes)->utc();
        $scheduledEnd = $scheduledStart->copy()->addMinutes($duration);

        $task->blocks()->delete();
        $task->blocks()->create(['scheduled_start' => $scheduledStart, 'scheduled_end' => $scheduledEnd]);

        $task->update([
            'scheduled_start' => $scheduledStart,
            'scheduled_end' => $scheduledEnd,
            'status' => TaskStatus::Scheduled,
            'is_ai_scheduled' => false,
            'is_pinned' => true,
        ]);

        $this->dispatch('task-scheduled');
        $this->dispatch('toast', type: 'success', title: 'Task scheduled', body: $task->title);
        ScheduleTasksJob::debounce(auth()->user());
    }

    public function togglePin(int $taskId): void
    {
        $task = auth()->user()->tasks()->where('status', TaskStatus::Scheduled)->findOrFail($taskId);
        $task->update(['is_pinned' => ! $task->is_pinned]);
    }

    public function completeTask(int $taskId): void
    {
        $task = auth()->user()->tasks()->findOrFail($taskId);
        $task->update(['status' => TaskStatus::Completed]);
        $task->blocks()->delete();
        $this->dispatch('task-scheduled');
        $this->dispatch('toast', type: 'success', title: 'Task completed', body: $task->title);
        ScheduleTasksJob::debounce(auth()->user());
    }

    public function moveEvent(int $eventId, string $date, int $startMinutes): void
    {
        $event = auth()->user()->calendarEvents()->findOrFail($eventId);
        $tz = auth()->user()->timezone ?? 'UTC';
        $duration = $event->starts_at->diffInMinutes($event->ends_at);
        $newStart = Carbon::parse($date, $tz)->startOfDay()->addMinutes($startMinutes)->utc();
        $newEnd = $newStart->copy()->addMinutes($duration);

        $event->update([
            'starts_at' => $newStart,
            'ends_at' => $newEnd,
        ]);

        ResolveOverlapsJob::dispatch(auth()->user(), $newStart, $newEnd);
        $this->dispatch('calendar-event-created');
    }

    public function resizeEvent(int $eventId, int $startMinutes, int $endMinutes): void
    {
        $event = auth()->user()->calendarEvents()->findOrFail($eventId);
        $tz = auth()->user()->timezone ?? 'UTC';
        $day = $event->starts_at->copy()->setTimezone($tz)->startOfDay();
        $newStart = $day->copy()->addMinutes($startMinutes)->utc();
        $newEnd = $day->copy()->addMinutes($endMinutes)->utc();

        $event->update([
            'starts_at' => $newStart,
            'ends_at' => $newEnd,
        ]);

        ResolveOverlapsJob::dispatch(auth()->user(), $newStart, $newEnd);
        $this->dispatch('calendar-event-created');
    }

    public function moveTask(int $taskId, string $date, int $startMinutes): void
    {
        $task = auth()->user()->tasks()->where('status', TaskStatus::Scheduled)->findOrFail($taskId);
        $tz = auth()->user()->timezone ?? 'UTC';

        // When manually moving, collapse all blocks into a single block
        $totalDuration = $task->estimated_duration ?? $task->scheduled_start->diffInMinutes($task->scheduled_end);
        $newStart = Carbon::parse($date, $tz)->startOfDay()->addMinutes($startMinutes)->utc();
        $newEnd = $newStart->copy()->addMinutes($totalDuration);

        $task->blocks()->delete();
        $task->blocks()->create(['scheduled_start' => $newStart, 'scheduled_end' => $newEnd]);

        $task->update([
            'scheduled_start' => $newStart,
            'scheduled_end' => $newEnd,
            'is_ai_scheduled' => false,
            'is_pinned' => true,
        ]);

        ResolveOverlapsJob::dispatch(auth()->user(), $newStart, $newEnd, $taskId);
        $this->dispatch('task-scheduled');
        $this->dispatch('toast', type: 'success', title: 'Task moved', body: $task->title);
    }

    public function resizeTask(int $taskId, int $startMinutes, int $endMinutes): void
    {
        $task = auth()->user()->tasks()->where('status', TaskStatus::Scheduled)->findOrFail($taskId);
        $tz = auth()->user()->timezone ?? 'UTC';
        $day = $task->scheduled_start->copy()->setTimezone($tz)->startOfDay();
        $newStart = $day->copy()->addMinutes($startMinutes)->utc();
        $newEnd = $day->copy()->addMinutes($endMinutes)->utc();

        // Collapse to single block on resize
        $task->blocks()->delete();
        $task->blocks()->create(['scheduled_start' => $newStart, 'scheduled_end' => $newEnd]);

        $task->update([
            'scheduled_start' => $newStart,
            'scheduled_end' => $newEnd,
            'is_ai_scheduled' => false,
        ]);

        ResolveOverlapsJob::dispatch(auth()->user(), $newStart, $newEnd, $taskId);
        $this->dispatch('task-scheduled');
    }

    public function moveProjectBlock(int $blockId, string $date, int $startMinutes): void
    {
        $block = ProjectBlock::whereHas('project', fn ($q) => $q->where('user_id', auth()->id()))->findOrFail($blockId);
        $tz = auth()->user()->timezone ?? 'UTC';
        $duration = $block->scheduled_start->diffInMinutes($block->scheduled_end);
        $newStart = Carbon::parse($date, $tz)->startOfDay()->addMinutes($startMinutes)->utc();
        $newEnd = $newStart->copy()->addMinutes($duration);

        $block->update([
            'scheduled_start' => $newStart,
            'scheduled_end' => $newEnd,
        ]);
    }

    public function resizeProjectBlock(int $blockId, int $startMinutes, int $endMinutes): void
    {
        $block = ProjectBlock::whereHas('project', fn ($q) => $q->where('user_id', auth()->id()))->findOrFail($blockId);
        $tz = auth()->user()->timezone ?? 'UTC';
        $day = $block->scheduled_start->copy()->setTimezone($tz)->startOfDay();
        $newStart = $day->copy()->addMinutes($startMinutes)->utc();
        $newEnd = $day->copy()->addMinutes($endMinutes)->utc();

        $block->update([
            'scheduled_start' => $newStart,
            'scheduled_end' => $newEnd,
        ]);
    }

    public function loadMore(string $direction): void
    {
        $step = match ($this->currentView) {
            'month' => 1,
            default => 2,
        };

        if ($direction === 'past') {
            $this->pastBuffer += $step;
        } else {
            $this->futureBuffer += $step;
        }
    }

    public function render()
    {
        $user = auth()->user();

        $viewData = match ($this->currentView) {
            'day' => $this->buildDayData(),
            'week' => $this->buildWeekData(),
            'month' => $this->buildMonthData(),
        };

        $events = $user->calendarEvents()
            ->where('starts_at', '>=', $viewData['rangeStart'])
            ->where('starts_at', '<=', $viewData['rangeEnd'])
            ->orderBy('starts_at')
            ->get();

        $hiddenProjectIds = app(UserPreferences::class)->hidden_project_ids;

        $taskBlocks = TaskBlock::query()
            ->whereHas('task', fn ($q) => $q
                ->where('user_id', $user->id)
                ->where('status', TaskStatus::Scheduled)
                ->where(fn ($q2) => $q2->whereNull('project_id')->orWhereNotIn('project_id', $hiddenProjectIds))
            )
            ->where('scheduled_start', '>=', $viewData['rangeStart'])
            ->where('scheduled_start', '<=', $viewData['rangeEnd'])
            ->with(['task.integration', 'task.project'])
            ->orderBy('scheduled_start')
            ->get();

        // One-off project blocks
        $projectBlocks = ProjectBlock::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id)->whereNotIn('id', $hiddenProjectIds))
            ->where('scheduled_start', '>=', $viewData['rangeStart'])
            ->where('scheduled_start', '<=', $viewData['rangeEnd'])
            ->with('project')
            ->orderBy('scheduled_start')
            ->get();

        // Generate virtual project blocks from recurring schedules
        $tz = $user->timezone ?? 'UTC';
        $projects = $user->projects()->whereNotIn('id', $hiddenProjectIds)->with('schedules')->get();
        $workSchedules = $user->workSchedules()->get()->keyBy('day');
        $rangeStart = $viewData['rangeStart'];
        $rangeEnd = $viewData['rangeEnd'];

        // Build a HashSet of existing block keys for O(1) duplicate detection
        $existingBlockKeys = $projectBlocks->mapWithKeys(
            fn ($b) => [$b->project_id.'-'.$b->scheduled_start->timestamp.'-'.$b->scheduled_end->timestamp => true]
        )->all();

        foreach ($projects as $project) {
            $schedulesByDay = $project->schedules->groupBy('day');
            if ($schedulesByDay->isEmpty()) {
                continue;
            }

            $cursor = $rangeStart->copy();
            while ($cursor->lte($rangeEnd)) {
                $isoDay = $cursor->dayOfWeekIso;
                $daySchedules = $schedulesByDay->get($isoDay);

                if ($daySchedules) {
                    $dateStr = $cursor->toDateString();

                    if ($project->starts_at && $dateStr < $project->starts_at->toDateString()) {
                        $cursor->addDay();

                        continue;
                    }
                    if ($project->ends_at && $dateStr > $project->ends_at->toDateString()) {
                        $cursor->addDay();

                        continue;
                    }

                    // Get lunch break for this day
                    $workSchedule = $workSchedules->get($isoDay);
                    $lunchStart = ($workSchedule && $workSchedule->lunch_start)
                        ? Carbon::parse($dateStr, $tz)->setTimeFromTimeString($workSchedule->lunch_start)->utc()
                        : null;
                    $lunchEnd = ($workSchedule && $workSchedule->lunch_end)
                        ? Carbon::parse($dateStr, $tz)->setTimeFromTimeString($workSchedule->lunch_end)->utc()
                        : null;

                    foreach ($daySchedules as $schedule) {
                        $blockStart = Carbon::parse($dateStr, $tz)->setTimeFromTimeString($schedule->start)->utc();
                        $blockEnd = Carbon::parse($dateStr, $tz)->setTimeFromTimeString($schedule->end)->utc();

                        // Split around lunch break if it falls within this block
                        $segments = [];
                        if ($lunchStart && $lunchEnd && $lunchStart->gt($blockStart) && $lunchEnd->lt($blockEnd)) {
                            $segments[] = ['start' => $blockStart, 'end' => $lunchStart];
                            $segments[] = ['start' => $lunchEnd, 'end' => $blockEnd];
                        } else {
                            $segments[] = ['start' => $blockStart, 'end' => $blockEnd];
                        }

                        foreach ($segments as $seg) {
                            $existingKey = $project->id.'-'.$seg['start']->timestamp.'-'.$seg['end']->timestamp;
                            if (! isset($existingBlockKeys[$existingKey])) {
                                $virtualBlock = new ProjectBlock([
                                    'project_id' => $project->id,
                                    'scheduled_start' => $seg['start'],
                                    'scheduled_end' => $seg['end'],
                                ]);
                                $virtualBlock->id = 0;
                                $virtualBlock->setRelation('project', $project);
                                $projectBlocks->push($virtualBlock);
                                $existingBlockKeys[$existingKey] = true;
                            }
                        }
                    }
                }
                $cursor->addDay();
            }
        }

        // Generate project indicators from task blocks for projects without schedules on that day
        $projectsById = $projects->keyBy('id');
        $taskBlocksByProject = $taskBlocks->filter(fn ($b) => $b->task->project_id)->groupBy(fn ($b) => $b->task->project_id);

        // Build a date-based set for O(1) "does this project already have a block on this date?" checks
        $projectBlockDateKeys = [];
        foreach ($projectBlocks as $pb) {
            $projectBlockDateKeys[$pb->project_id.'-'.$pb->scheduled_start->copy()->setTimezone($tz)->format('Y-m-d')] = true;
        }

        foreach ($taskBlocksByProject as $projectId => $blocks) {
            $project = $projectsById->get($projectId);
            if (! $project) {
                continue;
            }

            // Group task blocks by date
            $blocksByDate = $blocks->groupBy(fn ($b) => $b->scheduled_start->copy()->setTimezone($tz)->format('Y-m-d'));

            foreach ($blocksByDate as $dateStr => $dayBlocks) {
                // Skip if we already have a project block for this project on this date
                if (isset($projectBlockDateKeys[$projectId.'-'.$dateStr])) {
                    continue;
                }

                // Create a project block spanning all task blocks on this day
                $earliest = $dayBlocks->min(fn ($b) => $b->scheduled_start);
                $latest = $dayBlocks->max(fn ($b) => $b->scheduled_end);

                $virtualBlock = new ProjectBlock([
                    'project_id' => $projectId,
                    'scheduled_start' => $earliest,
                    'scheduled_end' => $latest,
                ]);
                $virtualBlock->id = 0;
                $virtualBlock->setRelation('project', $project);
                $projectBlocks->push($virtualBlock);
                $projectBlockDateKeys[$projectId.'-'.$dateStr] = true;
            }
        }

        $projectBlocks = $projectBlocks->sortBy('scheduled_start')->values();

        // Pre-index all collections by 'Y-m-d-H' cell key for O(1) lookups in Blade
        $eventsByCell = [];
        foreach ($events as $e) {
            $local = $e->starts_at->copy()->setTimezone($tz);
            $key = $local->format('Y-m-d').'-'.$local->hour;
            $eventsByCell[$key][] = $e;
        }

        $taskBlocksByCell = [];
        foreach ($taskBlocks as $b) {
            $local = $b->scheduled_start->copy()->setTimezone($tz);
            $key = $local->format('Y-m-d').'-'.$local->hour;
            $taskBlocksByCell[$key][] = $b;
        }

        $projectBlocksByCell = [];
        foreach ($projectBlocks as $pb) {
            $local = $pb->scheduled_start->copy()->setTimezone($tz);
            $key = $local->format('Y-m-d').'-'.$local->hour;
            $projectBlocksByCell[$key][] = $pb;
        }

        // Pre-index by date only for the month view
        $eventsByDate = [];
        foreach ($events as $e) {
            $key = $e->starts_at->copy()->setTimezone($tz)->format('Y-m-d');
            $eventsByDate[$key][] = $e;
        }

        $taskBlocksByDate = [];
        foreach ($taskBlocks as $b) {
            $key = $b->scheduled_start->copy()->setTimezone($tz)->format('Y-m-d');
            $taskBlocksByDate[$key][] = $b;
        }

        // Map allDays to days for week view
        if ($this->currentView === 'week') {
            $viewData['days'] = $viewData['allDays'];
            unset($viewData['allDays']);
        }

        $overrideDates = $user->dayOverrides()
            ->where('date', '>=', $viewData['rangeStart'])
            ->where('date', '<=', $viewData['rangeEnd'])
            ->pluck('date')
            ->map(fn ($d) => $d->format('Y-m-d'))
            ->flip()
            ->all();

        unset($viewData['rangeStart'], $viewData['rangeEnd']);

        return view('livewire.pages.planner-page', [
            'events' => $events,
            'taskBlocks' => $taskBlocks,
            'projectBlocks' => $projectBlocks,
            'eventsByCell' => $eventsByCell,
            'taskBlocksByCell' => $taskBlocksByCell,
            'projectBlocksByCell' => $projectBlocksByCell,
            'eventsByDate' => $eventsByDate,
            'taskBlocksByDate' => $taskBlocksByDate,
            'currentView' => $this->currentView,
            'weekDaysCount' => $this->weekDaysCount,
            'selectedDate' => $this->selectedDate,
            'overrideDates' => $overrideDates,
            ...$viewData,
        ]);
    }

    /** @return array{hours: Collection, day: Carbon, rangeStart: Carbon, rangeEnd: Carbon} */
    private function buildDayData(): array
    {
        return [
            'hours' => collect(range(0, 23)),
            'day' => $this->currentDate->copy(),
            'rangeStart' => $this->currentDate->copy()->startOfDay(),
            'rangeEnd' => $this->currentDate->copy()->endOfDay(),
        ];
    }

    /** @return array{allDays: Collection, hours: Collection, anchorDate: string, rangeStart: Carbon, rangeEnd: Carbon} */
    private function buildWeekData(): array
    {
        $daysCount = $this->weekDaysCount;
        $anchorStart = $this->currentDate->copy()->startOfDay();
        $rangeStart = $anchorStart->copy()->subDays($daysCount * $this->pastBuffer);
        $rangeEnd = $anchorStart->copy()->addDays($daysCount * $this->futureBuffer + $daysCount - 1)->endOfDay();

        $allDays = collect();
        $cursor = $rangeStart->copy();
        while ($cursor->lte($rangeEnd)) {
            $allDays->push($cursor->copy());
            $cursor->addDay();
        }

        return [
            'allDays' => $allDays,
            'hours' => collect(range(0, 23)),
            'anchorDate' => $anchorStart->toDateString(),
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
        ];
    }

    /**
     * Build month groups for the month view. Each group is one calendar month
     * containing its week rows, designed to fill the full viewport height.
     *
     * @return array{monthGroups: Collection, weeks: Collection, anchorDate: string, rangeStart: Carbon, rangeEnd: Carbon}
     */
    private function buildMonthData(): array
    {
        $rangeStart = $this->currentDate->copy()->subMonths($this->pastBuffer)->startOfMonth()->startOfWeek();
        $rangeEnd = $this->currentDate->copy()->addMonths($this->futureBuffer)->endOfMonth()->endOfWeek();

        $allDays = collect();
        $cursor = $rangeStart->copy();
        while ($cursor->lte($rangeEnd)) {
            $allDays->push($cursor->copy());
            $cursor->addDay();
        }

        $weeks = $allDays->chunk(7);

        // Group weeks by the month their Thursday falls in (ISO standard)
        $monthGroups = collect();
        $currentGroup = null;

        foreach ($weeks as $weekIndex => $week) {
            $thursday = $week->values()->get(3);
            $monthKey = $thursday->format('Y-m');
            $monthLabel = $thursday->format('F Y');

            if (! $currentGroup || $currentGroup['key'] !== $monthKey) {
                if ($currentGroup) {
                    $monthGroups->push($currentGroup);
                }
                $currentGroup = [
                    'key' => $monthKey,
                    'label' => $monthLabel,
                    'weeks' => collect(),
                ];
            }

            $currentGroup['weeks']->push(['index' => $weekIndex, 'days' => $week]);
        }

        if ($currentGroup) {
            $monthGroups->push($currentGroup);
        }

        return [
            'monthGroups' => $monthGroups,
            'weeks' => $weeks,
            'anchorDate' => $this->currentDate->copy()->startOfMonth()->startOfWeek()->toDateString(),
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
        ];
    }

    private function triggerSyncIfNeeded(): void
    {
        $user = auth()->user();
        $integration = $user->integrations()
            ->where('type', IntegrationType::GoogleCalendar)
            ->where('status', IntegrationStatus::Connected)
            ->first();

        if (! $integration) {
            return;
        }

        $config = $integration->configuration;
        $lastSynced = isset($config['last_synced_at'])
            ? Carbon::parse($config['last_synced_at'])
            : null;

        if (! $lastSynced || $lastSynced->diffInMinutes(now()) >= 1) {
            SyncGoogleCalendarJob::dispatch($user);
        }
    }
}
