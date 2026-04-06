<?php

namespace App\Jobs;

use App\Events\RescheduleProposed;
use App\Models\RescheduleProposal;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateRescheduleProposalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $triggerType,
        public ?string $triggerDescription = null,
    ) {}

    public function handle(): void
    {
        // Expire any existing pending proposals for this user
        RescheduleProposal::where('user_id', $this->user->id)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        // Run scheduling synchronously in dry-run mode
        $job = new ScheduleTasksJob($this->user, dryRun: true);
        $job->handle();
        $proposedChanges = $job->getProposedChanges();

        if (empty($proposedChanges)) {
            return;
        }

        $proposal = RescheduleProposal::create([
            'user_id' => $this->user->id,
            'trigger_type' => $this->triggerType,
            'trigger_description' => $this->triggerDescription,
            'proposed_changes' => $proposedChanges,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(30),
        ]);

        RescheduleProposed::dispatch($this->user->id, $proposal->id);
    }
}
