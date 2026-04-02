<?php

use App\Livewire\PlanSummary;
use App\Models\Task;
use App\Models\User;
use Livewire\Livewire;

it('renders the plan summary page', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(PlanSummary::class)
        ->assertSee('AI Schedule Proposal')
        ->assertStatus(200);
});

it('shows scheduled tasks for review', function () {
    $user = User::factory()->create();
    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Review docs',
        'status' => 'scheduled',
        'is_ai_scheduled' => true,
        'scheduled_start' => now()->addHour(),
        'scheduled_end' => now()->addHours(2),
    ]);
    Livewire::actingAs($user)
        ->test(PlanSummary::class)
        ->assertSee('Review docs');
});
