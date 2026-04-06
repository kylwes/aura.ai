<?php

namespace App\Livewire;

use App\Jobs\ScheduleTasksJob;
use App\Models\DayOverride;
use Illuminate\Support\Carbon;
use LivewireUI\Modal\ModalComponent;

class DaySettingsModal extends ModalComponent
{
    public string $date;

    public bool $isDayOff = false;

    public ?string $start = null;

    public ?string $end = null;

    public ?string $lunchStart = null;

    public ?string $lunchEnd = null;

    public bool $hasExistingOverride = false;

    public function mount(string $date): void
    {
        $this->date = $date;

        $user = auth()->user();
        $override = $user->dayOverrides()->whereDate('date', $date)->first();

        if ($override) {
            $this->hasExistingOverride = true;
            $this->isDayOff = $override->is_day_off;
            $this->start = $override->start ? substr($override->start, 0, 5) : null;
            $this->end = $override->end ? substr($override->end, 0, 5) : null;
            $this->lunchStart = $override->lunch_start ? substr($override->lunch_start, 0, 5) : null;
            $this->lunchEnd = $override->lunch_end ? substr($override->lunch_end, 0, 5) : null;
        } else {
            $schedule = $user->effectiveScheduleFor(Carbon::parse($date));
            $this->isDayOff = ! $schedule['enabled'];
            $this->start = $schedule['start'] ? substr($schedule['start'], 0, 5) : null;
            $this->end = $schedule['end'] ? substr($schedule['end'], 0, 5) : null;
            $this->lunchStart = $schedule['lunch_start'] ? substr($schedule['lunch_start'], 0, 5) : null;
            $this->lunchEnd = $schedule['lunch_end'] ? substr($schedule['lunch_end'], 0, 5) : null;
        }
    }

    public function save(): void
    {
        $user = auth()->user();

        $data = [
            'is_day_off' => $this->isDayOff,
            'start' => $this->isDayOff ? null : $this->start,
            'end' => $this->isDayOff ? null : $this->end,
            'lunch_start' => $this->isDayOff ? null : $this->lunchStart,
            'lunch_end' => $this->isDayOff ? null : $this->lunchEnd,
        ];

        $existing = DayOverride::where('user_id', $user->id)
            ->whereDate('date', $this->date)
            ->first();

        if ($existing) {
            $existing->update($data);
        } else {
            DayOverride::create(array_merge(['user_id' => $user->id, 'date' => $this->date], $data));
        }

        $rescheduled = $this->rescheduleAffectedTasks($user, [$this->date]);

        $this->dispatch('day-override-saved');
        $this->dispatch('toast',
            type: $rescheduled ? 'info' : 'success',
            title: 'Day settings saved',
            body: $rescheduled ? 'Rescheduling affected tasks...' : 'Schedule updated.',
        );
        $this->forceClose()->closeModal();
    }

    public function resetToDefault(): void
    {
        $user = auth()->user();

        DayOverride::where('user_id', $user->id)
            ->whereDate('date', $this->date)
            ->delete();

        $rescheduled = $this->rescheduleAffectedTasks($user, [$this->date]);

        $this->dispatch('day-override-saved');
        $this->dispatch('toast',
            type: $rescheduled ? 'info' : 'success',
            title: 'Reset to default',
            body: $rescheduled ? 'Rescheduling affected tasks...' : 'Schedule restored.',
        );
        $this->forceClose()->closeModal();
    }

    public static function modalMaxWidth(): string
    {
        return 'md';
    }

    public function render()
    {
        return view('livewire.day-settings-modal');
    }

    /** @param array<string> $dates */
    private function rescheduleAffectedTasks(mixed $user, array $dates): bool
    {
        $tz = $user->timezone ?? 'UTC';

        $hasAffectedTasks = false;
        foreach ($dates as $dateStr) {
            $dayStart = Carbon::parse($dateStr, $tz)->startOfDay();
            $dayEnd = $dayStart->copy()->endOfDay();

            $affectedTasks = $user->tasks()
                ->where('status', 'scheduled')
                ->where('is_ai_scheduled', true)
                ->where('is_pinned', false)
                ->where('scheduled_start', '>=', $dayStart->utc())
                ->where('scheduled_start', '<=', $dayEnd->utc())
                ->get();

            if ($affectedTasks->isNotEmpty()) {
                $hasAffectedTasks = true;
                $affectedTasks->each(fn ($task) => $task->update([
                    'status' => 'pending',
                    'scheduled_start' => null,
                    'scheduled_end' => null,
                    'is_ai_scheduled' => false,
                    'ai_reasoning' => null,
                ]));
            }
        }

        if ($hasAffectedTasks) {
            ScheduleTasksJob::debounce($user);
        }

        return $hasAffectedTasks;
    }
}
