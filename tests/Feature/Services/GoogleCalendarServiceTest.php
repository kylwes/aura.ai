<?php

use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use App\Models\Integration;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Google\Client as GoogleClient;

it('generates an auth url', function () {
    $service = app(GoogleCalendarService::class);
    $url = $service->getAuthUrl();

    expect($url)->toContain('accounts.google.com')
        ->toContain('calendar');
});

it('builds an authenticated client from integration tokens', function () {
    $user = User::factory()->create();
    $integration = Integration::factory()->create([
        'user_id' => $user->id,
        'type' => IntegrationType::GoogleCalendar,
        'status' => IntegrationStatus::Connected,
        'configuration' => [
            'access_token' => 'test-token',
            'refresh_token' => 'test-refresh',
            'token_expires_at' => now()->addHour()->toIso8601String(),
        ],
    ]);

    $service = app(GoogleCalendarService::class);
    $client = $service->getClient($integration);

    expect($client)->toBeInstanceOf(GoogleClient::class)
        ->and($client->getAccessToken())->toBe(['access_token' => 'test-token']);
});

it('refreshes expired tokens', function () {
    $user = User::factory()->create();
    $integration = Integration::factory()->create([
        'user_id' => $user->id,
        'type' => IntegrationType::GoogleCalendar,
        'status' => IntegrationStatus::Connected,
        'configuration' => [
            'access_token' => 'old-token',
            'refresh_token' => 'test-refresh',
            'token_expires_at' => now()->subHour()->toIso8601String(),
        ],
    ]);

    $mockClient = Mockery::mock(GoogleClient::class);
    $mockClient->shouldReceive('setAccessToken')->once();
    $mockClient->shouldReceive('isAccessTokenExpired')->andReturn(true);
    $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
        ->with('test-refresh')
        ->andReturn([
            'access_token' => 'new-token',
            'expires_in' => 3600,
        ]);
    $mockClient->shouldReceive('getAccessToken')->andReturn('new-token');

    $service = app(GoogleCalendarService::class);
    $service->refreshTokenIfNeeded($integration, $mockClient);

    $integration->refresh();
    expect($integration->configuration['access_token'])->toBe('new-token');
});
