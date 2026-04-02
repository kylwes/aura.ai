<?php

use App\Livewire\Pages\PlannerPage as Calendar;
use App\Models\CalendarEvent;
use App\Models\Task;
use App\Models\TaskBlock;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

it('renders the calendar page', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    Livewire::actingAs($user)
        ->test(Calendar::class)
        ->assertStatus(200);
});

it('shows calendar events for the current week', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'title' => 'Team Standup',
        'starts_at' => now()->startOfWeek()->setTime(9, 30),
        'ends_at' => now()->startOfWeek()->setTime(9, 45),
    ]);
    Livewire::actingAs($user)
        ->test(Calendar::class)
        ->assertSee('Team Standup');
});

it('shows scheduled tasks on the calendar', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Review PR',
        'status' => 'scheduled',
        'scheduled_start' => now()->startOfWeek()->setTime(10, 0),
        'scheduled_end' => now()->startOfWeek()->setTime(11, 0),
        'is_ai_scheduled' => true,
    ]);
    TaskBlock::create([
        'task_id' => $task->id,
        'scheduled_start' => $task->scheduled_start,
        'scheduled_end' => $task->scheduled_end,
    ]);
    Livewire::actingAs($user)
        ->test(Calendar::class)
        ->assertSee('Review PR');
});

it('switches to month view and shows events', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'title' => 'Monthly Review',
        'starts_at' => now()->startOfMonth()->addDays(10)->setTime(14, 0),
        'ends_at' => now()->startOfMonth()->addDays(10)->setTime(15, 0),
    ]);
    Livewire::actingAs($user)
        ->test(Calendar::class)
        ->dispatch('calendar-navigate', view: 'month', date: now()->toDateString())
        ->assertSee('Monthly Review')
        ->assertSee('Mon');
});

it('switches to day view and shows events', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'title' => 'Daily Standup',
        'starts_at' => now()->setTime(9, 0),
        'ends_at' => now()->setTime(9, 15),
    ]);
    Livewire::actingAs($user)
        ->test(Calendar::class)
        ->dispatch('calendar-navigate', view: 'day', date: now()->toDateString())
        ->assertSee('Daily Standup');
});

it('shows scheduled tasks in month view', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Monthly Task',
        'status' => 'scheduled',
        'scheduled_start' => now()->startOfMonth()->addDays(5)->setTime(10, 0),
        'scheduled_end' => now()->startOfMonth()->addDays(5)->setTime(11, 0),
        'is_ai_scheduled' => true,
    ]);
    TaskBlock::create([
        'task_id' => $task->id,
        'scheduled_start' => $task->scheduled_start,
        'scheduled_end' => $task->scheduled_end,
    ]);
    Livewire::actingAs($user)
        ->test(Calendar::class)
        ->dispatch('calendar-navigate', view: 'month', date: now()->toDateString())
        ->assertSee('Monthly Task');
});

it('shows scheduled tasks in day view', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Focus Work',
        'status' => 'scheduled',
        'scheduled_start' => now()->setTime(14, 0),
        'scheduled_end' => now()->setTime(15, 30),
        'is_ai_scheduled' => true,
    ]);
    TaskBlock::create([
        'task_id' => $task->id,
        'scheduled_start' => $task->scheduled_start,
        'scheduled_end' => $task->scheduled_end,
    ]);
    Livewire::actingAs($user)
        ->test(Calendar::class)
        ->dispatch('calendar-navigate', view: 'day', date: now()->toDateString())
        ->assertSee('Focus Work');
});

it('schedules a pending task via scheduleTask', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Deploy hotfix',
        'status' => 'pending',
        'estimated_duration' => 60,
    ]);

    Livewire::actingAs($user)
        ->test(Calendar::class)
        ->call('scheduleTask', $task->id, now()->toDateString(), 600, 60)
        ->assertDispatched('task-scheduled');

    $task->refresh();
    expect($task->status->value)->toBe('scheduled')
        ->and($task->scheduled_start)->not->toBeNull()
        ->and($task->scheduled_end)->not->toBeNull()
        ->and($task->is_ai_scheduled)->toBeFalse();
});

it('cannot schedule another users task', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    $other = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $other->id,
        'status' => 'pending',
    ]);

    Livewire::actingAs($user)
        ->test(Calendar::class)
        ->call('scheduleTask', $task->id, now()->toDateString(), 600, 60);
})->throws(ModelNotFoundException::class);

it('moves an event to a new time and date', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    $event = CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'starts_at' => '2026-04-02 10:00:00',
        'ends_at' => '2026-04-02 11:00:00',
    ]);

    Livewire::actingAs($user)
        ->test(Calendar::class)
        ->call('moveEvent', $event->id, '2026-04-03', 840)
        ->assertDispatched('calendar-event-created');

    $event->refresh();
    expect($event->starts_at->format('Y-m-d H:i'))->toBe('2026-04-03 14:00')
        ->and($event->ends_at->format('Y-m-d H:i'))->toBe('2026-04-03 15:00');
});

it('resizes an event to a new end time', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    $event = CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'starts_at' => '2026-04-02 10:00:00',
        'ends_at' => '2026-04-02 11:00:00',
    ]);

    Livewire::actingAs($user)
        ->test(Calendar::class)
        ->call('resizeEvent', $event->id, 600, 720)
        ->assertDispatched('calendar-event-created');

    $event->refresh();
    expect($event->ends_at->format('Y-m-d H:i'))->toBe('2026-04-02 12:00');
});

it('moves a scheduled task to a new time and date', function () {
    $user = User::factory()->create(['onboarded_at' => now(), 'timezone' => 'UTC']);
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'scheduled',
        'estimated_duration' => 60,
        'scheduled_start' => '2026-04-02 10:00:00',
        'scheduled_end' => '2026-04-02 11:00:00',
    ]);

    Livewire::actingAs($user)
        ->test(Calendar::class)
        ->call('moveTask', $task->id, '2026-04-03', 540)
        ->assertDispatched('task-scheduled');

    $task->refresh();
    expect($task->scheduled_start->format('Y-m-d H:i'))->toBe('2026-04-03 09:00')
        ->and($task->scheduled_end->format('Y-m-d H:i'))->toBe('2026-04-03 10:00');
});

it('resizes a scheduled task to a new end time', function () {
    $user = User::factory()->create(['onboarded_at' => now(), 'timezone' => 'UTC']);
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'scheduled',
        'scheduled_start' => '2026-04-02 10:00:00',
        'scheduled_end' => '2026-04-02 11:00:00',
    ]);

    Livewire::actingAs($user)
        ->test(Calendar::class)
        ->call('resizeTask', $task->id, 600, 780)
        ->assertDispatched('task-scheduled');

    $task->refresh();
    expect($task->scheduled_end->format('Y-m-d H:i'))->toBe('2026-04-02 13:00');
});

it('cannot move another users event', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    $other = User::factory()->create();
    $event = CalendarEvent::factory()->create([
        'user_id' => $other->id,
        'starts_at' => '2026-04-02 10:00:00',
        'ends_at' => '2026-04-02 11:00:00',
    ]);

    Livewire::actingAs($user)
        ->test(Calendar::class)
        ->call('moveEvent', $event->id, '2026-04-03', 840);
})->throws(ModelNotFoundException::class);

it('requires authentication', function () {
    $this->get('/')->assertRedirect('/login');
});
