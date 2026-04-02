<?php

namespace App\Jobs;

use App\Enums\TaskStatus;
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

        // Set overlapping tasks back to pending so the AI can reschedule them
        foreach ($overlapping as $task) {
            $task->update([
                'status' => TaskStatus::Pending,
                'scheduled_start' => null,
                'scheduled_end' => null,
                'is_ai_scheduled' => false,
                'ai_reasoning' => null,
            ]);
        }

        // Let the TaskScheduler agent reschedule all pending tasks
        ScheduleTasksJob::dispatch($this->user);
    }
}
