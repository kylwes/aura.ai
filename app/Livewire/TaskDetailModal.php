<?php

namespace App\Livewire;

use App\Enums\TaskStatus;
use App\Jobs\ScheduleTasksJob;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use LivewireUI\Modal\ModalComponent;

class TaskDetailModal extends ModalComponent
{
    public ?Task $task = null;

    public bool $showAiReasoning = true;

    /** @var array<int> */
    public array $dependencyIds = [];

    public string $dependencySearch = '';

    public function mount(int $taskId): void
    {
        $this->task = Task::where('user_id', auth()->id())
            ->with(['integration', 'project'])
            ->findOrFail($taskId);

        $this->dependencyIds = $this->task->dependencies()->pluck('tasks.id')->all();
    }

    public function updateField(string $field, ?string $value): void
    {
        $allowed = ['title', 'description', 'estimated_duration', 'deadline', 'project_id'];

        if (! in_array($field, $allowed)) {
            return;
        }

        $this->task->update([
            $field => $value === '' ? null : $value,
        ]);
        $this->task->refresh();

        if (in_array($field, ['estimated_duration', 'deadline', 'project_id'])) {
            ScheduleTasksJob::debounce(auth()->user());
        }
    }

    public function setPriority(string $priority): void
    {
        $this->task->update(['priority' => $priority]);
        $this->task->refresh();
        ScheduleTasksJob::debounce(auth()->user());
    }

    public function togglePin(): void
    {
        $this->task->update(['is_pinned' => ! $this->task->is_pinned]);
        $this->task->refresh();
        $this->dispatch('task-scheduled');
    }

    public function markDone(): void
    {
        $this->task->update(['status' => TaskStatus::Completed]);
        $this->task->blocks()->delete();
        $this->dispatch('task-scheduled');
        ScheduleTasksJob::debounce(auth()->user());
        $this->forceClose()->closeModal();
    }

    public function dismiss(): void
    {
        $this->task->update([
            'status' => TaskStatus::Dismissed,
            'scheduled_start' => null,
            'scheduled_end' => null,
            'is_ai_scheduled' => false,
        ]);
        $this->task->blocks()->delete();
        $this->dispatch('task-scheduled');
        ScheduleTasksJob::debounce(auth()->user());
        $this->forceClose()->closeModal();
    }

    public function putOnHold(): void
    {
        $this->task->update(['status' => TaskStatus::OnHold]);
        $this->task->blocks()->delete();
        $this->dispatch('task-scheduled');
        ScheduleTasksJob::debounce(auth()->user());
        $this->forceClose()->closeModal();
    }

    public function resume(): void
    {
        $this->task->update(['status' => TaskStatus::Pending]);
        $this->dispatch('task-scheduled');
        ScheduleTasksJob::debounce(auth()->user());
        $this->forceClose()->closeModal();
    }

    public function reschedule(): void
    {
        $this->task->update([
            'is_pinned' => false,
            'is_ai_scheduled' => false,
            'ai_reasoning' => null,
        ]);

        ScheduleTasksJob::debounce(auth()->user());

        $this->dispatch('toast', type: 'info', title: 'Scheduling...', body: 'Rescheduling your task.');
        $this->forceClose()->closeModal();
    }

    public function addDependency(int $taskId): void
    {
        if ($taskId === $this->task->id) {
            return;
        }

        if ($this->wouldCreateCycle($this->task->id, $taskId)) {
            $this->dispatch('toast', type: 'error', title: 'Circular dependency', body: 'This would create a dependency cycle.');

            return;
        }

        $this->task->dependencies()->syncWithoutDetaching([$taskId]);
        $this->dependencyIds = $this->task->dependencies()->pluck('tasks.id')->all();
        $this->dependencySearch = '';
        ScheduleTasksJob::debounce(auth()->user());
    }

    public function removeDependency(int $taskId): void
    {
        $this->task->dependencies()->detach($taskId);
        $this->dependencyIds = $this->task->dependencies()->pluck('tasks.id')->all();
        ScheduleTasksJob::debounce(auth()->user());
    }

    private function wouldCreateCycle(int $taskId, int $newDepId): bool
    {
        // DFS from newDepId's dependencies to see if we can reach taskId
        $visited = [];
        $stack = [$newDepId];

        while (! empty($stack)) {
            $current = array_pop($stack);
            if ($current === $taskId) {
                return true;
            }
            if (in_array($current, $visited)) {
                continue;
            }
            $visited[] = $current;

            $deps = DB::table('task_dependencies')
                ->where('task_id', $current)
                ->pluck('depends_on_task_id')
                ->all();

            foreach ($deps as $dep) {
                $stack[] = $dep;
            }
        }

        return false;
    }

    public static function modalMaxWidth(): string
    {
        return 'xl';
    }

    public function render()
    {
        return view('livewire.task-detail-modal');
    }
}
