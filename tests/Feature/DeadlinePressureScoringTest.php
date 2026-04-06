<?php

use App\Ai\Agents\TaskScheduler;
use App\Jobs\ScheduleTasksJob;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;

// ---------------------------------------------------------------------------
// computeCapacityByDate
// ---------------------------------------------------------------------------

it('sums slot minutes per date correctly', function () {
    Carbon::setTestNow('2026-04-02 09:00:00');

    $user = User::factory()->create([
        'onboarded_at' => now(),
        'timezone' => 'UTC',
    ]);

    $job = new ScheduleTasksJob($user);
    $reflection = new ReflectionMethod($job, 'computeCapacityByDate');
    $reflection->setAccessible(true);

    $slots = [
        ['date' => '2026-04-02', 'start' => '09:00', 'end' => '12:00'],  // 180 min
        ['date' => '2026-04-02', 'start' => '13:00', 'end' => '17:00'],  // 240 min
        ['date' => '2026-04-03', 'start' => '09:00', 'end' => '11:00'],  // 120 min
    ];

    $result = $reflection->invoke($job, $slots, 'UTC');

    expect($result)->toBe([
        '2026-04-02' => 420,
        '2026-04-03' => 120,
    ]);

    Carbon::setTestNow();
});

it('handles multiple non-contiguous slots on the same date', function () {
    $user = User::factory()->create(['onboarded_at' => now(), 'timezone' => 'UTC']);

    $job = new ScheduleTasksJob($user);
    $reflection = new ReflectionMethod($job, 'computeCapacityByDate');
    $reflection->setAccessible(true);

    $slots = [
        ['date' => '2026-04-05', 'start' => '09:00', 'end' => '10:00'],  // 60 min
        ['date' => '2026-04-05', 'start' => '14:00', 'end' => '14:30'],  // 30 min
        ['date' => '2026-04-05', 'start' => '15:00', 'end' => '17:00'],  // 120 min
    ];

    $result = $reflection->invoke($job, $slots, 'UTC');

    expect($result['2026-04-05'])->toBe(210);
});

// ---------------------------------------------------------------------------
// urgencyScore via public interface (tested indirectly through sort behavior)
// and via reflection for unit-level assertions
// ---------------------------------------------------------------------------

it('returns base priority score when task has no deadline', function () {
    Carbon::setTestNow('2026-04-02 09:00:00');

    $user = User::factory()->create(['onboarded_at' => now(), 'timezone' => 'UTC']);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'priority' => 'medium',
        'deadline' => null,
    ]);

    $job = new ScheduleTasksJob($user);
    $reflection = new ReflectionMethod($job, 'urgencyScore');
    $reflection->setAccessible(true);

    $priorityWeight = ['urgent' => 0, 'high' => 10, 'medium' => 20, 'low' => 30];
    $score = $reflection->invoke($job, $task, $priorityWeight, null);

    expect($score)->toBe(20);

    Carbon::setTestNow();
});

it('subtracts 35 extra points when capacity ratio is below 1.0 (impossible deadline)', function () {
    Carbon::setTestNow('2026-04-02 09:00:00');

    $user = User::factory()->create(['onboarded_at' => now(), 'timezone' => 'UTC']);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'priority' => 'high',
        'deadline' => Carbon::parse('2026-04-10'),
        'estimated_duration' => 60,
    ]);

    $job = new ScheduleTasksJob($user);
    $reflection = new ReflectionMethod($job, 'urgencyScore');
    $reflection->setAccessible(true);

    $priorityWeight = ['urgent' => 0, 'high' => 10, 'medium' => 20, 'low' => 30];
    // ratio 0.5 → critical
    $score = $reflection->invoke($job, $task, $priorityWeight, 0.5);

    // base 10, no calendar penalty (>3 days), capacity -35 = -25
    expect($score)->toBe(-25);

    Carbon::setTestNow();
});

it('subtracts 20 extra points when capacity ratio is tight (1.0 – 1.5)', function () {
    Carbon::setTestNow('2026-04-02 09:00:00');

    $user = User::factory()->create(['onboarded_at' => now(), 'timezone' => 'UTC']);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'priority' => 'medium',
        'deadline' => Carbon::parse('2026-04-10'),
        'estimated_duration' => 60,
    ]);

    $job = new ScheduleTasksJob($user);
    $reflection = new ReflectionMethod($job, 'urgencyScore');
    $reflection->setAccessible(true);

    $priorityWeight = ['urgent' => 0, 'high' => 10, 'medium' => 20, 'low' => 30];
    $score = $reflection->invoke($job, $task, $priorityWeight, 1.2);

    // base 20, no calendar penalty (>3 days), capacity -20 = 0
    expect($score)->toBe(0);

    Carbon::setTestNow();
});

it('subtracts 10 extra points when capacity ratio is moderate (1.5 – 2.0)', function () {
    Carbon::setTestNow('2026-04-02 09:00:00');

    $user = User::factory()->create(['onboarded_at' => now(), 'timezone' => 'UTC']);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'priority' => 'low',
        'deadline' => Carbon::parse('2026-04-10'),
        'estimated_duration' => 60,
    ]);

    $job = new ScheduleTasksJob($user);
    $reflection = new ReflectionMethod($job, 'urgencyScore');
    $reflection->setAccessible(true);

    $priorityWeight = ['urgent' => 0, 'high' => 10, 'medium' => 20, 'low' => 30];
    $score = $reflection->invoke($job, $task, $priorityWeight, 1.8);

    // base 30, no calendar penalty (>3 days), capacity -10 = 20
    expect($score)->toBe(20);

    Carbon::setTestNow();
});

