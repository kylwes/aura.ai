<?php

namespace App\Livewire;

use App\Settings\UserPreferences;
use Livewire\Component;

class DarkModeToggle extends Component
{
    public bool $darkMode = false;

    public function mount(): void
    {
        $this->darkMode = app(UserPreferences::class)->dark_mode;
    }

    public function toggle(): void
    {
        $preferences = app(UserPreferences::class);
        $preferences->dark_mode = ! $preferences->dark_mode;
        $preferences->save();

        $this->darkMode = $preferences->dark_mode;

        $this->js($this->darkMode
            ? "document.body.classList.add('dark')"
            : "document.body.classList.remove('dark')"
        );
    }

    public function render()
    {
        return view('livewire.dark-mode-toggle');
    }
}
