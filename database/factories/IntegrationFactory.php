<?php

namespace Database\Factories;

use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use App\Models\Integration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Integration> */
class IntegrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(IntegrationType::cases()),
            'status' => IntegrationStatus::Connected,
            'configuration' => null,
            'connected_at' => now(),
        ];
    }

    public function disconnected(): static
    {
        return $this->state(fn () => [
            'status' => IntegrationStatus::Disconnected,
            'connected_at' => null,
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn () => [
            'status' => IntegrationStatus::Paused,
        ]);
    }
}
