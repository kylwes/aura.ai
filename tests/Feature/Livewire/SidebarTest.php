<?php

use App\Livewire\Sidebar;
use App\Models\Task;
use App\Models\User;
use Livewire\Livewire;

it('renders the sidebar', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(Sidebar::class)
        ->assertSee('Unscheduled Tasks')
        ->assertStatus(200);
});

it('shows unscheduled tasks', function () {
    $user = User::factory()->create();
    Task::factory()->create(['user_id' => $user->id, 'title' => 'Fix the bug', 'status' => 'pending']);
    Livewire::actingAs($user)
        ->test(Sidebar::class)
        ->assertSee('Fix the bug');
});

it('re-renders after task-created event', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(Sidebar::class)
        ->assertDontSee('New task from event');

    Task::factory()->create(['user_id' => $user->id, 'title' => 'New task from event', 'status' => 'pending']);

    $component->dispatch('task-created')
        ->assertSee('New task from event');
});

it('hides task from sidebar after task-scheduled event', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'title' => 'Schedule me', 'status' => 'pending']);

    $component = Livewire::actingAs($user)
        ->test(Sidebar::class)
        ->assertSee('Schedule me');

    $task->update([
        'status' => 'scheduled',
        'scheduled_start' => now()->setTime(10, 0),
        'scheduled_end' => now()->setTime(11, 0),
    ]);

    $component->dispatch('task-scheduled')
        ->assertDontSee('Schedule me');
});
