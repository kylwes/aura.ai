<?php

namespace App\Models;

use App\Enums\TaskStatus;
use App\Events\ScheduleCompleted;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'trigger', 'description', 'task_states', 'created_at'])]
class ScheduleSnapshot extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'task_states' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $snapshot) {
            $snapshot->created_at = now();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function capture(User $user, string $trigger, ?string $description = null): self
    {
        $tasks = $user->tasks()
            ->whereIn('status', [TaskStatus::Scheduled, TaskStatus::Pending])
            ->with('blocks')
            ->get();

        $states = $tasks->map(fn ($task) => [
            'task_id' => $task->id,
            'status' => $task->status->value,
            'scheduled_start' => $task->scheduled_start?->toISOString(),
            'scheduled_end' => $task->scheduled_end?->toISOString(),
            'is_ai_scheduled' => $task->is_ai_scheduled,
            'is_pinned' => $task->is_pinned,
            'ai_reasoning' => $task->ai_reasoning,
            'blocks' => $task->blocks->map(fn ($b) => [
                'scheduled_start' => $b->scheduled_start->toISOString(),
                'scheduled_end' => $b->scheduled_end->toISOString(),
            ])->all(),
        ])->all();

        $snapshot = self::create([
            'user_id' => $user->id,
            'trigger' => $trigger,
            'description' => $description,
            'task_states' => $states,
            'created_at' => now(),
        ]);

        // Prune old snapshots, keep last 20
        $keepIds = self::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->pluck('id');

        self::where('user_id', $user->id)
            ->whereNotIn('id', $keepIds)
            ->delete();

        return $snapshot;
    }

    public function restore(): void
    {
        $user = $this->user;

        foreach ($this->task_states as $state) {
            $task = $user->tasks()->find($state['task_id']);
            if (! $task) {
                continue;
            }

            $task->update([
                'status' => $state['status'],
                'scheduled_start' => $state['scheduled_start'],
                'scheduled_end' => $state['scheduled_end'],
                'is_ai_scheduled' => $state['is_ai_scheduled'],
                'is_pinned' => $state['is_pinned'],
                'ai_reasoning' => $state['ai_reasoning'],
            ]);

            // Recreate blocks
            $task->blocks()->delete();
            foreach ($state['blocks'] as $block) {
                TaskBlock::create([
                    'task_id' => $task->id,
                    'scheduled_start' => $block['scheduled_start'],
                    'scheduled_end' => $block['scheduled_end'],
                ]);
            }
        }

        ScheduleCompleted::dispatch($user->id);
    }

    public function taskCount(): int
    {
        return count($this->task_states);
    }
}
