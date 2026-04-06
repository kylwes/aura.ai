<?php

namespace App\Livewire;

use App\Jobs\ScheduleTasksJob;
use App\Models\DayOverride;
use Illuminate\Support\Carbon;
use LivewireUI\Modal\ModalComponent;

class WhatIfSimulator extends ModalComponent
{
    public string $scenarioType = 'day_off';

    // Day off scenario
    public string $dayOffDate = '';

    // Change hours scenario
    public string $changeDate = '';

    public string $changeStart = '09:00';

    public string $changeEnd = '17:30';

    // Results
    public bool $hasResults = false;

    /** @var array<int, array{task_id: int, title: string, start: ?string, end: ?string}> */
    public array $currentSchedule = [];

    /** @var array<int, array{task_id: int, title: string, old_start: ?string, old_end: ?string, new_start: string, new_end: string, action: string}> */
    public array $simulatedSchedule = [];

    public function mount(): void
    {
        $this->dayOffDate = now()->addDay()->format('Y-m-d');
        $this->changeDate = now()->addDay()->format('Y-m-d');
    }

    public function simulate(): void
    {
        $user = auth()->user();
        $tz = $user->timezone ?? 'UTC';

        $overrides = $this->buildTemporaryOverrides();

        // Capture current schedule
        $this->currentSchedule = $user->tasks()
            ->where('status', 'scheduled')
            ->where('is_ai_scheduled', true)
            ->get()
            ->map(fn ($t) => [
                'task_id' => $t->id,
                'title' => $t->title,
                'start' => $t->scheduled_start?->setTimezone($tz)->format('D H:i'),
                'end' => $t->scheduled_end?->setTimezone($tz)->format('H:i'),
            ])
            ->all();

        // Run dry-run with temporary overrides
        $job = new ScheduleTasksJob($user, dryRun: true, temporaryOverrides: $overrides);
        $job->handle();
        $proposed = $job->getProposedChanges();

        $this->simulatedSchedule = collect($proposed)->map(function ($change) use ($tz, $user) {
            $task = $user->tasks()->find($change['task_id']);

            return [
                'task_id' => $change['task_id'],
                'title' => $task?->title ?? 'Unknown',
                'old_start' => $change['old_start'] ? Carbon::parse($change['old_start'])->setTimezone($tz)->format('D H:i') : null,
                'old_end' => $change['old_end'] ? Carbon::parse($change['old_end'])->setTimezone($tz)->format('H:i') : null,
                'new_start' => Carbon::parse($change['new_start'])->setTimezone($tz)->format('D H:i'),
                'new_end' => Carbon::parse($change['new_end'])->setTimezone($tz)->format('H:i'),
                'action' => $change['action'],
            ];
        })->all();

        $this->hasResults = true;
    }

    public function apply(): void
    {
        $user = auth()->user();

        $overrides = match ($this->scenarioType) {
            'day_off' => [
                $this->dayOffDate => [
                    'is_day_off' => true,
                    'start' => null,
                    'end' => null,
                    'lunch_start' => null,
                    'lunch_end' => null,
                ],
            ],
            'change_hours' => [
                $this->changeDate => [
                    'is_day_off' => false,
                    'start' => $this->changeStart,
                    'end' => $this->changeEnd,
                    'lunch_start' => '12:00',
                    'lunch_end' => '13:00',
                ],
            ],
            default => [],
        };

        foreach ($overrides as $date => $data) {
            DayOverride::updateOrCreate(
                ['user_id' => $user->id, 'date' => $date],
                $data,
            );
        }

        ScheduleTasksJob::dispatch($user);

        $this->dispatch('day-override-saved');
        $this->dispatch('toast', type: 'info', title: 'Scenario applied', body: 'Rescheduling your tasks...');
        $this->forceClose()->closeModal();
    }

    public static function modalMaxWidth(): string
    {
        return 'xl';
    }

    public function render()
    {
        return view('livewire.what-if-simulator');
    }

    /**
     * Build the temporary overrides array for the selected scenario type.
     *
     * @return array<string, array{enabled: bool, start: ?string, end: ?string, lunch_start: ?string, lunch_end: ?string}>
     */
    private function buildTemporaryOverrides(): array
    {
        return match ($this->scenarioType) {
            'day_off' => [
                $this->dayOffDate => [
                    'enabled' => false,
                    'start' => null,
                    'end' => null,
                    'lunch_start' => null,
                    'lunch_end' => null,
                ],
            ],
            'change_hours' => [
                $this->changeDate => [
                    'enabled' => true,
                    'start' => $this->changeStart,
                    'end' => $this->changeEnd,
                    'lunch_start' => '12:00',
                    'lunch_end' => '13:00',
                ],
            ],
            default => [],
        };
    }
}
