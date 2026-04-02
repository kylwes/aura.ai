<?php

use App\Livewire\EventPanel;
use App\Models\CalendarEvent;
use App\Models\User;
use Livewire\Livewire;

it('renders the event panel', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(EventPanel::class)
        ->assertStatus(200);
});

it('opens for creation with correct parameters', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(EventPanel::class)
        ->dispatch('open-create-event-panel', date: '2026-04-02', startMinutes: 480, endMinutes: 540)
        ->assertSet('open', true)
        ->assertSet('date', '2026-04-02')
        ->assertSet('startMinutes', 480)
        ->assertSet('endMinutes', 540)
        ->assertSet('eventId', null)
        ->assertSee('08:00')
        ->assertSee('09:00');
});

it('opens for editing an existing event', function () {
    $user = User::factory()->create();
    $event = CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'title' => 'Existing Meeting',
        'description' => 'Some notes',
        'starts_at' => '2026-04-02 10:00:00',
        'ends_at' => '2026-04-02 11:30:00',
    ]);

    Livewire::actingAs($user)
        ->test(EventPanel::class)
        ->dispatch('open-edit-event-panel', eventId: $event->id)
        ->assertSet('open', true)
        ->assertSet('eventId', $event->id)
        ->assertSet('title', 'Existing Meeting')
        ->assertSet('description', 'Some notes');
});

it('auto-creates event on first title input', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(EventPanel::class)
        ->dispatch('open-create-event-panel', date: '2026-04-02', startMinutes: 480, endMinutes: 540)
        ->set('title', 'Team Standup')
        ->assertDispatched('calendar-event-created');

    $event = CalendarEvent::where('user_id', $user->id)->first();
    expect($event)->not->toBeNull()
        ->and($event->title)->toBe('Team Standup')
        ->and($event->starts_at->format('Y-m-d H:i'))->toBe('2026-04-02 08:00')
        ->and($event->ends_at->format('Y-m-d H:i'))->toBe('2026-04-02 09:00')
        ->and($event->is_all_day)->toBeFalse();
});

it('auto-updates existing event on title change', function () {
    $user = User::factory()->create();
    $event = CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'title' => 'Old Title',
        'starts_at' => '2026-04-02 10:00:00',
        'ends_at' => '2026-04-02 11:00:00',
    ]);

    Livewire::actingAs($user)
        ->test(EventPanel::class)
        ->dispatch('open-edit-event-panel', eventId: $event->id)
        ->set('title', 'Updated Title')
        ->assertDispatched('calendar-event-created');

    expect($event->fresh()->title)->toBe('Updated Title');
});

it('auto-updates existing event on description change', function () {
    $user = User::factory()->create();
    $event = CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'title' => 'Meeting',
        'starts_at' => '2026-04-02 10:00:00',
        'ends_at' => '2026-04-02 11:00:00',
    ]);

    Livewire::actingAs($user)
        ->test(EventPanel::class)
        ->dispatch('open-edit-event-panel', eventId: $event->id)
        ->set('description', 'New notes')
        ->assertDispatched('calendar-event-created');

    expect($event->fresh()->description)->toBe('New notes');
});

it('deletes an event', function () {
    $user = User::factory()->create();
    $event = CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'starts_at' => '2026-04-02 10:00:00',
        'ends_at' => '2026-04-02 11:00:00',
    ]);

    Livewire::actingAs($user)
        ->test(EventPanel::class)
        ->dispatch('open-edit-event-panel', eventId: $event->id)
        ->call('delete')
        ->assertDispatched('calendar-event-created')
        ->assertSet('open', false);

    expect(CalendarEvent::find($event->id))->toBeNull();
});

it('cleans up untitled events on close', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(EventPanel::class)
        ->dispatch('open-create-event-panel', date: '2026-04-02', startMinutes: 480, endMinutes: 540)
        ->set('title', '')
        ->call('close')
        ->assertSet('open', false);

    expect(CalendarEvent::where('user_id', $user->id)->count())->toBe(0);
});

it('shows event title input for creation mode', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(EventPanel::class)
        ->dispatch('open-create-event-panel', date: '2026-04-02', startMinutes: 480, endMinutes: 540)
        ->assertSee('Event title');
});

it('opens in editing mode for existing events', function () {
    $user = User::factory()->create();
    $event = CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'starts_at' => '2026-04-02 10:00:00',
        'ends_at' => '2026-04-02 11:00:00',
    ]);

    Livewire::actingAs($user)
        ->test(EventPanel::class)
        ->dispatch('open-edit-event-panel', eventId: $event->id)
        ->assertSet('open', true)
        ->assertSet('eventId', $event->id);
});
