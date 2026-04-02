<?php

use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use App\Models\Integration;
use App\Models\User;
use App\Services\GoogleCalendarService;

it('redirects to google oauth', function () {
    $user = User::factory()->create();

    $mock = Mockery::mock(GoogleCalendarService::class);
    $mock->shouldReceive('getAuthUrl')->once()->andReturn('https://accounts.google.com/o/oauth2/auth?test=1');
    app()->instance(GoogleCalendarService::class, $mock);

    $this->actingAs($user)
        ->get('/auth/google/redirect')
        ->assertRedirect('https://accounts.google.com/o/oauth2/auth?test=1');
});

it('handles the oauth callback and creates integration', function () {
    $user = User::factory()->create();

    $mock = Mockery::mock(GoogleCalendarService::class);
    $mock->shouldReceive('handleCallback')
        ->with('test-code')
        ->once()
        ->andReturn([
            'access_token' => 'token-123',
            'refresh_token' => 'refresh-456',
            'token_expires_at' => now()->addHour()->toIso8601String(),
        ]);
    app()->instance(GoogleCalendarService::class, $mock);

    $this->actingAs($user)
        ->get('/auth/google/callback?code=test-code')
        ->assertRedirect('/settings');

    $integration = Integration::where('user_id', $user->id)
        ->where('type', IntegrationType::GoogleCalendar->value)
        ->first();

    expect($integration)->not->toBeNull()
        ->and($integration->status)->toBe(IntegrationStatus::Connected)
        ->and($integration->configuration['access_token'])->toBe('token-123');
});

it('updates existing integration on reconnect', function () {
    $user = User::factory()->create();
    $existing = Integration::factory()->create([
        'user_id' => $user->id,
        'type' => IntegrationType::GoogleCalendar,
        'status' => IntegrationStatus::Disconnected,
    ]);

    $mock = Mockery::mock(GoogleCalendarService::class);
    $mock->shouldReceive('handleCallback')
        ->with('new-code')
        ->once()
        ->andReturn([
            'access_token' => 'new-token',
            'refresh_token' => 'new-refresh',
            'token_expires_at' => now()->addHour()->toIso8601String(),
        ]);
    app()->instance(GoogleCalendarService::class, $mock);

    $this->actingAs($user)
        ->get('/auth/google/callback?code=new-code')
        ->assertRedirect('/settings');

    expect($existing->fresh()->status)->toBe(IntegrationStatus::Connected)
        ->and($existing->fresh()->configuration['access_token'])->toBe('new-token');
});

it('requires authentication for oauth routes', function () {
    $this->get('/auth/google/redirect')->assertRedirect('/login');
    $this->get('/auth/google/callback')->assertRedirect('/login');
});

it('redirects to settings with error on oauth failure', function () {
    $user = User::factory()->create();

    $mock = Mockery::mock(GoogleCalendarService::class);
    $mock->shouldReceive('handleCallback')
        ->andThrow(new Exception('OAuth failed'));
    app()->instance(GoogleCalendarService::class, $mock);

    $this->actingAs($user)
        ->get('/auth/google/callback?code=bad-code')
        ->assertRedirect('/settings')
        ->assertSessionHas('error');
});
