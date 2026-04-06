<?php

namespace App\Livewire;

use App\Models\TaskBlock;
use App\Settings\UserPreferences;
use Illuminate\Support\Carbon;
use Livewire\Component;

class TopBar extends Component
{
    public string $title = '';

    public string $currentView = 'week';

    public int $weekDaysCount = 7;

    public Carbon $currentDate;

    public function mount(UserPreferences $preferences, string $title = ''): void
    {
        $this->title = $title;
        $this->currentView = $preferences->calendar_view->value;
        $this->weekDaysCount = $preferences->week_days_count;
        $this->currentDate = now();
    }

    public function setView(string $view): void
    {
        $this->currentView = $view;
        $this->dispatch('calendar-navigate', view: $this->currentView, date: $this->currentDate->toDateString());
    }

    public function setWeekDaysCount(int $days): void
    {
        $this->weekDaysCount = $days;

        $preferences = app(UserPreferences::class);
        $preferences->week_days_count = $days;
        $preferences->save();

        $this->dispatch('calendar-navigate', view: $this->currentView, date: $this->currentDate->toDateString());
    }

    public function previous(): void
    {
        $this->currentDate = match ($this->currentView) {
            'day' => $this->currentDate->subDay(),
            'week' => $this->currentDate->subDays($this->weekDaysCount),
            'month' => $this->currentDate->subMonthWithoutOverflow(),
        };
        $this->dispatch('calendar-navigate', view: $this->currentView, date: $this->currentDate->toDateString());
    }

    public function next(): void
    {
        $this->currentDate = match ($this->currentView) {
            'day' => $this->currentDate->addDay(),
            'week' => $this->currentDate->addDays($this->weekDaysCount),
            'month' => $this->currentDate->addMonthWithoutOverflow(),
        };
        $this->dispatch('calendar-navigate', view: $this->currentView, date: $this->currentDate->toDateString());
    }

    public function goToToday(): void
    {
        $this->currentDate = now();
        $this->dispatch('calendar-navigate', view: $this->currentView, date: $this->currentDate->toDateString());
    }

    public function pendingInboxCount(): int
    {
        return auth()->user()->inboxItems()->where('status', 'pending')->count();
    }

    public function render()
    {
        $dateLabel = match ($this->currentView) {
            'day' => $this->currentDate->format('l, M j, Y'),
            'week' => $this->currentDate->format('M j').' – '.$this->currentDate->copy()->addDays($this->weekDaysCount - 1)->format('M j, Y'),
            'month' => $this->currentDate->format('F Y'),
        };

        $tz = auth()->user()->timezone ?? 'UTC';
        $today = Carbon::now($tz)->startOfDay();
        $todayUtcStart = $today->copy()->utc();
        $todayUtcEnd = $today->copy()->endOfDay()->utc();

        $todayBlocks = TaskBlock::whereHas('task', fn ($q) => $q->where('user_id', auth()->id())->where('status', 'scheduled'))
            ->where('scheduled_start', '>=', $todayUtcStart)
            ->where('scheduled_start', '<', $todayUtcEnd)
            ->get();

        $taskCount = $todayBlocks->pluck('task_id')->unique()->count();
        $scheduledMinutes = $todayBlocks->sum(fn ($b) => $b->scheduled_start->diffInMinutes($b->scheduled_end));

        $schedule = auth()->user()->effectiveScheduleFor($today);
        $availableMinutes = 0;
        if ($schedule['enabled'] && $schedule['start'] && $schedule['end']) {
            $availableMinutes = Carbon::parse($schedule['start'])->diffInMinutes(Carbon::parse($schedule['end']));
            if ($schedule['lunch_start'] && $schedule['lunch_end']) {
                $availableMinutes -= Carbon::parse($schedule['lunch_start'])->diffInMinutes($schedule['lunch_end']);
            }
        }
        $freeMinutes = max(0, $availableMinutes - $scheduledMinutes);

        return view('livewire.top-bar', [
            'inboxCount' => $this->pendingInboxCount(),
            'dateLabel' => $dateLabel,
            'taskCount' => $taskCount,
            'scheduledMinutes' => $scheduledMinutes,
            'freeMinutes' => $freeMinutes,
            'availableMinutes' => $availableMinutes,
        ]);
    }
}
