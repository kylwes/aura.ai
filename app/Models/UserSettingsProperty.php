<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\LaravelSettings\Models\SettingsProperty;

class UserSettingsProperty extends SettingsProperty
{
    protected static function booted(): void
    {
        static::addGlobalScope('user', function (Builder $query) {
            $query->where('user_id', auth()->id());
        });

        static::creating(function (self $property) {
            $property->user_id ??= auth()->id();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
