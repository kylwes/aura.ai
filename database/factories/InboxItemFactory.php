<?php

namespace Database\Factories;

use App\Enums\InboxItemStatus;
use App\Enums\TaskPriority;
use App\Models\InboxItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InboxItem> */
class InboxItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'channel_name' => fake()->randomElement(['#dev-team', '#design', '#general', 'inbox', 'AUR-'.fake()->numberBetween(100, 999)]),
            'preview_text' => fake()->sentence(10),
            'source_url' => fake()->url(),
            'ai_suggested_priority' => fake()->randomElement(TaskPriority::cases())->value,
            'ai_confidence' => fake()->numberBetween(1, 3),
            'status' => InboxItemStatus::Pending,
        ];
    }

    public function snoozed(): static
    {
        return $this->state(fn () => [
            'status' => InboxItemStatus::Snoozed,
            'snoozed_until' => now()->addHours(2),
        ]);
    }
}
