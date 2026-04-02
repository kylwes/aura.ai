# Google Calendar Integration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Two-way Google Calendar sync — import events into Aura's weekly grid and push AI-scheduled tasks back to Google Calendar.

**Architecture:** OAuth2 flow via a controller, a `GoogleCalendarService` encapsulating all Google API calls, two queued jobs (sync inbound, push outbound), and a scheduler entry for periodic polling. The existing Integration model stores tokens in its `configuration` JSON column.

**Tech Stack:** Laravel 13, Livewire 4, `google/apiclient` PHP SDK, Pest 4

**Spec:** `docs/superpowers/specs/2026-03-31-google-calendar-integration-design.md`

---

## File Map

### Dependencies & Config
- Modify: `composer.json` (add `google/apiclient`)
- Modify: `config/services.php` (add Google credentials)
- Modify: `.env.example` (add Google env vars)

### Migration
- Create: migration to add `google_event_id` to `tasks` table

### Service
- Create: `app/Services/GoogleCalendarService.php`

### Controller
- Create: `app/Http/Controllers/GoogleCalendarController.php`

### Jobs
- Create: `app/Jobs/SyncGoogleCalendarJob.php`
- Create: `app/Jobs/PushTaskToGoogleJob.php`

### Routes & Scheduler
- Modify: `routes/web.php` (add OAuth routes)
- Modify: `routes/console.php` (add scheduler)

### Model Updates
- Modify: `app/Models/Task.php` (add `google_event_id` to fillable)

### UI Updates
- Modify: `resources/views/components/integration-card.blade.php` (wire Connect for Google Calendar to OAuth)
- Modify: `app/Livewire/Settings.php` (add sync/configure actions for Google Calendar)
- Modify: `resources/views/livewire/settings.blade.php` (add Google Calendar config section)
- Modify: `app/Livewire/Calendar.php` (trigger sync on mount)

### Tests
- Create: `tests/Feature/Services/GoogleCalendarServiceTest.php`
- Create: `tests/Feature/Jobs/SyncGoogleCalendarJobTest.php`
- Create: `tests/Feature/Jobs/PushTaskToGoogleJobTest.php`
- Create: `tests/Feature/Http/GoogleCalendarControllerTest.php`

---

## Phase 1: Foundation

### Task 1: Install google/apiclient and configure credentials

**Files:**
- Modify: `composer.json`
- Modify: `config/services.php`
- Modify: `.env.example`

- [ ] **Step 1: Install the Google API client**

```bash
cd /Users/kylianwester/Sites/Personal/aura.ai
composer require google/apiclient --no-interaction
```

- [ ] **Step 2: Add Google config to services.php**

In `config/services.php`, add after the `slack` entry:

```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect_uri' => env('GOOGLE_REDIRECT_URI', 'http://localhost:8000/auth/google/callback'),
],
```

- [ ] **Step 3: Add env vars to .env.example**

Append to `.env.example`:

```
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

- [ ] **Step 4: Commit**

```bash
git add composer.json composer.lock config/services.php .env.example
git commit -m "feat: install google/apiclient and add Google OAuth config"
```

---

### Task 2: Add google_event_id migration and update Task model

**Files:**
- Create: migration for `google_event_id` column
- Modify: `app/Models/Task.php`

- [ ] **Step 1: Create migration**

```bash
php artisan make:migration add_google_event_id_to_tasks_table --no-interaction
```

Replace the `up()` method:

```php
public function up(): void
{
    Schema::table('tasks', function (Blueprint $table) {
        $table->string('google_event_id')->nullable()->after('ai_reasoning');
    });
}
```

And the `down()` method:

```php
public function down(): void
{
    Schema::table('tasks', function (Blueprint $table) {
        $table->dropColumn('google_event_id');
    });
}
```

- [ ] **Step 2: Run the migration**

```bash
php artisan migrate --no-interaction
```

- [ ] **Step 3: Add google_event_id to Task model fillable**

In `app/Models/Task.php`, update the `#[Fillable]` attribute to include `'google_event_id'`:

