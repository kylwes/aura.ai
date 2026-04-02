# Google Calendar Integration — Design Spec

## Overview

Two-way sync between Aura and Google Calendar. Import events from Google into the Aura weekly grid, and push AI-scheduled tasks back to Google Calendar. Sync via polling job (every 5 minutes) + on-demand on page load.

## OAuth Flow

- User clicks "Connect" on the Google Calendar integration card in Settings (`/settings`)
- Route `GET /auth/google/redirect` redirects to Google OAuth consent screen
- Scopes requested: `calendar.readonly`, `calendar.events`
- Callback route `GET /auth/google/callback` handles the response
- On success: store `access_token`, `refresh_token`, `token_expires_at` in the Integration model's `configuration` JSON column. Set `status` to `connected`, `connected_at` to now.
- On failure: redirect back to Settings with error flash message
- Token refresh: before every API call, check if `token_expires_at` is in the past. If so, use `refresh_token` to get a new access token and update the stored tokens.
- Disconnect: clear `configuration`, set `status` to `disconnected`, delete the Aura Tasks calendar reference (but don't delete the calendar from Google)

## Event Sync (Google to Aura)

### Trigger Conditions
- **On page load**: Calendar Livewire component checks if last sync was >1 minute ago. If so, dispatches `SyncGoogleCalendarJob` and updates `configuration.last_synced_at`.
- **Background scheduler**: `SyncGoogleCalendarJob` dispatched every 5 minutes via `routes/console.php` for all users with a connected Google Calendar integration.

### Sync Logic
- Fetch events from Google Calendar API for a date range: today -2 weeks to today +2 weeks
- Fetch from all calendars the user has access to (primary + shared), excluding the Aura Tasks calendar (to avoid importing our own pushed events)
- For each Google event:
  - Match on `external_id` (Google event ID) in `calendar_events` table
  - If exists: update `title`, `description`, `starts_at`, `ends_at`, `is_all_day`
  - If new: create `CalendarEvent` with `user_id`, `integration_id`, `external_id`, and event data
  - If previously synced but no longer in Google response: delete from `calendar_events`
- Store `configuration.last_synced_at` timestamp after successful sync

### Edge Cases
- All-day events: set `is_all_day` to true, `starts_at` to start of day, `ends_at` to end of day
- Multi-day events: store actual start/end timestamps, calendar view handles spanning
- Recurring events: Google API returns expanded instances — treat each instance as a separate event
- Cancelled events: check for `status: cancelled` in Google response, delete local copy

## Task Push (Aura to Google)

### When to Push
- When a task transitions to `scheduled` status with `scheduled_start` and `scheduled_end` set (via AI auto-schedule or manual)
- When a scheduled task is rescheduled (update the Google event)
- When a scheduled task is removed/dismissed (delete the Google event)

### Push Target (user-configurable)
- **Option A (default)**: Dedicated "Aura Tasks" calendar
  - On first push, create a new calendar named "Aura Tasks" in the user's Google account via `calendars.insert`
  - Store the created calendar ID in `configuration.aura_calendar_id`
  - Subsequent pushes use this calendar ID
- **Option B**: User's default (primary) calendar
  - Push events directly to the primary calendar
- User selects preference in Settings page. Stored in `configuration.push_target` as `aura_calendar` (default) or `primary`.

### Event Format
- Title: task title (no prefix needed if using separate calendar; "[Aura] " prefix if pushing to primary calendar)
- Description: task description + "Managed by Aura" footer + link back to Aura app
- Color: use Google Calendar's "Lavender" color ID (1) for the Aura Tasks calendar to match indigo accent

### Tracking
- Store Google event ID in `tasks.google_event_id` column (new migration)
- On reschedule: update event via `events.update` using stored ID
- On remove/dismiss: delete event via `events.delete` using stored ID
- If the Google event was already deleted externally, catch 404 and clear `google_event_id`

## Service Architecture

### `App\Services\GoogleCalendarService`
Single class encapsulating all Google API interactions:
- `getAuthUrl(): string` — Generate OAuth redirect URL
- `handleCallback(string $code): array` — Exchange code for tokens
- `refreshTokenIfNeeded(Integration $integration): void` — Check expiry, refresh if needed
- `fetchEvents(Integration $integration, Carbon $from, Carbon $to): array` — Get events from all calendars
- `createEvent(Integration $integration, Task $task): string` — Create event, return Google event ID
- `updateEvent(Integration $integration, Task $task): void` — Update existing event
- `deleteEvent(Integration $integration, string $googleEventId): void` — Delete event
- `createAuraCalendar(Integration $integration): string` — Create "Aura Tasks" calendar, return ID
- `getClient(Integration $integration): Google\Client` — Build authenticated client

### `App\Jobs\SyncGoogleCalendarJob`
- Accepts `User $user`
- Calls `GoogleCalendarService::fetchEvents()` for the sync window
- Upserts `CalendarEvent` records
- Removes stale events
- Updates `configuration.last_synced_at`
- Implements `ShouldQueue`, uses `database` queue connection

### `App\Jobs\PushTaskToGoogleJob`
- Accepts `Task $task` and `string $action` (create/update/delete)
- Calls appropriate `GoogleCalendarService` method
- Updates `tasks.google_event_id` on create
- Clears `google_event_id` on delete
- Handles 404 gracefully on update/delete (event already deleted externally)

## Routes

```
GET  /auth/google/redirect   — GoogleCalendarController@redirect (auth middleware)
GET  /auth/google/callback    — GoogleCalendarController@callback (auth middleware)
```

## Migration

- Add `google_event_id` nullable string column to `tasks` table

## Settings Page Changes

- Google Calendar integration card "Connect" button links to `/auth/google/redirect`
- Once connected, "Configure" section shows:
  - Push target dropdown: "Aura Tasks calendar" / "Default calendar"
  - Last synced timestamp: "Last synced 2 minutes ago"
  - "Sync now" button — dispatches `SyncGoogleCalendarJob` immediately
  - "Disconnect" danger link

## Package Dependency

- `google/apiclient` — Google APIs Client Library for PHP

## Environment Variables

```
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=${APP_URL}/auth/google/callback
```

## Config File

`config/services.php` — add `google` key:
```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
],
```

## Testing Strategy

- `GoogleCalendarServiceTest` — Unit tests with mocked Google Client. Test token refresh logic, event mapping, error handling.
- `SyncGoogleCalendarJobTest` — Feature test verifying events are upserted/deleted correctly from mock API response.
- `PushTaskToGoogleJobTest` — Feature test verifying create/update/delete calls with correct payloads.
- `GoogleCalendarControllerTest` — Feature tests for OAuth redirect and callback routes.
- Settings page test — Verify "Connect" button, push target selector, sync now action.
