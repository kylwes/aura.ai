<?php

use App\Livewire\DarkModeToggle;
use App\Models\User;
use App\Settings\UserPreferences;
use Livewire\Livewire;

it('renders the dark mode toggle', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(DarkModeToggle::class)
        ->assertStatus(200);
});

it('defaults to light mode', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(DarkModeToggle::class)
        ->assertSet('darkMode', false);
});

it('toggles dark mode on', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(DarkModeToggle::class)
        ->call('toggle')
        ->assertSet('darkMode', true);

    $this->actingAs($user);
    expect(app(UserPreferences::class)->dark_mode)->toBeTrue();
});

it('toggles dark mode off again', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(DarkModeToggle::class)
        ->call('toggle')
        ->call('toggle')
        ->assertSet('darkMode', false);

    $this->actingAs($user);
    expect(app(UserPreferences::class)->dark_mode)->toBeFalse();
});

it('stores dark mode per user', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    // User A enables dark mode
    Livewire::actingAs($userA)
        ->test(DarkModeToggle::class)
        ->call('toggle')
        ->assertSet('darkMode', true);

    // Clear the scoped UserPreferences singleton so User B gets a fresh instance
    app()->forgetScopedInstances();

    // User B should still be light
    Livewire::actingAs($userB)
        ->test(DarkModeToggle::class)
        ->assertSet('darkMode', false);
});
