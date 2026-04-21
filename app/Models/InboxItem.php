<?php

namespace App\Models;

use App\Enums\InboxItemStatus;
use Database\Factories\InboxItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'integration_id', 'channel_name', 'preview_text',
    'source_url', 'ai_suggested_priority', 'ai_confidence', 'ai_estimated_duration',
    'ai_suggested_project_id', 'ai_action', 'ai_reasoning', 'status', 'snoozed_until',
])]
class InboxItem extends Model
{
    /** @use HasFactory<InboxItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => InboxItemStatus::class,
            'ai_confidence' => 'integer',
            'snoozed_until' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function suggestedProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'ai_suggested_project_id');
    }
}
