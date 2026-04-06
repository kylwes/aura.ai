<?php

namespace App\Livewire;

use App\Jobs\ScheduleTasksJob;
use App\Models\RescheduleProposal;
use LivewireUI\Modal\ModalComponent;

class ReschedulePreviewModal extends ModalComponent
{
    public int $proposalId;

    public function mount(int $proposalId): void
    {
        $this->proposalId = $proposalId;
    }

    public function accept(): void
    {
        $proposal = RescheduleProposal::where('user_id', auth()->id())
            ->where('id', $this->proposalId)
            ->where('status', 'pending')
            ->firstOrFail();

        $proposal->accept();

        $this->dispatch('toast', type: 'success', title: 'Schedule updated', body: 'Proposed changes have been applied.');
        $this->forceClose()->closeModal();
    }

    public function reject(): void
    {
        $proposal = RescheduleProposal::where('user_id', auth()->id())
            ->where('id', $this->proposalId)
            ->firstOrFail();

        $proposal->reject();

        ScheduleTasksJob::debounce(auth()->user());

        $this->dispatch('toast', type: 'info', title: 'Proposal rejected', body: 'Rescheduling without preview...');
        $this->forceClose()->closeModal();
    }

    public static function modalMaxWidth(): string
    {
        return 'lg';
    }

    public function render()
    {
        $proposal = RescheduleProposal::where('user_id', auth()->id())
            ->find($this->proposalId);

        return view('livewire.reschedule-preview-modal', [
            'proposal' => $proposal,
            'changes' => $proposal?->proposed_changes ?? [],
        ]);
    }
}
