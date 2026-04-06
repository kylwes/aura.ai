<?php

use App\Ai\Agents\TaskScheduler;
use App\Enums\TaskStatus;
use App\Jobs\ScheduleTasksJob;
use App\Livewire\TaskDetailModal;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

// ---------------------------------------------------------------------------
// Task model — dependency relationships
// ---------------------------------------------------------------------------

it('can attach and retrieve dependencies', function () {
    $user = User::factory()->create();
    $taskA = Task::factory()->create(['user_id' => $user->id]);
    $taskB = Task::factory()->create(['user_id' => $user->id]);

    $taskB->dependencies()->attach($taskA->id);

    expect($taskB->dependencies()->pluck('tasks.id')->all())->toContain($taskA->id);
    expect($taskA->dependents()->pluck('tasks.id')->all())->toContain($taskB->id);
});

it('hasUnmetDependencies returns true when dependency is not completed', function () {
    $user = User::factory()->create();
    $taskA = Task::factory()->create(['user_id' => $user->id, 'status' => TaskStatus::Pending]);
    $taskB = Task::factory()->create(['user_id' => $user->id]);

    $taskB->dependencies()->attach($taskA->id);

    expect($taskB->hasUnmetDependencies())->toBeTrue();
});

it('hasUnmetDependencies returns false when all dependencies are completed', function () {
    $user = User::factory()->create();
    $taskA = Task::factory()->create(['user_id' => $user->id, 'status' => TaskStatus::Completed]);
    $taskB = Task::factory()->create(['user_id' => $user->id]);

    $taskB->dependencies()->attach($taskA->id);

    expect($taskB->hasUnmetDependencies())->toBeFalse();
});

// ---------------------------------------------------------------------------
// ScheduleTasksJob — topological sort
// ---------------------------------------------------------------------------

it('topological sort places dependencies before dependents', function () {
    $user = User::factory()->create();
    $taskA = Task::factory()->create(['user_id' => $user->id, 'status' => TaskStatus::Pending]);
    $taskB = Task::factory()->create(['user_id' => $user->id, 'status' => TaskStatus::Pending]);

    // B depends on A
    $taskB->dependencies()->attach($taskA->id);

    $scheduledTasks = [
        ['task_id' => $taskB->id, 'date' => '2026-04-03', 'start_time' => '09:00', 'reasoning' => 'test'],
        ['task_id' => $taskA->id, 'date' => '2026-04-03', 'start_time' => '10:00', 'reasoning' => 'test'],
    ];

    $taskLookup = Task::whereIn('id', [$taskA->id, $taskB->id])->with('dependencies')->get()->keyBy('id');

    $job = new ScheduleTasksJob($user);
    $reflection = new ReflectionMethod($job, 'topologicalSort');
    $reflection->setAccessible(true);

    $sorted = $reflection->invoke($job, $scheduledTasks, $taskLookup);

    $sortedIds = array_column($sorted, 'task_id');
    expect(array_search($taskA->id, $sortedIds))->toBeLessThan(array_search($taskB->id, $sortedIds));
});

it('topological sort handles multiple levels A -> B -> C', function () {
    $user = User::factory()->create();
    $taskA = Task::factory()->create(['user_id' => $user->id, 'status' => TaskStatus::Pending]);
    $taskB = Task::factory()->create(['user_id' => $user->id, 'status' => TaskStatus::Pending]);
    $taskC = Task::factory()->create(['user_id' => $user->id, 'status' => TaskStatus::Pending]);

    // C depends on B, B depends on A
    $taskB->dependencies()->attach($taskA->id);
    $taskC->dependencies()->attach($taskB->id);

    // AI returns them in reverse order
    $scheduledTasks = [
        ['task_id' => $taskC->id, 'date' => '2026-04-03', 'start_time' => '09:00', 'reasoning' => 'test'],
        ['task_id' => $taskB->id, 'date' => '2026-04-03', 'start_time' => '09:30', 'reasoning' => 'test'],
        ['task_id' => $taskA->id, 'date' => '2026-04-03', 'start_time' => '10:00', 'reasoning' => 'test'],
    ];

    $taskLookup = Task::whereIn('id', [$taskA->id, $taskB->id, $taskC->id])->with('dependencies')->get()->keyBy('id');

    $job = new ScheduleTasksJob($user);
    $reflection = new ReflectionMethod($job, 'topologicalSort');
    $reflection->setAccessible(true);

    $sorted = $reflection->invoke($job, $scheduledTasks, $taskLookup);
    $sortedIds = array_column($sorted, 'task_id');

    $posA = array_search($taskA->id, $sortedIds);
    $posB = array_search($taskB->id, $sortedIds);
    $posC = array_search($taskC->id, $sortedIds);

    expect($posA)->toBeLessThan($posB);
    expect($posB)->toBeLessThan($posC);
});

