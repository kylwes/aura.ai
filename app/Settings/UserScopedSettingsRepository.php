<?php

namespace App\Settings;

use Spatie\LaravelSettings\SettingsRepositories\DatabaseSettingsRepository;

class UserScopedSettingsRepository extends DatabaseSettingsRepository
{
    public function updatePropertiesPayload(string $group, array $properties): void
    {
        $propertiesInBatch = collect($properties)->map(function ($payload, $name) use ($group) {
            return [
                'user_id' => auth()->id(),
                'group' => $group,
                'name' => $name,
                'payload' => $this->encode($payload),
            ];
        })->values()->toArray();

        $this->getBuilder()
            ->where('group', $group)
            ->upsert($propertiesInBatch, ['user_id', 'group', 'name'], ['payload']);
    }

    public function createProperty(string $group, string $name, $payload): void
    {
        $this->getBuilder()->create([
            'user_id' => auth()->id(),
            'group' => $group,
            'name' => $name,
            'payload' => $this->encode($payload),
            'locked' => false,
        ]);
    }
}
