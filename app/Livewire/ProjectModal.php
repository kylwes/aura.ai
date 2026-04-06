<?php

namespace App\Livewire;

use App\Jobs\ScheduleTasksJob;
use App\Models\ProjectSchedule;
use LivewireUI\Modal\ModalComponent;

class ProjectModal extends ModalComponent
{
    public ?int $projectId = null;

    public string $title = '';

    public string $description = '';

    public string $color = '#6366f1';

    public ?string $startsAt = null;

    public ?string $endsAt = null;

    /** @var array<int, array{start: string, end: string}> Keyed by ISO day (1-7) */
    public array $schedules = [];

    public function mount(?int $projectId = null): void
    {
        $this->projectId = $projectId;

        if ($projectId !== null) {
            $project = auth()->user()->projects()->findOrFail($projectId);
            $this->title = $project->title;
            $this->description = $project->description ?? '';
            $this->color = $project->color;
            $this->startsAt = $project->starts_at?->format('Y-m-d');
            $this->endsAt = $project->ends_at?->format('Y-m-d');

            foreach ($project->schedules as $schedule) {
                $this->schedules[$schedule->day] = [
                    'start' => $schedule->start,
                    'end' => $schedule->end,
                ];
            }
        }
    }

    public function updateSchedule(int $day, ?string $start, ?string $end): void
    {
        if ($start && $end && $start < $end) {
            $this->schedules[$day] = ['start' => $start, 'end' => $end];
        } else {
            unset($this->schedules[$day]);
        }
    }

    public function removeSchedule(int $day): void
    {
        unset($this->schedules[$day]);
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'startsAt' => 'nullable|date',
            'endsAt' => 'nullable|date|after_or_equal:startsAt',
        ]);

        $data = [
            'title' => $this->title,
            'description' => $this->description ?: null,
            'color' => $this->color,
            'starts_at' => $this->startsAt ?: null,
            'ends_at' => $this->endsAt ?: null,
        ];

        if ($this->projectId !== null) {
            $project = auth()->user()->projects()->findOrFail($this->projectId);
            $project->update($data);
        } else {
            $project = auth()->user()->projects()->create($data);
            $this->projectId = $project->id;
        }

        // Sync schedules
        $project->schedules()->delete();
        foreach ($this->schedules as $day => $times) {
            ProjectSchedule::create([
                'project_id' => $project->id,
                'day' => $day,
                'start' => $times['start'],
                'end' => $times['end'],
            ]);
        }

        // Reschedule — project windows affect all task scheduling
        ScheduleTasksJob::debounce(auth()->user());

        $this->dispatch('project-saved');

        $this->forceClose()->closeModal();
    }

    public function delete(): void
    {
        if ($this->projectId === null) {
            return;
        }

        auth()->user()->projects()->findOrFail($this->projectId)->delete();

        $this->dispatch('project-saved');

        $this->forceClose()->closeModal();
    }

    public string $tab = 'details';

    public static function modalMaxWidth(): string
    {
        return '2xl';
    }

    public function render()
    {
        return view('livewire.project-modal');
    }
}
