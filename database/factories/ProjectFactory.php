<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'color' => fake()->randomElement([
                '#6366f1', '#8b5cf6', '#ec4899', '#f43f5e',
                '#f97316', '#eab308', '#22c55e', '#06b6d4',
                '#3b82f6', '#6b7280',
            ]),
        ];
    }
}
