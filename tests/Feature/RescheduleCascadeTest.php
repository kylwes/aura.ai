<?php

use App\Ai\Agents\TaskScheduler;
use App\Enums\TaskStatus;
use App\Events\RescheduleProposed;
use App\Events\ScheduleCompleted;
use App\Jobs\GenerateRescheduleProposalJob;
use App\Jobs\ResolveOverlapsJob;
use App\Jobs\ScheduleTasksJob;
use App\Livewire\ReschedulePreviewModal;
use App\Models\RescheduleProposal;
use App\Models\ScheduleSnapshot;
use App\Models\Task;
use App\Models\TaskBlock;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

// ---------------------------------------------------------------------------
// Dry-run mode on ScheduleTasksJob
// ---------------------------------------------------------------------------

it('dry-run collects proposed changes without persisting to database', function () {
    Carbon::setTestNow('2026-04-03 08:00:00');

    $user = User::factory()->create([
        'onboarded_at' => now(),
        'timezone' => 'UTC',
        'buffer_time' => 0,
    ]);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => TaskStatus::Pending,
        'estimated_duration' => 60,
    ]);

    TaskScheduler::fake(function () use ($task) {
        return [
            'scheduled_tasks' => [
                [
                    'task_id' => $task->id,
                    'date' => '2026-04-03',
                    'start_time' => '09:00',
                    'reasoning' => 'Good morning slot.',
                ],
            ],
        ];
    });

    $job = new ScheduleTasksJob($user, dryRun: true);
    $job->handle();

    // Task must still be pending — no persistence
    expect($task->fresh()->status)->toBe(TaskStatus::Pending);
    expect(TaskBlock::where('task_id', $task->id)->exists())->toBeFalse();

    // Proposed changes must be collected
    $changes = $job->getProposedChanges();
    expect($changes)->toHaveCount(1);
    expect($changes[0]['task_id'])->toBe($task->id);
    expect($changes[0]['new_start'])->not->toBeNull();

    Carbon::setTestNow();
});

it('dry-run does not create a snapshot or dispatch ScheduleCompleted', function () {
    Carbon::setTestNow('2026-04-03 08:00:00');
    Event::fake([ScheduleCompleted::class]);

    $user = User::factory()->create([
        'onboarded_at' => now(),
        'timezone' => 'UTC',
        'buffer_time' => 0,
    ]);

    Task::factory()->create([
        'user_id' => $user->id,
        'status' => TaskStatus::Pending,
        'estimated_duration' => 60,
    ]);

    TaskScheduler::fake(fn () => ['scheduled_tasks' => []]);

    $job = new ScheduleTasksJob($user, dryRun: true);
    $job->handle();

    expect(ScheduleSnapshot::where('user_id', $user->id)->count())->toBe(0);
    Event::assertNotDispatched(ScheduleCompleted::class);

    Carbon::setTestNow();
});

// ---------------------------------------------------------------------------
// GenerateRescheduleProposalJob
// ---------------------------------------------------------------------------

it('generates a proposal with proposed_changes when tasks can be placed', function () {
    Carbon::setTestNow('2026-04-03 08:00:00');
    Event::fake([RescheduleProposed::class]);

    $user = User::factory()->create([
        'onboarded_at' => now(),
        'timezone' => 'UTC',
        'buffer_time' => 0,
    ]);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => TaskStatus::Pending,
        'estimated_duration' => 60,
    ]);

    TaskScheduler::fake(function () use ($task) {
        return [
            'scheduled_tasks' => [
                [
                    'task_id' => $task->id,
                    'date' => '2026-04-03',
                    'start_time' => '09:00',
                    'reasoning' => 'Placed in morning.',
                ],
            ],
        ];
    });

    (new GenerateRescheduleProposalJob($user, 'overlap', 'Tasks displaced by moved block'))->handle();

    $proposal = RescheduleProposal::where('user_id', $user->id)->latest()->first();
    expect($proposal)->not->toBeNull();
    expect($proposal->status)->toBe('pending');
    expect($proposal->trigger_type)->toBe('overlap');
    expect($proposal->proposed_changes)->toHaveCount(1);
    expect($proposal->proposed_changes[0]['task_id'])->toBe($task->id);

    Event::assertDispatched(RescheduleProposed::class, fn ($e) => $e->proposalId === $proposal->id);

    Carbon::setTestNow();
});

