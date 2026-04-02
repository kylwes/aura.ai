<?php

namespace App\Ai\Agents;

use App\Models\Project;
use App\Models\TaskBlock;
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
You are an AI task scheduler for a personal planner app. Your job is to optimally schedule tasks into the AVAILABLE TIME SLOTS listed in the context.

You will receive two types of tasks:
1. PENDING tasks — not yet scheduled, must be placed into slots.
2. AI-SCHEDULED tasks — previously scheduled by AI, can be moved to better slots if needed (e.g., to make room for higher-priority tasks).

CRITICAL RULES:
- Each "Available Time Slot" has a date, start time, and end time. You can pack MULTIPLE tasks into one slot sequentially.
- The FIRST task in a slot starts at the slot's start time. Each subsequent task starts after the previous task's duration + the buffer time.
- Example: Slot "2026-04-03 09:00 - 17:30", buffer=15min. Place 30min task at 09:00, next task at 09:45, next at 10:00+duration, etc.
- A task must FIT within the slot. Do not exceed the slot's end time.
- Schedule urgent and high-priority tasks FIRST and in the EARLIEST available slots.
- An urgent task should bump a lower-priority AI-scheduled task if it would get a better (earlier) slot.
- Within the same priority level, use the task's CONTEXT to determine real-world urgency. For example: a production outage, security vulnerability, or blocker for others should come before routine work like documentation or blog posts. Read the task title and description to judge what matters most.
- Respect deadlines — a task must be scheduled before its deadline date. Tasks with closer deadlines should generally come first.
- Respect deadlines — a task must be scheduled before its deadline date.
- If focus time is enabled, prefer placing longer tasks (60+ minutes) during focus hours.
- If a task cannot fit in any available slot, skip it entirely — do not include it in the output.
- Tasks belonging to a project MUST ONLY be scheduled within that project's constrained time slots (listed under "Project-Constrained Slots").
- Non-project tasks with URGENT priority MAY be scheduled in any available slot, including time that overlaps with project blocks — urgent tasks override project boundaries.
- Non-project tasks that are NOT urgent must be scheduled OUTSIDE project block time windows — use only the general available slots that do not overlap with any project block.
- GROUP tasks by project: schedule all tasks from the same project consecutively (back-to-back) within the same project time window. This minimizes context switching. Within a project group, order by priority then deadline.
- Return ALL tasks you are placing — both newly scheduled pending tasks AND AI-scheduled tasks you are moving to a new slot.
- Do NOT return AI-scheduled tasks that stay in their current slot (only return them if you are moving them).

