# AI Task Scheduler Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build an AI-powered task scheduler that uses Claude to optimally place pending tasks into available calendar slots.

**Architecture:** A Laravel AI SDK agent (`TaskScheduler`) with structured output returns task placements as JSON. A queued job collects user context, prompts the agent, and persists results. The existing `PlannerPage` button dispatches the job.

**Tech Stack:** Laravel AI SDK (`laravel/ai`), Anthropic Claude Sonnet, Laravel queues, Pest tests

---

### File Structure

| File | Action | Responsibility |
|------|--------|---------------|
| `app/Ai/Agents/TaskScheduler.php` | Create | Structured output agent with scheduling instructions |
| `app/Jobs/ScheduleTasksJob.php` | Create | Collects context, prompts agent, persists results |
| `app/Livewire/Pages/PlannerPage.php` | Modify | Add `autoSchedule()` method |
| `tests/Feature/Ai/TaskSchedulerTest.php` | Create | Tests for the full scheduling flow |

The existing `app/Ai/Agents/Planner.php` is an unused stub and will not be modified.

---

### Task 1: Create the TaskScheduler agent

**Files:**
- Create: `app/Ai/Agents/TaskScheduler.php`

- [ ] **Step 1: Generate the agent scaffold**

Run: `php artisan make:agent TaskScheduler --structured --no-interaction`

- [ ] **Step 2: Replace the generated file with the full implementation**

Replace the entire content of `app/Ai/Agents/TaskScheduler.php` with:

```php
<?php

namespace App\Ai\Agents;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::Anthropic)]
#[MaxTokens(4096)]
#[Temperature(0.3)]
class TaskScheduler implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        private User $user,
        private string $context,
    ) {}

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You are an AI task scheduler for a personal planner app. Your job is to schedule pending tasks into available time slots over the next 7 days.

Rules:
- Only schedule within the user's working hours and working days.
- Never overlap with existing calendar events or already scheduled tasks.
- Add the specified buffer time between consecutive items.
- Prioritize urgent and high-priority tasks earlier in the day and sooner in the week.
- Respect deadlines — schedule tasks before their deadline date.
- If focus time is enabled, prefer placing longer tasks (60+ minutes) during focus hours.
- Don't split tasks across multiple blocks — each task is one contiguous block.
- If a task has no estimated duration, assume 60 minutes.
- If a task cannot fit in any available slot within 7 days, skip it entirely — do not include it in the output.
- Return ONLY tasks you are scheduling. Do not return tasks that are already scheduled.

Respond with the scheduled tasks as a JSON array. Each entry must have:
- task_id: the task's ID (integer)
- date: the date to schedule on (string, "YYYY-MM-DD")
- start_time: when the task starts (string, "HH:MM", 24-hour format)
- reasoning: a brief explanation of why this slot was chosen (string)
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'scheduled_tasks' => $schema->array()->items([
                'task_id' => $schema->integer()->required(),
                'date' => $schema->string()->required(),
                'start_time' => $schema->string()->required(),
                'reasoning' => $schema->string()->required(),
            ])->required(),
        ];
    }

    public function buildPrompt(): string
    {
        return $this->context;
    }

    public static function buildContext(User $user): string
    {
        $tz = $user->timezone ?? 'UTC';
        $now = Carbon::now($tz);
        $rangeEnd = $now->copy()->addDays(7)->endOfDay();

        $pendingTasks = $user->tasks()
            ->where('status', 'pending')
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderBy('deadline')
            ->get();

        $calendarEvents = $user->calendarEvents()
            ->where('starts_at', '>=', $now->copy()->startOfDay())
            ->where('starts_at', '<=', $rangeEnd)
            ->orderBy('starts_at')
            ->get();

        $scheduledTasks = $user->tasks()
            ->where('status', 'scheduled')
            ->where('scheduled_start', '>=', $now->copy()->startOfDay())
            ->where('scheduled_start', '<=', $rangeEnd)
            ->orderBy('scheduled_start')
            ->get();

        $workingDays = $user->working_days
            ? implode(', ', $user->working_days)
            : 'Monday, Tuesday, Wednesday, Thursday, Friday';

        $context = "Current date and time: {$now->format('Y-m-d H:i')} ({$tz})\n\n";

        $context .= "## User Preferences\n";
        $context .= "- Working hours: {$user->working_hours_start} - {$user->working_hours_end}\n";
        $context .= "- Working days: {$workingDays}\n";
        if ($user->focus_time_enabled) {
            $context .= "- Focus time: enabled, {$user->focus_time_start} - {$user->focus_time_end} (minimum {$user->focus_time_min_duration} min blocks)\n";
        }
        $context .= "- Buffer between tasks: {$user->buffer_time} minutes\n";
        $context .= "- Maximum task duration: {$user->max_task_duration} minutes\n\n";

        $context .= "## Pending Tasks to Schedule\n";
        if ($pendingTasks->isEmpty()) {
            $context .= "No pending tasks.\n\n";
        } else {
            foreach ($pendingTasks as $task) {
                $duration = $task->estimated_duration ?? 60;
                $deadline = $task->deadline ? $task->deadline->format('Y-m-d') : 'No deadline';
                $context .= "- [ID: {$task->id}] \"{$task->title}\" - Priority: {$task->priority->value} - Duration: {$duration}min - Deadline: {$deadline}\n";
            }
            $context .= "\n";
        }

        $context .= "## Existing Calendar Events (next 7 days)\n";
        if ($calendarEvents->isEmpty()) {
            $context .= "No events.\n\n";
        } else {
            foreach ($calendarEvents as $event) {
                $context .= "- {$event->starts_at->format('Y-m-d H:i')}-{$event->ends_at->format('H:i')}: {$event->title}\n";
            }
            $context .= "\n";
        }

        $context .= "## Already Scheduled Tasks (next 7 days)\n";
        if ($scheduledTasks->isEmpty()) {
            $context .= "No scheduled tasks.\n\n";
        } else {
            foreach ($scheduledTasks as $task) {
                $context .= "- {$task->scheduled_start->format('Y-m-d H:i')}-{$task->scheduled_end->format('H:i')}: {$task->title}\n";
            }
            $context .= "\n";
        }

        $context .= "Schedule the pending tasks into available time slots over the next 7 days. Return only the tasks you can schedule.";

        return $context;
    }
}
```

