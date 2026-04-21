<?php

namespace App\Jobs;

use App\Ai\Agents\InboxAnalyzer;
use App\Enums\InboxItemStatus;
use App\Enums\IntegrationStatus;
use App\Enums\TaskStatus;
use App\Models\User;
use App\Providers\TaskProviders\TaskProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PollProvidersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
    ) {}

    public function handle(): void
    {
        $integrations = $this->user->integrations()
            ->where('status', IntegrationStatus::Connected)
            ->get();

        // Collect all items from all providers first
        $allItems = [];

        foreach ($integrations as $integration) {
            $provider = TaskProvider::for($integration->type);

            if (! $provider) {
                continue;
            }

            try {
                $items = $provider->fetch($integration);
            } catch (\Throwable $e) {
                logger()->warning('Provider fetch failed', [
                    'provider' => $provider->channel(),
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            foreach ($items as $item) {
                $formatted = $provider->format($item);
                $sourceUrl = method_exists($provider, 'sourceUrl')
                    ? $provider->sourceUrl($item, $integration)
                    : null;

                $allItems[] = [
                    'formatted' => $formatted,
                    'channel' => $provider->channel(),
                    'sourceUrl' => $sourceUrl,
                    'integration' => $integration,
                ];
            }

            $provider->updateLastPolledAt($integration);
        }

        if (empty($allItems)) {
            return;
        }

        // Single AI call for all items
        $context = InboxAnalyzer::buildBatchContext($this->user, $allItems);
        $agent = new InboxAnalyzer($this->user, $context);
        $response = $agent->prompt($context);

        $results = $response['results'] ?? [];
        $needsReschedule = false;

        foreach ($results as $result) {
            $index = $result['item_index'] ?? null;
            if ($index === null || ! isset($allItems[$index])) {
                continue;
            }

            $itemData = $allItems[$index];

            $action = $result['action'] ?? 'skip';

            match ($action) {
                'create_inbox' => $this->createInboxItem($itemData['integration'], $result, $itemData['sourceUrl'], $itemData['channel']),
                'update_task' => $needsReschedule = $this->updateTask($result, $itemData['integration'], $itemData['sourceUrl'], $itemData['channel']) || $needsReschedule,
                'resume_task' => $needsReschedule = $this->resumeTask($result, $itemData['integration'], $itemData['sourceUrl'], $itemData['channel']) || $needsReschedule,
                default => $this->createSkippedItem($itemData['integration'], $result, $itemData['sourceUrl'], $itemData['channel']),
            };
        }

        if ($needsReschedule) {
            ScheduleTasksJob::debounce($this->user);
        }
    }

    private function createInboxItem($integration, array $result, ?string $sourceUrl, string $channel): void
    {
        $this->user->inboxItems()->create([
            'integration_id' => $integration->id,
            'channel_name' => $channel,
            'preview_text' => $result['title'] ?? 'New item',
            'source_url' => $sourceUrl,
            'ai_suggested_priority' => $result['priority'] ?? 'medium',
            'ai_confidence' => 2,
            'ai_estimated_duration' => $result['estimated_duration'] ?? null,
            'ai_suggested_project_id' => $result['project_id'] ?? null,
            'ai_action' => 'create_inbox',
            'ai_reasoning' => $result['reasoning'] ?? null,
            'status' => InboxItemStatus::Pending,
        ]);
    }

    private function createSkippedItem($integration, array $result, ?string $sourceUrl, string $channel): void
    {
        $this->user->inboxItems()->create([
            'integration_id' => $integration->id,
            'channel_name' => $channel,
            'preview_text' => $result['title'] ?? 'Skipped item',
            'source_url' => $sourceUrl,
            'ai_action' => 'skip',
            'ai_reasoning' => $result['reasoning'] ?? null,
            'status' => InboxItemStatus::Skipped,
        ]);
    }

    private function updateTask(array $result, $integration, ?string $sourceUrl, string $channel): bool
    {
        if (! ($result['match_task_id'] ?? null)) {
            return false;
        }

        $task = $this->user->tasks()->find($result['match_task_id']);

        if (! $task) {
            return false;
        }

        $this->user->inboxItems()->create([
            'integration_id' => $integration->id,
            'channel_name' => $channel,
            'preview_text' => $result['title'] ?? $task->title,
            'source_url' => $sourceUrl,
            'ai_action' => 'update_task',
            'ai_reasoning' => $result['reasoning'] ?? null,
            'status' => InboxItemStatus::Accepted,
        ]);

        $updates = [];

        if (($result['priority'] ?? null) && $result['priority'] !== $task->priority->value) {
            $updates['priority'] = $result['priority'];
        }

        if (($result['estimated_duration'] ?? null) && ! $task->estimated_duration) {
            $updates['estimated_duration'] = $result['estimated_duration'];
        }

        if (! empty($updates)) {
            $task->update($updates);

            return isset($updates['priority']) && $task->status->value === 'scheduled' && $task->is_ai_scheduled;
        }

        return false;
    }

    private function resumeTask(array $result, $integration, ?string $sourceUrl, string $channel): bool
    {
        if (! ($result['match_task_id'] ?? null)) {
            return false;
        }

        $task = $this->user->tasks()
            ->where('status', TaskStatus::OnHold)
            ->find($result['match_task_id']);

        if (! $task) {
            return false;
        }

        $this->user->inboxItems()->create([
            'integration_id' => $integration->id,
            'channel_name' => $channel,
            'preview_text' => $result['title'] ?? $task->title,
            'source_url' => $sourceUrl,
            'ai_action' => 'resume_task',
            'ai_reasoning' => $result['reasoning'] ?? null,
            'status' => InboxItemStatus::Accepted,
        ]);

        $task->update(['status' => TaskStatus::Pending]);

        return true;
    }
}
