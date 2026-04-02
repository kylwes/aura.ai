<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Task> */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'estimated_duration' => fake()->randomElement([30, 45, 60, 90, 120, 150, 180]),
            'status' => TaskStatus::Pending,
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn () => [
            'status' => TaskStatus::Scheduled,
            'scheduled_start' => now()->addHours(fake()->numberBetween(1, 8)),
            'scheduled_end' => now()->addHours(fake()->numberBetween(9, 12)),
            'is_ai_scheduled' => true,
            'ai_reasoning' => fake()->sentence(),
        ]);
    }

    public function withSource(): static
    {
        return $this->state(fn () => [
            'source_url' => fake()->url(),
            'source_reference' => 'AUR-'.fake()->numberBetween(100, 999),
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn () => [
            'priority' => TaskPriority::Urgent,
            'deadline' => now()->addDay(),
        ]);
    }
}
