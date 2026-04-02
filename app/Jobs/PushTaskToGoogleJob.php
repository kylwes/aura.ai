<?php

namespace App\Jobs;

use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use App\Models\Task;
use App\Services\GoogleCalendarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PushTaskToGoogleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Task $task,
        public string $action,
    ) {}

    public function handle(GoogleCalendarService $service): void
    {
        $integration = $this->task->user->integrations()
            ->where('type', IntegrationType::GoogleCalendar)
            ->where('status', IntegrationStatus::Connected)
            ->first();

        if (! $integration) {
            return;
        }

        match ($this->action) {
            'create' => $this->createEvent($service, $integration),
            'update' => $service->updateEvent($integration, $this->task),
            'delete' => $this->deleteEvent($service, $integration),
        };
    }

    private function createEvent(GoogleCalendarService $service, $integration): void
    {
        $googleEventId = $service->createEvent($integration, $this->task);
        $this->task->update(['google_event_id' => $googleEventId]);
    }

    private function deleteEvent(GoogleCalendarService $service, $integration): void
    {
        if ($this->task->google_event_id) {
            $service->deleteEvent($integration, $this->task->google_event_id);
            $this->task->update(['google_event_id' => null]);
        }
    }
}
