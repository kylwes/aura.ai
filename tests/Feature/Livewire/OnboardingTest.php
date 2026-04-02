<?php

use App\Livewire\Onboarding;
use App\Models\User;
use Livewire\Livewire;

it('renders the onboarding page', function () {
    $user = User::factory()->create(['onboarded_at' => null]);
    Livewire::actingAs($user)
        ->test(Onboarding::class)
        ->assertSee('Welcome to Aura')
        ->assertStatus(200);
});

it('can complete onboarding', function () {
    $user = User::factory()->create(['onboarded_at' => null]);
    Livewire::actingAs($user)
        ->test(Onboarding::class)
        ->set('step', 3)
        ->call('complete')
        ->assertRedirect('/');
    expect($user->fresh()->onboarded_at)->not->toBeNull();
});
