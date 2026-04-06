<?php

namespace App\Settings;

use App\Enums\CalendarView;
use Spatie\LaravelSettings\Settings;

class UserPreferences extends Settings
{
    public bool $dark_mode;

    public CalendarView $calendar_view;

    public bool $event_panel_collapsed;

    public int $week_days_count;

    public string $task_view;

    /** @var array<int> */
    public array $hidden_project_ids;

    public static function group(): string
    {
        return 'preferences';
    }
}
