<?php

use App\Ai\Agents\TaskScheduler;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Jobs\ResolveOverlapsJob;
use App\Jobs\ScheduleTasksJob;
use App\Livewire\StaleTaskNudge;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

it('increments reschedule_count when scheduling a task that was previously at a different time', function () {
    Carbon::setTestNow('2026-04-02 09:00:00');

    $user = User::factory()->create([
        'onboarded_at' => now(),
        'timezone' => 'UTC',
        'buffer_time' => 15,
    ]);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => TaskStatus::Scheduled,
        'scheduled_start' => now()->setTime(11, 0),
        'scheduled_end' => now()->setTime(12, 0),
        'is_ai_scheduled' => true,
        'is_pinned' => false,
        'estimated_duration' => 60,
        'reschedule_count' => 0,
    ]);

    TaskScheduler::fake(function () use ($task) {
        return [
            'scheduled_tasks' => [
                [
                    'task_id' => $task->id,
                    'date' => '2026-04-02',
                    'start_time' => '09:00',
                    'reasoning' => 'Moved to earlier slot.',
                ],
            ],
        ];
    });

    ScheduleTasksJob::dispatchSync($user);

    $task->refresh();
    expect($task->reschedule_count)->toBe(1)
        ->and($task->last_rescheduled_at)->not->toBeNull();

    Carbon::setTestNow();
});

it('does not increment reschedule_count when scheduling a task for the first time', function () {
    Carbon::setTestNow('2026-04-02 09:00:00');

    $user = User::factory()->create([
        'onboarded_at' => now(),
        'timezone' => 'UTC',
        'buffer_time' => 15,
    ]);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => TaskStatus::Pending,
        'scheduled_start' => null,
        'estimated_duration' => 60,
        'reschedule_count' => 0,
    ]);

    TaskScheduler::fake(function () use ($task) {
        return [
            'scheduled_tasks' => [
                [
                    'task_id' => $task->id,
                    'date' => '2026-04-02',
                    'start_time' => '09:00',
                    'reasoning' => 'First scheduling.',
                ],
            ],
        ];
    });

    ScheduleTasksJob::dispatchSync($user);

    $task->refresh();
    expect($task->reschedule_count)->toBe(0)
        ->and($task->last_rescheduled_at)->toBeNull();

    Carbon::setTestNow();
});

it('increments reschedule_count when ResolveOverlapsJob resets a task to pending', function () {
    $user = User::factory()->create(['onboarded_at' => now(), 'timezone' => 'UTC']);

    $blockStart = now()->addHour();
    $blockEnd = now()->addHours(2);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => TaskStatus::Scheduled,
        'scheduled_start' => $blockStart->copy()->addMinutes(10),
        'scheduled_end' => $blockStart->copy()->addMinutes(70),
        'is_ai_scheduled' => true,
        'is_pinned' => false,
        'reschedule_count' => 1,
        'last_rescheduled_at' => now()->subHour(),
    ]);

    Queue::fake();

    (new ResolveOverlapsJob($user, $blockStart, $blockEnd))->handle();

    $task->refresh();
    expect($task->reschedule_count)->toBe(2)
        ->and($task->status)->toBe(TaskStatus::Pending)
        ->and($task->last_rescheduled_at->isToday())->toBeTrue();
});

it('includes STALE marker in buildContext for tasks with reschedule_count >= 3', function () {
    Carbon::setTestNow('2026-04-02 09:00:00');

    $user = User::factory()->create([
        'onboarded_at' => now(),
        'timezone' => 'UTC',
    ]);

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Stale bugfix task',
        'status' => TaskStatus::Pending,
        'reschedule_count' => 4,
    ]);

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Fresh new task',
        'status' => TaskStatus::Pending,
        'reschedule_count' => 1,
    ]);

    $context = TaskScheduler::buildContext($user);

    expect($context)
        ->toContain('STALE: rescheduled 4 times')
        ->toContain('Stale bugfix task')
        ->not->toContain('STALE: rescheduled 1 times');

    Carbon::setTestNow();
});

it('does not include STALE marker for tasks with reschedule_count below 3', function () {
    Carbon::setTestNow('2026-04-02 09:00:00');

    $user = User::factory()->create([
        'onboarded_at' => now(),
        'timezone' => 'UTC',
    ]);

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Almost stale task',
        'status' => TaskStatus::Pending,
        'reschedule_count' => 2,
    ]);

    $context = TaskScheduler::buildContext($user);

    expect($context)
        ->toContain('Almost stale task')
        ->not->toContain('STALE');

    Carbon::setTestNow();
});

it('renders stale tasks in StaleTaskNudge component', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Repeatedly bumped task',
        'status' => TaskStatus::Pending,
        'reschedule_count' => 5,
    ]);

    Livewire::actingAs($user)
        ->test(StaleTaskNudge::class)
        ->assertSee('Repeatedly bumped task')
        ->assertSee('Rescheduled 5x');
});

it('does not render tasks with reschedule_count below 3 in StaleTaskNudge', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Not yet stale',
        'status' => TaskStatus::Pending,
        'reschedule_count' => 2,
    ]);

    Livewire::actingAs($user)
        ->test(StaleTaskNudge::class)
        ->assertDontSee('Not yet stale');
});

it('dismiss action sets task status to dismissed', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => TaskStatus::Pending,
        'reschedule_count' => 3,
    ]);

    Livewire::actingAs($user)
        ->test(StaleTaskNudge::class)
        ->call('dismiss', $task->id);

    expect($task->fresh()->status)->toBe(TaskStatus::Dismissed);
});

it('escalate action sets priority to urgent and resets reschedule_count', function () {
    Queue::fake();

    $user = User::factory()->create(['onboarded_at' => now()]);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => TaskStatus::Pending,
        'priority' => TaskPriority::Low,
        'reschedule_count' => 4,
    ]);

    Livewire::actingAs($user)
        ->test(StaleTaskNudge::class)
        ->call('escalate', $task->id);

    $task->refresh();
    expect($task->priority)->toBe(TaskPriority::Urgent)
        ->and($task->reschedule_count)->toBe(0);

    Queue::assertPushed(ScheduleTasksJob::class, fn ($job) => $job->user->id === $user->id);
});