```php
#[Fillable([
    'user_id', 'integration_id', 'title', 'description', 'source_url',
    'source_reference', 'priority', 'estimated_duration', 'deadline',
    'scheduled_start', 'scheduled_end', 'is_ai_scheduled', 'ai_reasoning',
    'google_event_id', 'status',
])]
```

- [ ] **Step 4: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 5: Commit**

```bash
git add database/migrations/ app/Models/Task.php
git commit -m "feat: add google_event_id column to tasks table"
```

---

## Phase 2: Google Calendar Service

### Task 3: Create GoogleCalendarService with OAuth methods

**Files:**
- Create: `app/Services/GoogleCalendarService.php`
- Create: `tests/Feature/Services/GoogleCalendarServiceTest.php`

- [ ] **Step 1: Write tests for OAuth methods**

Create `tests/Feature/Services/GoogleCalendarServiceTest.php`:

```php
<?php

use App\Models\Integration;
use App\Models\User;
use App\Enums\IntegrationType;
use App\Enums\IntegrationStatus;
use App\Services\GoogleCalendarService;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendarApi;
use Google\Service\Calendar\Resource\Events as EventsResource;
use Google\Service\Calendar\Resource\CalendarList as CalendarListResource;
use Google\Service\Calendar\Resource\Calendars as CalendarsResource;

it('generates an auth url', function () {
    $service = app(GoogleCalendarService::class);
    $url = $service->getAuthUrl();

    expect($url)->toContain('accounts.google.com')
        ->toContain('calendar');
});

it('builds an authenticated client from integration tokens', function () {
    $user = User::factory()->create();
    $integration = Integration::factory()->create([
        'user_id' => $user->id,
        'type' => IntegrationType::GoogleCalendar,
        'status' => IntegrationStatus::Connected,
        'configuration' => [
            'access_token' => 'test-token',
            'refresh_token' => 'test-refresh',
            'token_expires_at' => now()->addHour()->toIso8601String(),
        ],
    ]);

    $service = app(GoogleCalendarService::class);
    $client = $service->getClient($integration);

    expect($client)->toBeInstanceOf(GoogleClient::class)
        ->and($client->getAccessToken())->toBe('test-token');
});

it('refreshes expired tokens', function () {
    $user = User::factory()->create();
    $integration = Integration::factory()->create([
        'user_id' => $user->id,
        'type' => IntegrationType::GoogleCalendar,
        'status' => IntegrationStatus::Connected,
        'configuration' => [
            'access_token' => 'old-token',
            'refresh_token' => 'test-refresh',
            'token_expires_at' => now()->subHour()->toIso8601String(),
        ],
    ]);

    $mockClient = Mockery::mock(GoogleClient::class);
    $mockClient->shouldReceive('setAccessToken')->once();
    $mockClient->shouldReceive('isAccessTokenExpired')->andReturn(true);
    $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
        ->with('test-refresh')
        ->andReturn([
            'access_token' => 'new-token',
            'expires_in' => 3600,
        ]);
    $mockClient->shouldReceive('getAccessToken')->andReturn('new-token');

    $service = app(GoogleCalendarService::class);
    $service->refreshTokenIfNeeded($integration, $mockClient);

    $integration->refresh();
    expect($integration->configuration['access_token'])->toBe('new-token');
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact --filter=GoogleCalendarServiceTest
```

Expected: FAIL — service doesn't exist.

- [ ] **Step 3: Create GoogleCalendarService**

Create `app/Services/GoogleCalendarService.php`:

```php
<?php

namespace App\Services;

use App\Enums\IntegrationStatus;
use App\Models\CalendarEvent;
use App\Models\Integration;
use App\Models\Task;
use App\Models\User;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendarApi;
use Google\Service\Calendar\Event as GoogleEvent;
use Google\Service\Calendar\Calendar as GoogleCalendar;
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
        $client = $client ?? $this->getClient($integration);
        $config = $integration->configuration;

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
        } catch (\Google\Service\Exception $e) {
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
        } catch (\Google\Service\Exception $e) {
            if ($e->getCode() !== 404) {
                throw $e;
            }
        }
    }

    public function createAuraCalendar(Integration $integration): string
    {
        $client = $this->refreshTokenIfNeeded($integration);
        $calendarApi = new GoogleCalendarApi($client);

        $calendar = new GoogleCalendar();
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
        $client = new GoogleClient();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect_uri'));

        return $client;
    }
}
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --compact --filter=GoogleCalendarServiceTest
```