- [ ] **Step 3: Run Pint**

Run: `vendor/bin/pint app/Ai/Agents/TaskScheduler.php --format agent`

- [ ] **Step 4: Commit**

```bash
git add app/Ai/Agents/TaskScheduler.php
git commit -m "feat: add TaskScheduler AI agent with structured output"
```

---

### Task 2: Create the ScheduleTasksJob

**Files:**
- Create: `app/Jobs/ScheduleTasksJob.php`

- [ ] **Step 1: Create the job file**

Create `app/Jobs/ScheduleTasksJob.php`:

```php
<?php

namespace App\Jobs;

use App\Ai\Agents\TaskScheduler;
use App\Enums\TaskStatus;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ScheduleTasksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
    ) {}

    public function handle(): void
    {
        $context = TaskScheduler::buildContext($this->user);
        $agent = new TaskScheduler($this->user, $context);
        $response = $agent->prompt($context);

        $scheduledTasks = $response['scheduled_tasks'] ?? [];

        foreach ($scheduledTasks as $placement) {
            $task = $this->user->tasks()
                ->where('status', TaskStatus::Pending)
                ->find($placement['task_id']);

            if (! $task) {
                continue;
            }

            $duration = $task->estimated_duration ?? 60;
            $tz = $this->user->timezone ?? 'UTC';

            $scheduledStart = Carbon::parse(
                $placement['date'].' '.$placement['start_time'],
                $tz,
            );
            $scheduledEnd = $scheduledStart->copy()->addMinutes($duration);

            $task->update([
                'scheduled_start' => $scheduledStart,
                'scheduled_end' => $scheduledEnd,
                'status' => TaskStatus::Scheduled,
                'is_ai_scheduled' => true,
                'ai_reasoning' => $placement['reasoning'] ?? null,
            ]);
        }
    }
}
```

- [ ] **Step 2: Run Pint**

