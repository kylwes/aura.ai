<?php

use App\Livewire\Profile;
use App\Models\User;
use Livewire\Livewire;

it('renders the profile page', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(Profile::class)
        ->assertSee($user->name)
        ->assertStatus(200);
});

it('can update profile name and email', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('name', 'New Name')
        ->set('email', 'new@example.com')
        ->call('updateProfile');
    expect($user->fresh())
        ->name->toBe('New Name')
        ->email->toBe('new@example.com');
});
