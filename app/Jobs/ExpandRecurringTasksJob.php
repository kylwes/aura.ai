<?php

namespace App\Jobs;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ExpandRecurringTasksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $templates = Task::whereNotNull('recurrence_type')
            ->whereNull('parent_task_id')
            ->whereIn('status', [TaskStatus::Pending, TaskStatus::Scheduled])
            ->with('user')
            ->get();

        $usersToReschedule = collect();

        foreach ($templates as $template) {
            $tz = $template->user->timezone ?? 'UTC';
            $today = Carbon::now($tz)->startOfDay();

            // Skip if recurrence has ended
            if ($template->recurrence_end_date && $today->gt($template->recurrence_end_date)) {
                continue;
            }

            $created = false;

            for ($dayOffset = 0; $dayOffset < 7; $dayOffset++) {
                $date = $today->copy()->addDays($dayOffset);

                if (! $this->matchesRecurrence($template, $date)) {
                    continue;
                }

                // Check if instance already exists for this date
                $exists = $template->instances()
                    ->whereDate('deadline', $date)
                    ->whereIn('status', [TaskStatus::Pending, TaskStatus::Scheduled])
                    ->exists();

                if ($exists) {
                    continue;
                }

                // Create instance
                Task::create([
                    'user_id' => $template->user_id,
                    'project_id' => $template->project_id,
                    'title' => $template->title,
                    'description' => $template->description,
                    'priority' => $template->priority,
                    'estimated_duration' => $template->estimated_duration,
                    'deadline' => $date,
                    'status' => TaskStatus::Pending,
                    'parent_task_id' => $template->id,
                ]);

                $created = true;
            }

            if ($created) {
                $usersToReschedule->push($template->user_id);
            }
        }

        // Reschedule affected users
        $usersToReschedule->unique()->each(function ($userId) {
            $user = User::find($userId);
            if ($user) {
                ScheduleTasksJob::debounce($user);
            }
        });
    }

    private function matchesRecurrence(Task $template, Carbon $date): bool
    {
        return match ($template->recurrence_type) {
            'daily' => true,
            'weekly' => in_array($date->dayOfWeekIso, $template->recurrence_days ?? []),
            'monthly' => $date->day === Carbon::parse($template->created_at)->day,
            default => false,
        };
    }
}
