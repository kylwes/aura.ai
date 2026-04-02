<?php

namespace App\Livewire\Auth;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('Verify email — Aura')]
class VerifyEmail extends Component
{
    public function resend(): void
    {
        auth()->user()->sendEmailVerificationNotification();
        session()->flash('message', 'Verification link sent!');
    }

    public function render()
    {
        return view('livewire.auth.verify-email');
    }
}
