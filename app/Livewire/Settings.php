<?php

namespace App\Livewire;

use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use App\Jobs\ScheduleTasksJob;
use App\Jobs\SyncGoogleCalendarJob;
use App\Models\WorkSchedule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Settings — Aura')]
class Settings extends Component
{
    public string $activeTab = 'integrations';

    /** @var array<int, array{day: int, enabled: bool, start: ?string, end: ?string, lunch_start: ?string, lunch_end: ?string}> */
    public array $schedules = [];

    public bool $focusTimeEnabled;

    public ?string $focusTimeStart = null;

    public ?string $focusTimeEnd = null;

    public bool $focusTimeProtected = false;

    public int $maxTaskDuration;

    public int $bufferTime;

    #[On('integration-updated')]
    public function refreshIntegrations(): void {}

    public function mount(): void
    {
        $user = auth()->user();

        $this->focusTimeEnabled = $user->focus_time_enabled ?? false;
        $this->focusTimeStart = $user->focus_time_start;
        $this->focusTimeEnd = $user->focus_time_end;
        $this->focusTimeProtected = $user->focus_time_protected ?? false;
        $this->maxTaskDuration = $user->max_task_duration ?? 120;
        $this->bufferTime = $user->buffer_time ?? 15;

        $this->loadSchedules();
    }

    public function loadSchedules(): void
    {
        $this->schedules = auth()->user()->workSchedules
            ->map(fn (WorkSchedule $ws) => [
                'id' => $ws->id,
                'day' => $ws->day,
                'day_name' => $ws->dayName(),
                'enabled' => $ws->enabled,
                'start' => $ws->start,
                'end' => $ws->end,
                'lunch_start' => $ws->lunch_start,
                'lunch_end' => $ws->lunch_end,
            ])
            ->values()
            ->toArray();
    }

    public function updatedSchedules(mixed $value, string $key): void
    {
        $parts = explode('.', $key);
        if (count($parts) !== 2) {
            return;
        }

        [$index, $field] = $parts;
        $schedule = $this->schedules[(int) $index] ?? null;

        if (! $schedule) {
            return;
        }

        $data = [$field => $value];

        if ($field === 'enabled' && ! $value) {
            $data['start'] = null;
            $data['end'] = null;
            $data['lunch_start'] = null;
            $data['lunch_end'] = null;
            $this->schedules[(int) $index]['start'] = null;
            $this->schedules[(int) $index]['end'] = null;
            $this->schedules[(int) $index]['lunch_start'] = null;
            $this->schedules[(int) $index]['lunch_end'] = null;
        }

        if ($field === 'enabled' && $value) {
            $data['start'] = '09:00';
            $data['end'] = '17:30';
            $data['lunch_start'] = '12:00';
            $data['lunch_end'] = '13:00';
            $this->schedules[(int) $index]['start'] = '09:00';
            $this->schedules[(int) $index]['end'] = '17:30';
            $this->schedules[(int) $index]['lunch_start'] = '12:00';
            $this->schedules[(int) $index]['lunch_end'] = '13:00';
        }

        WorkSchedule::where('id', $schedule['id'])->update($data);

        $this->rescheduleIfNeeded();
    }

    private function rescheduleIfNeeded(): void
    {
        $user = auth()->user();
        $tz = $user->timezone ?? 'UTC';
        $schedules = $user->workSchedules()->get()->keyBy('day');

        $tasksToReset = $user->tasks()
            ->where('status', 'scheduled')
            ->where('is_ai_scheduled', true)
            ->where('scheduled_start', '>=', now())
            ->get()
            ->filter(function ($task) use ($schedules, $tz) {
                $start = $task->scheduled_start->copy()->setTimezone($tz);
                $end = $task->scheduled_end->copy()->setTimezone($tz);
                $daySchedule = $schedules->get($start->dayOfWeekIso);

                if (! $daySchedule || ! $daySchedule->enabled || ! $daySchedule->start || ! $daySchedule->end) {
                    return true;
                }

                $workStart = $start->copy()->setTimeFromTimeString($daySchedule->start);
                $workEnd = $start->copy()->setTimeFromTimeString($daySchedule->end);

                return $start->lessThan($workStart) || $end->greaterThan($workEnd);
            });

        if ($tasksToReset->isEmpty()) {
            return;
        }

        $tasksToReset->each(fn ($task) => $task->update([
            'status' => 'pending',
            'scheduled_start' => null,
            'scheduled_end' => null,
            'is_ai_scheduled' => false,
            'ai_reasoning' => null,
        ]));

        ScheduleTasksJob::debounce($user);
    }

    public function savePreferences(): void
    {
        auth()->user()->update([
            'focus_time_enabled' => $this->focusTimeEnabled,
            'focus_time_start' => $this->focusTimeStart,
            'focus_time_end' => $this->focusTimeEnd,
            'focus_time_protected' => $this->focusTimeProtected,
            'max_task_duration' => $this->maxTaskDuration,
            'buffer_time' => $this->bufferTime,
        ]);

        ScheduleTasksJob::debounce(auth()->user());

        session()->flash('message', 'Preferences saved.');
    }

    public function syncGoogleCalendar(): void
    {
        $user = auth()->user();
        $integration = $user->integrations()
            ->where('type', IntegrationType::GoogleCalendar)
            ->where('status', IntegrationStatus::Connected)
            ->first();

        if ($integration) {
            SyncGoogleCalendarJob::dispatch($user);
            session()->flash('message', 'Sync started. Events will appear shortly.');
        }
    }

    public function disconnectGoogleCalendar(): void
    {
        $user = auth()->user();
        $integration = $user->integrations()
            ->where('type', IntegrationType::GoogleCalendar)
            ->first();

        if ($integration) {
            $integration->update([
                'status' => IntegrationStatus::Disconnected,
                'configuration' => null,
            ]);
            session()->flash('message', 'Google Calendar disconnected.');
        }
    }

    public function render()
    {
        $user = auth()->user();
        $connectedIntegrations = $user->integrations()->get()->keyBy(fn ($i) => $i->type->value);

        return view('livewire.settings', [
            'integrationTypes' => IntegrationType::cases(),
            'connectedIntegrations' => $connectedIntegrations,
        ]);
    }
}
