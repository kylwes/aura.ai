<?php

namespace App\Services;

use App\Models\Integration;
use App\Models\Task;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendarApi;
use Google\Service\Calendar\Calendar as GoogleCalendar;
use Google\Service\Calendar\Event as GoogleEvent;
use Google\Service\Exception;
use Illuminate\Support\Carbon;

class GoogleCalendarService
{
    public function getAuthUrl(): string
    {
        $client = $this->buildBaseClient();
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->addScope(GoogleCalendarApi::CALENDAR_READONLY);
        $client->addScope(GoogleCalendarApi::CALENDAR_EVENTS);

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
        $config = $integration->configuration;
        $auraCalendarId = $config['aura_calendar_id'] ?? null;

        $calendarList = $calendarApi->calendarList->listCalendarList();
        $allEvents = [];

        foreach ($calendarList->getItems() as $calendar) {
            if ($calendar->getId() === $auraCalendarId) {
                continue;
            }

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

    public function createEvent(Integration $integration, Task $task): string
    {
        $client = $this->refreshTokenIfNeeded($integration);
        $calendarApi = new GoogleCalendarApi($client);
        $calendarId = $this->resolveTargetCalendar($integration, $calendarApi);
        $config = $integration->configuration;
        $pushTarget = $config['push_target'] ?? 'aura_calendar';

        $title = $pushTarget === 'primary' ? '[Aura] '.$task->title : $task->title;

        $event = new GoogleEvent([
            'summary' => $title,
            'description' => ($task->description ?? '')."\n\nManaged by Aura",
            'start' => [
                'dateTime' => $task->scheduled_start->toRfc3339String(),
                'timeZone' => $task->user->timezone ?? 'UTC',
            ],
            'end' => [
                'dateTime' => $task->scheduled_end->toRfc3339String(),
                'timeZone' => $task->user->timezone ?? 'UTC',
            ],
        ]);

        if ($pushTarget === 'aura_calendar') {
            $event->setColorId('1');
        }

        $created = $calendarApi->events->insert($calendarId, $event);

        return $created->getId();
    }

    public function updateEvent(Integration $integration, Task $task): void
    {
        if (! $task->google_event_id) {
            return;
        }

        $client = $this->refreshTokenIfNeeded($integration);
        $calendarApi = new GoogleCalendarApi($client);
        $calendarId = $this->resolveTargetCalendar($integration, $calendarApi);
        $config = $integration->configuration;
        $pushTarget = $config['push_target'] ?? 'aura_calendar';

        $title = $pushTarget === 'primary' ? '[Aura] '.$task->title : $task->title;

        try {
            $event = $calendarApi->events->get($calendarId, $task->google_event_id);
            $event->setSummary($title);
            $event->setDescription(($task->description ?? '')."\n\nManaged by Aura");
            $event->getStart()->setDateTime($task->scheduled_start->toRfc3339String());
            $event->getEnd()->setDateTime($task->scheduled_end->toRfc3339String());

            $calendarApi->events->update($calendarId, $task->google_event_id, $event);
        } catch (Exception $e) {
            if ($e->getCode() === 404) {
                $task->update(['google_event_id' => null]);
            } else {
                throw $e;
            }
        }
    }

    public function deleteEvent(Integration $integration, string $googleEventId): void
    {
        $client = $this->refreshTokenIfNeeded($integration);
        $calendarApi = new GoogleCalendarApi($client);
        $calendarId = $this->resolveTargetCalendar($integration, $calendarApi);

        try {
            $calendarApi->events->delete($calendarId, $googleEventId);
        } catch (Exception $e) {
            if ($e->getCode() !== 404) {
                throw $e;
            }
        }
    }

    public function createAuraCalendar(Integration $integration): string
    {
        $client = $this->refreshTokenIfNeeded($integration);
        $calendarApi = new GoogleCalendarApi($client);

        $calendar = new GoogleCalendar;
        $calendar->setSummary('Aura Tasks');
        $calendar->setDescription('Tasks scheduled by Aura AI');
        $calendar->setTimeZone($integration->user->timezone ?? 'UTC');

        $created = $calendarApi->calendars->insert($calendar);
        $calendarId = $created->getId();

        $config = $integration->configuration;
        $config['aura_calendar_id'] = $calendarId;
        $integration->update(['configuration' => $config]);

        return $calendarId;
    }

    private function resolveTargetCalendar(Integration $integration, GoogleCalendarApi $calendarApi): string
    {
        $config = $integration->configuration;
        $pushTarget = $config['push_target'] ?? 'aura_calendar';

        if ($pushTarget === 'primary') {
            return 'primary';
        }

        if (! empty($config['aura_calendar_id'])) {
            return $config['aura_calendar_id'];
        }

        return $this->createAuraCalendar($integration);
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
