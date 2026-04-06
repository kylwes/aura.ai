<?php

use App\Ai\Agents\TaskScheduler;
use App\Enums\TaskStatus;
use App\Jobs\ScheduleTasksJob;
use App\Livewire\Settings;
use App\Models\CalendarEvent;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

// ---------------------------------------------------------------------------
// computeAvailableSlots — focus time exclusion
// ---------------------------------------------------------------------------

it('excludes focus time from available slots when protected', function () {
    Carbon::setTestNow('2026-04-02 08:00:00');

    $user = User::factory()->create([
        'timezone' => 'UTC',
        'buffer_time' => 0,
        'focus_time_enabled' => true,
        'focus_time_protected' => true,
        'focus_time_start' => '09:00',
        'focus_time_end' => '11:00',
    ]);

    $slots = TaskScheduler::computeAvailableSlots($user);
    $today = collect($slots)->where('date', '2026-04-02');

    // 09:00-11:00 must not appear as a schedulable slot
    $overlapping = $today->filter(
        fn ($s) => $s['start'] < '11:00' && $s['end'] > '09:00'
    );

    expect($overlapping)->toBeEmpty();

    // Time after focus window should still be available
    $afterFocus = $today->filter(fn ($s) => $s['start'] >= '11:00');
    expect($afterFocus)->not->toBeEmpty();

    Carbon::setTestNow();
});

it('does not exclude focus time from available slots when not protected', function () {
    Carbon::setTestNow('2026-04-02 08:00:00');

    $user = User::factory()->create([
        'timezone' => 'UTC',
        'buffer_time' => 0,
        'focus_time_enabled' => true,
        'focus_time_protected' => false,
        'focus_time_start' => '09:00',
        'focus_time_end' => '11:00',
    ]);

    $slots = TaskScheduler::computeAvailableSlots($user);
    $today = collect($slots)->where('date', '2026-04-02');

    // Focus window should be included in regular slots when not protected.
    // A slot that starts at or before 09:00 and ends at or after 11:00 (or anywhere
    // within the window) confirms the focus time is not carved out.
    $coversWindow = $today->filter(
        fn ($s) => $s['start'] <= '09:00' && $s['end'] >= '11:00'
    );

    expect($coversWindow)->not->toBeEmpty();

    Carbon::setTestNow();
});

// ---------------------------------------------------------------------------
// computeFocusSlots
// ---------------------------------------------------------------------------

it('returns focus time slots when focus is enabled and protected', function () {
    Carbon::setTestNow('2026-04-02 08:00:00');

    $user = User::factory()->create([
        'timezone' => 'UTC',
        'buffer_time' => 0,
        'focus_time_enabled' => true,
        'focus_time_protected' => true,
        'focus_time_start' => '09:00',
        'focus_time_end' => '11:00',
    ]);

    $slots = TaskScheduler::computeFocusSlots($user);

    expect($slots)->not->toBeEmpty();

    $today = collect($slots)->where('date', '2026-04-02');
    expect($today)->not->toBeEmpty();

    foreach ($today as $slot) {
        expect($slot['start'])->toBeGreaterThanOrEqual('09:00');
        expect($slot['end'])->toBeLessThanOrEqual('11:00');
    }

    Carbon::setTestNow();
});

it('returns empty focus slots when focus is not protected', function () {
    Carbon::setTestNow('2026-04-02 08:00:00');

    $user = User::factory()->create([
        'timezone' => 'UTC',
        'focus_time_enabled' => true,
        'focus_time_protected' => false,
        'focus_time_start' => '09:00',
        'focus_time_end' => '11:00',
    ]);

    $slots = TaskScheduler::computeFocusSlots($user);

    expect($slots)->toBeEmpty();

    Carbon::setTestNow();
});

it('returns empty focus slots when focus is disabled', function () {
    $user = User::factory()->create([
        'timezone' => 'UTC',
        'focus_time_enabled' => false,
        'focus_time_protected' => true,
        'focus_time_start' => '09:00',
        'focus_time_end' => '11:00',
    ]);

    expect(TaskScheduler::computeFocusSlots($user))->toBeEmpty();
});

it('excludes calendar events from focus slots', function () {
    Carbon::setTestNow('2026-04-02 08:00:00');

    $user = User::factory()->create([
        'timezone' => 'UTC',
        'buffer_time' => 0,
        'focus_time_enabled' => true,
        'focus_time_protected' => true,
        'focus_time_start' => '09:00',
        'focus_time_end' => '11:00',
    ]);

    CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'title' => 'Meeting',
        'starts_at' => Carbon::parse('2026-04-02 09:30:00', 'UTC'),
        'ends_at' => Carbon::parse('2026-04-02 10:00:00', 'UTC'),
    ]);

    $slots = TaskScheduler::computeFocusSlots($user);
    $today = collect($slots)->where('date', '2026-04-02');

    // No slot should span across the meeting
    foreach ($today as $slot) {
        $doesNotOverlap = $slot['end'] <= '09:30' || $slot['start'] >= '10:00';
        expect($doesNotOverlap)->toBeTrue("Slot {$slot['start']}-{$slot['end']} overlaps the meeting");
    }

    Carbon::setTestNow();
});