Expected: All PASS.

- [ ] **Step 5: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 6: Commit**

```bash
git add app/Services/GoogleCalendarService.php tests/Feature/Services/GoogleCalendarServiceTest.php
git commit -m "feat: add GoogleCalendarService with OAuth, fetch, create, update, delete methods"
```

---

## Phase 3: OAuth Controller & Routes

### Task 4: Create GoogleCalendarController with OAuth routes

**Files:**
- Create: `app/Http/Controllers/GoogleCalendarController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Http/GoogleCalendarControllerTest.php`

- [ ] **Step 1: Write controller tests**

Create `tests/Feature/Http/GoogleCalendarControllerTest.php`:

```php
<?php

use App\Models\User;
use App\Models\Integration;
use App\Enums\IntegrationType;
use App\Enums\IntegrationStatus;
use App\Services\GoogleCalendarService;

it('redirects to google oauth', function () {
    $user = User::factory()->create();

    $mock = Mockery::mock(GoogleCalendarService::class);
    $mock->shouldReceive('getAuthUrl')->once()->andReturn('https://accounts.google.com/o/oauth2/auth?test=1');
    app()->instance(GoogleCalendarService::class, $mock);

    $this->actingAs($user)
        ->get('/auth/google/redirect')
        ->assertRedirect('https://accounts.google.com/o/oauth2/auth?test=1');
});

it('handles the oauth callback and creates integration', function () {
    $user = User::factory()->create();

    $mock = Mockery::mock(GoogleCalendarService::class);
    $mock->shouldReceive('handleCallback')
        ->with('test-code')
        ->once()
        ->andReturn([
            'access_token' => 'token-123',
            'refresh_token' => 'refresh-456',
            'token_expires_at' => now()->addHour()->toIso8601String(),
        ]);
    app()->instance(GoogleCalendarService::class, $mock);

    $this->actingAs($user)
        ->get('/auth/google/callback?code=test-code')
        ->assertRedirect('/settings');

    $integration = Integration::where('user_id', $user->id)
        ->where('type', IntegrationType::GoogleCalendar->value)
        ->first();

    expect($integration)->not->toBeNull()
        ->and($integration->status)->toBe(IntegrationStatus::Connected)
        ->and($integration->configuration['access_token'])->toBe('token-123');
});

it('updates existing integration on reconnect', function () {
    $user = User::factory()->create();
    $existing = Integration::factory()->create([
        'user_id' => $user->id,
        'type' => IntegrationType::GoogleCalendar,
        'status' => IntegrationStatus::Disconnected,
    ]);

    $mock = Mockery::mock(GoogleCalendarService::class);
    $mock->shouldReceive('handleCallback')
        ->with('new-code')
        ->once()
        ->andReturn([
            'access_token' => 'new-token',
            'refresh_token' => 'new-refresh',
            'token_expires_at' => now()->addHour()->toIso8601String(),
        ]);
    app()->instance(GoogleCalendarService::class, $mock);

    $this->actingAs($user)
        ->get('/auth/google/callback?code=new-code')
        ->assertRedirect('/settings');

    expect($existing->fresh()->status)->toBe(IntegrationStatus::Connected)
        ->and($existing->fresh()->configuration['access_token'])->toBe('new-token');
});

it('requires authentication for oauth routes', function () {
    $this->get('/auth/google/redirect')->assertRedirect('/login');
    $this->get('/auth/google/callback')->assertRedirect('/login');
});

it('redirects to settings with error on oauth failure', function () {
    $user = User::factory()->create();

    $mock = Mockery::mock(GoogleCalendarService::class);
    $mock->shouldReceive('handleCallback')
        ->andThrow(new \Exception('OAuth failed'));
    app()->instance(GoogleCalendarService::class, $mock);

    $this->actingAs($user)
        ->get('/auth/google/callback?code=bad-code')
        ->assertRedirect('/settings')
        ->assertSessionHas('error');
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact --filter=GoogleCalendarControllerTest
```

