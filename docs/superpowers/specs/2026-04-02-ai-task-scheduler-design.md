# AI Task Scheduler Design

## Overview

An AI-powered task scheduling agent that analyzes the user's pending tasks, existing calendar events, and preferences to automatically place tasks into optimal time slots. Uses the Laravel AI SDK with Anthropic's Claude to generate intelligent scheduling with per-task reasoning.

## Flow

1. User clicks "Auto-schedule" button in the top-bar
2. `PlannerPage::autoSchedule()` dispatches `ScheduleTasksJob` to the queue
3. The job collects all context for the authenticated user:
   - Pending tasks (id, title, description, priority, estimated_duration, deadline)
   - Calendar events for the next 7 days (title, starts_at, ends_at, is_all_day)
   - Already scheduled tasks for the next 7 days (title, scheduled_start, scheduled_end)
   - User preferences (working_hours_start/end, working_days, focus_time_enabled/start/end/min_duration, buffer_time, max_task_duration, timezone)
   - Current date/time in user's timezone
4. Job creates a `TaskScheduler` agent and prompts it with the context
5. Agent returns structured JSON: array of task placements
6. Job parses response and updates each task:
   - `scheduled_start` and `scheduled_end` computed from the agent's date + start_time + task's estimated_duration
   - `status` set to `TaskStatus::Scheduled`
   - `is_ai_scheduled` set to `true`
   - `ai_reasoning` set to the agent's reasoning string
7. Calendar re-renders with newly scheduled tasks on next page load/navigation

## Components

### `app/Ai/Agents/TaskScheduler.php`

Laravel AI SDK agent with structured output.

**Configuration:**
- Provider: Anthropic
- Model: `claude-sonnet-4-5-20250514`
- MaxTokens: 4096
- Temperature: 0.3 (low for consistent, logical scheduling)

**Constructor receives:** User preferences, pending tasks, calendar events, scheduled tasks, current datetime — all as structured data.

**Instructions:** System prompt explaining the scheduling rules:
- Only schedule within working hours and working days
- Respect existing calendar events (don't double-book)
- Respect existing scheduled tasks
- Add buffer_time between consecutive items
- Place high-priority and urgent tasks earlier in the day
- Respect deadlines — schedule before deadline
- If focus_time is enabled, prefer placing longer/complex tasks during focus hours
- Don't exceed max_task_duration for any single block
- If a task has no estimated_duration, assume 60 minutes
- Don't schedule tasks that can't fit in available slots — skip them

**Structured output schema:**
```
[
    {
        "task_id": integer (required),
        "date": string "YYYY-MM-DD" (required),
        "start_time": string "HH:MM" (required),
        "reasoning": string (required)
    }
]
```

### `app/Jobs/ScheduleTasksJob.php`

Queued job that orchestrates the scheduling.

**Input:** User ID

**Process:**
1. Load user with preferences
2. Query pending tasks (status = pending, ordered by priority then deadline)
3. Query calendar events for next 7 days
4. Query already scheduled tasks for next 7 days
5. Build context message string with all data formatted as readable text
6. Create `TaskScheduler` agent and call `->prompt($contextMessage)`
7. Iterate over response array, for each placement:
   - Find the task by ID (scoped to user)
   - Parse date + start_time into Carbon datetime
   - Calculate end time from start + estimated_duration (default 60 min)
   - Update task with scheduled_start, scheduled_end, status, is_ai_scheduled, ai_reasoning

### `app/Livewire/Pages/PlannerPage.php` (modify)

Add `autoSchedule()` method:
- Dispatches `ScheduleTasksJob` for the authenticated user
- No redirect, no blocking — the job runs in the background

## Prompt structure

The context message sent to the agent is a structured text block:

```
Current date and time: 2026-04-02 09:00 (Europe/Amsterdam)

## User Preferences
- Working hours: 09:00 - 17:00
- Working days: Monday, Tuesday, Wednesday, Thursday, Friday
- Focus time: enabled, 09:00 - 12:00 (minimum 60 min blocks)
- Buffer between tasks: 15 minutes
- Maximum task duration: 120 minutes

## Pending Tasks to Schedule
1. [ID: 5] "Fix login bug" - Priority: Urgent - Duration: 60min - Deadline: 2026-04-03
2. [ID: 8] "Write docs" - Priority: Medium - Duration: 90min - No deadline
...

## Existing Calendar Events (next 7 days)
- 2026-04-02 10:00-11:00: Team Standup
- 2026-04-02 14:00-15:00: Design Review
...

## Already Scheduled Tasks (next 7 days)
- 2026-04-02 11:15-12:15: Deploy hotfix
...

Schedule the pending tasks into available time slots over the next 7 days.
```

## Error handling

- If the Claude API call fails, the job fails silently (Laravel's built-in job failure handling)
- If a task_id in the response doesn't match a pending task, skip it
- If a proposed time slot overlaps with an existing event, skip that placement
- Tasks that couldn't be scheduled remain in pending status

## Testing

- Test the job with faked agent responses using `TaskScheduler::fake()`
- Test that tasks get updated correctly from a known response
- Test that overlapping proposals are skipped
- Test that non-pending tasks are not affected

## Out of scope

- Real-time progress indicator while scheduling
- Streaming the scheduling process
- Multi-week scheduling (limited to 7 days)
- Rescheduling already-scheduled tasks
- Recurring task patterns
