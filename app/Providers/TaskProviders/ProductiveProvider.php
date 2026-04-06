<?php

namespace App\Providers\TaskProviders;

use App\Models\Integration;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ProductiveProvider extends TaskProvider
{
    /** @var array<string, array> Cache of fetched task details keyed by task ID */
    private array $taskDetailsCache = [];

    public function fetch(Integration $integration): array
    {
        $config = $integration->configuration;
        $lastPolled = $this->getLastPolledAt($integration);

        $items = [];
        $page = 1;
        $maxPages = 5; // Safety limit: 5 × 50 = 250 notifications max

        do {
            $query = [
                'filter[dismissed]' => 'false',
                'page[size]' => 50,
                'page[number]' => $page,
            ];

            // if ($lastPolled) {
            //     $query['filter[date_after]'] = $lastPolled;
            // }

            $response = $this->request($config, 'GET', '/api/v2/notifications', $query);

            if (! $response) {
                break;
            }

            $pageItems = $response->json('data', []);
            // ray($pageItems);
            $items = array_merge($items, $pageItems);

            $hasMore = count($pageItems) === 50;
            $page++;
        } while ($hasMore && $page <= $maxPages);

        // Batch-fetch task details for all task-related notifications
        $taskIds = collect($items)
            ->filter(fn ($item) => ($item['attributes']['target_type'] ?? '') === 'Task' && ($item['attributes']['target_id'] ?? null))
            ->pluck('attributes.target_id')
            ->unique()
            ->values()
            ->all();

        if (! empty($taskIds)) {
            $this->prefetchTaskDetails($config, $taskIds);
        }

        return $items;
    }

    public function channel(): string
    {
        return 'Productive';
    }

    public function format(array $item): string
    {
        $attrs = $item['attributes'] ?? [];

        $title = $attrs['title'] ?? 'Untitled';
        $excerpt = $attrs['excerpt'] ?? '';
        $targetTitle = $attrs['target_title'] ?? '';
        $targetType = $attrs['target_type'] ?? '';
        $parentTitle = $attrs['parent_title'] ?? '';
        $important = $attrs['important'] ?? false;
        $mention = $attrs['mention'] ?? false;
        $lastAction = $attrs['last_action_at'] ?? '';
        $targetId = $attrs['target_id'] ?? null;

        $lines = [
            'Source: Productive notification',
            "Title: {$title}",
        ];

        if ($targetTitle) {
            $lines[] = "Target: [{$targetType}] {$targetTitle}";
        }

        if ($parentTitle && $parentTitle !== $targetTitle) {
            $lines[] = "Project: {$parentTitle}";
        }

        if ($excerpt) {
            $lines[] = "Excerpt: {$excerpt}";
        }

        if ($important) {
            $lines[] = 'Marked as: Important';
        }

        if ($mention) {
            $lines[] = 'You were: Mentioned';
        }

        if ($lastAction) {
            $lines[] = "When: {$lastAction}";
        }

        // Enrich with task details if available
        if ($targetType === 'Task' && $targetId && isset($this->taskDetailsCache[$targetId])) {
            $details = $this->taskDetailsCache[$targetId];
            $lines[] = '';
            $lines[] = '--- Task Details ---';

            if ($details['description'] ?? null) {
                $lines[] = 'Description: '.str($details['description'])->limit(500);
            }

            if ($details['status'] ?? null) {
                $lines[] = "Status: {$details['status']}";
            }

            if ($details['budget_name'] ?? null) {
                $lines[] = "Budget: {$details['budget_name']}";
            }

            if ($details['budget_status'] ?? null) {
                $lines[] = "Budget status: {$details['budget_status']}";
            }

            if (! empty($details['recent_comments'])) {
                $lines[] = '';
                $lines[] = 'Recent comments:';
                foreach (array_slice($details['recent_comments'], 0, 3) as $comment) {
                    $author = $comment['author'] ?? 'Unknown';
                    $body = str($comment['body'] ?? '')->limit(200);
                    $lines[] = "  - {$author}: {$body}";
                }
            }
        }

        return implode("\n", $lines);
    }

    public function sourceUrl(array $item, Integration $integration): ?string
    {
        $attrs = $item['attributes'] ?? [];
        $targetId = $attrs['target_id'] ?? null;
        $targetType = $attrs['target_type'] ?? null;
        $orgId = $integration->configuration['organization_id'] ?? null;

        if ($targetId && $orgId && $targetType === 'Task') {
            return "https://app.productive.io/{$orgId}/tasks/{$targetId}";
        }

        return null;
    }

    public static function fetchOrganizationId(string $apiToken): ?string
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/vnd.api+json',
            'X-Auth-Token' => $apiToken,
        ])
            ->timeout(10)
            ->get('https://api.productive.io/api/v2/organizations');

        if ($response->failed()) {
            return null;
        }

        return $response->json('data.0.id');
    }

    /**
     * Batch-fetch task details for multiple task IDs.
     *
     * @param  array<int|string>  $taskIds
     */
    private function prefetchTaskDetails(array $config, array $taskIds): void
    {
        foreach ($taskIds as $taskId) {
            $taskResponse = $this->request($config, 'GET', "/api/v2/tasks/{$taskId}", [
                'include' => 'comments,project',
            ]);

            if (! $taskResponse) {
                continue;
            }

            $taskData = $taskResponse->json('data.attributes', []);
            $included = collect($taskResponse->json('included', []));

            // Extract project info
            $project = $included->firstWhere('type', 'projects');
            $budgetName = $project ? ($project['attributes']['name'] ?? null) : null;

            // Extract recent comments
            $comments = $included->where('type', 'comments')
                ->sortByDesc('attributes.created_at')
                ->take(3)
                ->map(fn ($c) => [
                    'author' => $c['attributes']['creator_name'] ?? 'Unknown',
                    'body' => strip_tags($c['attributes']['body'] ?? ''),
                ])
                ->values()
                ->all();

            $this->taskDetailsCache[$taskId] = [
                'description' => strip_tags($taskData['description'] ?? ''),
                'status' => $taskData['workflow_status_name'] ?? null,
                'budget_name' => $budgetName,
                'budget_status' => $taskData['budget_status'] ?? null,
                'recent_comments' => $comments,
            ];
        }
    }

    private function request(array $config, string $method, string $path, array $query = []): ?Response
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/vnd.api+json',
            'X-Auth-Token' => $config['api_token'],
            'X-Organization-Id' => $config['organization_id'],
        ])
            ->timeout(15)
            ->{strtolower($method)}('https://api.productive.io'.$path, $query);

        if ($response->failed()) {
            logger()->warning('Productive API request failed', [
                'path' => $path,
                'status' => $response->status(),
            ]);

            return null;
        }

        return $response;
    }
}
