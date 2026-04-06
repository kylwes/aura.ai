<?php

namespace App\Livewire;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Jobs\ExpandRecurringTasksJob;
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

    public bool $isRecurring = false;

    public string $recurrenceType = 'weekly';

    public array $recurrenceDays = [];

    public ?string $recurrenceEndDate = null;

    public function mount(?int $projectId = null): void
    {
        $this->projectId = $projectId;
    }

    public function toggleDay(int $day): void
    {
        if (in_array($day, $this->recurrenceDays)) {
            $this->recurrenceDays = array_values(array_diff($this->recurrenceDays, [$day]));
        } else {
            $this->recurrenceDays[] = $day;
        }
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'priority' => 'required|in:'.implode(',', array_column(TaskPriority::cases(), 'value')),
            'estimatedDuration' => 'nullable|integer|min:1',
            'deadline' => 'nullable|date',
            'projectId' => 'nullable|integer|exists:projects,id',
            'recurrenceDays' => 'array',
            'recurrenceEndDate' => 'nullable|date',
        ]);

        $data = [
            'title' => $this->title,
            'description' => $this->description ?: null,
            'priority' => $this->priority,
            'estimated_duration' => $this->estimatedDuration,
            'deadline' => $this->deadline ?: null,
            'status' => TaskStatus::Pending,
            'project_id' => $this->projectId,
        ];

        if ($this->isRecurring && $this->recurrenceType) {
            $data['recurrence_type'] = $this->recurrenceType;
            $data['recurrence_days'] = $this->recurrenceType === 'weekly' ? $this->recurrenceDays : null;
            $data['recurrence_end_date'] = $this->recurrenceEndDate ?: null;
        }

        auth()->user()->tasks()->create($data);

        $this->dispatch('task-created');
        $this->dispatch('toast', type: 'info', title: 'Scheduling...', body: 'Scheduling your new task.');

        if ($this->isRecurring) {
            ExpandRecurringTasksJob::dispatch();
        } else {
            ScheduleTasksJob::debounce(auth()->user());
        }

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
