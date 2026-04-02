<?php

use App\Models\DayOverride;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

it('belongs to a user', function () {
    $user = User::factory()->create();
    $override = DayOverride::create([
        'user_id' => $user->id,
        'date' => '2026-04-05',
        'is_day_off' => false,
        'start' => '07:00',
        'end' => '15:00',
        'lunch_start' => '12:00',
        'lunch_end' => '12:30',
    ]);

    expect($override->user)->toBeInstanceOf(User::class)
        ->and($override->user->id)->toBe($user->id);
});

it('enforces unique user_id + date', function () {
    $user = User::factory()->create();
    DayOverride::create([
        'user_id' => $user->id,
        'date' => '2026-04-05',
        'is_day_off' => false,
        'start' => '07:00',
        'end' => '15:00',
    ]);

    DayOverride::create([
        'user_id' => $user->id,
        'date' => '2026-04-05',
        'is_day_off' => true,
    ]);
})->throws(QueryException::class);

it('casts is_day_off to boolean and date to date', function () {
    $user = User::factory()->create();
    $override = DayOverride::create([
        'user_id' => $user->id,
        'date' => '2026-04-05',
        'is_day_off' => true,
    ]);

    $override->refresh();
    expect($override->is_day_off)->toBeTrue()
        ->and($override->date)->toBeInstanceOf(Carbon::class);
});

it('is accessible via user dayOverrides relationship', function () {
    $user = User::factory()->create();
    DayOverride::create([
        'user_id' => $user->id,
        'date' => '2026-04-05',
        'is_day_off' => false,
        'start' => '07:00',
        'end' => '15:00',
    ]);

    expect($user->dayOverrides)->toHaveCount(1)
        ->and($user->dayOverrides->first()->start)->toBe('07:00');
});

it('effectiveScheduleFor returns override when one exists', function () {
    $user = User::factory()->create();
    $user->ensureWorkSchedule();

    DayOverride::create([
        'user_id' => $user->id,
        'date' => '2026-04-05',
        'is_day_off' => false,
        'start' => '07:00',
        'end' => '15:00',
        'lunch_start' => '12:00',
        'lunch_end' => '12:30',
    ]);

    $schedule = $user->effectiveScheduleFor(Carbon::parse('2026-04-05'));

    expect($schedule['enabled'])->toBeTrue()
        ->and($schedule['start'])->toBe('07:00')
        ->and($schedule['end'])->toBe('15:00')
        ->and($schedule['lunch_start'])->toBe('12:00')
        ->and($schedule['lunch_end'])->toBe('12:30');
});

it('effectiveScheduleFor falls back to WorkSchedule when no override', function () {
    $user = User::factory()->create();
    $user->ensureWorkSchedule();

    // April 6, 2026 is a Monday (ISO day 1), default 09:00-17:30
    $schedule = $user->effectiveScheduleFor(Carbon::parse('2026-04-06'));

    expect($schedule['enabled'])->toBeTrue()
        ->and($schedule['start'])->toBe('09:00')
        ->and($schedule['end'])->toBe('17:30');
});

it('effectiveScheduleFor returns day off when override is_day_off', function () {
    $user = User::factory()->create();
    $user->ensureWorkSchedule();

    DayOverride::create([
        'user_id' => $user->id,
        'date' => '2026-04-06',
        'is_day_off' => true,
    ]);

    $schedule = $user->effectiveScheduleFor(Carbon::parse('2026-04-06'));

    expect($schedule['enabled'])->toBeFalse()
        ->and($schedule['start'])->toBeNull()
        ->and($schedule['end'])->toBeNull();
});
