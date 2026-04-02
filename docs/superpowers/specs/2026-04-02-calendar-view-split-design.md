# Calendar View Split — Design Spec

Split the monolithic `Calendar` Livewire component into three child components (day, week, month) and persist the user's selected view in a new `user_preferences` table.

## Persistence

### `user_preferences` table

| Column     | Type                  | Notes                        |
|------------|-----------------------|------------------------------|
| id         | bigint unsigned PK    |                              |
| user_id    | bigint unsigned FK    | cascades on delete           |
| key        | varchar(255)          | from `PreferenceKey` enum    |
| value      | varchar(255)          |                              |
| created_at | timestamp             |                              |
| updated_at | timestamp             |                              |

Unique constraint on `(user_id, key)`.

### Enums

**`App\Enums\CalendarView`** — `Day`, `Week`, `Month` (string-backed, values: `day`, `week`, `month`).

**`App\Enums\PreferenceKey`** — `CalendarView` (string-backed, value: `calendar_view`). Extensible for future preferences.

### User model helpers

```php
// Read a preference with a fallback default
$user->preference(PreferenceKey::CalendarView, CalendarView::Week->value);

// Write a preference (upsert)
$user->setPreference(PreferenceKey::CalendarView, CalendarView::Month->value);
```

The `User` model gets a `preferences()` HasMany relationship to `UserPreference`.

## Component Architecture

### Parent: `Calendar` (full-page, route `/`)

Responsibilities:
- Read the user's `CalendarView` preference on `mount()` (default: `Week`)
- Own `$currentView` (CalendarView enum) and `$currentDate` (Carbon)
- Listen to `calendar-navigate` from TopBar, update state, persist view to DB when it changes
- Render the active child component, passing `$currentDate` as a prop
- Trigger Google Calendar sync (`triggerSyncIfNeeded()`)

Blade becomes:
```blade
<div class="flex-1 overflow-hidden">
    @if ($currentView === \App\Enums\CalendarView::Day)
        <livewire:calendar-day :current-date="$currentDate" :key="'day-'.$currentDate->toDateString()" />
    @elseif ($currentView === \App\Enums\CalendarView::Week)
        <livewire:calendar-week :current-date="$currentDate" :key="'week-'.$navigationVersion" />
    @elseif ($currentView === \App\Enums\CalendarView::Month)
        <livewire:calendar-month :current-date="$currentDate" :key="'month-'.$navigationVersion" />
    @endif
</div>
```

### Children: `CalendarDay`, `CalendarWeek`, `CalendarMonth`

Each child component:
- Receives `$currentDate` as a prop
- Queries its own events and tasks for the appropriate date range
- Owns its own Blade template and `@script` JS block
- `CalendarWeek` and `CalendarMonth` own their infinite scroll logic (`loadMore`, `pastBuffer`, `futureBuffer`)
- `CalendarDay` has no infinite scroll (single day view)

### TopBar — no changes

Still dispatches `calendar-navigate` events. The parent `Calendar` handles them.

### Data flow

```
TopBar --dispatch('calendar-navigate')--> Calendar (parent)
  Calendar updates $currentView, $currentDate
  Calendar persists view to DB (only when view changes)
  Calendar passes $currentDate as prop to active child
  Child queries its own events/tasks and renders
  Child dispatches 'calendar-label-update' to TopBar via JS
```

## File Changes

### New files

- `app/Enums/CalendarView.php`
- `app/Enums/PreferenceKey.php`
- `app/Models/UserPreference.php`
- Migration: `create_user_preferences_table`
- `app/Livewire/CalendarDay.php`
- `app/Livewire/CalendarWeek.php`
- `app/Livewire/CalendarMonth.php`
- `resources/views/livewire/calendar-day.blade.php`
- `resources/views/livewire/calendar-week.blade.php`
- `resources/views/livewire/calendar-month.blade.php`

### Modified files

- `app/Models/User.php` — add `preferences()` relationship, `preference()` and `setPreference()` helpers
- `app/Livewire/Calendar.php` — slim down to coordinator role
- `resources/views/livewire/calendar.blade.php` — replace inline views with child component tags
- `tests/Feature/Livewire/CalendarTest.php` — update tests for new structure, add preference persistence tests

### Unchanged

- `app/Livewire/TopBar.php` — no changes
- `resources/views/livewire/top-bar.blade.php` — no changes
- `routes/web.php` — `/` stays mapped to `Calendar`
- `resources/views/layouts/app.blade.php` — no changes
