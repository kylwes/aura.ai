<?php

use App\Ai\Agents\TaskScheduler;
use App\Jobs\ScheduleTasksJob;
use App\Livewire\WhatIfSimulator;
use App\Models\DayOverride;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

// ---------------------------------------------------------------------------
// User::effectiveScheduleFor — temporary overrides
// ---------------------------------------------------------------------------

it('effectiveScheduleFor uses temporary day-off override instead of DB schedule', function () {
    $user = User::factory()->create(['timezone' => 'UTC']);
    $user->ensureWorkSchedule();

    $temporaryOverrides = [
        '2026-04-06' => [
            'enabled' => false,
            'start' => null,
            'end' => null,
            'lunch_start' => null,
            'lunch_end' => null,
        ],
    ];

    $schedule = $user->effectiveScheduleFor(Carbon::parse('2026-04-06'), $temporaryOverrides);

    expect($schedule['enabled'])->toBeFalse()
        ->and($schedule['start'])->toBeNull()
        ->and($schedule['end'])->toBeNull();
});

it('effectiveScheduleFor uses temporary changed-hours override', function () {
    $user = User::factory()->create(['timezone' => 'UTC']);
    $user->ensureWorkSchedule();

    $temporaryOverrides = [
        '2026-04-06' => [
            'enabled' => true,
            'start' => '07:00',
            'end' => '14:00',
            'lunch_start' => '12:00',
            'lunch_end' => '12:30',
        ],
    ];

    $schedule = $user->effectiveScheduleFor(Carbon::parse('2026-04-06'), $temporaryOverrides);

    expect($schedule['enabled'])->toBeTrue()
        ->and($schedule['start'])->toBe('07:00')
        ->and($schedule['end'])->toBe('14:00');
});

it('effectiveScheduleFor without temporary overrides returns normal schedule', function () {
    $user = User::factory()->create(['timezone' => 'UTC']);
    $user->ensureWorkSchedule();

    // 2026-04-06 is Monday — default 09:00-17:30
    $schedule = $user->effectiveScheduleFor(Carbon::parse('2026-04-06'));

    expect($schedule['enabled'])->toBeTrue()
        ->and($schedule['start'])->toBe('09:00')
        ->and($schedule['end'])->toBe('17:30');
});

it('effectiveScheduleFor prefers temporary override over an existing DB override', function () {
    $user = User::factory()->create(['timezone' => 'UTC']);
    $user->ensureWorkSchedule();

    DayOverride::create([
        'user_id' => $user->id,
        'date' => '2026-04-06',
        'is_day_off' => false,
        'start' => '07:00',
        'end' => '15:00',
        'lunch_start' => null,
        'lunch_end' => null,
    ]);

    $temporaryOverrides = [
        '2026-04-06' => [
            'enabled' => false,
            'start' => null,
            'end' => null,
            'lunch_start' => null,
            'lunch_end' => null,
        ],
    ];

    $schedule = $user->effectiveScheduleFor(Carbon::parse('2026-04-06'), $temporaryOverrides);

    // Temporary override wins over the DB override
    expect($schedule['enabled'])->toBeFalse();
});

// ---------------------------------------------------------------------------
// TaskScheduler::computeAvailableSlots — temporary day-off excludes the day
// ---------------------------------------------------------------------------

it('computeAvailableSlots excludes a day marked off via temporary override', function () {
    Carbon::setTestNow('2026-04-05 08:00:00'); // Sunday before Monday April 6

    $user = User::factory()->create([
        'timezone' => 'UTC',
        'buffer_time' => 0,
    ]);
    $user->ensureWorkSchedule();

    $temporaryOverrides = [
        '2026-04-06' => [
            'enabled' => false,
            'start' => null,
            'end' => null,
            'lunch_start' => null,
            'lunch_end' => null,
        ],
    ];

    $slots = TaskScheduler::computeAvailableSlots($user, [], $temporaryOverrides);
    $mondaySlots = collect($slots)->where('date', '2026-04-06');

    expect($mondaySlots)->toBeEmpty();

    Carbon::setTestNow();
});

// ---------------------------------------------------------------------------
// TaskScheduler::buildContext — temporary overrides appear in context string
// ---------------------------------------------------------------------------

it('buildContext includes simulation section when temporary overrides are provided', function () {
    Carbon::setTestNow('2026-04-05 08:00:00');

    $user = User::factory()->create([
        'timezone' => 'UTC',
        'buffer_time' => 15,
    ]);
    $user->ensureWorkSchedule();

    $temporaryOverrides = [
        '2026-04-06' => [
            'enabled' => false,
            'start' => null,
            'end' => null,
            'lunch_start' => null,
            'lunch_end' => null,
        ],
    ];

    $context = TaskScheduler::buildContext($user, $temporaryOverrides);

    expect($context)->toContain('Temporary Schedule Changes (simulation)')
        ->and($context)->toContain('2026-04-06')
        ->and($context)->toContain('DAY OFF');

    Carbon::setTestNow();
});

it('buildContext does not include simulation section when no temporary overrides passed', function () {
    Carbon::setTestNow('2026-04-05 08:00:00');

    $user = User::factory()->create([
        'timezone' => 'UTC',
        'buffer_time' => 15,
    ]);
    $user->ensureWorkSchedule();

    $context = TaskScheduler::buildContext($user);

    expect($context)->not->toContain('Temporary Schedule Changes (simulation)');

    Carbon::setTestNow();
});

// ---------------------------------------------------------------------------
// ScheduleTasksJob — dry run with temporary overrides
// ---------------------------------------------------------------------------

