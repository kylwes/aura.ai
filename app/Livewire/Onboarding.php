<?php

namespace App\Livewire;

use App\Enums\IntegrationType;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('Get started — Aura')]
class Onboarding extends Component
{
    public int $step = 1;

    public string $workingHoursStart = '09:00';

    public string $workingHoursEnd = '17:00';

    /** @var array<int, int> */
    public array $workingDays = [1, 2, 3, 4, 5];

    public function nextStep(): void
    {
        if ($this->step === 2) {
            auth()->user()->update([
                'working_hours_start' => $this->workingHoursStart,
                'working_hours_end' => $this->workingHoursEnd,
                'working_days' => $this->workingDays,
            ]);
        }
        $this->step = min(3, $this->step + 1);
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function complete(): void
    {
        auth()->user()->update(['onboarded_at' => now()]);
        $this->redirect('/', navigate: true);
    }

    public function skip(): void
    {
        $this->complete();
    }

    public function render()
    {
        return view('livewire.onboarding', [
            'integrationTypes' => IntegrationType::cases(),
        ]);
    }
}
