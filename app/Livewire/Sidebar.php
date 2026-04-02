<?php

namespace App\Livewire;

use Illuminate\Support\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class Sidebar extends Component
{
    public Carbon $viewMonth;

    public function mount(): void
    {
        $this->viewMonth = now()->startOfMonth();
    }

    public function previousMonth(): void
    {
        $this->viewMonth = $this->viewMonth->subMonth();
    }

    public function nextMonth(): void
    {
        $this->viewMonth = $this->viewMonth->addMonth();
    }

    public function goToDate(string $date): void
    {
        $this->dispatch('calendar-go-to-date', date: $date);
    }

    #[On('task-created')]
    #[On('task-scheduled')]
    public function refreshTasks(): void {}

    public function render()
    {
        $user = auth()->user();

        return view('livewire.sidebar', [
            'integrations' => $user->integrations()->get(),
            'unscheduledTasks' => $user->tasks()->where('status', 'pending')->orderBy('priority')->get(),
            'calendarDays' => $this->buildMiniCalendar(),
        ]);
    }

    /** @return array<int, array{date: Carbon, inMonth: bool, isToday: bool}> */
    private function buildMiniCalendar(): array
    {
        $start = $this->viewMonth->copy()->startOfWeek();
        $end = $this->viewMonth->copy()->endOfMonth()->endOfWeek();
        $days = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $days[] = [
                'date' => $current->copy(),
                'inMonth' => $current->month === $this->viewMonth->month,
                'isToday' => $current->isToday(),
            ];
            $current->addDay();
        }

        return $days;
    }
}
