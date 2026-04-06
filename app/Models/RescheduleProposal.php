<?php

namespace App\Models;

use App\Enums\TaskStatus;
use App\Events\ScheduleCompleted;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'trigger_type', 'trigger_description',
    'proposed_changes', 'status', 'expires_at',
])]
class RescheduleProposal extends Model
{
    protected function casts(): array
    {
        return [
            'proposed_changes' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param Builder<RescheduleProposal> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'pending')->where('expires_at', '>', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function accept(): void
    {
        $user = $this->user;

        foreach ($this->proposed_changes as $change) {
            $task = $user->tasks()->find($change['task_id']);

            if (! $task) {
                continue;
            }

            $task->blocks()->delete();

            foreach ($change['blocks'] as $block) {
                TaskBlock::create([
                    'task_id' => $task->id,
                    'scheduled_start' => $block['start'],
                    'scheduled_end' => $block['end'],
                ]);
            }

            $task->update([
                'scheduled_start' => $change['new_start'],
                'scheduled_end' => $change['new_end'],
                'status' => TaskStatus::Scheduled->value,
                'is_ai_scheduled' => true,
                'ai_reasoning' => $change['reasoning'],
            ]);
        }

        $this->update(['status' => 'accepted']);

        ScheduleCompleted::dispatch($user->id);
    }

    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
    }
}
