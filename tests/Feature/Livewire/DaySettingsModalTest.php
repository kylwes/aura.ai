<?php

use App\Livewire\DaySettingsModal;
use App\Models\DayOverride;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->ensureWorkSchedule();
    $this->actingAs($this->user);
});

it('mounts with work schedule defaults when no override exists', function () {
    // 2026-04-06 is Monday, default 09:00-17:30
    Livewire::test(DaySettingsModal::class, ['date' => '2026-04-06'])
        ->assertSet('date', '2026-04-06')
        ->assertSet('isDayOff', false)
        ->assertSet('start', '09:00')
        ->assertSet('end', '17:30')
        ->assertSet('lunchStart', '12:00')
        ->assertSet('lunchEnd', '13:00');
});

it('mounts with existing override values', function () {
    DayOverride::create([
        'user_id' => $this->user->id,
        'date' => '2026-04-06',
        'is_day_off' => false,
        'start' => '07:00',
        'end' => '15:00',
        'lunch_start' => '12:00',
        'lunch_end' => '12:30',
    ]);

    Livewire::test(DaySettingsModal::class, ['date' => '2026-04-06'])
        ->assertSet('start', '07:00')
        ->assertSet('end', '15:00')
        ->assertSet('lunchEnd', '12:30')
        ->assertSet('hasExistingOverride', true);
});

it('saves a new day override', function () {
    Livewire::test(DaySettingsModal::class, ['date' => '2026-04-06'])
        ->set('start', '07:00')
        ->set('end', '15:00')
        ->call('save');

    $override = DayOverride::where('user_id', $this->user->id)
        ->whereDate('date', '2026-04-06')
        ->first();

    expect($override)->not->toBeNull()
        ->and($override->start)->toBe('07:00')
        ->and($override->end)->toBe('15:00');
});

it('updates an existing override', function () {
    DayOverride::create([
        'user_id' => $this->user->id,
        'date' => '2026-04-06',
        'is_day_off' => false,
        'start' => '07:00',
        'end' => '15:00',
    ]);

    Livewire::test(DaySettingsModal::class, ['date' => '2026-04-06'])
        ->set('start', '08:00')
        ->set('end', '16:00')
        ->call('save');

    $override = DayOverride::where('user_id', $this->user->id)
        ->whereDate('date', '2026-04-06')
        ->first();

    expect($override->start)->toBe('08:00')
        ->and($override->end)->toBe('16:00');
});

it('saves a day off override', function () {
    Livewire::test(DaySettingsModal::class, ['date' => '2026-04-06'])
        ->set('isDayOff', true)
        ->call('save');

    $override = DayOverride::where('user_id', $this->user->id)
        ->whereDate('date', '2026-04-06')
        ->first();

    expect($override->is_day_off)->toBeTrue()
        ->and($override->start)->toBeNull();
});

it('resets override to default', function () {
    DayOverride::create([
        'user_id' => $this->user->id,
        'date' => '2026-04-06',
        'is_day_off' => false,
        'start' => '07:00',
        'end' => '15:00',
    ]);

    Livewire::test(DaySettingsModal::class, ['date' => '2026-04-06'])
        ->call('resetToDefault');

    expect(DayOverride::where('user_id', $this->user->id)->whereDate('date', '2026-04-06')->exists())->toBeFalse();
});
