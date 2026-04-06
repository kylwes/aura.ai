<?php

namespace App\Livewire;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Jobs\ScheduleTasksJob;
use Livewire\Component;

class StaleTaskNudge extends Component
{
    public function dismiss(int $taskId): void
    {
        auth()->user()->tasks()->where('id', $taskId)->update(['status' => TaskStatus::Dismissed]);
        $this->dispatch('toast', type: 'info', title: 'Task dismissed');
    }

    public function escalate(int $taskId): void
    {
        $task = auth()->user()->tasks()->findOrFail($taskId);
        $task->update(['priority' => TaskPriority::Urgent, 'reschedule_count' => 0]);
        ScheduleTasksJob::debounce(auth()->user());
        $this->dispatch('toast', type: 'info', title: 'Task escalated', body: 'Priority set to urgent, rescheduling...');
    }

    public function render()
    {
        $staleTasks = auth()->user()->tasks()
            ->where('reschedule_count', '>=', 3)
            ->whereIn('status', [TaskStatus::Pending, TaskStatus::Scheduled])
            ->orderByDesc('reschedule_count')
            ->limit(5)
            ->get();

        return view('livewire.stale-task-nudge', compact('staleTasks'));
    }
}
