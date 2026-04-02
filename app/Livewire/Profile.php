<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Profile — Aura')]
class Profile extends Component
{
    public string $name;

    public string $email;

    public string $timezone;

    public string $current_password = '';

    public string $new_password = '';

    public string $new_password_confirmation = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->timezone = $user->timezone ?? 'UTC';
    }

    public function updateProfile(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore(auth()->id())],
            'timezone' => ['required', 'string'],
        ]);
        auth()->user()->update([
            'name' => $this->name,
            'email' => $this->email,
            'timezone' => $this->timezone,
        ]);
        session()->flash('profile-message', 'Profile updated.');
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        if (! Hash::check($this->current_password, auth()->user()->password)) {
            $this->addError('current_password', 'The current password is incorrect.');

            return;
        }
        auth()->user()->update(['password' => Hash::make($this->new_password)]);
        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        session()->flash('password-message', 'Password updated.');
    }

    public function render()
    {
        return view('livewire.profile');
    }
}
