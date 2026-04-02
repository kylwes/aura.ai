<?php

namespace App\Livewire;

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

        return view('livewire.top-bar', [
            'inboxCount' => $this->pendingInboxCount(),
            'dateLabel' => $dateLabel,
        ]);
    }
}
