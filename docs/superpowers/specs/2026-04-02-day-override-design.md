# Day Override Feature — Design Spec

## Overview

Allow users to override their recurring work schedule for specific dates. For example, coming in at 7:00 and leaving at 15:00 on a particular day, or marking a day off entirely. Overrides take precedence over the recurring `WorkSchedule` configuration.

## Data Model

### New Model: `DayOverride`

**Table:** `day_overrides`

| Column       | Type      | Notes                              |
|--------------|-----------|------------------------------------|
| id           | bigint    | Primary key                        |
| user_id      | FK        | References `users.id`, cascades    |
| date         | date      | The specific date being overridden |
| is_day_off   | boolean   | Default: false                     |
| start        | time      | Nullable (null when day off)       |
| end          | time      | Nullable (null when day off)       |
| lunch_start  | time      | Nullable                           |
| lunch_end    | time      | Nullable                           |
| timestamps   |           |                                    |

**Unique constraint:** `[user_id, date]` — one override per user per date.

### Schedule Resolution Priority

When determining a day's effective schedule:

1. **`DayOverride`** for that specific date (if exists) — use it
2. **`WorkSchedule`** for that day-of-week — fallback

A helper method on the `User` model (e.g., `effectiveScheduleFor(Carbon $date)`) encapsulates this logic, returning a consistent shape regardless of source.

## UI: Entry Points

### 3-Dot Menu on Day Header

- **Trigger:** Hover over a day column header (week view) or the day header (day view) reveals a 3-dot icon.
- **Click:** Opens a dropdown with two options:
  - **"Day settings"** — opens the single-day override modal
  - **"Bulk override"** — opens the bulk override modal

The 3-dot icon should be subtle (muted color, small) and only appear on hover to avoid cluttering the header.

## UI: Single-Day Override Modal

Opens pre-filled with the current effective schedule for that date (existing override if one exists, otherwise the recurring `WorkSchedule` values).

### Fields

- **Date display** — Read-only, shows the day name and full date (e.g., "Thursday, April 5, 2026")
- **Working day / Day off** — Radio toggle. Selecting "Day off" hides the time fields.
- **Start time** — Time input (e.g., 07:00)
- **End time** — Time input (e.g., 15:00)
- **Lunch start** — Time input (e.g., 12:00)
- **Lunch end** — Time input (e.g., 12:30)

### Actions

- **Save** — Creates or updates the `DayOverride` record
- **Cancel** — Closes without saving
- **Reset to default** — Visible only when an override exists. Deletes the override, reverting to the recurring `WorkSchedule`. Requires confirmation.

## UI: Bulk Override Modal

Allows applying the same schedule override to multiple dates at once.

### Date Selection

Toggle between two modes:

1. **Date range** — Start date and end date pickers. Applies override to every date in the range.
2. **Pick dates** — Mini calendar where individual dates can be clicked to select/deselect. Supports non-consecutive selections (e.g., every Tuesday in April).

### Fields

Same as single-day modal:

- Working day / Day off toggle
- Start time, End time
- Lunch start, Lunch end

### Actions

- **Save** — Creates or updates a `DayOverride` record for each selected date
- **Cancel** — Closes without saving

### Behavior

- If any selected date already has an override, it gets replaced with the new values.
- Dates that fall on days disabled in `WorkSchedule` (e.g., weekends) are still valid targets — the override explicitly enables or disables them.

## Visual Indicators

### Day Header Badge

Days with an active `DayOverride` show a small visual indicator on the day header (e.g., a dot or subtle icon) so users can see at a glance which days have custom schedules.

### Dimmed Non-Working Hours

In week and day views, hours outside the effective work window are dimmed/greyed out. This already needs to respect `WorkSchedule`; now it additionally respects `DayOverride` when one exists for that date.

For example, if the override is 7:00–15:00, hours 0–6 and 15–23 appear dimmed on that day's column.

## Scheduling Integration

### Auto-Reschedule on Save

When a day override is saved (single or bulk):

1. Find all AI-scheduled tasks (`is_ai_scheduled = true`, `is_pinned = false`) on the affected date(s) that fall outside the new effective work hours.
2. Dispatch `ScheduleTasksJob` to reschedule them within the new constraints.
3. Pinned tasks are left untouched.

This mirrors the existing behavior in `Settings.php::updatedSchedules()` when recurring work hours change.

### Calendar Rendering

`PlannerPage::render()` is updated to:

1. Query `DayOverride` records for the visible date range.
2. Build an effective schedule per visible day (override if exists, else `WorkSchedule`).
3. Pass the effective schedules to blade views.
4. Week/day views use the effective schedule to:
   - Apply dimmed styling on non-working hour cells
   - Split project blocks around the correct lunch break times

## Components Summary

| Component | Changes |
|-----------|---------|
| **Migration** | New `day_overrides` table |
| **DayOverride model** | New Eloquent model with `user()` relationship |
| **User model** | Add `dayOverrides()` relationship, `effectiveScheduleFor()` method |
| **DaySettingsModal** | New Livewire component for single-day editing |
| **BulkOverrideModal** | New Livewire component for multi-date editing |
| **PlannerPage** | Load overrides, pass effective schedules to views |
| **week-view.blade.php** | Add 3-dot menu on day headers, dimmed hour styling |
| **day-view.blade.php** | Add 3-dot menu on day header, dimmed hour styling |
| **ScheduleTasksJob** | Already handles rescheduling; dispatched after override save |
