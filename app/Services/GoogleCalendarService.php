<?php

namespace App\Services;

use App\Models\Integration;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendarApi;
use Illuminate\Support\Carbon;

class GoogleCalendarService
{
    public function getAuthUrl(): string
    {
        $client = $this->buildBaseClient();
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->addScope(GoogleCalendarApi::CALENDAR_READONLY);

        return $client->createAuthUrl();
    }

    /**
     * @return array{access_token: string, refresh_token: string, token_expires_at: string}
     */
    public function handleCallback(string $code): array
    {
        $client = $this->buildBaseClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);

        return [
            'access_token' => $token['access_token'],
            'refresh_token' => $token['refresh_token'] ?? '',
            'token_expires_at' => now()->addSeconds($token['expires_in'])->toIso8601String(),
        ];
    }

    public function getClient(Integration $integration): GoogleClient
    {
        $client = $this->buildBaseClient();
        $config = $integration->configuration;
        $client->setAccessToken($config['access_token']);

        return $client;
    }

    public function refreshTokenIfNeeded(Integration $integration, ?GoogleClient $client = null): GoogleClient
    {
        $config = $integration->configuration;

        if ($client === null) {
            $client = $this->getClient($integration);
        } else {
            $client->setAccessToken($config['access_token']);
        }

        $isExpired = $client->isAccessTokenExpired()
            || (isset($config['token_expires_at']) && Carbon::parse($config['token_expires_at'])->isPast());

        if ($isExpired && ! empty($config['refresh_token'])) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($config['refresh_token']);

            $integration->update([
                'configuration' => array_merge($config, [
                    'access_token' => $newToken['access_token'] ?? $client->getAccessToken(),
                    'token_expires_at' => now()->addSeconds($newToken['expires_in'] ?? 3600)->toIso8601String(),
                ]),
            ]);
        }

        return $client;
    }

    /**
     * @return array<int, array{id: string, summary: string, start: string, end: string, description: ?string, is_all_day: bool, status: string}>
     */
    public function fetchEvents(Integration $integration, Carbon $from, Carbon $to): array
    {
        $client = $this->refreshTokenIfNeeded($integration);
        $calendarApi = new GoogleCalendarApi($client);

        $calendarList = $calendarApi->calendarList->listCalendarList();
        $allEvents = [];

        foreach ($calendarList->getItems() as $calendar) {
            $events = $calendarApi->events->listEvents($calendar->getId(), [
                'timeMin' => $from->toRfc3339String(),
                'timeMax' => $to->toRfc3339String(),
                'singleEvents' => true,
                'orderBy' => 'startTime',
                'maxResults' => 250,
            ]);

            foreach ($events->getItems() as $event) {
                if ($event->getStatus() === 'cancelled') {
                    $allEvents[] = [
                        'id' => $event->getId(),
                        'summary' => '',
                        'start' => '',
                        'end' => '',
                        'description' => null,
                        'is_all_day' => false,
                        'status' => 'cancelled',
                    ];

                    continue;
                }

                $start = $event->getStart();
                $end = $event->getEnd();
                $isAllDay = ! empty($start->getDate());

                $allEvents[] = [
                    'id' => $event->getId(),
                    'summary' => $event->getSummary() ?? '(No title)',
                    'start' => $isAllDay ? $start->getDate() : $start->getDateTime(),
                    'end' => $isAllDay ? $end->getDate() : $end->getDateTime(),
                    'description' => $event->getDescription(),
                    'is_all_day' => $isAllDay,
                    'status' => $event->getStatus(),
                ];
            }
        }

        return $allEvents;
    }

    private function buildBaseClient(): GoogleClient
    {
        $client = new GoogleClient;
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect_uri'));

        return $client;
    }
}
