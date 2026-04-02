<?php

use App\Livewire\TopBar;
use App\Models\InboxItem;
use App\Models\User;
use Livewire\Livewire;

it('renders the top bar', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(TopBar::class)
        ->assertSee('Today')
        ->assertSee('Auto-schedule')
        ->assertStatus(200);
});

it('shows pending inbox count as badge', function () {
    $user = User::factory()->create();
    InboxItem::factory()->count(3)->create(['user_id' => $user->id, 'status' => 'pending']);
    Livewire::actingAs($user)
        ->test(TopBar::class)
        ->assertSee('3');
});
