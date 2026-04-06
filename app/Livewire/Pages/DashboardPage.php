<?php

namespace App\Livewire\Pages;

use App\Enums\InboxItemStatus;
use App\Enums\TaskStatus;
use App\Jobs\ScheduleTasksJob;
use App\Models\TaskBlock;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard — Aura')]
class DashboardPage extends Component
{
    /** @return array<string, string> */
    public function getListeners(): array
    {
        return [
            'echo-private:App.Models.User.'.auth()->id().',ScheduleCompleted' => '$refresh',
        ];
    }

    #[On('task-created')]
    #[On('task-scheduled')]
    public function refresh(): void {}

    public function completeTask(int $taskId): void
    {
        $task = auth()->user()->tasks()->findOrFail($taskId);
        $task->update(['status' => TaskStatus::Completed]);
        $task->blocks()->delete();
        $this->dispatch('task-scheduled');
        $this->dispatch('toast', type: 'success', title: 'Task completed', body: $task->title);
        ScheduleTasksJob::debounce(auth()->user());
    }

    public function dismissTask(int $taskId): void
    {
        $task = auth()->user()->tasks()->findOrFail($taskId);
        $task->update(['status' => TaskStatus::Dismissed, 'scheduled_start' => null, 'scheduled_end' => null]);
        $task->blocks()->delete();
        $this->dispatch('task-scheduled');
        $this->dispatch('toast', type: 'success', title: 'Task dismissed', body: $task->title);
        ScheduleTasksJob::debounce(auth()->user());
    }

    public function rescheduleTask(int $taskId): void
    {
        $task = auth()->user()->tasks()->findOrFail($taskId);
        $task->update(['is_pinned' => false, 'is_ai_scheduled' => false, 'ai_reasoning' => null]);
        ScheduleTasksJob::debounce(auth()->user());
        $this->dispatch('toast', type: 'info', title: 'Scheduling...', body: 'Rescheduling your task.');
    }

    public function acceptInboxItem(int $itemId): void
    {
        $item = auth()->user()->inboxItems()->findOrFail($itemId);
        auth()->user()->tasks()->create([
            'integration_id' => $item->integration_id,
            'project_id' => $item->ai_suggested_project_id,
            'title' => str($item->preview_text)->limit(80),
            'description' => $item->preview_text,
            'source_url' => $item->source_url,
            'source_reference' => $item->channel_name,
            'priority' => $item->ai_suggested_priority ?? 'medium',
            'estimated_duration' => $item->ai_estimated_duration,
            'status' => TaskStatus::Pending,
        ]);
        $item->update(['status' => InboxItemStatus::Accepted]);
        $this->dispatch('task-created');
        $this->dispatch('toast', type: 'success', title: 'Task created');
        ScheduleTasksJob::debounce(auth()->user());
    }

    public function dismissInboxItem(int $itemId): void
    {
        auth()->user()->inboxItems()->findOrFail($itemId)->update(['status' => InboxItemStatus::Dismissed]);
    }