it('applies no capacity penalty when ratio is >= 2.0 (comfortable)', function () {
    Carbon::setTestNow('2026-04-02 09:00:00');

    $user = User::factory()->create(['onboarded_at' => now(), 'timezone' => 'UTC']);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'priority' => 'low',
        'deadline' => Carbon::parse('2026-04-10'),
        'estimated_duration' => 60,
    ]);

    $job = new ScheduleTasksJob($user);
    $reflection = new ReflectionMethod($job, 'urgencyScore');
    $reflection->setAccessible(true);

    $priorityWeight = ['urgent' => 0, 'high' => 10, 'medium' => 20, 'low' => 30];
    $score = $reflection->invoke($job, $task, $priorityWeight, 3.0);

    // base 30, no calendar penalty, no capacity penalty = 30
    expect($score)->toBe(30);

    Carbon::setTestNow();
});

it('stacks calendar and capacity penalties for imminent tight deadlines', function () {
    Carbon::setTestNow('2026-04-02 09:00:00');

    $user = User::factory()->create(['onboarded_at' => now(), 'timezone' => 'UTC']);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'priority' => 'medium',
        'deadline' => Carbon::parse('2026-04-02'),  // today = 0 days until
        'estimated_duration' => 60,
    ]);

    $job = new ScheduleTasksJob($user);
    $reflection = new ReflectionMethod($job, 'urgencyScore');
    $reflection->setAccessible(true);

    $priorityWeight = ['urgent' => 0, 'high' => 10, 'medium' => 20, 'low' => 30];
    $score = $reflection->invoke($job, $task, $priorityWeight, 0.3);

    // base 20, calendar -15 (<=1 day), capacity -35 = -30
    expect($score)->toBe(-30);

    Carbon::setTestNow();
});

// ---------------------------------------------------------------------------
// Sorting: tight-capacity task beats higher-priority ample-capacity task
// ---------------------------------------------------------------------------

it('sorts a tight-capacity task before a higher-priority task with ample capacity', function () {
    Carbon::setTestNow('2026-04-02 09:00:00');

    $user = User::factory()->create(['onboarded_at' => now(), 'timezone' => 'UTC']);

    $job = new ScheduleTasksJob($user);

    $urgencyScore = new ReflectionMethod($job, 'urgencyScore');
    $urgencyScore->setAccessible(true);

    $priorityWeight = ['urgent' => 0, 'high' => 10, 'medium' => 20, 'low' => 30];

    // Higher priority but ample capacity — not urgent despite "high" label
    $highPriorityTask = Task::factory()->create([
        'user_id' => $user->id,
        'priority' => 'high',
        'deadline' => Carbon::parse('2026-04-10'),
        'estimated_duration' => 60,
    ]);

    // Lower priority but critically tight capacity
    $mediumTightTask = Task::factory()->create([
        'user_id' => $user->id,
        'priority' => 'medium',
        'deadline' => Carbon::parse('2026-04-10'),
        'estimated_duration' => 60,
    ]);

    $highScore = $urgencyScore->invoke($job, $highPriorityTask, $priorityWeight, 3.5);  // comfortable
    $tightScore = $urgencyScore->invoke($job, $mediumTightTask, $priorityWeight, 0.8);  // critical

    // Tight task should have LOWER score (= higher urgency, scheduled first)
    expect($tightScore)->toBeLessThan($highScore);

    Carbon::setTestNow();
});

// ---------------------------------------------------------------------------
// buildContext includes capacity label for tasks with deadlines
// ---------------------------------------------------------------------------

it('buildContext includes capacity ratio label for tasks with deadlines', function () {
    Carbon::setTestNow('2026-04-02 09:00:00');

    $user = User::factory()->create([
        'onboarded_at' => now(),
        'buffer_time' => 15,
        'timezone' => 'UTC',
    ]);

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Submit quarterly report',
        'priority' => 'high',
        'estimated_duration' => 60,
        'status' => 'pending',
        'deadline' => Carbon::parse('2026-04-10'),
    ]);

    $context = TaskScheduler::buildContext($user);

    expect($context)
        ->toContain('Submit quarterly report')
        ->toContain('Capacity:')
        ->toMatch('/Capacity: [\d.]+x \((critical|tight|moderate|comfortable)\)/');

    Carbon::setTestNow();
});

it('buildContext omits capacity label for tasks without deadlines', function () {
    Carbon::setTestNow('2026-04-02 09:00:00');

    $user = User::factory()->create([
        'onboarded_at' => now(),
        'buffer_time' => 15,
        'timezone' => 'UTC',
    ]);

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Backlog cleanup',
        'priority' => 'low',
        'estimated_duration' => 30,
        'status' => 'pending',
        'deadline' => null,
    ]);

    $context = TaskScheduler::buildContext($user);

    expect($context)
        ->toContain('Backlog cleanup')
        ->not->toContain('Capacity:');

    Carbon::setTestNow();
});