Run: `vendor/bin/pint app/Jobs/ScheduleTasksJob.php --format agent`

- [ ] **Step 3: Commit**

```bash
git add app/Jobs/ScheduleTasksJob.php
git commit -m "feat: add ScheduleTasksJob to orchestrate AI scheduling"
```

---

### Task 3: Add autoSchedule method to PlannerPage

**Files:**
- Modify: `app/Livewire/Pages/PlannerPage.php`

- [ ] **Step 1: Add the autoSchedule method**

Add this method to `app/Livewire/Pages/PlannerPage.php` after the `onTaskScheduled` method (around line 63). Also add the import for `ScheduleTasksJob`.

Add to imports:
```php
use App\Jobs\ScheduleTasksJob;
```

Add method:
```php
public function autoSchedule(): void
{
    ScheduleTasksJob::dispatch(auth()->user());
}
```

- [ ] **Step 2: Run Pint**

Run: `vendor/bin/pint app/Livewire/Pages/PlannerPage.php --format agent`

- [ ] **Step 3: Commit**

```bash
git add app/Livewire/Pages/PlannerPage.php
git commit -m "feat: add autoSchedule method to PlannerPage"
```

---

### Task 4: Wire up the Auto-schedule button

**Files:**
- Modify: `resources/views/livewire/top-bar.blade.php`

- [ ] **Step 1: Update the Auto-schedule button**

In `resources/views/livewire/top-bar.blade.php`, find the Auto-schedule link (around line 37):

```blade
<a href="/plan-summary" wire:navigate
   class="inline-flex items-center gap-1.5 rounded-lg bg-accent-600 px-4 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-accent-700 transition-colors">
    <x-icons.sparkle class="size-3.5" />
    Auto-schedule
</a>
```

Replace with a button that calls the Livewire method on PlannerPage via `$dispatchTo`:

```blade
<button x-data @click="Livewire.dispatch('auto-schedule')"
        class="inline-flex items-center gap-1.5 rounded-lg bg-accent-600 px-4 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-accent-700 transition-colors">
    <x-icons.sparkle class="size-3.5" />
    Auto-schedule
</button>
```

- [ ] **Step 2: Add the event listener on PlannerPage**

In `app/Livewire/Pages/PlannerPage.php`, add the `#[On]` attribute to `autoSchedule`:

```php
#[On('auto-schedule')]
public function autoSchedule(): void
{
    ScheduleTasksJob::dispatch(auth()->user());
}
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/livewire/top-bar.blade.php app/Livewire/Pages/PlannerPage.php
git commit -m "feat: wire Auto-schedule button to dispatch ScheduleTasksJob"
```

---

### Task 5: Write tests

**Files:**
- Create: `tests/Feature/Ai/TaskSchedulerTest.php`

- [ ] **Step 1: Create the test file**

Run: `php artisan make:test --pest Ai/TaskSchedulerTest --no-interaction`

- [ ] **Step 2: Write the tests**

Replace `tests/Feature/Ai/TaskSchedulerTest.php` with:

