<?php

use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use App\Models\Integration;
use App\Models\User;

it('belongs to a user', function () {
    $integration = Integration::factory()->create();
    expect($integration->user)->toBeInstanceOf(User::class);
});

it('casts type to IntegrationType enum', function () {
    $integration = Integration::factory()->create(['type' => 'jira']);
    expect($integration->type)->toBe(IntegrationType::Jira);
});

it('casts status to IntegrationStatus enum', function () {
    $integration = Integration::factory()->create(['status' => 'connected']);
    expect($integration->status)->toBe(IntegrationStatus::Connected);
});