it('expires existing pending proposals before creating a new one', function () {
    Carbon::setTestNow('2026-04-03 08:00:00');
    Event::fake([RescheduleProposed::class]);

    $user = User::factory()->create(['onboarded_at' => now(), 'timezone' => 'UTC', 'buffer_time' => 0]);

    // Existing pending proposal
    $old = RescheduleProposal::create([
        'user_id' => $user->id,
        'trigger_type' => 'overlap',
        'proposed_changes' => [],
        'status' => 'pending',
        'expires_at' => now()->addMinutes(30),
    ]);

    TaskScheduler::fake(fn () => ['scheduled_tasks' => []]);

    (new GenerateRescheduleProposalJob($user, 'overlap'))->handle();

    expect($old->fresh()->status)->toBe('expired');

    Carbon::setTestNow();
});

it('does not create a proposal when there are no proposed changes', function () {
    Carbon::setTestNow('2026-04-03 08:00:00');
    Event::fake([RescheduleProposed::class]);

    $user = User::factory()->create(['onboarded_at' => now(), 'timezone' => 'UTC', 'buffer_time' => 0]);

    TaskScheduler::fake(fn () => ['scheduled_tasks' => []]);

    (new GenerateRescheduleProposalJob($user, 'overlap'))->handle();

    expect(RescheduleProposal::where('user_id', $user->id)->count())->toBe(0);
    Event::assertNotDispatched(RescheduleProposed::class);

    Carbon::setTestNow();
});

// ---------------------------------------------------------------------------
// RescheduleProposal::accept()
// ---------------------------------------------------------------------------

it('accept applies proposed changes to tasks and creates blocks', function () {
    Event::fake([ScheduleCompleted::class]);

    $user = User::factory()->create(['timezone' => 'UTC']);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => TaskStatus::Pending,
        'scheduled_start' => null,
        'scheduled_end' => null,
    ]);

    $newStart = now()->addHours(2)->utc()->toISOString();
    $newEnd = now()->addHours(3)->utc()->toISOString();

    $proposal = RescheduleProposal::create([
        'user_id' => $user->id,
        'trigger_type' => 'overlap',
        'proposed_changes' => [
            [
                'task_id' => $task->id,
                'action' => 'schedule',
                'old_start' => null,
                'old_end' => null,
                'new_start' => $newStart,
                'new_end' => $newEnd,
                'blocks' => [
                    ['start' => $newStart, 'end' => $newEnd],
                ],
                'reasoning' => 'Placed in open slot.',
            ],
        ],
        'status' => 'pending',
        'expires_at' => now()->addMinutes(30),
    ]);

    $proposal->accept();

    $task->refresh();
    expect($task->status)->toBe(TaskStatus::Scheduled);
    expect($task->is_ai_scheduled)->toBeTrue();
    // Compare at second precision — MySQL drops sub-second fractions
    expect($task->scheduled_start->format('Y-m-d H:i:s'))->toBe(Carbon::parse($newStart)->format('Y-m-d H:i:s'));
    expect(TaskBlock::where('task_id', $task->id)->count())->toBe(1);
    expect($proposal->fresh()->status)->toBe('accepted');

    Event::assertDispatched(ScheduleCompleted::class, fn ($e) => $e->userId === $user->id);
});

// ---------------------------------------------------------------------------
// RescheduleProposal::reject()
// ---------------------------------------------------------------------------

it('reject marks the proposal as rejected', function () {
    $user = User::factory()->create();

    $proposal = RescheduleProposal::create([
        'user_id' => $user->id,
        'trigger_type' => 'overlap',
        'proposed_changes' => [],
        'status' => 'pending',
        'expires_at' => now()->addMinutes(30),
    ]);

    $proposal->reject();

    expect($proposal->fresh()->status)->toBe('rejected');
});

// ---------------------------------------------------------------------------
// ResolveOverlapsJob now dispatches GenerateRescheduleProposalJob
// ---------------------------------------------------------------------------

it('ResolveOverlapsJob dispatches GenerateRescheduleProposalJob instead of ScheduleTasksJob', function () {
    Bus::fake();

    $user = User::factory()->create(['timezone' => 'UTC']);

    $blockStart = now()->addHour()->utc();
    $blockEnd = $blockStart->copy()->addHour();

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => TaskStatus::Scheduled,
        'is_ai_scheduled' => true,
        'is_pinned' => false,
        'scheduled_start' => $blockStart->copy()->subMinutes(15),
        'scheduled_end' => $blockStart->copy()->addMinutes(30),
    ]);

    (new ResolveOverlapsJob($user, $blockStart, $blockEnd))->handle();

    $task->refresh();
    expect($task->status)->toBe(TaskStatus::Pending);

    Bus::assertDispatched(GenerateRescheduleProposalJob::class, fn ($j) => $j->user->id === $user->id && $j->triggerType === 'overlap');
    Bus::assertNotDispatched(ScheduleTasksJob::class);
});