Expected: FAIL.

- [ ] **Step 3: Create the controller**

Create `app/Http/Controllers/GoogleCalendarController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use App\Services\GoogleCalendarService;
use Illuminate\Http\RedirectResponse;

class GoogleCalendarController extends Controller
{
    public function __construct(
        private GoogleCalendarService $googleCalendarService,
    ) {}

    public function redirect(): RedirectResponse
    {
        return redirect()->away($this->googleCalendarService->getAuthUrl());
    }

    public function callback(): RedirectResponse
    {
        $code = request()->query('code');

        if (! $code) {
            return redirect()->route('settings')->with('error', 'Google Calendar authorization was cancelled.');
        }

        try {
            $tokens = $this->googleCalendarService->handleCallback($code);
        } catch (\Exception $e) {
            return redirect()->route('settings')->with('error', 'Failed to connect Google Calendar. Please try again.');
        }

        $user = auth()->user();

        $integration = $user->integrations()
            ->where('type', IntegrationType::GoogleCalendar)
            ->first();

        if ($integration) {
            $integration->update([
                'status' => IntegrationStatus::Connected,
                'configuration' => array_merge($integration->configuration ?? [], $tokens),
                'connected_at' => now(),
            ]);
        } else {
            $user->integrations()->create([
                'type' => IntegrationType::GoogleCalendar,
                'status' => IntegrationStatus::Connected,
                'configuration' => array_merge($tokens, ['push_target' => 'aura_calendar']),
                'connected_at' => now(),
            ]);
        }

        return redirect()->route('settings')->with('message', 'Google Calendar connected successfully.');
    }
}
```

- [ ] **Step 4: Add routes**

In `routes/web.php`, add inside the `auth` middleware group, before the logout route:

```php
Route::get('/auth/google/redirect', [\App\Http\Controllers\GoogleCalendarController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [\App\Http\Controllers\GoogleCalendarController::class, 'callback'])->name('google.callback');
```

- [ ] **Step 5: Run tests**

```bash
php artisan test --compact --filter=GoogleCalendarControllerTest
```

Expected: All PASS.

- [ ] **Step 6: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/GoogleCalendarController.php routes/web.php tests/Feature/Http/GoogleCalendarControllerTest.php
git commit -m "feat: add Google Calendar OAuth controller with redirect and callback routes"
```

---

## Phase 4: Sync & Push Jobs

### Task 5: Create SyncGoogleCalendarJob

**Files:**
- Create: `app/Jobs/SyncGoogleCalendarJob.php`
- Create: `tests/Feature/Jobs/SyncGoogleCalendarJobTest.php`

- [ ] **Step 1: Write sync job tests**

Create `tests/Feature/Jobs/SyncGoogleCalendarJobTest.php`:

```php
<?php

use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use App\Jobs\SyncGoogleCalendarJob;
use App\Models\CalendarEvent;
use App\Models\Integration;
use App\Models\User;
use App\Services\GoogleCalendarService;

it('syncs events from google into calendar_events table', function () {
    $user = User::factory()->create();
    $integration = Integration::factory()->create([
        'user_id' => $user->id,
        'type' => IntegrationType::GoogleCalendar,
        'status' => IntegrationStatus::Connected,
        'configuration' => [
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addHour()->toIso8601String(),
        ],
    ]);

    $mock = Mockery::mock(GoogleCalendarService::class);
    $mock->shouldReceive('fetchEvents')
        ->once()
        ->andReturn([
            [
                'id' => 'google-event-1',
                'summary' => 'Team Standup',
                'start' => now()->setTime(9, 30)->toRfc3339String(),
                'end' => now()->setTime(9, 45)->toRfc3339String(),
                'description' => null,
                'is_all_day' => false,
                'status' => 'confirmed',
            ],
            [
                'id' => 'google-event-2',
                'summary' => 'Lunch',
                'start' => now()->setTime(12, 0)->toRfc3339String(),
                'end' => now()->setTime(13, 0)->toRfc3339String(),
                'description' => 'Team lunch',
                'is_all_day' => false,
                'status' => 'confirmed',
            ],
        ]);
    app()->instance(GoogleCalendarService::class, $mock);

    (new SyncGoogleCalendarJob($user))->handle(app(GoogleCalendarService::class));

    expect(CalendarEvent::where('user_id', $user->id)->count())->toBe(2)
        ->and(CalendarEvent::where('external_id', 'google-event-1')->first()->title)->toBe('Team Standup');
});

