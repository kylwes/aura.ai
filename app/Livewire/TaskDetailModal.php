<?php

namespace App\Livewire;

use App\Enums\TaskStatus;
use App\Jobs\ScheduleTasksJob;
use App\Models\Task;
use LivewireUI\Modal\ModalComponent;

class TaskDetailModal extends ModalComponent
{
    public ?Task $task = null;

    public bool $showAiReasoning = true;

    public function mount(int $taskId): void
    {
        $this->task = Task::where('user_id', auth()->id())
            ->with(['integration', 'project'])
            ->findOrFail($taskId);
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
    }

    public function setPriority(string $priority): void
    {
        $this->task->update(['priority' => $priority]);
        $this->task->refresh();
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
        $this->forceClose()->closeModal();
    }

    public function reschedule(): void
    {
        $taskId = $this->task->id;

        $this->task->update([
            'scheduled_start' => null,
            'scheduled_end' => null,
            'status' => TaskStatus::Pending,
            'is_ai_scheduled' => false,
            'ai_reasoning' => null,
        ]);

        ScheduleTasksJob::dispatch(auth()->user());

        $this->dispatch('task-rescheduling', taskId: $taskId);
        $this->dispatch('task-scheduled');
        $this->forceClose()->closeModal();
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
