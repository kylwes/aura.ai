<?php

namespace Database\Seeders;

use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\CalendarEvent;
use App\Models\InboxItem;
use App\Models\Integration;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Kylian Wester',
            'email' => 'kylian@aura.ai',
            'password' => 'password',
            'timezone' => 'Europe/Amsterdam',
            'working_hours_start' => '09:00',
            'working_hours_end' => '17:30',
            'working_days' => [1, 2, 3, 4, 5],
            'onboarded_at' => now(),
        ]);

        $jira = Integration::factory()->create([
            'user_id' => $user->id,
            'type' => IntegrationType::Jira,
            'status' => IntegrationStatus::Connected,
        ]);

        $slack = Integration::factory()->create([
            'user_id' => $user->id,
            'type' => IntegrationType::Slack,
            'status' => IntegrationStatus::Connected,
        ]);

        $gmail = Integration::factory()->create([
            'user_id' => $user->id,
            'type' => IntegrationType::Gmail,
            'status' => IntegrationStatus::Connected,
        ]);

        $github = Integration::factory()->create([
            'user_id' => $user->id,
            'type' => IntegrationType::GitHub,
            'status' => IntegrationStatus::Paused,
        ]);

        $monday = now()->startOfWeek();

        // Calendar events for the week - daily standups
        foreach (range(0, 4) as $dayOffset) {
            $day = $monday->copy()->addDays($dayOffset);
            CalendarEvent::factory()->create([
                'user_id' => $user->id,
                'title' => 'Team Standup',
                'starts_at' => $day->copy()->setTime(9, 30),
                'ends_at' => $day->copy()->setTime(9, 45),
            ]);
        }

        CalendarEvent::factory()->create([
            'user_id' => $user->id,
            'title' => 'Sprint Planning',
            'starts_at' => $monday->copy()->setTime(14, 0),
            'ends_at' => $monday->copy()->setTime(15, 30),
        ]);

        CalendarEvent::factory()->create([
            'user_id' => $user->id,
            'title' => 'Design Review',
            'starts_at' => $monday->copy()->addDays(2)->setTime(11, 0),
            'ends_at' => $monday->copy()->addDays(2)->setTime(12, 0),
        ]);

        CalendarEvent::factory()->create([
            'user_id' => $user->id,
            'title' => 'Client Demo',
            'starts_at' => $monday->copy()->addDays(3)->setTime(15, 0),
            'ends_at' => $monday->copy()->addDays(3)->setTime(16, 0),
        ]);

        // Scheduled AI tasks
        Task::factory()->create([
            'user_id' => $user->id,
            'integration_id' => $jira->id,
            'title' => 'Quarterly Performance Review Design',
            'description' => 'Finalize the visual direction for the upcoming Q3 performance dashboard. Focus on the data visualization components and the atmospheric precision theme.',
            'source_url' => 'https://jira.example.com/browse/AUR-402',
            'source_reference' => 'AUR-402',
            'priority' => TaskPriority::Urgent,
            'estimated_duration' => 150,
            'deadline' => $monday->copy()->addDays(1),
            'scheduled_start' => $monday->copy()->setTime(10, 0),
            'scheduled_end' => $monday->copy()->setTime(12, 30),
            'is_ai_scheduled' => true,
            'ai_reasoning' => 'Scheduled before the "Sync Meeting" due to high priority and deadline tomorrow. We\'ve allocated this slot as it\'s your peak focus period based on past task completion rates.',
            'status' => TaskStatus::Scheduled,
        ]);

        Task::factory()->create([
            'user_id' => $user->id,
            'integration_id' => $slack->id,
            'title' => 'Review API integration docs',
            'description' => 'Review the new API documentation shared in #dev-team for the payment gateway migration.',
            'source_reference' => '#dev-team',
            'priority' => TaskPriority::High,
            'estimated_duration' => 45,
            'scheduled_start' => $monday->copy()->addDays(1)->setTime(10, 0),
            'scheduled_end' => $monday->copy()->addDays(1)->setTime(10, 45),
            'is_ai_scheduled' => true,
            'ai_reasoning' => 'Placed in your morning focus block on Tuesday. Related Jira tickets suggest this is blocking other team members.',
            'status' => TaskStatus::Scheduled,
        ]);

        Task::factory()->create([
            'user_id' => $user->id,
            'integration_id' => $gmail->id,
            'title' => 'Reply to partnership proposal',
            'description' => 'Draft a response to the partnership proposal from Acme Corp received via email.',
            'priority' => TaskPriority::Medium,
            'estimated_duration' => 30,
            'scheduled_start' => $monday->copy()->addDays(2)->setTime(14, 0),
            'scheduled_end' => $monday->copy()->addDays(2)->setTime(14, 30),
            'is_ai_scheduled' => true,
            'ai_reasoning' => 'Scheduled after Design Review on Wednesday. Medium priority, no hard deadline detected.',
            'status' => TaskStatus::Scheduled,
        ]);

        // Unscheduled tasks (in queue)
        Task::factory()->create([
            'user_id' => $user->id,
            'integration_id' => $jira->id,
            'title' => 'Fix mobile nav overflow',
            'source_reference' => 'AUR-418',
            'priority' => TaskPriority::High,
            'estimated_duration' => 60,
            'status' => TaskStatus::Pending,
        ]);

        Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Update README with new setup steps',
            'priority' => TaskPriority::Low,
            'estimated_duration' => 20,
            'status' => TaskStatus::Pending,
        ]);

        // Inbox items
        InboxItem::factory()->create([
            'user_id' => $user->id,
            'integration_id' => $slack->id,
            'channel_name' => '#dev-team',
            'preview_text' => 'Hey, can someone review the PR for the auth refactor? It\'s been sitting for 2 days now.',
            'ai_suggested_priority' => TaskPriority::High->value,
            'ai_confidence' => 3,
        ]);

        InboxItem::factory()->create([
            'user_id' => $user->id,
            'integration_id' => $jira->id,
            'channel_name' => 'AUR-425',
            'preview_text' => 'New ticket: Implement dark mode toggle for dashboard settings page.',
            'ai_suggested_priority' => TaskPriority::Medium->value,
            'ai_confidence' => 2,
        ]);

        InboxItem::factory()->create([
            'user_id' => $user->id,
            'integration_id' => $gmail->id,
            'channel_name' => 'inbox',
            'preview_text' => 'Meeting notes from yesterday\'s product sync - action items for your team attached.',
            'ai_suggested_priority' => TaskPriority::Medium->value,
            'ai_confidence' => 2,
        ]);

        InboxItem::factory()->create([
            'user_id' => $user->id,
            'integration_id' => $github->id,
            'channel_name' => 'aura-ai/core',
            'preview_text' => 'Dependabot: Bump axios from 1.12.0 to 1.14.0 — security patch for CVE-2026-1234.',
            'ai_suggested_priority' => TaskPriority::Urgent->value,
            'ai_confidence' => 3,
        ]);

        InboxItem::factory()->create([
            'user_id' => $user->id,
            'integration_id' => $slack->id,
            'channel_name' => '#general',
            'preview_text' => 'Reminder: Team lunch on Friday at 12:30. Please RSVP in the thread.',
            'ai_suggested_priority' => TaskPriority::Low->value,
            'ai_confidence' => 3,
        ]);
    }
}
