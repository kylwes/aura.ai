<?php

namespace Database\Seeders;

use App\Jobs\ScheduleTasksJob;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'kylian@aura.ai')->firstOrFail();

        Task::factory()
            ->count(10)
            ->for($user)
            ->sequence(
                ['title' => 'Review Q2 marketing budget', 'estimated_duration' => 45, 'deadline' => now()->addDays(2)],
                ['title' => 'Prepare investor deck slides', 'estimated_duration' => 90, 'deadline' => now()->addDays(5)],
                ['title' => 'Fix broken checkout flow', 'estimated_duration' => 60, 'deadline' => now()->addDay()],
                ['title' => 'Write blog post about product launch', 'estimated_duration' => 120, 'deadline' => now()->addDays(7)],
                ['title' => 'Code review for auth refactor PR', 'estimated_duration' => 30],
                ['title' => 'Update API documentation', 'estimated_duration' => 45, 'deadline' => now()->addDays(3)],
                ['title' => 'Design new onboarding flow mockups', 'estimated_duration' => 90, 'deadline' => now()->addDays(4)],
                ['title' => 'Set up staging environment', 'estimated_duration' => 60],
                ['title' => 'Customer feedback analysis', 'estimated_duration' => 150, 'deadline' => now()->addDays(6)],
                ['title' => 'Plan sprint retrospective agenda', 'estimated_duration' => 30, 'deadline' => now()->addDays(2)],
            )
            ->create();

        ScheduleTasksJob::dispatch($user);

        $this->command->info('Created 10 tasks for kylian@aura.ai and dispatched scheduling job.');
    }
}