// ---------------------------------------------------------------------------
// Active scope and isExpired()
// ---------------------------------------------------------------------------

it('scopeActive returns only pending non-expired proposals', function () {
    $user = User::factory()->create();

    RescheduleProposal::create([
        'user_id' => $user->id,
        'trigger_type' => 'overlap',
        'proposed_changes' => [],
        'status' => 'pending',
        'expires_at' => now()->addMinutes(30),
    ]);

    RescheduleProposal::create([
        'user_id' => $user->id,
        'trigger_type' => 'overlap',
        'proposed_changes' => [],
        'status' => 'pending',
        'expires_at' => now()->subMinute(), // already expired
    ]);

    RescheduleProposal::create([
        'user_id' => $user->id,
        'trigger_type' => 'overlap',
        'proposed_changes' => [],
        'status' => 'rejected',
        'expires_at' => now()->addMinutes(30),
    ]);

    expect(RescheduleProposal::where('user_id', $user->id)->active()->count())->toBe(1);
});

it('isExpired returns true when expires_at is in the past', function () {
    $user = User::factory()->create();

    $proposal = RescheduleProposal::create([
        'user_id' => $user->id,
        'trigger_type' => 'overlap',
        'proposed_changes' => [],
        'status' => 'pending',
        'expires_at' => now()->subMinute(),
    ]);

    expect($proposal->isExpired())->toBeTrue();
});

// ---------------------------------------------------------------------------
// ReschedulePreviewModal Livewire component
// ---------------------------------------------------------------------------

it('renders proposed changes in the modal', function () {
    $user = User::factory()->create(['timezone' => 'UTC']);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => TaskStatus::Pending,
    ]);

    $newStart = now()->addHours(2)->utc()->toISOString();
    $newEnd = now()->addHours(3)->utc()->toISOString();

    $proposal = RescheduleProposal::create([
        'user_id' => $user->id,
        'trigger_type' => 'overlap',
        'trigger_description' => 'Tasks displaced by moved block',
        'proposed_changes' => [
            [
                'task_id' => $task->id,
                'action' => 'schedule',
                'old_start' => null,
                'old_end' => null,
                'new_start' => $newStart,
                'new_end' => $newEnd,
                'blocks' => [['start' => $newStart, 'end' => $newEnd]],
                'reasoning' => 'Open slot found.',
            ],
        ],
        'status' => 'pending',
        'expires_at' => now()->addMinutes(30),
    ]);

    Livewire::actingAs($user)
        ->test(ReschedulePreviewModal::class, ['proposalId' => $proposal->id])
        ->assertSee('Reschedule Preview')
        ->assertSee('Tasks displaced by moved block')
        ->assertSee($task->title);
});

it('accept action applies changes and closes the modal', function () {
    Event::fake([ScheduleCompleted::class]);

    $user = User::factory()->create(['timezone' => 'UTC']);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => TaskStatus::Pending,
    ]);

    $newStart = now()->addHours(2)->utc()->toISOString();
    $newEnd = now()->addHours(3)->utc()->toISOString();

    $proposal = RescheduleProposal::create([
        'user_id' => $user->id,
        'trigger_type' => 'overlap',
        'proposed_changes' => [
            [
                'task_id' => $task->id,
                'action' => 'schedule',
                'old_start' => null,
                'old_end' => null,
                'new_start' => $newStart,
                'new_end' => $newEnd,
                'blocks' => [['start' => $newStart, 'end' => $newEnd]],
                'reasoning' => 'Open slot.',
            ],
        ],
        'status' => 'pending',
        'expires_at' => now()->addMinutes(30),
    ]);

    Livewire::actingAs($user)
        ->test(ReschedulePreviewModal::class, ['proposalId' => $proposal->id])
        ->call('accept')
        ->assertDispatched('toast');

    expect($task->fresh()->status)->toBe(TaskStatus::Scheduled);
    expect($proposal->fresh()->status)->toBe('accepted');
});

it('reject action marks proposal as rejected and dispatches ScheduleTasksJob', function () {
    Bus::fake();

    $user = User::factory()->create(['timezone' => 'UTC']);

    $proposal = RescheduleProposal::create([
        'user_id' => $user->id,
        'trigger_type' => 'overlap',
        'proposed_changes' => [],
        'status' => 'pending',
        'expires_at' => now()->addMinutes(30),
    ]);

    Livewire::actingAs($user)
        ->test(ReschedulePreviewModal::class, ['proposalId' => $proposal->id])
        ->call('reject')
        ->assertDispatched('toast');

    expect($proposal->fresh()->status)->toBe('rejected');
    Bus::assertDispatched(ScheduleTasksJob::class, fn ($j) => $j->user->id === $user->id);
});
