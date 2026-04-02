<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Integration;
use App\Models\Task;
use App\Models\User;

it('belongs to a user', function () {
    $task = Task::factory()->create();
    expect($task->user)->toBeInstanceOf(User::class);
});

it('optionally belongs to an integration', function () {
    $integration = Integration::factory()->create();
    $task = Task::factory()->create(['integration_id' => $integration->id]);
    expect($task->integration)->toBeInstanceOf(Integration::class);
});

it('casts priority to TaskPriority enum', function () {
    $task = Task::factory()->create(['priority' => 'urgent']);
    expect($task->priority)->toBe(TaskPriority::Urgent);
});

it('casts status to TaskStatus enum', function () {
    $task = Task::factory()->create(['status' => 'scheduled']);
    expect($task->status)->toBe(TaskStatus::Scheduled);
});

it('scopes to scheduled tasks for a date range', function () {
    $user = User::factory()->create();
    $inRange = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'scheduled',
        'scheduled_start' => now()->setTime(10, 0),
        'scheduled_end' => now()->setTime(11, 30),
    ]);
    Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'scheduled',
        'scheduled_start' => now()->addWeek(),
        'scheduled_end' => now()->addWeek()->addHour(),
    ]);
    Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending',
    ]);

    $results = Task::query()
        ->where('user_id', $user->id)
        ->where('status', 'scheduled')
        ->whereBetween('scheduled_start', [now()->startOfDay(), now()->endOfDay()])
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($inRange->id);
});