it('task with unmet dependencies is skipped during scheduling loop', function () {
    Carbon::setTestNow('2026-04-03 08:00:00');

    $user = User::factory()->create([
        'timezone' => 'UTC',
        'buffer_time' => 0,
    ]);

    $taskA = Task::factory()->create([
        'user_id' => $user->id,
        'status' => TaskStatus::Pending,
        'estimated_duration' => 60,
        'priority' => 'medium',
    ]);

    $taskB = Task::factory()->create([
        'user_id' => $user->id,
        'status' => TaskStatus::Pending,
        'estimated_duration' => 60,
        'priority' => 'medium',
    ]);

    // B depends on A (A is not completed)
    $taskB->dependencies()->attach($taskA->id);

    // Fake the AI response: both tasks scheduled
    $scheduledTasks = [
        ['task_id' => $taskA->id, 'date' => '2026-04-03', 'start_time' => '09:00', 'reasoning' => 'test'],
        ['task_id' => $taskB->id, 'date' => '2026-04-03', 'start_time' => '10:15', 'reasoning' => 'test'],
    ];

    $job = new ScheduleTasksJob($user);

    // Inject the $scheduledTasks directly via the placement loop by running just
    // the topological sort and hasUnmetDependencies check logic inline
    $taskLookup = $user->tasks()
        ->whereIn('status', [TaskStatus::Pending, TaskStatus::Scheduled])
        ->with('dependencies')
        ->get()
        ->keyBy('id');

    // After topological sort, taskA comes first (no deps), taskB comes second (dep on A)
    $topologicalSort = new ReflectionMethod($job, 'topologicalSort');
    $topologicalSort->setAccessible(true);
    $sorted = $topologicalSort->invoke($job, $scheduledTasks, $taskLookup);

    // Simulate which tasks would be skipped: taskB should be skipped (has unmet dep)
    $skippedIds = [];
    foreach ($sorted as $placement) {
        $task = $taskLookup->get($placement['task_id']);
        if ($task && $task->hasUnmetDependencies()) {
            $skippedIds[] = $task->id;
        }
    }

    expect($skippedIds)->toContain($taskB->id);
    expect($skippedIds)->not->toContain($taskA->id);

    Carbon::setTestNow();
});

it('task with completed dependency is not skipped', function () {
    $user = User::factory()->create();

    $taskA = Task::factory()->create(['user_id' => $user->id, 'status' => TaskStatus::Completed]);
    $taskB = Task::factory()->create(['user_id' => $user->id, 'status' => TaskStatus::Pending]);

    $taskB->dependencies()->attach($taskA->id);
    $taskB->load('dependencies');

    expect($taskB->hasUnmetDependencies())->toBeFalse();
});

// ---------------------------------------------------------------------------
// TaskScheduler::buildContext — dependency info
// ---------------------------------------------------------------------------

it('buildContext includes dependency info for pending tasks', function () {
    Carbon::setTestNow('2026-04-03 08:00:00');

    $user = User::factory()->create(['timezone' => 'UTC', 'buffer_time' => 15]);

    $taskA = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Task Alpha',
        'status' => TaskStatus::Pending,
        'estimated_duration' => 60,
    ]);

    $taskB = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Task Beta',
        'status' => TaskStatus::Pending,
        'estimated_duration' => 60,
    ]);

    $taskB->dependencies()->attach($taskA->id);

    $context = TaskScheduler::buildContext($user);

    // Context should mention the dependency for taskB
    expect($context)->toContain('Depends on:');
    expect($context)->toContain("[ID:{$taskA->id}]");

    Carbon::setTestNow();
});

// ---------------------------------------------------------------------------
// TaskDetailModal — addDependency / removeDependency
// ---------------------------------------------------------------------------

it('can add a dependency via TaskDetailModal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $taskA = Task::factory()->create(['user_id' => $user->id]);
    $taskB = Task::factory()->create(['user_id' => $user->id]);

    Livewire::test(TaskDetailModal::class, ['taskId' => $taskB->id])
        ->call('addDependency', $taskA->id)
        ->assertSet('dependencyIds', [$taskA->id]);

    expect(DB::table('task_dependencies')
        ->where('task_id', $taskB->id)
        ->where('depends_on_task_id', $taskA->id)
        ->exists()
    )->toBeTrue();
});

it('can remove a dependency via TaskDetailModal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $taskA = Task::factory()->create(['user_id' => $user->id]);
    $taskB = Task::factory()->create(['user_id' => $user->id]);
    $taskB->dependencies()->attach($taskA->id);

    Livewire::test(TaskDetailModal::class, ['taskId' => $taskB->id])
        ->call('removeDependency', $taskA->id)
        ->assertSet('dependencyIds', []);

    expect(DB::table('task_dependencies')
        ->where('task_id', $taskB->id)
        ->where('depends_on_task_id', $taskA->id)
        ->exists()
    )->toBeFalse();
});

it('rejects self-dependency in TaskDetailModal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $task = Task::factory()->create(['user_id' => $user->id]);

    Livewire::test(TaskDetailModal::class, ['taskId' => $task->id])
        ->call('addDependency', $task->id)
        ->assertSet('dependencyIds', []);

    expect(DB::table('task_dependencies')
        ->where('task_id', $task->id)
        ->where('depends_on_task_id', $task->id)
        ->exists()
    )->toBeFalse();
});

it('rejects circular dependency in TaskDetailModal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $taskA = Task::factory()->create(['user_id' => $user->id]);
    $taskB = Task::factory()->create(['user_id' => $user->id]);

    // B depends on A
    $taskB->dependencies()->attach($taskA->id);

    // Now try to make A depend on B — this would be circular
    Livewire::test(TaskDetailModal::class, ['taskId' => $taskA->id])
        ->call('addDependency', $taskB->id)
        ->assertDispatched('toast');

    // The circular dependency should NOT have been created
    expect(DB::table('task_dependencies')
        ->where('task_id', $taskA->id)
        ->where('depends_on_task_id', $taskB->id)
        ->exists()
    )->toBeFalse();
});
