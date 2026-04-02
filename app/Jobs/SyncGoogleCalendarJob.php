<?php

namespace App\Jobs;

use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SyncGoogleCalendarJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
    ) {}

    public function handle(GoogleCalendarService $service): void
    {
        $integration = $this->user->integrations()
            ->where('type', IntegrationType::GoogleCalendar)
            ->where('status', IntegrationStatus::Connected)
            ->first();

        if (! $integration) {
            return;
        }

        $from = now()->subWeeks(2)->startOfDay();
        $to = now()->addWeeks(2)->endOfDay();

        $googleEvents = $service->fetchEvents($integration, $from, $to);

        foreach ($googleEvents as $gEvent) {
            if ($gEvent['status'] === 'cancelled') {
                CalendarEvent::where('user_id', $this->user->id)
                    ->where('external_id', $gEvent['id'])
                    ->delete();

                continue;
            }

            $startsAt = $gEvent['is_all_day']
                ? Carbon::parse($gEvent['start'])->startOfDay()
                : Carbon::parse($gEvent['start']);

            $endsAt = $gEvent['is_all_day']
                ? Carbon::parse($gEvent['end'])->endOfDay()
                : Carbon::parse($gEvent['end']);

            CalendarEvent::updateOrCreate(
                [
                    'user_id' => $this->user->id,
                    'external_id' => $gEvent['id'],
                ],
                [
                    'integration_id' => $integration->id,
                    'title' => $gEvent['summary'],
                    'description' => $gEvent['description'],
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'is_all_day' => $gEvent['is_all_day'],
                ]
            );
        }

        $config = $integration->configuration;
        $config['last_synced_at'] = now()->toIso8601String();
        $integration->update(['configuration' => $config]);
    }
}
