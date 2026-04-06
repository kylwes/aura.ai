<?php

namespace App\Livewire;

use App\Models\ScheduleSnapshot;
use Livewire\Attributes\On;
use Livewire\Component;

class UndoTimeline extends Component
{
    public bool $open = false;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function restore(int $snapshotId): void
    {
        $snapshot = ScheduleSnapshot::where('user_id', auth()->id())
            ->findOrFail($snapshotId);

        // Capture current state before undoing
        ScheduleSnapshot::capture(auth()->user(), 'manual', 'Before undo');

        $snapshot->restore();

        $this->open = false;
        $this->dispatch('toast', type: 'success', title: 'Schedule restored', body: 'Tasks have been rolled back.');
    }

    #[On('day-override-saved')]
    #[On('task-scheduled')]
    public function refresh(): void
    {
        // Re-render to show new snapshots
    }

    public function render()
    {
        $snapshots = ScheduleSnapshot::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('livewire.undo-timeline', compact('snapshots'));
    }
}