it('updates existing events on re-sync', function () {
    $user = User::factory()->create();
    $integration = Integration::factory()->create([
        'user_id' => $user->id,
        'type' => IntegrationType::GoogleCalendar,
        'status' => IntegrationStatus::Connected,
        'configuration' => [
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addHour()->toIso8601String(),
        ],
    ]);

    CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'integration_id' => $integration->id,
        'external_id' => 'google-event-1',
        'title' => 'Old Title',
        'starts_at' => now()->setTime(9, 0),
        'ends_at' => now()->setTime(10, 0),
    ]);

    $mock = Mockery::mock(GoogleCalendarService::class);
    $mock->shouldReceive('fetchEvents')
        ->once()
        ->andReturn([
            [
                'id' => 'google-event-1',
                'summary' => 'Updated Title',
                'start' => now()->setTime(9, 30)->toRfc3339String(),
                'end' => now()->setTime(10, 30)->toRfc3339String(),
                'description' => null,
                'is_all_day' => false,
                'status' => 'confirmed',
            ],
        ]);
    app()->instance(GoogleCalendarService::class, $mock);

    (new SyncGoogleCalendarJob($user))->handle(app(GoogleCalendarService::class));

    $event = CalendarEvent::where('external_id', 'google-event-1')->first();
    expect($event->title)->toBe('Updated Title')
        ->and(CalendarEvent::where('user_id', $user->id)->count())->toBe(1);
});

it('deletes cancelled events', function () {
    $user = User::factory()->create();
    $integration = Integration::factory()->create([
        'user_id' => $user->id,
        'type' => IntegrationType::GoogleCalendar,
        'status' => IntegrationStatus::Connected,
        'configuration' => [
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addHour()->toIso8601String(),
        ],
    ]);

    CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'integration_id' => $integration->id,
        'external_id' => 'google-event-cancelled',
        'title' => 'Will be cancelled',
        'starts_at' => now()->setTime(14, 0),
        'ends_at' => now()->setTime(15, 0),
    ]);

    $mock = Mockery::mock(GoogleCalendarService::class);
    $mock->shouldReceive('fetchEvents')
        ->once()
        ->andReturn([
            [
                'id' => 'google-event-cancelled',
                'summary' => '',
                'start' => '',
                'end' => '',
                'description' => null,
                'is_all_day' => false,
                'status' => 'cancelled',
            ],
        ]);
    app()->instance(GoogleCalendarService::class, $mock);

    (new SyncGoogleCalendarJob($user))->handle(app(GoogleCalendarService::class));

    expect(CalendarEvent::where('external_id', 'google-event-cancelled')->exists())->toBeFalse();
});

it('skips sync if no google calendar integration exists', function () {
    $user = User::factory()->create();

    $mock = Mockery::mock(GoogleCalendarService::class);
    $mock->shouldNotReceive('fetchEvents');
    app()->instance(GoogleCalendarService::class, $mock);

    (new SyncGoogleCalendarJob($user))->handle(app(GoogleCalendarService::class));

    expect(CalendarEvent::where('user_id', $user->id)->count())->toBe(0);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact --filter=SyncGoogleCalendarJobTest
```

Expected: FAIL.

- [ ] **Step 3: Create SyncGoogleCalendarJob**

Create `app/Jobs/SyncGoogleCalendarJob.php`:

```php
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
        $syncedExternalIds = [];

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

            $syncedExternalIds[] = $gEvent['id'];
        }

        $config = $integration->configuration;
        $config['last_synced_at'] = now()->toIso8601String();
        $integration->update(['configuration' => $config]);
    }
}
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --compact --filter=SyncGoogleCalendarJobTest
```

Expected: All PASS.

- [ ] **Step 5: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 6: Commit**

```bash
git add app/Jobs/SyncGoogleCalendarJob.php tests/Feature/Jobs/SyncGoogleCalendarJobTest.php
git commit -m "feat: add SyncGoogleCalendarJob for importing Google events"
```

---

### Task 6: Create PushTaskToGoogleJob

**Files:**
- Create: `app/Jobs/PushTaskToGoogleJob.php`
- Create: `tests/Feature/Jobs/PushTaskToGoogleJobTest.php`

- [ ] **Step 1: Write push job tests**

Create `tests/Feature/Jobs/PushTaskToGoogleJobTest.php`:

```php
<?php

