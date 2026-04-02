<?php

use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use App\Jobs\PushTaskToGoogleJob;
use App\Models\Integration;
use App\Models\Task;
use App\Models\User;
use App\Services\GoogleCalendarService;

it('creates a google event for a scheduled task', function () {
    $user = User::factory()->create();
    Integration::factory()->create([
        'user_id' => $user->id,
        'type' => IntegrationType::GoogleCalendar,
        'status' => IntegrationStatus::Connected,
        'configuration' => [
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addHour()->toIso8601String(),
            'push_target' => 'aura_calendar',
        ],
    ]);
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'scheduled_start' => now()->addHour(),
        'scheduled_end' => now()->addHours(2),
        'status' => 'scheduled',
    ]);

    $mock = Mockery::mock(GoogleCalendarService::class);
    $mock->shouldReceive('createEvent')->once()->andReturn('google-created-id');
    app()->instance(GoogleCalendarService::class, $mock);

    (new PushTaskToGoogleJob($task, 'create'))->handle(app(GoogleCalendarService::class));

    expect($task->fresh()->google_event_id)->toBe('google-created-id');
});

it('updates a google event when task is rescheduled', function () {
    $user = User::factory()->create();
    Integration::factory()->create([
        'user_id' => $user->id,
        'type' => IntegrationType::GoogleCalendar,
        'status' => IntegrationStatus::Connected,
        'configuration' => [
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addHour()->toIso8601String(),
        ],
    ]);
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'google_event_id' => 'existing-google-id',
        'scheduled_start' => now()->addHour(),
        'scheduled_end' => now()->addHours(2),
        'status' => 'scheduled',
    ]);

    $mock = Mockery::mock(GoogleCalendarService::class);
    $mock->shouldReceive('updateEvent')->once();
    app()->instance(GoogleCalendarService::class, $mock);

    (new PushTaskToGoogleJob($task, 'update'))->handle(app(GoogleCalendarService::class));
});

it('deletes a google event when task is removed', function () {
    $user = User::factory()->create();
    Integration::factory()->create([
        'user_id' => $user->id,
        'type' => IntegrationType::GoogleCalendar,
        'status' => IntegrationStatus::Connected,
        'configuration' => [
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addHour()->toIso8601String(),
        ],
    ]);
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'google_event_id' => 'to-delete-id',
        'status' => 'scheduled',
    ]);

    $mock = Mockery::mock(GoogleCalendarService::class);
    $mock->shouldReceive('deleteEvent')
        ->with(Mockery::type(Integration::class), 'to-delete-id')
        ->once();
    app()->instance(GoogleCalendarService::class, $mock);

    (new PushTaskToGoogleJob($task, 'delete'))->handle(app(GoogleCalendarService::class));

    expect($task->fresh()->google_event_id)->toBeNull();
});

it('skips push if no google integration exists', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id]);

    $mock = Mockery::mock(GoogleCalendarService::class);
    $mock->shouldNotReceive('createEvent');
    $mock->shouldNotReceive('updateEvent');
    $mock->shouldNotReceive('deleteEvent');
    app()->instance(GoogleCalendarService::class, $mock);

    (new PushTaskToGoogleJob($task, 'create'))->handle(app(GoogleCalendarService::class));
});
