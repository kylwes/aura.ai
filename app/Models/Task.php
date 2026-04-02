<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'project_id', 'integration_id', 'title', 'description', 'source_url',
    'source_reference', 'priority', 'estimated_duration', 'deadline',
    'scheduled_start', 'scheduled_end', 'is_ai_scheduled', 'is_pinned',
    'ai_reasoning', 'google_event_id', 'status',
])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
            'estimated_duration' => 'integer',
            'deadline' => 'datetime',
            'scheduled_start' => 'datetime',
            'scheduled_end' => 'datetime',
            'is_ai_scheduled' => 'boolean',
            'is_pinned' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(TaskBlock::class)->orderBy('scheduled_start');
    }

    public function formattedDuration(): string
    {
        if (! $this->estimated_duration) {
            return '';
        }
        $hours = intdiv($this->estimated_duration, 60);
        $minutes = $this->estimated_duration % 60;
        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        }

        return $hours > 0 ? "{$hours}h" : "{$minutes}m";
    }
}
