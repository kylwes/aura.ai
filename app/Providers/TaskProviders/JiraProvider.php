<?php

namespace App\Providers\TaskProviders;

use App\Models\Integration;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class JiraProvider extends TaskProvider
{
    /** @var array<string, array> Cache of fetched issue details keyed by issue key */
    private array $issueDetailsCache = [];

    public function fetch(Integration $integration): array
    {
        $config = $integration->configuration;
        $lastPolled = $this->getLastPolledAt($integration);

        // Fetch notifications (activity stream) via REST API
        $items = [];
        $startAt = 0;
        $maxPages = 5;
        $pageSize = 50;

        do {
            $jql = 'assignee = currentUser() OR watcher = currentUser() OR reporter = currentUser()';

            if ($lastPolled) {
                $jql .= " AND updated >= '{$lastPolled}'";
            } else {
                $jql .= ' AND updated >= -1d';
            }

            $jql .= ' ORDER BY updated DESC';

            $response = $this->request($config, 'GET', '/rest/api/3/search', [
                'jql' => $jql,
                'startAt' => $startAt,
                'maxResults' => $pageSize,
                'fields' => 'summary,status,priority,assignee,reporter,comment,updated,project,issuetype,description',
            ]);

            if (! $response) {
                break;
            }

            $issues = $response->json('issues', []);
            $items = array_merge($items, $issues);

            $total = $response->json('total', 0);
            $startAt += $pageSize;
            $hasMore = $startAt < $total;
            $maxPages--;
        } while ($hasMore && $maxPages > 0);

        // Pre-fetch recent comments for issues that have them
        $this->prefetchIssueDetails($config, $items);

        return $items;
    }

    public function channel(): string
    {
        return 'Jira';
    }

    public function format(array $item): string
    {
        $fields = $item['fields'] ?? [];
        $key = $item['key'] ?? 'Unknown';

        $summary = $fields['summary'] ?? 'Untitled';
        $status = $fields['status']['name'] ?? 'Unknown';
        $priority = $fields['priority']['name'] ?? 'Unknown';
        $issueType = $fields['issuetype']['name'] ?? 'Unknown';
        $project = $fields['project']['name'] ?? '';
        $assignee = $fields['assignee']['displayName'] ?? 'Unassigned';
        $reporter = $fields['reporter']['displayName'] ?? 'Unknown';
        $updated = $fields['updated'] ?? '';

        $lines = [
            'Source: Jira issue',
            "Issue: [{$key}] {$summary}",
            "Type: {$issueType}",
            "Project: {$project}",
            "Status: {$status}",
            "Priority: {$priority}",
            "Assignee: {$assignee}",
            "Reporter: {$reporter}",
        ];

        if ($updated) {
            $lines[] = "Updated: {$updated}";
        }

        // Add description snippet
        $description = $this->extractTextFromAdf($fields['description'] ?? null);
        if ($description) {
            $lines[] = 'Description: '.str($description)->limit(300);
        }

        // Add cached details (recent comments)
        if (isset($this->issueDetailsCache[$key])) {
            $details = $this->issueDetailsCache[$key];

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
        $config = $integration->configuration;
        $domain = rtrim($config['domain'] ?? '', '/');
        $key = $item['key'] ?? null;

        if ($domain && $key) {
            return "{$domain}/browse/{$key}";
        }

        return null;
    }

    /**
     * Validate Jira credentials by fetching the current user.
     */
    public static function validateCredentials(string $domain, string $email, string $apiToken): bool
    {
        $response = Http::withBasicAuth($email, $apiToken)
            ->timeout(10)
            ->get(rtrim($domain, '/').'/rest/api/3/myself');

        return $response->successful();
    }

    /**
     * Pre-fetch recent comments for issues.
     */
    private function prefetchIssueDetails(array $config, array $issues): void
    {
        foreach ($issues as $issue) {
            $key = $issue['key'] ?? null;
            if (! $key) {
                continue;
            }

            $comments = [];
            $commentData = $issue['fields']['comment']['comments'] ?? [];

            // Take last 3 comments
            $recentComments = array_slice($commentData, -3);

            foreach ($recentComments as $comment) {
                $comments[] = [
                    'author' => $comment['author']['displayName'] ?? 'Unknown',
                    'body' => $this->extractTextFromAdf($comment['body'] ?? null),
                ];
            }

            $this->issueDetailsCache[$key] = [
                'recent_comments' => $comments,
            ];
        }
    }

    /**
     * Extract plain text from Jira's Atlassian Document Format (ADF).
     */
    private function extractTextFromAdf(?array $adf): string
    {
        if (! $adf || ! isset($adf['content'])) {
            return '';
        }

        $text = '';

        foreach ($adf['content'] as $block) {
            if (isset($block['content'])) {
                foreach ($block['content'] as $inline) {
                    if (isset($inline['text'])) {
                        $text .= $inline['text'];
                    }
                }
                $text .= "\n";
            }
        }

        return trim($text);
    }

    private function request(array $config, string $method, string $path, array $query = []): ?Response
    {
        $domain = rtrim($config['domain'] ?? '', '/');
        $email = $config['email'] ?? '';
        $apiToken = $config['api_token'] ?? '';

        $response = Http::withBasicAuth($email, $apiToken)
            ->timeout(15)
            ->acceptJson()
            ->{strtolower($method)}($domain.$path, $query);

        if ($response->failed()) {
            logger()->warning('Jira API request failed', [
                'path' => $path,
                'status' => $response->status(),
            ]);

            return null;
        }

        return $response;
    }
}
