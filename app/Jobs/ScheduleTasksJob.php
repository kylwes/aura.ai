<?php

namespace App\Jobs;

use App\Ai\Agents\TaskScheduler;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Events\ScheduleCompleted;
use App\Models\Project;
use App\Models\ProjectBlock;
use App\Models\TaskBlock;
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
        $tz = $this->user->timezone ?? 'UTC';
        $buffer = $this->user->buffer_time ?? 15;

        $context = TaskScheduler::buildContext($this->user);
        $agent = new TaskScheduler($this->user, $context);
        $response = $agent->prompt($context, model: 'claude-haiku-4-5-20251001');

        // Get non-pinned AI-scheduled task IDs so their slots are treated as available
        $aiTaskIds = $this->user->tasks()
            ->where('status', TaskStatus::Scheduled)
            ->where('is_ai_scheduled', true)
            ->where('is_pinned', false)
            ->pluck('id')
            ->toArray();

        $availableSlots = TaskScheduler::computeAvailableSlots($this->user, $aiTaskIds);

        // Compute slots excluding project block time (for non-urgent, non-project tasks)
        $nonProjectSlots = $this->computeNonProjectSlots($availableSlots, $tz);

        $scheduledTasks = $response['scheduled_tasks'] ?? [];

        // Sort tasks by urgency score: priority + deadline proximity
        // Tasks with deadlines within 2 days get boosted regardless of priority
        $taskLookup = $this->user->tasks()
            ->whereIn('status', [TaskStatus::Pending, TaskStatus::Scheduled])
            ->get()
            ->keyBy('id');

        $priorityWeight = ['urgent' => 0, 'high' => 10, 'medium' => 20, 'low' => 30];

        // Sort: project tasks grouped together first, then by urgency within each group
        usort($scheduledTasks, function ($a, $b) use ($taskLookup, $priorityWeight) {
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

            // Within same group: sort by urgency
            return $this->urgencyScore($taskA, $priorityWeight) <=> $this->urgencyScore($taskB, $priorityWeight);
        });

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

            // Determine which slots to use based on task type:
            // - Project tasks → project-constrained slots only
            // - Non-project urgent tasks → all available slots (can override project blocks)
            // - Non-project non-urgent tasks → slots outside project blocks
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

            $remaining = $task->estimated_duration ?? 60;
            $minBlockSize = 30;
            $blocks = [];

            while ($remaining > 0) {
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

                $this->consumeSlot($slotsRef, $slotIndex, $blockEnd, $tz, $buffer);
                usort($slotsRef, fn ($a, $b) => ($a['date'].' '.$a['start']) <=> ($b['date'].' '.$b['start']));

                // Consume from ALL other slot arrays to prevent overlaps
                $this->consumeFromGeneralSlots($availableSlots, $slotStart, $blockEnd, $tz, $buffer);
                $this->consumeFromGeneralSlots($nonProjectSlots, $slotStart, $blockEnd, $tz, $buffer);
                foreach ($projectSlotCache as &$pSlots) {
                    $this->consumeFromGeneralSlots($pSlots, $slotStart, $blockEnd, $tz, $buffer);
                }
                unset($pSlots);
            }

            if (empty($blocks)) {
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

            $task->update([
                'scheduled_start' => $blocks[0]['start'],
                'scheduled_end' => end($blocks)['end'],
                'status' => TaskStatus::Scheduled,
                'is_ai_scheduled' => true,
                'ai_reasoning' => $placement['reasoning'] ?? null,
            ]);
        }

        ScheduleCompleted::dispatch($this->user->id);
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
     * A deadline within 1 day subtracts 25, within 2 days subtracts 15, within 3 days subtracts 10.
     * This means a medium task due tomorrow (20-25=-5) beats a high task with no deadline (10).
     */
    private function urgencyScore(mixed $task, array $priorityWeight): int
    {
        $score = $priorityWeight[$task->priority->value] ?? 40;

        if ($task->deadline) {
            $daysUntil = (int) now()->startOfDay()->diffInDays($task->deadline->startOfDay(), absolute: false);

            if ($daysUntil <= 1) {
                $score -= 25;
            } elseif ($daysUntil <= 2) {
                $score -= 15;
            } elseif ($daysUntil <= 3) {
                $score -= 10;
            }
        }

        return $score;
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
