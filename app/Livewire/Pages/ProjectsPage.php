<?php

namespace App\Livewire\Pages;

use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Projects — Aura')]
class ProjectsPage extends Component
{
    #[On('project-saved')]
    public function onProjectSaved(): void {}

    public function deleteProject(int $projectId): void
    {
        auth()->user()->projects()->findOrFail($projectId)->delete();
        $this->dispatch('toast', type: 'success', title: 'Project deleted');
    }

    public function render()
    {
        return view('livewire.pages.projects-page', [
            'projects' => auth()->user()->projects()->withCount('tasks')->with('schedules')->orderBy('title')->get(),
        ]);
    }
}
