<?php

use App\Ai\Agents\TaskScheduler;
use App\Enums\TaskStatus;
use App\Jobs\ScheduleTasksJob;
use App\Livewire\Pages\PlannerPage;
use App\Models\CalendarEvent;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

it('builds context with pending tasks and available slots', function () {
    Carbon::setTestNow('2026-04-02 09:00:00');

    $user = User::factory()->create([
        'onboarded_at' => now(),
        'buffer_time' => 15,
        'timezone' => 'UTC',
    ]);

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Fix login bug',
        'priority' => 'urgent',
        'estimated_duration' => 60,
        'status' => 'pending',
    ]);

    CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'title' => 'Team Standup',
        'starts_at' => now()->setTime(10, 0),
        'ends_at' => now()->setTime(10, 30),
    ]);

    $context = TaskScheduler::buildContext($user);

    expect($context)
        ->toContain('Fix login bug')
        ->toContain('Priority: urgent')
        ->toContain('Duration: 60min')
        ->toContain('Available Time Slots');

    // Verify slots don't overlap with the event (10:00-10:30)
    $slots = TaskScheduler::computeAvailableSlots($user);
    $slotTimes = collect($slots)->map(fn ($s) => $s['start'])->toArray();
    expect($slotTimes)->toContain('09:00') // Before the event
        ->toContain('10:45'); // After event (10:30 + 15 buffer)

    Carbon::setTestNow();
});

it('schedules tasks from agent response', function () {
    Carbon::setTestNow('2026-04-02 09:00:00');

    $user = User::factory()->create([
        'onboarded_at' => now(),
        'timezone' => 'UTC',
    ]);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Fix login bug',
        'priority' => 'urgent',
        'estimated_duration' => 60,
        'status' => 'pending',
    ]);

    TaskScheduler::fake(function () use ($task) {
        return [
            'scheduled_tasks' => [
                [
                    'task_id' => $task->id,
                    'date' => '2026-04-02',
                    'start_time' => '09:00',
                    'reasoning' => 'Urgent task scheduled at start of working day.',
                ],
            ],
        ];
    });

    ScheduleTasksJob::dispatchSync($user);

    $task->refresh();
    expect($task->status)->toBe(TaskStatus::Scheduled)
        ->and($task->is_ai_scheduled)->toBeTrue()
        ->and($task->ai_reasoning)->toBe('Urgent task scheduled at start of working day.')
        ->and($task->scheduled_start->format('Y-m-d H:i'))->toBe('2026-04-02 09:00')
        ->and($task->scheduled_end->format('Y-m-d H:i'))->toBe('2026-04-02 10:00');

    Carbon::setTestNow();
});

it('skips tasks that are not pending', function () {
    $user = User::factory()->create([
        'onboarded_at' => now(),
        'timezone' => 'UTC',
    ]);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'completed',
    ]);

    TaskScheduler::fake(function () use ($task) {
        return [
            'scheduled_tasks' => [
                [
                    'task_id' => $task->id,
                    'date' => '2026-04-02',
                    'start_time' => '09:00',
                    'reasoning' => 'Test',
                ],
            ],
        ];
    });

    ScheduleTasksJob::dispatchSync($user);

    $task->refresh();
    expect($task->status->value)->toBe('completed')
        ->and($task->is_ai_scheduled)->toBeFalse();
});

it('skips unknown task ids', function () {
    $user = User::factory()->create([
        'onboarded_at' => now(),
        'timezone' => 'UTC',
    ]);

    TaskScheduler::fake(function () {
        return [
            'scheduled_tasks' => [
                [
                    'task_id' => 99999,
                    'date' => '2026-04-02',
                    'start_time' => '09:00',
                    'reasoning' => 'Test',
                ],
            ],
        ];
    });

    ScheduleTasksJob::dispatchSync($user);
});

it('uses default 60 min duration when task has no estimate', function () {
    Carbon::setTestNow('2026-04-02 09:00:00');

    $user = User::factory()->create([
        'onboarded_at' => now(),
        'timezone' => 'UTC',
        'buffer_time' => 15,
    ]);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending',
        'estimated_duration' => null,
    ]);

    TaskScheduler::fake(function () use ($task) {
        return [
            'scheduled_tasks' => [
                [
                    'task_id' => $task->id,
                    'date' => '2026-04-02',
                    'start_time' => '09:00',
                    'reasoning' => 'First available slot.',
                ],
            ],
        ];
    });

    ScheduleTasksJob::dispatchSync($user);

    $task->refresh();
    expect($task->scheduled_start->format('H:i'))->toBe('09:00')
        ->and($task->scheduled_end->format('H:i'))->toBe('10:00');

    Carbon::setTestNow();
});

it('dispatches job from autoSchedule on PlannerPage', function () {
    Queue::fake();

    $user = User::factory()->create(['onboarded_at' => now()]);

    Livewire::actingAs($user)
        ->test(PlannerPage::class)
        ->call('autoSchedule');

    Queue::assertPushed(ScheduleTasksJob::class, function ($job) use ($user) {
        return $job->user->id === $user->id;
    });
});
