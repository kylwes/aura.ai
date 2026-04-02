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
            $this->start = $override->start;
            $this->end = $override->end;
            $this->lunchStart = $override->lunch_start;
            $this->lunchEnd = $override->lunch_end;
        } else {
            $schedule = $user->effectiveScheduleFor(Carbon::parse($date));
            $this->isDayOff = ! $schedule['enabled'];
            $this->start = $schedule['start'];
            $this->end = $schedule['end'];
            $this->lunchStart = $schedule['lunch_start'];
            $this->lunchEnd = $schedule['lunch_end'];
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

        $this->rescheduleAffectedTasks($user, [$this->date]);

        $this->dispatch('day-override-saved');
        $this->forceClose()->closeModal();
    }

    public function resetToDefault(): void
    {
        $user = auth()->user();

        DayOverride::where('user_id', $user->id)
            ->where('date', $this->date)
            ->delete();

        $this->rescheduleAffectedTasks($user, [$this->date]);

        $this->dispatch('day-override-saved');
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
    private function rescheduleAffectedTasks(mixed $user, array $dates): void
    {
        $tz = $user->timezone ?? 'UTC';

        $hasAffectedTasks = false;
        foreach ($dates as $dateStr) {
            $schedule = $user->effectiveScheduleFor(Carbon::parse($dateStr));
            $dayStart = Carbon::parse($dateStr, $tz)->startOfDay();
            $dayEnd = $dayStart->copy()->endOfDay();

            $affectedTasks = $user->tasks()
                ->where('status', 'scheduled')
                ->where('is_ai_scheduled', true)
                ->where('is_pinned', false)
                ->where('scheduled_start', '>=', $dayStart->utc())
                ->where('scheduled_start', '<=', $dayEnd->utc())
                ->get()
                ->filter(function ($task) use ($schedule, $tz) {
                    if (! $schedule['enabled']) {
                        return true;
                    }

                    $start = $task->scheduled_start->copy()->setTimezone($tz);
                    $end = $task->scheduled_end->copy()->setTimezone($tz);
                    $workStart = $start->copy()->setTimeFromTimeString($schedule['start']);
                    $workEnd = $start->copy()->setTimeFromTimeString($schedule['end']);

                    return $start->lessThan($workStart) || $end->greaterThan($workEnd);
                });

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
            ScheduleTasksJob::dispatch($user);
        }
    }
}
