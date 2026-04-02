<?php

use App\Enums\IntegrationType;
use App\Livewire\InboxPanel;
use App\Models\InboxItem;
use App\Models\Integration;
use App\Models\User;
use Livewire\Livewire;

it('renders the inbox panel', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(InboxPanel::class)
        ->assertSee('Inbox')
        ->assertStatus(200);
});

it('shows pending inbox items', function () {
    $user = User::factory()->create();
    $integration = Integration::factory()->create(['user_id' => $user->id, 'type' => IntegrationType::Slack]);
    InboxItem::factory()->create([
        'user_id' => $user->id,
        'integration_id' => $integration->id,
        'preview_text' => 'Review the PR please',
        'status' => 'pending',
    ]);
    Livewire::actingAs($user)
        ->test(InboxPanel::class)
        ->assertSee('Review the PR please');
});

it('can dismiss an inbox item', function () {
    $user = User::factory()->create();
    $item = InboxItem::factory()->create(['user_id' => $user->id, 'status' => 'pending']);
    Livewire::actingAs($user)
        ->test(InboxPanel::class)
        ->call('dismiss', $item->id);
    expect($item->fresh()->status->value)->toBe('dismissed');
});
