<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->settingExists('preferences.task_view')) {
            $this->migrator->add('preferences.task_view', 'list');
        }
    }

    private function settingExists(string $property): bool
    {
        [$group, $name] = explode('.', $property);

        return DB::table('settings')->where('group', $group)->where('name', $name)->exists();
    }
};