it('dry-run with temporary overrides does not persist a DayOverride record', function () {
    Carbon::setTestNow('2026-04-05 08:00:00');

    $user = User::factory()->create([
        'timezone' => 'UTC',
        'buffer_time' => 0,
    ]);
    $user->ensureWorkSchedule();

    $temporaryOverrides = [
        '2026-04-06' => [
            'enabled' => false,
            'start' => null,
            'end' => null,
            'lunch_start' => null,
            'lunch_end' => null,
        ],
    ];

    $job = new ScheduleTasksJob($user, dryRun: true, temporaryOverrides: $temporaryOverrides);
    $job->handle();

    expect(DayOverride::where('user_id', $user->id)->exists())->toBeFalse();

    Carbon::setTestNow();
});

it('ScheduleTasksJob stores temporaryOverrides on the constructor', function () {
    $user = User::factory()->create(['timezone' => 'UTC']);

    $overrides = [
        '2026-04-06' => [
            'enabled' => false,
            'start' => null,
            'end' => null,
            'lunch_start' => null,
            'lunch_end' => null,
        ],
    ];

    $job = new ScheduleTasksJob($user, dryRun: true, temporaryOverrides: $overrides);

    expect($job->temporaryOverrides)->toBe($overrides)
        ->and($job->dryRun)->toBeTrue();
});

// ---------------------------------------------------------------------------
// WhatIfSimulator Livewire component
// ---------------------------------------------------------------------------

beforeEach(function () {
    $this->user = User::factory()->create(['timezone' => 'UTC']);
    $this->user->ensureWorkSchedule();
    $this->actingAs($this->user);
});

it('renders the scenario selection form by default', function () {
    Livewire::test(WhatIfSimulator::class)
        ->assertSet('hasResults', false)
        ->assertSet('scenarioType', 'day_off')
        ->assertSee('Take a day off')
        ->assertSee('Change work hours')
        ->assertSee('Simulate');
});

it('mounts with the next day pre-selected', function () {
    Carbon::setTestNow('2026-04-05 10:00:00');

    Livewire::test(WhatIfSimulator::class)
        ->assertSet('dayOffDate', '2026-04-06')
        ->assertSet('changeDate', '2026-04-06');

    Carbon::setTestNow();
});

it('simulate populates hasResults and shows apply button', function () {
    Carbon::setTestNow('2026-04-05 08:00:00');
    TaskScheduler::fake(fn () => ['scheduled_tasks' => []]);

    Livewire::test(WhatIfSimulator::class)
        ->set('dayOffDate', '2026-04-06')
        ->call('simulate')
        ->assertSet('hasResults', true)
        ->assertSee('Apply scenario');

    Carbon::setTestNow();
});

it('simulate does not persist any DayOverride records', function () {
    Carbon::setTestNow('2026-04-05 08:00:00');
    TaskScheduler::fake(fn () => ['scheduled_tasks' => []]);

    Livewire::test(WhatIfSimulator::class)
        ->set('dayOffDate', '2026-04-06')
        ->call('simulate');

    expect(DayOverride::where('user_id', $this->user->id)->exists())->toBeFalse();

    Carbon::setTestNow();
});

it('setting hasResults to false returns to scenario form', function () {
    Carbon::setTestNow('2026-04-05 08:00:00');
    TaskScheduler::fake(fn () => ['scheduled_tasks' => []]);

    Livewire::test(WhatIfSimulator::class)
        ->call('simulate')
        ->assertSet('hasResults', true)
        ->set('hasResults', false)
        ->assertSee('Simulate');

    Carbon::setTestNow();
});

it('apply creates a real DayOverride record for a day-off scenario', function () {
    Queue::fake();
    Carbon::setTestNow('2026-04-05 08:00:00');
    TaskScheduler::fake(fn () => ['scheduled_tasks' => []]);

    Livewire::test(WhatIfSimulator::class)
        ->set('dayOffDate', '2026-04-06')
        ->call('simulate')
        ->call('apply');

    expect(
        DayOverride::where('user_id', $this->user->id)
            ->whereDate('date', '2026-04-06')
            ->where('is_day_off', true)
            ->exists()
    )->toBeTrue();

    Carbon::setTestNow();
});

it('apply dispatches ScheduleTasksJob', function () {
    Queue::fake();
    Carbon::setTestNow('2026-04-05 08:00:00');
    TaskScheduler::fake(fn () => ['scheduled_tasks' => []]);

    Livewire::test(WhatIfSimulator::class)
        ->set('dayOffDate', '2026-04-06')
        ->call('simulate')
        ->call('apply');

    Queue::assertPushed(ScheduleTasksJob::class);

    Carbon::setTestNow();
});

it('apply with change_hours scenario creates override with the specified hours', function () {
    Queue::fake();
    Carbon::setTestNow('2026-04-05 08:00:00');
    TaskScheduler::fake(fn () => ['scheduled_tasks' => []]);

    Livewire::test(WhatIfSimulator::class)
        ->set('scenarioType', 'change_hours')
        ->set('changeDate', '2026-04-07')
        ->set('changeStart', '07:00')
        ->set('changeEnd', '14:00')
        ->call('simulate')
        ->call('apply');

    $override = DayOverride::where('user_id', $this->user->id)
        ->whereDate('date', '2026-04-07')
        ->first();

    expect($override)->not->toBeNull()
        ->and($override->is_day_off)->toBeFalse()
        ->and($override->start)->toBe('07:00')
        ->and($override->end)->toBe('14:00');

    Carbon::setTestNow();
});
