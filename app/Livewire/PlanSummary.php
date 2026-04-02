<?php

namespace App\Livewire;

use App\Enums\TaskStatus;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('AI Schedule Proposal — Aura')]
class PlanSummary extends Component
{
    /** @var array<int, bool> */
    public array $approved = [];

    public function approve(int $taskId): void
    {
        $this->approved[$taskId] = true;
    }

    public function remove(int $taskId): void
    {
        $task = auth()->user()->tasks()->findOrFail($taskId);
        $task->update([
            'status' => TaskStatus::Pending,
            'scheduled_start' => null,
            'scheduled_end' => null,
            'is_ai_scheduled' => false,
        ]);
        unset($this->approved[$taskId]);
    }

    public function approveAll(): void
    {
        $tasks = $this->getTasks();
        foreach ($tasks as $task) {
            $this->approved[$task->id] = true;
        }
    }

    public function render()
    {
        $tasks = $this->getTasks();
        $totalDuration = $tasks->sum('estimated_duration');
        $hours = intdiv($totalDuration, 60);
        $minutes = $totalDuration % 60;

        return view('livewire.plan-summary', [
            'tasks' => $tasks,
            'totalTasks' => $tasks->count(),
            'totalDuration' => ($hours > 0 ? "{$hours}h " : '').($minutes > 0 ? "{$minutes}m" : ''),
        ]);
    }

    private function getTasks()
    {
        return auth()->user()->tasks()
            ->where('status', TaskStatus::Scheduled)
            ->where('is_ai_scheduled', true)
            ->with('integration')
            ->orderBy('scheduled_start')
            ->get();
    }
}
