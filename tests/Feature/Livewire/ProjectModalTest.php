<?php

use App\Livewire\ProjectModal;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    Queue::fake();
});

it('renders the project modal in create mode', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ProjectModal::class)
        ->assertStatus(200)
        ->assertSee('New Project');
});

it('renders the project modal in edit mode', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id, 'title' => 'My Project']);

    Livewire::actingAs($user)
        ->test(ProjectModal::class, ['projectId' => $project->id])
        ->assertStatus(200)
        ->assertSee('Edit Project')
        ->assertSet('title', 'My Project');
});

it('loads the existing project data into form fields', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'title' => 'Design System',
        'description' => 'Our shared design tokens',
        'color' => '#8b5cf6',
    ]);

    Livewire::actingAs($user)
        ->test(ProjectModal::class, ['projectId' => $project->id])
        ->assertSet('title', 'Design System')
        ->assertSet('description', 'Our shared design tokens')
        ->assertSet('color', '#8b5cf6');
});

it('creates a new project on save', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ProjectModal::class)
        ->set('title', 'New Feature')
        ->set('color', '#22c55e')
        ->call('save');

    expect(Project::where('user_id', $user->id)->where('title', 'New Feature')->exists())->toBeTrue();
});

it('dispatches project-saved after creating', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ProjectModal::class)
        ->set('title', 'Alpha')
        ->call('save')
        ->assertDispatched('project-saved');
});

it('updates an existing project on save', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id, 'title' => 'Old Name']);

    Livewire::actingAs($user)
        ->test(ProjectModal::class, ['projectId' => $project->id])
        ->set('title', 'New Name')
        ->call('save');

    expect($project->fresh()->title)->toBe('New Name');
});

it('deletes the project when delete is called', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(ProjectModal::class, ['projectId' => $project->id])
        ->call('delete')
        ->assertDispatched('project-saved');

    expect(Project::find($project->id))->toBeNull();
});

it('does nothing on delete when in create mode', function () {
    $user = User::factory()->create();
    $otherProject = Project::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(ProjectModal::class)
        ->call('delete');

    expect(Project::find($otherProject->id))->not->toBeNull();
});

it('validates that title is required', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ProjectModal::class)
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title' => 'required']);
});

it('validates that title cannot exceed 255 characters', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ProjectModal::class)
        ->set('title', str_repeat('a', 256))
        ->call('save')
        ->assertHasErrors(['title' => 'max']);
});

it('validates that description cannot exceed 5000 characters', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ProjectModal::class)
        ->set('title', 'Valid Title')
        ->set('description', str_repeat('x', 5001))
        ->call('save')
        ->assertHasErrors(['description' => 'max']);
});

it('cannot edit a project belonging to another user', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $owner->id]);

    expect(fn () => Livewire::actingAs($attacker)
        ->test(ProjectModal::class, ['projectId' => $project->id])
    )->toThrow(ModelNotFoundException::class);
});

it('cannot delete a project belonging to another user', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $owner->id]);

    expect(fn () => Livewire::actingAs($attacker)
        ->test(ProjectModal::class, ['projectId' => $project->id])
    )->toThrow(ModelNotFoundException::class);

    expect(Project::find($project->id))->not->toBeNull();
});