use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use App\Jobs\PushTaskToGoogleJob;
use App\Models\Integration;
use App\Models\Task;
use App\Models\User;
use App\Services\GoogleCalendarService;

it('creates a google event for a scheduled task', function () {
    $user = User::factory()->create();
    $integration = Integration::factory()->create([
        'user_id' => $user->id,
        'type' => IntegrationType::GoogleCalendar,
        'status' => IntegrationStatus::Connected,
        'configuration' => [
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addHour()->toIso8601String(),
            'push_target' => 'aura_calendar',
        ],
    ]);
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'scheduled_start' => now()->addHour(),
        'scheduled_end' => now()->addHours(2),
        'status' => 'scheduled',
    ]);

    $mock = Mockery::mock(GoogleCalendarService::class);
    $mock->shouldReceive('createEvent')
        ->once()
        ->andReturn('google-created-id');
    app()->instance(GoogleCalendarService::class, $mock);

    (new PushTaskToGoogleJob($task, 'create'))->handle(app(GoogleCalendarService::class));

    expect($task->fresh()->google_event_id)->toBe('google-created-id');
});

it('updates a google event when task is rescheduled', function () {
    $user = User::factory()->create();
    $integration = Integration::factory()->create([
        'user_id' => $user->id,
        'type' => IntegrationType::GoogleCalendar,
        'status' => IntegrationStatus::Connected,
        'configuration' => [
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addHour()->toIso8601String(),
        ],
    ]);
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'google_event_id' => 'existing-google-id',
        'scheduled_start' => now()->addHour(),
        'scheduled_end' => now()->addHours(2),
        'status' => 'scheduled',
    ]);

    $mock = Mockery::mock(GoogleCalendarService::class);
    $mock->shouldReceive('updateEvent')->once();
    app()->instance(GoogleCalendarService::class, $mock);

    (new PushTaskToGoogleJob($task, 'update'))->handle(app(GoogleCalendarService::class));
});

it('deletes a google event when task is removed', function () {
    $user = User::factory()->create();
    $integration = Integration::factory()->create([
        'user_id' => $user->id,
        'type' => IntegrationType::GoogleCalendar,
        'status' => IntegrationStatus::Connected,
        'configuration' => [
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addHour()->toIso8601String(),
        ],
    ]);
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'google_event_id' => 'to-delete-id',
        'status' => 'scheduled',
    ]);

    $mock = Mockery::mock(GoogleCalendarService::class);
    $mock->shouldReceive('deleteEvent')
        ->with(Mockery::type(Integration::class), 'to-delete-id')
        ->once();
    app()->instance(GoogleCalendarService::class, $mock);

    (new PushTaskToGoogleJob($task, 'delete'))->handle(app(GoogleCalendarService::class));

    expect($task->fresh()->google_event_id)->toBeNull();
});

