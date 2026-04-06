<?php

namespace App\Livewire;

use App\Settings\UserPreferences;
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

    public function toggleProjectVisibility(int $projectId): void
    {
        $preferences = app(UserPreferences::class);
        $hidden = $preferences->hidden_project_ids;

        if (in_array($projectId, $hidden)) {
            $hidden = array_values(array_diff($hidden, [$projectId]));
        } else {
            $hidden[] = $projectId;
        }

        $preferences->hidden_project_ids = $hidden;
        $preferences->save();

        $this->dispatch('project-layers-changed');
    }

    public function showAllProjects(): void
    {
        $preferences = app(UserPreferences::class);
        $preferences->hidden_project_ids = [];
        $preferences->save();

        $this->dispatch('project-layers-changed');
    }

    public function hideAllProjects(): void
    {
        $preferences = app(UserPreferences::class);
        $preferences->hidden_project_ids = auth()->user()->projects()->pluck('id')->all();
        $preferences->save();

        $this->dispatch('project-layers-changed');
    }

    #[On('task-created')]
    #[On('task-scheduled')]
    public function refreshTasks(): void {}

    public function render()
    {
        $user = auth()->user();
        $hiddenProjectIds = app(UserPreferences::class)->hidden_project_ids;

        return view('livewire.sidebar', [
            'integrations' => $user->integrations()->get(),
            'projects' => $user->projects()->orderBy('title')->get(),
            'hiddenProjectIds' => $hiddenProjectIds,
            'unscheduledTasks' => $user->tasks()
                ->where('status', 'pending')
                ->where(fn ($q) => $q->whereNull('project_id')->orWhereNotIn('project_id', $hiddenProjectIds))
                ->orderBy('priority')
                ->get(),
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
