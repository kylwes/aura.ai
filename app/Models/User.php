<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

#[Fillable([
    'name', 'email', 'password', 'timezone', 'avatar_url',
    'working_hours_start', 'working_hours_end', 'working_days',
    'focus_time_enabled', 'focus_time_start', 'focus_time_end',
    'focus_time_min_duration', 'max_task_duration', 'buffer_time',
    'onboarded_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'working_days' => 'array',
            'focus_time_enabled' => 'boolean',
            'focus_time_min_duration' => 'integer',
            'max_task_duration' => 'integer',
            'buffer_time' => 'integer',
            'onboarded_at' => 'datetime',
        ];
    }

    /**
     * Seed default user preferences if they don't exist yet.
     */
    public function ensureSettings(): void
    {
        $defaults = [
            'dark_mode' => json_encode(false),
            'calendar_view' => json_encode('week'),
            'event_panel_collapsed' => json_encode(false),
            'week_days_count' => json_encode(7),
            'task_view' => json_encode('list'),
        ];

        $existing = UserSettingsProperty::withoutGlobalScope('user')
            ->where('user_id', $this->id)
            ->where('group', 'preferences')
            ->pluck('name')
            ->toArray();

        foreach ($defaults as $name => $payload) {
            if (in_array($name, $existing)) {
                continue;
            }

            UserSettingsProperty::withoutGlobalScope('user')->create([
                'user_id' => $this->id,
                'group' => 'preferences',
                'name' => $name,
                'payload' => $payload,
                'locked' => false,
            ]);
        }
    }

    public function workSchedules(): HasMany
    {
        return $this->hasMany(WorkSchedule::class)->orderBy('day');
    }

    public function dayOverrides(): HasMany
    {
        return $this->hasMany(DayOverride::class)->orderBy('date');
    }

    /**
     * Get the effective schedule for a specific date.
     * DayOverride takes precedence over the recurring WorkSchedule.
     *
     * @return array{enabled: bool, start: ?string, end: ?string, lunch_start: ?string, lunch_end: ?string}
     */
    public function effectiveScheduleFor(Carbon $date): array
    {
        $dateString = $date->toDateString();

        // Query directly using DayOverride model
        $override = DayOverride::where('user_id', $this->id)
            ->whereDate('date', $dateString)
            ->first();

        if ($override) {
            return [
                'enabled' => ! $override->is_day_off,
                'start' => $override->is_day_off ? null : $override->start,
                'end' => $override->is_day_off ? null : $override->end,
                'lunch_start' => $override->is_day_off ? null : $override->lunch_start,
                'lunch_end' => $override->is_day_off ? null : $override->lunch_end,
            ];
        }

        $workSchedule = $this->workSchedules()->where('day', $date->dayOfWeekIso)->first();

        if (! $workSchedule || ! $workSchedule->enabled) {
            return [
                'enabled' => false,
                'start' => null,
                'end' => null,
                'lunch_start' => null,
                'lunch_end' => null,
            ];
        }

        return [
            'enabled' => true,
            'start' => $workSchedule->start,
            'end' => $workSchedule->end,
            'lunch_start' => $workSchedule->lunch_start,
            'lunch_end' => $workSchedule->lunch_end,
        ];
    }

    /**
     * Seed 7 work_schedule rows if none exist.
     * Mon-Fri: 09:00-17:30, lunch 12:00-13:00. Sat-Sun: disabled.
     */
    public function ensureWorkSchedule(): void
    {
        if ($this->workSchedules()->exists()) {
            return;
        }

        $weekday = ['start' => '09:00', 'end' => '17:30', 'lunch_start' => '12:00', 'lunch_end' => '13:00'];

        for ($day = 1; $day <= 7; $day++) {
            $enabled = $day <= 5;
            $this->workSchedules()->create([
                'day' => $day,
                'enabled' => $enabled,
                'start' => $enabled ? $weekday['start'] : null,
                'end' => $enabled ? $weekday['end'] : null,
                'lunch_start' => $enabled ? $weekday['lunch_start'] : null,
                'lunch_end' => $enabled ? $weekday['lunch_end'] : null,
            ]);
        }
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function inboxItems(): HasMany
    {
        return $this->hasMany(InboxItem::class);
    }

    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarded_at !== null;
    }
}
