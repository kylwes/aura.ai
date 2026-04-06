<?php

namespace App\Jobs;

use App\Ai\Agents\TaskScheduler;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Events\ScheduleCompleted;
use App\Models\Project;
use App\Models\ProjectBlock;
use App\Models\ScheduleSnapshot;
use App\Models\Task;
use App\Models\TaskBlock;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ScheduleTasksJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var array<int, array<string, mixed>> */
    public array $proposedChanges = [];

    public function __construct(
        public User $user,
        public bool $dryRun = false,
        public ?array $temporaryOverrides = null,
    ) {}

    /**
     * Dispatch with a 5-second delay so rapid actions get deduplicated.
     */
    public static function debounce(User $user): void
    {
        static::dispatch($user)->delay(5);
    }

    /**
     * Unique key: only one schedule job per user at a time.
     */
    public function uniqueId(): string
    {
        return 'schedule-user-'.$this->user->id;
    }

    /**
     * Stay unique for 30 seconds — if another dispatch comes in within this window, it's dropped.
     */
    public int $uniqueFor = 30;

    /** @return array<int, array<string, mixed>> */
    public function getProposedChanges(): array
    {
        return $this->proposedChanges;
    }

    public function handle(): void
    {
        if (! $this->dryRun) {
            ScheduleSnapshot::capture($this->user, 'auto_schedule', 'Before AI scheduling run');
        }

        $tz = $this->user->timezone ?? 'UTC';
        $buffer = $this->user->buffer_time ?? 15;
        $today = Carbon::now($tz)->startOfDay()->utc();
        $tomorrow = Carbon::now($tz)->addDay()->startOfDay()->utc();

        $context = TaskScheduler::buildContext($this->user, $this->temporaryOverrides);
        $agent = new TaskScheduler($this->user, $context);
        $response = $agent->prompt($context, model: 'claude-haiku-4-5-20251001');

        // Get non-pinned AI-scheduled task IDs so their slots are treated as available
        $aiTaskIds = $this->user->tasks()
            ->where('status', TaskStatus::Scheduled)
            ->where('is_ai_scheduled', true)
            ->where('is_pinned', false)
            ->pluck('id')
            ->toArray();

        $availableSlots = TaskScheduler::computeAvailableSlots($this->user, $aiTaskIds, $this->temporaryOverrides);

        // Compute slots excluding project block time (for non-urgent, non-project tasks)
        $nonProjectSlots = $this->computeNonProjectSlots($availableSlots, $tz);

        // When focus time is protected, compute separate slots within the focus window
        $focusProtected = ($this->user->focus_time_enabled ?? false) && ($this->user->focus_time_protected ?? false);
        $focusMinDuration = $focusProtected ? ($this->user->focus_time_min_duration ?? 60) : 0;
        $focusSlots = $focusProtected
            ? TaskScheduler::computeFocusSlots($this->user, $aiTaskIds, $this->temporaryOverrides)
            : [];

        $scheduledTasks = $response['scheduled_tasks'] ?? [];

        // Sort tasks by urgency score: priority + deadline proximity + capacity pressure
        $taskLookup = $this->user->tasks()
            ->whereIn('status', [TaskStatus::Pending, TaskStatus::Scheduled])
            ->with('dependencies')
            ->get()
            ->keyBy('id');

        $priorityWeight = ['urgent' => 0, 'high' => 10, 'medium' => 20, 'low' => 30];

        $capacityByDate = $this->computeCapacityByDate($availableSlots, $tz);

        // Sort: project tasks grouped together first, then by urgency within each group
        usort($scheduledTasks, function ($a, $b) use ($taskLookup, $priorityWeight, $capacityByDate, $tz) {
            $taskA = $taskLookup->get($a['task_id']);
            $taskB = $taskLookup->get($b['task_id']);

            if (! $taskA || ! $taskB) {
                return 0;
            }

            // Project tasks come first, grouped by project_id
            $aProject = $taskA->project_id ?? PHP_INT_MAX;
            $bProject = $taskB->project_id ?? PHP_INT_MAX;

            if ($aProject !== $bProject) {
                // Both have projects: group by project_id
                if ($aProject !== PHP_INT_MAX && $bProject !== PHP_INT_MAX) {
                    return $aProject <=> $bProject;
                }

                // Project tasks before non-project tasks
                return $aProject <=> $bProject;
            }

            $ratioA = $this->capacityRatioForTask($capacityByDate, $taskA, $tz);
            $ratioB = $this->capacityRatioForTask($capacityByDate, $taskB, $tz);

            // Within same group: sort by urgency
            return $this->urgencyScore($taskA, $priorityWeight, $ratioA) <=> $this->urgencyScore($taskB, $priorityWeight, $ratioB);
        });

        // Topological sort: tasks with dependencies come after their dependencies
        $scheduledTasks = $this->topologicalSort($scheduledTasks, $taskLookup);

        // Phase 1: Schedule project-bound tasks first using project-constrained slots
        $projectSlotCache = [];

        // Phase 2: Schedule all tasks (project tasks use constrained slots, others use general slots)
        foreach ($scheduledTasks as $placement) {
            $task = $taskLookup->get($placement['task_id']);

            if (! $task || ! in_array($task->status, [TaskStatus::Pending, TaskStatus::Scheduled])) {
                continue;
            }

            if ($task->is_pinned) {
                continue;
            }

            if ($task->hasUnmetDependencies()) {
                continue;
            }

            // Determine which slots to use based on task type:
            // - Project tasks → project-constrained slots only
            // - Non-project urgent tasks → all available slots (can override project blocks)
            // - Non-project non-urgent tasks → slots outside project blocks
            // When focus time is protected, long tasks (>= focus_time_min_duration) use
            // focus slots first; short tasks skip focus slots entirely (already excluded).
            $slotsRef = &$nonProjectSlots;
            if ($task->project_id) {
                if (! isset($projectSlotCache[$task->project_id])) {
                    $project = Project::find($task->project_id);
                    $projectSlotCache[$task->project_id] = $project
                        ? TaskScheduler::computeProjectConstrainedSlots($this->user, $project, $aiTaskIds)
                        : [];
                }
                $slotsRef = &$projectSlotCache[$task->project_id];
            } elseif ($task->priority === TaskPriority::Urgent) {
                $slotsRef = &$availableSlots;
            }

            // For protected focus time: route qualifying long tasks through focus slots first
            $taskDuration = $task->estimated_duration ?? 60;
            $useFocusSlotsFirst = $focusProtected && ! $task->project_id && $taskDuration >= $focusMinDuration;

            $remaining = $task->estimated_duration ?? 60;
            $minBlockSize = 30;
            $blocks = [];

            while ($remaining > 0) {
                // When focus slots should be used first, prefer them until exhausted
                if ($useFocusSlotsFirst && ! empty($focusSlots)) {
                    $slotIndex = $this->findFirstUsableSlot($focusSlots, $tz, $minBlockSize);
                    if ($slotIndex !== null) {
                        $slotsRef = &$focusSlots;
                    } else {
                        $useFocusSlotsFirst = false;
                        $slotsRef = &$nonProjectSlots;
                    }
                }

                $slotIndex = $this->findFirstUsableSlot($slotsRef, $tz, $minBlockSize);

                if ($slotIndex === null) {
                    break;
                }

                $slot = $slotsRef[$slotIndex];
                $slotStart = Carbon::parse($slot['date'].' '.$slot['start'], $tz);
                $slotEnd = Carbon::parse($slot['date'].' '.$slot['end'], $tz);
                $slotMinutes = (int) $slotStart->diffInMinutes($slotEnd);

                $blockDuration = min($remaining, $slotMinutes);

                $wouldRemain = $remaining - $blockDuration;
                if ($wouldRemain > 0 && $wouldRemain < $minBlockSize) {
                    $blockDuration = $remaining - $minBlockSize;

                    if ($blockDuration < $minBlockSize) {
                        $blockDuration = min($remaining, $slotMinutes);
                    }
                }

                $blockEnd = $slotStart->copy()->addMinutes($blockDuration);

                $blocks[] = [
                    'start' => $slotStart->copy()->utc(),
                    'end' => $blockEnd->copy()->utc(),
                ];

                $remaining -= $blockDuration;

                // Consume the placed block + buffer from ALL slot arrays to prevent overlaps
                $consumeEnd = $blockEnd->copy()->addMinutes($buffer);
                $allArrays = [&$availableSlots, &$nonProjectSlots, &$focusSlots];
                foreach ($projectSlotCache as &$pSlots) {
                    $allArrays[] = &$pSlots;
                }
                unset($pSlots);

                foreach ($allArrays as &$arr) {
                    $newArr = [];
                    foreach ($arr as $s) {
                        $sStart = Carbon::parse($s['date'].' '.$s['start'], $tz);
                        $sEnd = Carbon::parse($s['date'].' '.$s['end'], $tz);

                        // No overlap — keep as is
                        if ($consumeEnd->lte($sStart) || $slotStart->gte($sEnd)) {
                            $newArr[] = $s;

                            continue;
                        }

                        // Keep portion before
                        if ($sStart->lt($slotStart) && (int) $sStart->diffInMinutes($slotStart) >= 15) {
                            $newArr[] = ['date' => $sStart->format('Y-m-d'), 'start' => $sStart->format('H:i'), 'end' => $slotStart->format('H:i')];
                        }

                        // Keep portion after (with buffer)
                        if ($consumeEnd->lt($sEnd) && (int) $consumeEnd->diffInMinutes($sEnd) >= 15) {
                            $newArr[] = ['date' => $consumeEnd->format('Y-m-d'), 'start' => $consumeEnd->format('H:i'), 'end' => $s['end']];
                        }
                    }
                    usort($newArr, fn ($a, $b) => ($a['date'].' '.$a['start']) <=> ($b['date'].' '.$b['start']));
                    $arr = $newArr;
                }
                unset($arr);
            }

            if (empty($blocks)) {
                continue;
            }

            if ($this->dryRun) {
                $this->proposedChanges[] = [
                    'task_id' => $task->id,
                    'action' => $task->scheduled_start ? 'move' : 'schedule',
                    'old_start' => $task->scheduled_start?->toISOString(),
                    'old_end' => $task->scheduled_end?->toISOString(),
                    'new_start' => $blocks[0]['start']->toISOString(),
                    'new_end' => end($blocks)['end']->toISOString(),
                    'blocks' => array_map(fn ($b) => [
                        'start' => $b['start']->toISOString(),
                        'end' => $b['end']->toISOString(),
                    ], $blocks),
                    'reasoning' => $placement['reasoning'] ?? null,
                ];

                continue;
            }

            $task->blocks()->delete();

            foreach ($blocks as $block) {
                TaskBlock::create([
                    'task_id' => $task->id,
                    'scheduled_start' => $block['start'],
                    'scheduled_end' => $block['end'],
                ]);
            }

            $isRescheduled = $task->scheduled_start && $task->scheduled_start->ne($blocks[0]['start']);

            $task->update([
                'scheduled_start' => $blocks[0]['start'],
                'scheduled_end' => end($blocks)['end'],
                'status' => TaskStatus::Scheduled,
                'is_ai_scheduled' => true,
                'ai_reasoning' => $placement['reasoning'] ?? null,
                ...($isRescheduled ? [
                    'reschedule_count' => $task->reschedule_count + 1,
                    'last_rescheduled_at' => now(),
                ] : []),
            ]);
        }

        if ($this->dryRun) {
            return;
        }

        // Auto-pin today's tasks after scheduling so they don't get moved next time
        $this->user->tasks()
            ->where('status', TaskStatus::Scheduled)
            ->where('is_ai_scheduled', true)
            ->where('is_pinned', false)
            ->where('scheduled_start', '>=', $today)
            ->where('scheduled_start', '<', $tomorrow)
            ->update(['is_pinned' => true]);

        ScheduleCompleted::dispatch($this->user->id);
    }

    /**
     * Sort scheduled tasks so that dependencies are placed before dependents.
     * Tasks with unmet (not-yet-completed) dependencies are moved after their deps.
     *
     * @param  array<int, array{task_id: int}>  $scheduledTasks
     * @param  Collection<int, Task>  $taskLookup
     * @return array<int, array{task_id: int}>
     */
    private function topologicalSort(array $scheduledTasks, $taskLookup): array
    {
        // Build dependency graph from task IDs in the scheduled list
        $taskIds = array_column($scheduledTasks, 'task_id');
        $placementByTaskId = [];
        foreach ($scheduledTasks as $placement) {
            $placementByTaskId[$placement['task_id']] = $placement;
        }

        // Load dependencies for all tasks in batch
        $deps = \DB::table('task_dependencies')
            ->whereIn('task_id', $taskIds)
            ->whereIn('depends_on_task_id', $taskIds)
            ->get()
            ->groupBy('task_id');

        // Kahn's algorithm for topological sort
        $inDegree = array_fill_keys($taskIds, 0);
        $graph = [];

        foreach ($deps as $taskId => $taskDeps) {
            foreach ($taskDeps as $dep) {
                // Only count dependencies that are in our scheduled list
                // and whose dependency task is NOT already completed
                $depTask = $taskLookup->get($dep->depends_on_task_id);
                if ($depTask && $depTask->status !== TaskStatus::Completed) {
                    $inDegree[$taskId] = ($inDegree[$taskId] ?? 0) + 1;
                    $graph[$dep->depends_on_task_id][] = $taskId;
                }
            }
        }

        $queue = [];
        foreach ($inDegree as $id => $degree) {
            if ($degree === 0) {
                $queue[] = $id;
            }
        }

        $sorted = [];
        while (! empty($queue)) {
            $current = array_shift($queue);
            if (isset($placementByTaskId[$current])) {
                $sorted[] = $placementByTaskId[$current];
            }
            foreach ($graph[$current] ?? [] as $dependent) {
                $inDegree[$dependent]--;
                if ($inDegree[$dependent] === 0) {
                    $queue[] = $dependent;
                }
            }
        }

        // Add any tasks not in the sorted result (circular deps or isolated)
        foreach ($scheduledTasks as $placement) {
            if (! in_array($placement, $sorted, true)) {
                $sorted[] = $placement;
            }
        }

        return $sorted;
    }

    private function findFirstUsableSlot(array $slots, string $tz, int $minMinutes = 30): ?int
    {
        foreach ($slots as $index => $slot) {
            $slotStart = Carbon::parse($slot['date'].' '.$slot['start'], $tz);
            $slotEnd = Carbon::parse($slot['date'].' '.$slot['end'], $tz);

            if ($slotStart->diffInMinutes($slotEnd) >= $minMinutes) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Lower score = higher urgency = scheduled first.
     *
     * Priority gives a base score (urgent=0, high=10, medium=20, low=30).
     * Calendar distance subtracts up to 15 points for imminent deadlines.
     * Capacity ratio stacks on top: insufficient capacity to complete before deadline
     * subtracts up to an additional 35 points, making over-committed tasks surface first.
     */
    private function urgencyScore(mixed $task, array $priorityWeight, ?float $capacityRatio = null): int
    {
        $score = $priorityWeight[$task->priority->value] ?? 40;

        if ($task->deadline) {
            $daysUntil = (int) now()->startOfDay()->diffInDays($task->deadline->startOfDay(), absolute: false);

            // Base deadline pressure from calendar distance
            if ($daysUntil <= 1) {
                $score -= 15;
            } elseif ($daysUntil <= 2) {
                $score -= 10;
            } elseif ($daysUntil <= 3) {
                $score -= 5;
            }

            // Capacity-based pressure (stacks with calendar distance)
            if ($capacityRatio !== null) {
                if ($capacityRatio < 1.0) {
                    $score -= 35; // Impossible without overtime
                } elseif ($capacityRatio < 1.5) {
                    $score -= 20; // Tight
                } elseif ($capacityRatio < 2.0) {
                    $score -= 10; // Moderate pressure
                }
            }
        }

        return $score;
    }

    /**
     * Sum available minutes per calendar date from the given slots.
     *
     * @param  array<int, array{date: string, start: string, end: string}>  $slots
     * @return array<string, int>
     */
    private function computeCapacityByDate(array $slots, string $tz): array
    {
        $capacityByDate = [];
        foreach ($slots as $slot) {
            $start = Carbon::parse($slot['date'].' '.$slot['start'], $tz);
            $end = Carbon::parse($slot['date'].' '.$slot['end'], $tz);
            $minutes = (int) $start->diffInMinutes($end);
            $capacityByDate[$slot['date']] = ($capacityByDate[$slot['date']] ?? 0) + $minutes;
        }

        return $capacityByDate;
    }

    /**
     * Compute the ratio of available capacity to task duration between today and the deadline.
     *
     * A ratio < 1.0 means there is not enough time to complete the task before the deadline.
     * Returns null when the task has no deadline.
     *
     * @param  array<string, int>  $capacityByDate
     */
    private function capacityRatioForTask(array $capacityByDate, mixed $task, string $tz): ?float
    {
        if (! $task->deadline) {
            return null;
        }

        $today = Carbon::now($tz)->format('Y-m-d');
        $deadlineDate = $task->deadline->copy()->setTimezone($tz)->format('Y-m-d');
        $duration = $task->estimated_duration ?? 60;

        $totalMinutes = 0;
        foreach ($capacityByDate as $date => $minutes) {
            if ($date >= $today && $date <= $deadlineDate) {
                $totalMinutes += $minutes;
            }
        }

        return $duration > 0 ? round($totalMinutes / $duration, 2) : null;
    }

    /**
     * Compute available slots with all project block time subtracted.
     *
     * @return array<int, array{date: string, start: string, end: string}>
     */
    private function computeNonProjectSlots(array $availableSlots, string $tz): array
    {
        $projects = $this->user->projects()->with('schedules')->get();

        if ($projects->isEmpty()) {
            return $availableSlots;
        }

        $result = $availableSlots;

        // Remove recurring schedule time from available slots
        foreach ($projects as $project) {
            $schedulesByDay = $project->schedules->groupBy('day');

            foreach ($result as $slot) {
                $slotDate = Carbon::parse($slot['date'], $tz);
                $isoDay = $slotDate->dayOfWeekIso;
                $daySchedules = $schedulesByDay->get($isoDay, collect());

                foreach ($daySchedules as $schedule) {
                    if ($project->starts_at && $slotDate->toDateString() < $project->starts_at->toDateString()) {
                        continue;
                    }
                    if ($project->ends_at && $slotDate->toDateString() > $project->ends_at->toDateString()) {
                        continue;
                    }

                    $blockStart = $slotDate->copy()->startOfDay()->setTimeFromTimeString($schedule->start);
                    $blockEnd = $slotDate->copy()->startOfDay()->setTimeFromTimeString($schedule->end);
                    $this->consumeFromGeneralSlots($result, $blockStart, $blockEnd, $tz, 0);
                }
            }
        }

        // Also remove one-off project blocks
        $projectBlocks = ProjectBlock::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $this->user->id))
            ->where('scheduled_end', '>', now())
            ->orderBy('scheduled_start')
            ->get();

        foreach ($projectBlocks as $pBlock) {
            $blockStart = $pBlock->scheduled_start->copy()->setTimezone($tz);
            $blockEnd = $pBlock->scheduled_end->copy()->setTimezone($tz);
            $this->consumeFromGeneralSlots($result, $blockStart, $blockEnd, $tz, 0);
        }

        return $result;
    }

    /**
     * Remove a time range from a set of slots, keeping portions before/after.
     */
    private function consumeFromGeneralSlots(array &$slots, Carbon $blockStart, Carbon $blockEnd, string $tz, int $buffer): void
    {
        foreach ($slots as $index => $slot) {
            $slotStart = Carbon::parse($slot['date'].' '.$slot['start'], $tz);
            $slotEnd = Carbon::parse($slot['date'].' '.$slot['end'], $tz);

            // Skip if no overlap
            if ($blockEnd->lessThanOrEqualTo($slotStart) || $blockStart->greaterThanOrEqualTo($slotEnd)) {
                continue;
            }

            unset($slots[$index]);

            // Keep portion before the block
            if ($slotStart->lessThan($blockStart)) {
                $beforeMinutes = (int) $slotStart->diffInMinutes($blockStart);
                if ($beforeMinutes >= 15) {
                    $slots[] = [
                        'date' => $slotStart->format('Y-m-d'),
                        'start' => $slotStart->format('H:i'),
                        'end' => $blockStart->format('H:i'),
                    ];
                }
            }

            // Keep portion after the block + buffer
            $afterStart = $blockEnd->copy()->addMinutes($buffer);
            if ($afterStart->lessThan($slotEnd)) {
                $afterMinutes = (int) $afterStart->diffInMinutes($slotEnd);
                if ($afterMinutes >= 15) {
                    $slots[] = [
                        'date' => $afterStart->format('Y-m-d'),
                        'start' => $afterStart->format('H:i'),
                        'end' => $slot['end'],
                    ];
                }
            }
        }

        usort($slots, fn ($a, $b) => ($a['date'].' '.$a['start']) <=> ($b['date'].' '.$b['start']));
    }

    private function consumeSlot(array &$slots, int $index, Carbon $taskEnd, string $tz, int $buffer): void
    {
        $slot = $slots[$index];
        $slotEnd = Carbon::parse($slot['date'].' '.$slot['end'], $tz);

        unset($slots[$index]);

        $afterStart = $taskEnd->copy()->addMinutes($buffer);

        if ($afterStart->lessThan($slotEnd)) {
            $remaining = (int) $afterStart->diffInMinutes($slotEnd);

            if ($remaining >= 15) {
                $slots[] = [
                    'date' => $afterStart->format('Y-m-d'),
                    'start' => $afterStart->format('H:i'),
                    'end' => $slot['end'],
                ];
            }
        }
    }
}
