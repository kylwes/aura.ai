# Settings Page Redesign Design

## Overview

Redesign the settings page with a left sidebar navigation and a per-day working hours schedule with lunch breaks. Replace the flat `working_hours_start/end` + `working_days` fields with a `work_schedules` database table. Update the AI scheduler to use the new per-day schedule.

## Layout

Replace the current tab-based layout with:
- **Left sidebar** (240px) — navigation links: Integrations, Preferences
- **Content area** — renders the selected section
- Same `layouts.app` as before (no planner chrome)

The `$activeTab` property on the Settings component controls which section is shown.

## Data Model

### New `work_schedules` table

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| user_id | FK → users | cascadeOnDelete |
| day | tinyint | ISO day number: 1=Mon, 7=Sun |
| enabled | boolean | Whether the user works this day |
| start | time, nullable | Work start time (null if disabled) |
| end | time, nullable | Work end time |
| lunch_start | time, nullable | Lunch break start (null = no lunch) |
| lunch_end | time, nullable | Lunch break end |

Unique constraint on `(user_id, day)`.

Default seed for new users: Mon-Fri enabled 09:00-17:30 with lunch 12:00-13:00, Sat-Sun disabled.

### New `WorkSchedule` model

- `belongsTo(User::class)`
- Fillable: `user_id`, `day`, `enabled`, `start`, `end`, `lunch_start`, `lunch_end`

### User model changes

- Add `workSchedules()` HasMany relationship
- Add `ensureWorkSchedule()` method that seeds 7 rows if none exist (called from `EnsureUserSettings` middleware)
- Keep old `working_hours_start/end` and `working_days` columns for now (backward compat), but stop reading them in the scheduler

## Preferences Section — Working Hours UI

A table/form with one row per day:

```
Day        | Enabled | Hours         | Lunch
───────────┼─────────┼───────────────┼──────────────
Monday     | [x]     | 09:00 - 17:30 | 12:00 - 13:00
Tuesday    | [x]     | 09:00 - 17:30 | 12:00 - 13:00
Wednesday  | [x]     | 09:00 - 17:30 | 12:00 - 13:00
Thursday   | [x]     | 09:00 - 17:30 | 12:00 - 13:00
Friday     | [x]     | 09:00 - 17:30 | 12:00 - 13:00
Saturday   | [ ]     | —             | —
Sunday     | [ ]     | —             | —
```

- Toggle enables/disables a day (grays out the row when off)
- Time inputs for start/end and lunch start/end
- Lunch is optional — can be left empty
- Auto-saves on change (`wire:model.live`)

Below the working hours: Focus time, buffer time, max task duration settings (existing, unchanged).

## Settings Page Layout (Blade)

```
┌──────────────────────────────────────────────┐
│ Header (Aura logo, nav, user avatar)         │
├────────────┬─────────────────────────────────┤
│ Sidebar    │ Content                         │
│            │                                 │
│ • Integ.   │ [Selected section renders here] │
│ • Prefs    │                                 │
│            │                                 │
└────────────┴─────────────────────────────────┘
```

## AI Scheduler Integration

### `TaskScheduler::computeAvailableSlots()`

Currently reads `$user->working_hours_start/end` and `$user->working_days`. Change to:

1. Load all `WorkSchedule` rows for the user
2. For each day in the 14-day scan:
   - Check if the day is enabled in `work_schedules`
   - Use that day's specific `start`/`end` times
   - Treat the lunch block (`lunch_start` → `lunch_end`) as an occupied block (same as calendar events)

### `TaskScheduler::buildContext()`

Update the "User Preferences" section to show per-day schedule:

```
## Working Schedule
- Monday: 09:00-17:30 (lunch 12:00-13:00)
- Tuesday: 09:00-17:30 (lunch 12:00-13:00)
- Saturday: OFF
- Sunday: OFF
```

### `ResolveOverlapsJob`

Same changes — read per-day hours from `work_schedules` and treat lunch as occupied.

## Files

| File | Action |
|------|--------|
| `database/migrations/xxxx_create_work_schedules_table.php` | Create |
| `app/Models/WorkSchedule.php` | Create |
| `app/Models/User.php` | Add relationship + `ensureWorkSchedule()` |
| `app/Http/Middleware/EnsureUserSettings.php` | Call `ensureWorkSchedule()` |
| `app/Livewire/Settings.php` | Redesign: sidebar, per-day schedule, auto-save |
| `resources/views/livewire/settings.blade.php` | New layout with sidebar + schedule table |
| `app/Ai/Agents/TaskScheduler.php` | Read `work_schedules` instead of flat fields |
| `app/Jobs/ResolveOverlapsJob.php` | Read `work_schedules` instead of flat fields |

## Out of scope

- Removing old `working_hours_*` and `working_days` columns (keep for backward compat)
- Timezone picker in settings (already on User model)
- Profile/account settings section
