<?php

namespace App\Jobs;

use App\Enums\TaskStatus;
use App\Models\ScheduleSnapshot;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ResolveOverlapsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Carbon $blockStart,
        public Carbon $blockEnd,
        public ?int $excludeTaskId = null,
    ) {}

    public function handle(): void
    {
        ScheduleSnapshot::capture($this->user, 'overlap_resolve', 'Before overlap resolution');

        // Find non-pinned AI-scheduled tasks that overlap with the moved block
        $overlapping = $this->user->tasks()
            ->where('status', TaskStatus::Scheduled)
            ->where('is_ai_scheduled', true)
            ->where('is_pinned', false)
            ->where('scheduled_start', '<', $this->blockEnd)
            ->where('scheduled_end', '>', $this->blockStart)
            ->when($this->excludeTaskId, fn ($q) => $q->where('id', '!=', $this->excludeTaskId))
            ->get();

        if ($overlapping->isEmpty()) {
            return;
        }

        // Reset overlapping tasks to pending so the proposal can re-place them
        foreach ($overlapping as $task) {
            $task->update([
                'status' => TaskStatus::Pending,
                'scheduled_start' => null,
                'scheduled_end' => null,
                'is_ai_scheduled' => false,
                'ai_reasoning' => null,
                'reschedule_count' => $task->reschedule_count + 1,
                'last_rescheduled_at' => now(),
            ]);
        }

        // Generate a reschedule proposal for the user to review instead of scheduling immediately
        GenerateRescheduleProposalJob::dispatch($this->user, 'overlap', 'Tasks displaced by moved block');
    }
}