Respond with the scheduled tasks as a JSON array. Each entry must have:
- task_id: the task's ID (integer)
- date: the date to schedule on (string, "YYYY-MM-DD")
- start_time: when the task starts (string, "HH:MM", 24-hour format) — calculated by packing tasks sequentially within slots
- reasoning: a brief explanation of why this slot was chosen (string)
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'scheduled_tasks' => $schema->array()->items(
                $schema->object([
                    'task_id' => $schema->integer()->required(),
                    'date' => $schema->string()->required(),
                    'start_time' => $schema->string()->required(),
                    'reasoning' => $schema->string()->required(),
                ])
            )->required(),
        ];
    }

    public static function buildContext(User $user): string
    {
        $tz = $user->timezone ?? 'UTC';
        $now = Carbon::now($tz);

        $pendingTasks = $user->tasks()
            ->where('status', 'pending')
            ->with('project')
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
            ->orderBy('deadline')
            ->get();

        // AI-scheduled tasks can be moved (unless pinned)
        $aiScheduledTasks = $user->tasks()
            ->where('status', 'scheduled')
            ->where('is_ai_scheduled', true)
            ->where('is_pinned', false)
            ->where('scheduled_end', '>=', $now)
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
            ->orderBy('scheduled_start')
            ->get();

        $schedules = $user->workSchedules()->get();

        $context = "Current date and time: {$now->format('Y-m-d H:i')} ({$tz})\n\n";

        $context .= "## Working Schedule\n";
        foreach ($schedules as $ws) {
            if ($ws->enabled) {
                $line = "- {$ws->dayName()}: {$ws->start}-{$ws->end}";
                if ($ws->lunch_start && $ws->lunch_end) {
                    $line .= " (lunch {$ws->lunch_start}-{$ws->lunch_end})";
                }
                $context .= $line."\n";
            } else {
                $context .= "- {$ws->dayName()}: OFF\n";
            }
        }
        $context .= "- Buffer between tasks: {$user->buffer_time} minutes\n";
        if ($user->focus_time_enabled) {
            $context .= "- Focus time: {$user->focus_time_start} - {$user->focus_time_end} (prefer long tasks 60+ min here)\n";
        }
        $context .= "\n";

        $context .= "## Pending Tasks to Schedule (ordered by priority then deadline)\n";
        if ($pendingTasks->isEmpty()) {
            $context .= "No pending tasks.\n\n";
        } else {
            foreach ($pendingTasks as $task) {
                $duration = $task->estimated_duration ?? 60;
                $deadline = $task->deadline ? $task->deadline->format('Y-m-d') : 'No deadline';
                $projectLabel = $task->project ? " - Project: \"{$task->project->title}\" (ID: {$task->project_id})" : '';
                $context .= "- [ID: {$task->id}] \"{$task->title}\" - Priority: {$task->priority->value} - Duration: {$duration}min - Deadline: {$deadline}{$projectLabel}\n";
            }
            $context .= "\n";
        }

        $context .= "## AI-Scheduled Tasks (can be moved to better slots)\n";
        if ($aiScheduledTasks->isEmpty()) {
            $context .= "No AI-scheduled tasks.\n\n";
        } else {
            foreach ($aiScheduledTasks as $task) {
                $duration = $task->estimated_duration ?? 60;
                $deadline = $task->deadline ? $task->deadline->format('Y-m-d') : 'No deadline';
                $context .= "- [ID: {$task->id}] \"{$task->title}\" - Priority: {$task->priority->value} - Duration: {$duration}min - Currently: {$task->scheduled_start->setTimezone($tz)->format('Y-m-d H:i')} - Deadline: {$deadline}\n";
            }
            $context .= "\n";
        }

        // Compute slots treating AI-scheduled tasks as free (since they can be moved)
        $aiTaskIds = $aiScheduledTasks->pluck('id')->toArray();
        $slots = static::computeAvailableSlots($user, $aiTaskIds);

        $context .= "## Available Time Slots\n";
        $context .= "Each slot shows date, start time, and end time.\n";
        $context .= "These slots include time currently occupied by AI-scheduled tasks (since those can be moved).\n";

        if (empty($slots)) {
            $context .= "No available slots.\n\n";
        } else {
            foreach ($slots as $slot) {
                $context .= "- {$slot['date']} {$slot['start']} - {$slot['end']}\n";
            }
            $context .= "\n";
        }

        // Add project-constrained slots for tasks that belong to projects
        $projectTasks = $pendingTasks->whereNotNull('project_id')->groupBy('project_id');
        if ($projectTasks->isNotEmpty()) {
            $context .= "## Project-Constrained Slots\n";
            $context .= "Tasks belonging to a project MUST ONLY be scheduled within that project's time windows below.\n";

            foreach ($projectTasks as $projectId => $tasks) {
                $project = $tasks->first()->project;
                $projectSlots = static::computeProjectConstrainedSlots($user, $project, $aiTaskIds);

                $context .= "### Project \"{$project->title}\" (ID: {$projectId})\n";
                if (empty($projectSlots)) {
                    $context .= "No available project slots.\n";
                } else {
                    foreach ($projectSlots as $slot) {
                        $context .= "- {$slot['date']} {$slot['start']} - {$slot['end']}\n";
                    }
                }
            }
            $context .= "\n";
        }

        $buffer = $user->buffer_time ?? 15;
        $context .= "Buffer time between tasks: {$buffer} minutes.\n\n";
        $context .= 'Schedule ALL pending tasks and optionally move AI-scheduled tasks to better slots. Pack tasks tightly within slots (task duration + buffer before next task). Calculate each start_time precisely. Prioritize urgent tasks in the earliest slots, bumping lower-priority AI-scheduled tasks if needed. IMPORTANT: Tasks with a project MUST be placed within their project-constrained slots only. Return only tasks you are placing or moving.';

        return $context;
    }

    /**
     * Compute all available time slots for the next 7 working days.
     *
     * @return array<int, array{date: string, start: string, end: string}>
     */
    /**
     * @param  array<int>  $excludeTaskIds  Task IDs to treat as free (e.g. AI-scheduled tasks that can be moved)
     * @return array<int, array{date: string, start: string, end: string}>
     */
    public static function computeAvailableSlots(User $user, array $excludeTaskIds = []): array
    {
        $tz = $user->timezone ?? 'UTC';
        $now = Carbon::now($tz);
        $buffer = $user->buffer_time ?? 15;

        // Index work schedules by ISO day number
        $workSchedules = $user->workSchedules()->get()->keyBy('day');

        $slots = [];

        for ($dayOffset = 0; $dayOffset < 365; $dayOffset++) {
            $day = $now->copy()->addDays($dayOffset)->startOfDay();
            $isoDay = $day->dayOfWeekIso;

            $schedule = $workSchedules->get($isoDay);

            if (! $schedule || ! $schedule->enabled || ! $schedule->start || ! $schedule->end) {
                continue;
            }

            $dayStart = $day->copy()->setTimeFromTimeString($schedule->start);
            $dayEnd = $day->copy()->setTimeFromTimeString($schedule->end);
            $lunchStart = $schedule->lunch_start;
            $lunchEnd = $schedule->lunch_end;

            // On the current day, don't schedule before now (rounded up to 15-min)
            $scanFrom = $dayStart->greaterThan($now)
                ? $dayStart->copy()
                : $now->copy()->ceilMinutes(15);

            if ($scanFrom->greaterThanOrEqualTo($dayEnd)) {
                continue;
            }

            // Collect all occupied blocks for this day (events + scheduled tasks + lunch)
            $occupied = collect();

            // Lunch break as an occupied block (no buffer needed after lunch)
            if ($lunchStart && $lunchEnd) {
                $occupied->push([
                    'start' => $day->copy()->setTimeFromTimeString($lunchStart),
                    'end' => $day->copy()->setTimeFromTimeString($lunchEnd),
                    'no_buffer' => true,
                ]);
            }

            // Convert to UTC for database queries (DB stores datetimes in UTC)
            $dayEndUtc = $dayEnd->copy()->utc();
            $scanFromUtc = $scanFrom->copy()->utc();

            $user->calendarEvents()
                ->where('starts_at', '<', $dayEndUtc)
                ->where('ends_at', '>', $scanFromUtc)
                ->orderBy('starts_at')
                ->each(function ($event) use ($occupied) {
                    $occupied->push(['start' => $event->starts_at, 'end' => $event->ends_at]);
                });

            TaskBlock::query()
                ->whereHas('task', fn ($q) => $q->where('user_id', $user->id)->where('status', 'scheduled'))
                ->when(! empty($excludeTaskIds), fn ($q) => $q->whereNotIn('task_id', $excludeTaskIds))
                ->where('scheduled_start', '<', $dayEndUtc)
                ->where('scheduled_end', '>', $scanFromUtc)
                ->orderBy('scheduled_start')
                ->each(function ($block) use ($occupied) {
                    $occupied->push(['start' => $block->scheduled_start, 'end' => $block->scheduled_end]);
                });

            $occupied = $occupied->sortBy('start')->values();

            // Walk through the day and find gaps
            $cursor = $scanFrom->copy();

            foreach ($occupied as $block) {
                $blockStart = $block['start']->copy()->setTimezone($tz);
                $blockEnd = $block['end']->copy()->setTimezone($tz);

                if ($blockStart->greaterThan($cursor)) {
                    $gapMinutes = (int) $cursor->diffInMinutes($blockStart);

                    if ($gapMinutes >= 15) {
                        $slots[] = [
                            'date' => $cursor->format('Y-m-d'),
                            'start' => $cursor->format('H:i'),
                            'end' => $blockStart->format('H:i'),
                        ];
                    }
                }

                // Move cursor past this block (+ buffer, unless it's a lunch break)
                $afterBlock = ($block['no_buffer'] ?? false)
                    ? $blockEnd->copy()
                    : $blockEnd->copy()->addMinutes($buffer);
                if ($afterBlock->greaterThan($cursor)) {
                    $cursor = $afterBlock;
                }
            }

            // Gap after the last occupied block until end of day
            if ($cursor->lessThan($dayEnd)) {
                $gapMinutes = (int) $cursor->diffInMinutes($dayEnd);

                if ($gapMinutes >= 15) {
                    $slots[] = [
                        'date' => $cursor->format('Y-m-d'),
                        'start' => $cursor->format('H:i'),
                        'end' => $dayEnd->format('H:i'),
                    ];
                }
            }

            // Stop after we have enough slots (200 max to avoid excessive context)
            if (count($slots) >= 200) {
                break;
            }
        }

        return $slots;
    }

    /**
     * Compute available slots constrained to a project's calendar blocks.
     *
     * Returns the intersection of general available slots with the project's block windows.
     *
     * @param  array<int>  $excludeTaskIds
     * @return array<int, array{date: string, start: string, end: string}>
     */
    public static function computeProjectConstrainedSlots(User $user, Project $project, array $excludeTaskIds = []): array
    {
        $tz = $user->timezone ?? 'UTC';
        $availableSlots = static::computeAvailableSlots($user, $excludeTaskIds);

        // Collect project time windows from both recurring schedules and one-off blocks
        $schedules = $project->schedules()->get()->groupBy('day');
        $oneOffBlocks = $project->blocks()
            ->where('scheduled_end', '>', Carbon::now($tz)->utc())
            ->orderBy('scheduled_start')
            ->get();

        $constrainedSlots = [];

        foreach ($availableSlots as $slot) {
            $slotStart = Carbon::parse($slot['date'].' '.$slot['start'], $tz);
            $slotEnd = Carbon::parse($slot['date'].' '.$slot['end'], $tz);
            $isoDay = $slotStart->dayOfWeekIso;

            // Check recurring weekly schedules for this day
            $daySchedules = $schedules->get($isoDay, collect());
            foreach ($daySchedules as $schedule) {
                // Check if this date is within the project's date range (if set)
                if ($project->starts_at && $slotStart->toDateString() < $project->starts_at->toDateString()) {
                    continue;
                }
                if ($project->ends_at && $slotStart->toDateString() > $project->ends_at->toDateString()) {
                    continue;
                }

                $blockStart = $slotStart->copy()->startOfDay()->setTimeFromTimeString($schedule->start);
                $blockEnd = $slotStart->copy()->startOfDay()->setTimeFromTimeString($schedule->end);

                static::addIntersection($constrainedSlots, $slotStart, $slotEnd, $blockStart, $blockEnd);
            }

            // Check one-off project blocks
            foreach ($oneOffBlocks as $block) {
                $blockStart = $block->scheduled_start->copy()->setTimezone($tz);
                $blockEnd = $block->scheduled_end->copy()->setTimezone($tz);

                static::addIntersection($constrainedSlots, $slotStart, $slotEnd, $blockStart, $blockEnd);
            }
        }

        return $constrainedSlots;
    }

    /**
     * Add the intersection of two time ranges to the slots array if >= 15 min.
     *
     * @param  array<int, array{date: string, start: string, end: string}>  $slots
     */
    private static function addIntersection(array &$slots, Carbon $slotStart, Carbon $slotEnd, Carbon $blockStart, Carbon $blockEnd): void
    {
        $intersectStart = $slotStart->greaterThan($blockStart) ? $slotStart : $blockStart;
        $intersectEnd = $slotEnd->lessThan($blockEnd) ? $slotEnd : $blockEnd;

        if ($intersectStart->lessThan($intersectEnd)) {
            $minutes = (int) $intersectStart->diffInMinutes($intersectEnd);

            if ($minutes >= 15) {
                $slots[] = [
                    'date' => $intersectStart->format('Y-m-d'),
                    'start' => $intersectStart->format('H:i'),
                    'end' => $intersectEnd->format('H:i'),
                ];
            }
        }
    }
}
