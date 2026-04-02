<?php

use App\Jobs\ScheduleTasksJob;
use App\Livewire\CreateTaskModal;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

it('renders the create task modal', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(CreateTaskModal::class)
        ->assertStatus(200);
});

it('shows New Task heading', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(CreateTaskModal::class)
        ->assertSee('New Task');
});

it('requires a title to save', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(CreateTaskModal::class)
        ->call('save')
        ->assertHasErrors(['title' => 'required']);
});

it('creates a task with correct fields', function () {
    Queue::fake();

    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(CreateTaskModal::class)
        ->set('title', 'Fix the login bug')
        ->set('priority', 'high')
        ->set('estimatedDuration', 60)
        ->set('deadline', '2026-04-10')
        ->set('description', 'The login form crashes on submit')
        ->call('save')
        ->assertDispatched('task-created');

    $task = Task::where('user_id', $user->id)->first();
    expect($task)->not->toBeNull()
        ->and($task->title)->toBe('Fix the login bug')
        ->and($task->priority->value)->toBe('high')
        ->and($task->estimated_duration)->toBe(60)
        ->and($task->deadline->format('Y-m-d'))->toBe('2026-04-10')
        ->and($task->description)->toBe('The login form crashes on submit')
        ->and($task->status->value)->toBe('pending');

    Queue::assertPushed(ScheduleTasksJob::class);
});

it('creates a task with minimal fields', function () {
    Queue::fake();

    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(CreateTaskModal::class)
        ->set('title', 'Quick task')
        ->call('save')
        ->assertDispatched('task-created');

    $task = Task::where('user_id', $user->id)->first();
    expect($task)->not->toBeNull()
        ->and($task->title)->toBe('Quick task')
        ->and($task->priority->value)->toBe('medium')
        ->and($task->estimated_duration)->toBeNull()
        ->and($task->deadline)->toBeNull()
        ->and($task->status->value)->toBe('pending');

    Queue::assertPushed(ScheduleTasksJob::class);
});
