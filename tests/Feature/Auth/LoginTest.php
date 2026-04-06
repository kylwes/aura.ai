<?php

use App\Livewire\Auth\Login;
use App\Models\User;
use Livewire\Livewire;

it('renders the login page', function () {
    $this->get('/login')->assertStatus(200);
});

it('can log in with valid credentials', function () {
    $user = User::factory()->create(['password' => 'password']);
    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect('/');
});

it('shows error with invalid credentials', function () {
    Livewire::test(Login::class)
        ->set('email', 'wrong@example.com')
        ->set('password', 'wrong')
        ->call('login')
        ->assertHasErrors('email');
});

it('redirects authenticated users away from login', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/login')->assertRedirect('/dashboard');
});
