<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'date', 'is_day_off', 'start', 'end', 'lunch_start', 'lunch_end'])]
class DayOverride extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_day_off' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
