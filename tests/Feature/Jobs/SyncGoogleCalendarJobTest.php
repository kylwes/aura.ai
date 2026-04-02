<?php

use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use App\Jobs\SyncGoogleCalendarJob;
use App\Models\CalendarEvent;
use App\Models\Integration;
use App\Models\User;
use App\Services\GoogleCalendarService;

it('syncs events from google into calendar_events table', function () {
    $user = User::factory()->create();
    $integration = Integration::factory()->create([
        'user_id' => $user->id,
        'type' => IntegrationType::GoogleCalendar,
        'status' => IntegrationStatus::Connected,
        'configuration' => [
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addHour()->toIso8601String(),
        ],
    ]);

    $mock = Mockery::mock(GoogleCalendarService::class);
    $mock->shouldReceive('fetchEvents')
        ->once()
        ->andReturn([
            [
                'id' => 'google-event-1',
                'summary' => 'Team Standup',
                'start' => now()->setTime(9, 30)->toRfc3339String(),
                'end' => now()->setTime(9, 45)->toRfc3339String(),
                'description' => null,
                'is_all_day' => false,
                'status' => 'confirmed',
            ],
            [
                'id' => 'google-event-2',
                'summary' => 'Lunch',
                'start' => now()->setTime(12, 0)->toRfc3339String(),
                'end' => now()->setTime(13, 0)->toRfc3339String(),
                'description' => 'Team lunch',
                'is_all_day' => false,
                'status' => 'confirmed',
            ],
        ]);
    app()->instance(GoogleCalendarService::class, $mock);

    (new SyncGoogleCalendarJob($user))->handle(app(GoogleCalendarService::class));

    expect(CalendarEvent::where('user_id', $user->id)->count())->toBe(2)
        ->and(CalendarEvent::where('external_id', 'google-event-1')->first()->title)->toBe('Team Standup');
});

it('updates existing events on re-sync', function () {
    $user = User::factory()->create();
    $integration = Integration::factory()->create([
        'user_id' => $user->id,
        'type' => IntegrationType::GoogleCalendar,
        'status' => IntegrationStatus::Connected,
        'configuration' => [
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addHour()->toIso8601String(),
        ],
    ]);

    CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'integration_id' => $integration->id,
        'external_id' => 'google-event-1',
        'title' => 'Old Title',
        'starts_at' => now()->setTime(9, 0),
        'ends_at' => now()->setTime(10, 0),
    ]);

    $mock = Mockery::mock(GoogleCalendarService::class);
    $mock->shouldReceive('fetchEvents')
        ->once()
        ->andReturn([
            [
                'id' => 'google-event-1',
                'summary' => 'Updated Title',
                'start' => now()->setTime(9, 30)->toRfc3339String(),
                'end' => now()->setTime(10, 30)->toRfc3339String(),
                'description' => null,
                'is_all_day' => false,
                'status' => 'confirmed',
            ],
        ]);
    app()->instance(GoogleCalendarService::class, $mock);

    (new SyncGoogleCalendarJob($user))->handle(app(GoogleCalendarService::class));

    $event = CalendarEvent::where('external_id', 'google-event-1')->first();
    expect($event->title)->toBe('Updated Title')
        ->and(CalendarEvent::where('user_id', $user->id)->count())->toBe(1);
});

it('deletes cancelled events', function () {
    $user = User::factory()->create();
    $integration = Integration::factory()->create([
        'user_id' => $user->id,
        'type' => IntegrationType::GoogleCalendar,
        'status' => IntegrationStatus::Connected,
        'configuration' => [
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addHour()->toIso8601String(),
        ],
    ]);

    CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'integration_id' => $integration->id,
        'external_id' => 'google-event-cancelled',
        'title' => 'Will be cancelled',
        'starts_at' => now()->setTime(14, 0),
        'ends_at' => now()->setTime(15, 0),
    ]);

    $mock = Mockery::mock(GoogleCalendarService::class);
    $mock->shouldReceive('fetchEvents')
        ->once()
        ->andReturn([
            [
                'id' => 'google-event-cancelled',
                'summary' => '',
                'start' => '',
                'end' => '',
                'description' => null,
                'is_all_day' => false,
                'status' => 'cancelled',
            ],
        ]);
    app()->instance(GoogleCalendarService::class, $mock);

    (new SyncGoogleCalendarJob($user))->handle(app(GoogleCalendarService::class));

    expect(CalendarEvent::where('external_id', 'google-event-cancelled')->exists())->toBeFalse();
});

it('skips sync if no google calendar integration exists', function () {
    $user = User::factory()->create();

    $mock = Mockery::mock(GoogleCalendarService::class);
    $mock->shouldNotReceive('fetchEvents');
    app()->instance(GoogleCalendarService::class, $mock);

    (new SyncGoogleCalendarJob($user))->handle(app(GoogleCalendarService::class));

    expect(CalendarEvent::where('user_id', $user->id)->count())->toBe(0);
});
