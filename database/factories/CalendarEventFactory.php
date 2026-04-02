<?php

namespace Database\Factories;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CalendarEvent> */
class CalendarEventFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->setTime(fake()->numberBetween(8, 16), fake()->randomElement([0, 30]));

        return [
            'user_id' => User::factory(),
            'title' => fake()->randomElement([
                'Team Standup', 'Sprint Planning', 'Design Review',
                'Client Call', '1:1 with Manager', 'Lunch', 'Tech Talk',
            ]),
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMinutes(fake()->randomElement([30, 60, 90])),
            'is_all_day' => false,
        ];
    }

    public function allDay(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->startOfDay(),
            'ends_at' => now()->endOfDay(),
            'is_all_day' => true,
        ]);
    }
}