it('skips push if no google integration exists', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id]);

    $mock = Mockery::mock(GoogleCalendarService::class);
    $mock->shouldNotReceive('createEvent');
    $mock->shouldNotReceive('updateEvent');
    $mock->shouldNotReceive('deleteEvent');
    app()->instance(GoogleCalendarService::class, $mock);

    (new PushTaskToGoogleJob($task, 'create'))->handle(app(GoogleCalendarService::class));
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact --filter=PushTaskToGoogleJobTest
```

Expected: FAIL.

- [ ] **Step 3: Create PushTaskToGoogleJob**

Create `app/Jobs/PushTaskToGoogleJob.php`:

```php
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
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --compact --filter=PushTaskToGoogleJobTest
```

Expected: All PASS.

- [ ] **Step 5: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 6: Commit**

```bash
git add app/Jobs/PushTaskToGoogleJob.php tests/Feature/Jobs/PushTaskToGoogleJobTest.php
git commit -m "feat: add PushTaskToGoogleJob for syncing scheduled tasks to Google Calendar"
```

---

## Phase 5: Scheduler & Calendar Trigger

### Task 7: Add scheduler and calendar mount sync trigger

**Files:**
- Modify: `routes/console.php`
- Modify: `app/Livewire/Calendar.php`

- [ ] **Step 1: Add scheduler entry**

In `routes/console.php`, add after the existing `inspire` command:

```php
use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use App\Jobs\SyncGoogleCalendarJob;
use App\Models\User;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    User::whereHas('integrations', function ($query) {
        $query->where('type', IntegrationType::GoogleCalendar)
            ->where('status', IntegrationStatus::Connected);
    })->each(function (User $user) {
        SyncGoogleCalendarJob::dispatch($user);
    });
})->everyFiveMinutes()->name('sync-google-calendars');
```

- [ ] **Step 2: Add sync trigger to Calendar component mount**

In `app/Livewire/Calendar.php`, add a `triggerSyncIfNeeded` method and call it from `mount()`:

```php
public function mount(): void
{
    $this->weekStart = now()->startOfWeek();
    $this->triggerSyncIfNeeded();
}

private function triggerSyncIfNeeded(): void
{
    $user = auth()->user();
    $integration = $user->integrations()
        ->where('type', \App\Enums\IntegrationType::GoogleCalendar)
        ->where('status', \App\Enums\IntegrationStatus::Connected)
        ->first();

    if (! $integration) {
        return;
    }

    $config = $integration->configuration;
    $lastSynced = isset($config['last_synced_at'])
        ? \Illuminate\Support\Carbon::parse($config['last_synced_at'])
        : null;

    if (! $lastSynced || $lastSynced->diffInMinutes(now()) >= 1) {
        \App\Jobs\SyncGoogleCalendarJob::dispatch($user);
    }
}
```

- [ ] **Step 3: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 4: Commit**

```bash
git add routes/console.php app/Livewire/Calendar.php
git commit -m "feat: add 5-minute scheduler for Google Calendar sync and on-mount trigger"
```

---

## Phase 6: Settings UI Updates

### Task 8: Update Settings page and integration card for Google Calendar

**Files:**
- Modify: `app/Livewire/Settings.php`
- Modify: `resources/views/livewire/settings.blade.php`
- Modify: `resources/views/components/integration-card.blade.php`

- [ ] **Step 1: Update integration-card to link Google Calendar Connect to OAuth**

In `resources/views/components/integration-card.blade.php`, replace the `@else` block (the "Connect" button for disconnected integrations) with:

```html
@else
    @if ($type === \App\Enums\IntegrationType::GoogleCalendar)
        <a href="{{ route('google.redirect') }}"
           class="rounded-lg border border-accent-300 px-4 py-1.5 text-xs font-medium text-accent-600 hover:bg-accent-50 dark:border-accent-700 dark:text-accent-400 dark:hover:bg-accent-950/30">
            Connect
        </a>
    @else
        <button class="rounded-lg border border-accent-300 px-4 py-1.5 text-xs font-medium text-accent-600 hover:bg-accent-50 dark:border-accent-700 dark:text-accent-400 dark:hover:bg-accent-950/30">
            Connect
        </button>
    @endif
@endif
```

- [ ] **Step 2: Add sync and configure actions to Settings component**

In `app/Livewire/Settings.php`, add these methods:

```php
public function syncGoogleCalendar(): void
{
    $user = auth()->user();
    $integration = $user->integrations()
        ->where('type', IntegrationType::GoogleCalendar)
        ->where('status', \App\Enums\IntegrationStatus::Connected)
        ->first();

    if ($integration) {
        \App\Jobs\SyncGoogleCalendarJob::dispatch($user);
        session()->flash('message', 'Sync started. Events will appear shortly.');
    }
}

