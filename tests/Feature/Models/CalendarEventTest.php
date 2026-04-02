<?php

use App\Models\CalendarEvent;
use App\Models\User;

it('belongs to a user', function () {
    $event = CalendarEvent::factory()->create();
    expect($event->user)->toBeInstanceOf(User::class);
});

it('scopes events to a date range', function () {
    $user = User::factory()->create();
    $today = CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'starts_at' => now()->setTime(14, 0),
        'ends_at' => now()->setTime(15, 0),
    ]);
    CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHour(),
    ]);

    $results = CalendarEvent::query()
        ->where('user_id', $user->id)
        ->where('starts_at', '>=', now()->startOfDay())
        ->where('starts_at', '<=', now()->endOfDay())
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($today->id);
});
