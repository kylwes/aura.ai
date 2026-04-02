<?php

use App\Enums\InboxItemStatus;
use App\Models\InboxItem;
use App\Models\User;

it('belongs to a user', function () {
    $item = InboxItem::factory()->create();
    expect($item->user)->toBeInstanceOf(User::class);
});

it('casts status to InboxItemStatus enum', function () {
    $item = InboxItem::factory()->create(['status' => 'pending']);
    expect($item->status)->toBe(InboxItemStatus::Pending);
});

it('scopes to pending items', function () {
    $user = User::factory()->create();
    $pending = InboxItem::factory()->create(['user_id' => $user->id, 'status' => 'pending']);
    InboxItem::factory()->create(['user_id' => $user->id, 'status' => 'accepted']);

    $results = InboxItem::query()
        ->where('user_id', $user->id)
        ->where('status', 'pending')
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($pending->id);
});