// ---------------------------------------------------------------------------
// ScheduleTasksJob — focus slot routing
// ---------------------------------------------------------------------------

it('places long tasks in focus slots when focus is protected', function () {
    Carbon::setTestNow('2026-04-02 08:00:00');

    $user = User::factory()->create([
        'timezone' => 'UTC',
        'buffer_time' => 0,
        'focus_time_enabled' => true,
        'focus_time_protected' => true,
        'focus_time_start' => '09:00',
        'focus_time_end' => '11:00',
        'focus_time_min_duration' => 60,
    ]);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending',
        'priority' => 'high',
        'estimated_duration' => 90,
    ]);

    TaskScheduler::fake(function () use ($task) {
        return [
            'scheduled_tasks' => [
                [
                    'task_id' => $task->id,
                    'date' => '2026-04-02',
                    'start_time' => '09:00',
                    'reasoning' => 'Long task placed in focus window.',
                ],
            ],
        ];
    });

    ScheduleTasksJob::dispatchSync($user);

    $task->refresh();
    expect($task->status)->toBe(TaskStatus::Scheduled);

    // Task must be scheduled within or starting inside the focus window
    $startTime = $task->scheduled_start->copy()->setTimezone('UTC')->format('H:i');
    expect($startTime)->toBeGreaterThanOrEqual('09:00');

    Carbon::setTestNow();
});

it('does not place short tasks in focus slots when focus is protected', function () {
    Carbon::setTestNow('2026-04-02 08:00:00');

    $user = User::factory()->create([
        'timezone' => 'UTC',
        'buffer_time' => 0,
        'focus_time_enabled' => true,
        'focus_time_protected' => true,
        'focus_time_start' => '09:00',
        'focus_time_end' => '11:00',
        'focus_time_min_duration' => 60,
    ]);

    // Short task (30 min — below the 60 min threshold)
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending',
        'priority' => 'medium',
        'estimated_duration' => 30,
    ]);

    TaskScheduler::fake(function () use ($task) {
        return [
            'scheduled_tasks' => [
                [
                    'task_id' => $task->id,
                    'date' => '2026-04-02',
                    'start_time' => '11:00',
                    'reasoning' => 'Short task placed after focus window.',
                ],
            ],
        ];
    });

    ScheduleTasksJob::dispatchSync($user);

    $task->refresh();
    expect($task->status)->toBe(TaskStatus::Scheduled);

    // Short task must NOT be placed inside the focus window
    $startTime = $task->scheduled_start->copy()->setTimezone('UTC')->format('H:i');
    expect($startTime)->toBeGreaterThanOrEqual('11:00');

    Carbon::setTestNow();
});

// ---------------------------------------------------------------------------
// buildContext — context string
// ---------------------------------------------------------------------------

it('includes protected label in context when focus is protected', function () {
    $user = User::factory()->create([
        'focus_time_enabled' => true,
        'focus_time_protected' => true,
        'focus_time_start' => '09:00',
        'focus_time_end' => '11:00',
        'focus_time_min_duration' => 60,
    ]);

    $context = TaskScheduler::buildContext($user);

    expect($context)->toContain('PROTECTED focus time');
    expect($context)->toContain('09:00');
    expect($context)->toContain('11:00');
    expect($context)->toContain('60min');
});

it('uses preference label in context when focus is not protected', function () {
    $user = User::factory()->create([
        'focus_time_enabled' => true,
        'focus_time_protected' => false,
        'focus_time_start' => '09:00',
        'focus_time_end' => '11:00',
    ]);

    $context = TaskScheduler::buildContext($user);

    expect($context)->toContain('Focus time: 09:00 - 11:00 (prefer long tasks 60+ min here)');
    expect($context)->not->toContain('PROTECTED');
});

// ---------------------------------------------------------------------------
// Settings UI
// ---------------------------------------------------------------------------

it('saves focus_time_protected from settings', function () {
    $user = User::factory()->create([
        'focus_time_enabled' => true,
        'focus_time_protected' => false,
    ]);

    Livewire::actingAs($user)
        ->test(Settings::class)
        ->set('focusTimeProtected', true)
        ->call('savePreferences');

    expect($user->fresh()->focus_time_protected)->toBeTrue();
});

it('loads focus_time_protected in settings mount', function () {
    $user = User::factory()->create([
        'focus_time_enabled' => true,
        'focus_time_protected' => true,
    ]);

    Livewire::actingAs($user)
        ->test(Settings::class)
        ->assertSet('focusTimeProtected', true);
});