public function setGooglePushTarget(string $target): void
{
    $user = auth()->user();
    $integration = $user->integrations()
        ->where('type', IntegrationType::GoogleCalendar)
        ->first();

    if ($integration) {
        $config = $integration->configuration;
        $config['push_target'] = $target;
        $integration->update(['configuration' => $config]);
    }
}

public function disconnectGoogleCalendar(): void
{
    $user = auth()->user();
    $integration = $user->integrations()
        ->where('type', IntegrationType::GoogleCalendar)
        ->first();

    if ($integration) {
        $integration->update([
            'status' => \App\Enums\IntegrationStatus::Disconnected,
            'configuration' => null,
        ]);
        session()->flash('message', 'Google Calendar disconnected.');
    }
}
```

- [ ] **Step 3: Add Google Calendar config section to settings view**

In `resources/views/livewire/settings.blade.php`, add after the integration grid (after the closing `</div>` of the grid, still inside the `@if ($activeTab === 'integrations')` block):

```html
@php $googleIntegration = $connectedIntegrations->get('google_calendar'); @endphp
@if ($googleIntegration && $googleIntegration->status === \App\Enums\IntegrationStatus::Connected)
    <div class="mt-6 rounded-xl bg-white p-6 ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800">
        <div class="flex items-center gap-3 mb-4">
            <x-icons.google-calendar class="size-6" />
            <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">Google Calendar Settings</h3>
        </div>

        <div class="space-y-4">
            <div>
                <label class="text-xs text-neutral-500 dark:text-neutral-400">Push scheduled tasks to</label>
                <div class="mt-2 flex gap-2">
                    @php $pushTarget = $googleIntegration->configuration['push_target'] ?? 'aura_calendar'; @endphp
                    <button wire:click="setGooglePushTarget('aura_calendar')"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium transition-colors {{ $pushTarget === 'aura_calendar' ? 'bg-accent-100 text-accent-700 dark:bg-accent-900 dark:text-accent-300' : 'bg-neutral-100 text-neutral-500 hover:text-neutral-700 dark:bg-neutral-800 dark:text-neutral-400' }}">
                        Aura Tasks calendar
                    </button>
                    <button wire:click="setGooglePushTarget('primary')"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium transition-colors {{ $pushTarget === 'primary' ? 'bg-accent-100 text-accent-700 dark:bg-accent-900 dark:text-accent-300' : 'bg-neutral-100 text-neutral-500 hover:text-neutral-700 dark:bg-neutral-800 dark:text-neutral-400' }}">
                        Default calendar
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div>
                    @php $lastSynced = isset($googleIntegration->configuration['last_synced_at']) ? \Illuminate\Support\Carbon::parse($googleIntegration->configuration['last_synced_at'])->diffForHumans() : 'Never'; @endphp
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">Last synced: {{ $lastSynced }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <button wire:click="syncGoogleCalendar" class="text-xs font-medium text-accent-600 hover:text-accent-700 dark:text-accent-400">Sync now</button>
                    <button wire:click="disconnectGoogleCalendar" wire:confirm="Are you sure you want to disconnect Google Calendar?" class="text-xs font-medium text-red-500 hover:text-red-600 dark:text-red-400">Disconnect</button>
                </div>
            </div>
        </div>
    </div>
@endif
```

- [ ] **Step 4: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/Settings.php resources/views/livewire/settings.blade.php resources/views/components/integration-card.blade.php
git commit -m "feat: wire Google Calendar OAuth to Settings UI with sync, push target, and disconnect"
```

---

## Phase 7: Final Integration

### Task 9: Run full test suite, Pint, and build

**Files:**
- All modified files

- [ ] **Step 1: Run full test suite**

```bash
php artisan test --compact
```

Expected: All tests PASS. Fix any failures.

- [ ] **Step 2: Run Pint on everything**

```bash
vendor/bin/pint --format agent
```

- [ ] **Step 3: Build frontend**

```bash
npm run build
```

- [ ] **Step 4: Commit if any fixes needed**

```bash
git add -A
git commit -m "chore: run full test suite, Pint, and build"
```
