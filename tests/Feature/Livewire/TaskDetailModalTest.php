<?php

use App\Livewire\TaskDetailModal;
use App\Models\Task;
use App\Models\User;
use Livewire\Livewire;

it('renders the task detail modal', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id]);
    Livewire::actingAs($user)
        ->test(TaskDetailModal::class, ['taskId' => $task->id])
        ->assertStatus(200);
});

it('loads a task with details', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'title' => 'Design the dashboard']);
    Livewire::actingAs($user)
        ->test(TaskDetailModal::class, ['taskId' => $task->id])
        ->assertSet('task.title', 'Design the dashboard');
});

it('can update task priority', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'priority' => 'medium']);
    Livewire::actingAs($user)
        ->test(TaskDetailModal::class, ['taskId' => $task->id])
        ->call('setPriority', 'urgent');
    expect($task->fresh()->priority->value)->toBe('urgent');
});
