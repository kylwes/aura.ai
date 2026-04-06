<?php

namespace App\Providers\TaskProviders;

use App\Enums\IntegrationType;
use App\Models\Integration;

abstract class TaskProvider
{
    abstract public function fetch(Integration $integration): array;

    abstract public function channel(): string;

    abstract public function format(array $item): string;

    public function getLastPolledAt(Integration $integration): ?string
    {
        return $integration->configuration['last_polled_at'] ?? null;
    }

    public function updateLastPolledAt(Integration $integration): void
    {
        $config = $integration->configuration ?? [];
        $config['last_polled_at'] = now()->toIso8601String();
        $integration->update(['configuration' => $config]);
    }

    public static function for(IntegrationType $type): ?static
    {
        return match ($type) {
            IntegrationType::Productive => new ProductiveProvider,
            IntegrationType::Jira => new JiraProvider,
            default => null,
        };
    }
}