    public function render()
    {
        $user = auth()->user();
        $tz = $user->timezone ?? 'UTC';
        $now = Carbon::now($tz);
        $todayStart = $now->copy()->startOfDay()->utc();
        $todayEnd = $now->copy()->endOfDay()->utc();

        // Overdue tasks: scheduled in the past but not completed
        $overdueTasks = $user->tasks()
            ->where('status', TaskStatus::Scheduled)
            ->where('scheduled_end', '<', $todayStart)
            ->with(['project', 'integration'])
            ->orderBy('scheduled_start', 'desc')
            ->limit(20)
            ->get();

        // Today's agenda
        $todayBlocks = TaskBlock::query()
            ->whereHas('task', fn ($q) => $q->where('user_id', $user->id)->where('status', TaskStatus::Scheduled))
            ->where('scheduled_start', '>=', $todayStart)
            ->where('scheduled_start', '<', $todayEnd)
            ->with(['task.project', 'task.integration'])
            ->orderBy('scheduled_start')
            ->get();

        $todayEvents = $user->calendarEvents()
            ->where('starts_at', '>=', $todayStart)
            ->where('starts_at', '<', $todayEnd)
            ->orderBy('starts_at')
            ->get();

        // Schedule stats
        $schedule = $user->effectiveScheduleFor($now->copy()->startOfDay());
        $availableMinutes = 0;
        if ($schedule['enabled'] && $schedule['start'] && $schedule['end']) {
            $availableMinutes = Carbon::parse($schedule['start'])->diffInMinutes(Carbon::parse($schedule['end']));
            if ($schedule['lunch_start'] && $schedule['lunch_end']) {
                $availableMinutes -= Carbon::parse($schedule['lunch_start'])->diffInMinutes($schedule['lunch_end']);
            }
        }
        $scheduledMinutes = $todayBlocks->sum(fn ($b) => $b->scheduled_start->diffInMinutes($b->scheduled_end));

        // Inbox items
        $inboxItems = $user->inboxItems()
            ->where('status', InboxItemStatus::Pending)
            ->with(['integration', 'suggestedProject'])
            ->latest()
            ->limit(5)
            ->get();

        $pendingCount = $user->tasks()->where('status', TaskStatus::Pending)->count();
        $completedTodayCount = $user->tasks()
            ->where('status', TaskStatus::Completed)
            ->where('updated_at', '>=', $todayStart)
            ->count();
        $projects = $user->projects()->withCount(['tasks' => fn ($q) => $q->whereIn('status', [TaskStatus::Pending, TaskStatus::Scheduled])])->get();

        // Upcoming deadlines (next 7 days)
        $weekEnd = $now->copy()->addDays(7)->endOfDay()->utc();
        $upcomingDeadlines = $user->tasks()
            ->whereIn('status', [TaskStatus::Pending, TaskStatus::Scheduled])
            ->whereNotNull('deadline')
            ->where('deadline', '>=', $todayStart)
            ->where('deadline', '<=', $weekEnd)
            ->with('project')
            ->orderBy('deadline')
            ->limit(10)
            ->get();

        // Capacity this week — remaining days including today
        $weekCapacity = [];
        $weekTotalAvailable = 0;
        $weekTotalScheduled = 0;
        $cursor = $now->copy()->startOfDay();
        $endOfWeek = $now->copy()->endOfWeek(Carbon::SUNDAY);

        while ($cursor->lte($endOfWeek)) {
            $daySchedule = $user->effectiveScheduleFor($cursor);
            $dayAvailable = 0;
            if ($daySchedule['enabled'] && $daySchedule['start'] && $daySchedule['end']) {
                $dayAvailable = Carbon::parse($daySchedule['start'])->diffInMinutes(Carbon::parse($daySchedule['end']));
                if ($daySchedule['lunch_start'] && $daySchedule['lunch_end']) {
                    $dayAvailable -= Carbon::parse($daySchedule['lunch_start'])->diffInMinutes($daySchedule['lunch_end']);
                }
            }

            $dayStart = $cursor->copy()->startOfDay()->utc();
            $dayEnd = $cursor->copy()->endOfDay()->utc();
            $dayScheduled = TaskBlock::query()
                ->whereHas('task', fn ($q) => $q->where('user_id', $user->id)->where('status', TaskStatus::Scheduled))
                ->where('scheduled_start', '>=', $dayStart)
                ->where('scheduled_start', '<', $dayEnd)
                ->get()
                ->sum(fn ($b) => $b->scheduled_start->diffInMinutes($b->scheduled_end));

            $weekCapacity[] = [
                'label' => $cursor->format('D'),
                'date' => $cursor->copy(),
                'available' => $dayAvailable,
                'scheduled' => $dayScheduled,
                'isToday' => $cursor->isToday(),
            ];
            $weekTotalAvailable += $dayAvailable;
            $weekTotalScheduled += $dayScheduled;
            $cursor->addDay();
        }

        return view('livewire.pages.dashboard-page', [
            'overdueTasks' => $overdueTasks,
            'todayBlocks' => $todayBlocks,
            'todayEvents' => $todayEvents,
            'scheduledMinutes' => $scheduledMinutes,
            'availableMinutes' => $availableMinutes,
            'freeMinutes' => max(0, $availableMinutes - $scheduledMinutes),
            'inboxItems' => $inboxItems,
            'inboxCount' => $user->inboxItems()->where('status', InboxItemStatus::Pending)->count(),
            'pendingCount' => $pendingCount,
            'completedTodayCount' => $completedTodayCount,
            'projects' => $projects,
            'upcomingDeadlines' => $upcomingDeadlines,
            'weekCapacity' => $weekCapacity,
            'weekTotalAvailable' => $weekTotalAvailable,
            'weekTotalScheduled' => $weekTotalScheduled,
        ]);
    }
}
