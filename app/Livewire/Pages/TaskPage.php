<?php

namespace App\Livewire\Pages;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Jobs\ScheduleTasksJob;
use App\Settings\UserPreferences;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Tasks — Aura')]
class TaskPage extends Component
{
    #[Url]
    public string $filter = 'all';

    #[Url]
    public string $search = '';

    public string $view = 'list';

    public function mount(UserPreferences $preferences): void
    {
        $this->view = $preferences->task_view;
    }

    public function switchView(string $view): void
    {
        $this->view = $view;

        $preferences = app(UserPreferences::class);
        $preferences->task_view = $view;
        $preferences->save();
    }

    public function completeTask(int $taskId): void
    {
        $task = auth()->user()->tasks()->findOrFail($taskId);
        $task->update(['status' => TaskStatus::Completed]);
    }

    public function reopenTask(int $taskId): void
    {
        $task = auth()->user()->tasks()->findOrFail($taskId);
        $task->update(['status' => TaskStatus::Pending]);

        ScheduleTasksJob::dispatch(auth()->user());
    }

    public function updateTaskStatus(int $taskId, string $status): void
    {
        $task = auth()->user()->tasks()->findOrFail($taskId);
        $newStatus = TaskStatus::from($status);

        if ($task->status === $newStatus) {
            return;
        }

        $task->update(['status' => $newStatus]);

        $label = match ($newStatus) {
            TaskStatus::Completed => 'Task completed',
            TaskStatus::Pending => 'Task reopened',
            TaskStatus::Scheduled => 'Task scheduled',
            default => 'Task updated',
        };

        $this->dispatch('toast', type: 'success', title: $label, body: $task->title);
    }

    public function render()
    {
        $baseQuery = auth()->user()->tasks()
            ->with(['integration', 'project'])
            ->when($this->search, fn (Builder $q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->filter === 'pending', fn (Builder $q) => $q->whereIn('status', [TaskStatus::Pending, TaskStatus::Scheduled]))
            ->when($this->filter === 'completed', fn (Builder $q) => $q->where('status', TaskStatus::Completed))
            ->when($this->filter === 'urgent', fn (Builder $q) => $q->whereIn('priority', [TaskPriority::Urgent, TaskPriority::High]));

        $tasks = (clone $baseQuery)
            ->orderByRaw("FIELD(status, 'pending', 'scheduled', 'snoozed', 'completed', 'dismissed')")
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->latest()
            ->get();

        $boardColumns = [];
        if ($this->view === 'board') {
            $boardTasks = (clone $baseQuery)
                ->whereIn('status', [TaskStatus::Pending, TaskStatus::Scheduled, TaskStatus::Completed])
                ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
                ->orderBy('deadline')
                ->latest()
                ->get();

            $boardColumns = [
                ['key' => 'pending', 'label' => 'Pending', 'tasks' => $boardTasks->where('status', TaskStatus::Pending)->values()],
                ['key' => 'scheduled', 'label' => 'Scheduled', 'tasks' => $boardTasks->where('status', TaskStatus::Scheduled)->values()],
                ['key' => 'completed', 'label' => 'Completed', 'tasks' => $boardTasks->where('status', TaskStatus::Completed)->values()],
            ];
        }

        return view('livewire.pages.task-page', [
            'tasks' => $tasks,
            'boardColumns' => $boardColumns,
            'counts' => [
                'all' => auth()->user()->tasks()->count(),
                'pending' => auth()->user()->tasks()->whereIn('status', [TaskStatus::Pending, TaskStatus::Scheduled])->count(),
                'completed' => auth()->user()->tasks()->where('status', TaskStatus::Completed)->count(),
            ],
        ]);
    }
}
