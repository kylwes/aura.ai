<?php

namespace App\Livewire;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Jobs\ScheduleTasksJob;
use LivewireUI\Modal\ModalComponent;

class CreateTaskModal extends ModalComponent
{
    public string $title = '';

    public string $description = '';

    public string $priority = 'medium';

    public ?int $estimatedDuration = null;

    public ?string $deadline = null;

    public ?int $projectId = null;

    public function save(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'priority' => 'required|in:'.implode(',', array_column(TaskPriority::cases(), 'value')),
            'estimatedDuration' => 'nullable|integer|min:1',
            'deadline' => 'nullable|date',
            'projectId' => 'nullable|integer|exists:projects,id',
        ]);

        auth()->user()->tasks()->create([
            'title' => $this->title,
            'description' => $this->description ?: null,
            'priority' => $this->priority,
            'estimated_duration' => $this->estimatedDuration,
            'deadline' => $this->deadline ?: null,
            'status' => TaskStatus::Pending,
            'project_id' => $this->projectId,
        ]);

        $this->dispatch('task-created');

        ScheduleTasksJob::dispatch(auth()->user());

        $this->forceClose()->closeModal();
    }

    public static function modalMaxWidth(): string
    {
        return 'xl';
    }

    public function render()
    {
        return view('livewire.create-task-modal');
    }
}
