<?php

use App\Livewire\Settings;
use App\Models\User;
use Livewire\Livewire;

it('renders the settings page', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(Settings::class)
        ->assertSee('Settings')
        ->assertSee('Integrations')
        ->assertStatus(200);
});

it('shows all integration types', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(Settings::class)
        ->assertSee('Jira')
        ->assertSee('Slack')
        ->assertSee('Gmail');
});

it('can save AI preferences', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(Settings::class)
        ->set('bufferTime', 10)
        ->set('maxTaskDuration', 90)
        ->call('savePreferences');

    expect($user->fresh())
        ->buffer_time->toBe(10)
        ->max_task_duration->toBe(90);
});

it('loads per-day work schedules', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(Settings::class)
        ->set('activeTab', 'preferences');

    $schedules = $component->get('schedules');

    expect($schedules)->toHaveCount(7)
        ->and($schedules[0]['day_name'])->toBe('Monday')
        ->and($schedules[0]['enabled'])->toBeTrue()
        ->and($schedules[0]['start'])->toBe('09:00')
        ->and($schedules[5]['day_name'])->toBe('Saturday')
        ->and($schedules[5]['enabled'])->toBeFalse();
});

it('auto-saves schedule changes', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Settings::class)
        ->set('schedules.0.start', '08:00');

    expect($user->workSchedules()->where('day', 1)->first()->start)->toBe('08:00');
});

it('clears times when disabling a day', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Settings::class)
        ->set('schedules.0.enabled', false);

    $monday = $user->workSchedules()->where('day', 1)->first();
    expect($monday->enabled)->toBeFalse()
        ->and($monday->start)->toBeNull()
        ->and($monday->lunch_start)->toBeNull();
});