```php
<?php

use App\Ai\Agents\TaskScheduler;
use App\Enums\TaskStatus;
use App\Jobs\ScheduleTasksJob;
use App\Models\CalendarEvent;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;

it('builds context with pending tasks and calendar events', function () {
    $user = User::factory()->create([
        'onboarded_at' => now(),
        'working_hours_start' => '09:00',
        'working_hours_end' => '17:00',
        'buffer_time' => 15,
        'timezone' => 'UTC',
    ]);

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Fix login bug',
        'priority' => 'urgent',
        'estimated_duration' => 60,
        'status' => 'pending',
    ]);

    CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'title' => 'Team Standup',
        'starts_at' => now()->addDay()->setTime(10, 0),
        'ends_at' => now()->addDay()->setTime(10, 30),
    ]);

    $context = TaskScheduler::buildContext($user);

    expect($context)
        ->toContain('Fix login bug')
        ->toContain('Priority: urgent')
        ->toContain('Duration: 60min')
        ->toContain('Team Standup')
        ->toContain('Working hours: 09:00 - 17:00')
        ->toContain('Buffer between tasks: 15 minutes');
});

it('schedules tasks from agent response', function () {
    Carbon::setTestNow('2026-04-02 09:00:00');

    $user = User::factory()->create([
        'onboarded_at' => now(),
        'timezone' => 'UTC',
    ]);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Fix login bug',
        'priority' => 'urgent',
        'estimated_duration' => 60,
        'status' => 'pending',
    ]);

    TaskScheduler::fake(function () use ($task) {
        return [
            'scheduled_tasks' => [
                [
                    'task_id' => $task->id,
                    'date' => '2026-04-02',
                    'start_time' => '09:00',
                    'reasoning' => 'Urgent task scheduled at start of working day.',
                ],
            ],
        ];
    });

    ScheduleTasksJob::dispatchSync($user);

    $task->refresh();
    expect($task->status)->toBe(TaskStatus::Scheduled)
        ->and($task->is_ai_scheduled)->toBeTrue()
        ->and($task->ai_reasoning)->toBe('Urgent task scheduled at start of working day.')
        ->and($task->scheduled_start->format('Y-m-d H:i'))->toBe('2026-04-02 09:00')
        ->and($task->scheduled_end->format('Y-m-d H:i'))->toBe('2026-04-02 10:00');

    Carbon::setTestNow();
});

it('skips tasks that are not pending', function () {
    $user = User::factory()->create([
        'onboarded_at' => now(),
        'timezone' => 'UTC',
    ]);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'completed',
    ]);

    TaskScheduler::fake(function () use ($task) {
        return [
            'scheduled_tasks' => [
                [
                    'task_id' => $task->id,
                    'date' => '2026-04-02',
                    'start_time' => '09:00',
                    'reasoning' => 'Test',
                ],
            ],
        ];
    });

    ScheduleTasksJob::dispatchSync($user);

    $task->refresh();
    expect($task->status->value)->toBe('completed')
        ->and($task->is_ai_scheduled)->toBeFalse();
});

it('skips unknown task ids', function () {
    $user = User::factory()->create([
        'onboarded_at' => now(),
        'timezone' => 'UTC',
    ]);

    TaskScheduler::fake(function () {
        return [
            'scheduled_tasks' => [
                [
                    'task_id' => 99999,
                    'date' => '2026-04-02',
                    'start_time' => '09:00',
                    'reasoning' => 'Test',
                ],
            ],
        ];
    });

    // Should not throw
    ScheduleTasksJob::dispatchSync($user);
});

it('uses default 60 min duration when task has no estimate', function () {
    Carbon::setTestNow('2026-04-02 09:00:00');

    $user = User::factory()->create([
        'onboarded_at' => now(),
        'timezone' => 'UTC',
    ]);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending',
        'estimated_duration' => null,
    ]);

    TaskScheduler::fake(function () use ($task) {
        return [
            'scheduled_tasks' => [
                [
                    'task_id' => $task->id,
                    'date' => '2026-04-02',
                    'start_time' => '14:00',
                    'reasoning' => 'Afternoon slot.',
                ],
            ],
        ];
    });

    ScheduleTasksJob::dispatchSync($user);

    $task->refresh();
    expect($task->scheduled_start->format('H:i'))->toBe('14:00')
        ->and($task->scheduled_end->format('H:i'))->toBe('15:00');

    Carbon::setTestNow();
});

it('dispatches job from autoSchedule on PlannerPage', function () {
    Queue::fake();

    $user = User::factory()->create(['onboarded_at' => now()]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Pages\PlannerPage::class)
        ->call('autoSchedule');

    Queue::assertPushed(ScheduleTasksJob::class, function ($job) use ($user) {
        return $job->user->id === $user->id;
    });
});
```

- [ ] **Step 3: Add missing imports**

Make sure the test file has the required imports at the top. The `Queue` and `Livewire` facades need to be imported:

```php
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
```

- [ ] **Step 4: Run the tests**

Run: `php artisan test --compact --filter=TaskScheduler`
Expected: All 6 tests PASS

- [ ] **Step 5: Run Pint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add tests/Feature/Ai/TaskSchedulerTest.php
git commit -m "test: add tests for AI task scheduler"
```
