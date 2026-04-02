<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('preferences.dark_mode', false);
        $this->migrator->add('preferences.calendar_view', 'week');
    }
};
