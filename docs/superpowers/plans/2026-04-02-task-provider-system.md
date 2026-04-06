# Task Provider System — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build an abstract TaskProvider system that polls external APIs for new items, uses AI to determine if they're tasks, and creates InboxItems or updates existing tasks. Start with Productive.io as the first provider.

**Architecture:** Abstract `TaskProvider` base class with `fetch()`, `channel()`, `format()` methods. `InboxAnalyzer` AI agent analyzes items and decides: skip, create inbox item, or update existing task. `PollProvidersCommand` runs every 5 minutes via scheduler.

**Tech Stack:** Laravel 13, Laravel AI (Anthropic), Productive.io REST API (API key auth, JSON:API format)

---

### Task 1: Abstract TaskProvider Base Class

**Files:**
- Create: `app/Providers/TaskProviders/TaskProvider.php`

- [ ] **Step 1: Create the abstract base class**

```php
<?php

namespace App\Providers\TaskProviders;

use App\Enums\IntegrationType;
use App\Models\Integration;

abstract class TaskProvider
{
    abstract public function fetch(Integration $integration): array;

    abstract public function channel(): string;

    abstract public function format(array $item): string;

    public function getLastPolledAt(Integration $integration): ?string
    {
        return $integration->configuration['last_polled_at'] ?? null;
    }

    public function updateLastPolledAt(Integration $integration): void
    {
        $config = $integration->configuration ?? [];
        $config['last_polled_at'] = now()->toIso8601String();
        $integration->update(['configuration' => $config]);
    }

    public static function for(IntegrationType $type): ?static
    {
        return match ($type) {
            IntegrationType::Productive => new ProductiveProvider,
            default => null,
        };
    }
}
```

- [ ] **Step 2: Commit**

```
git add app/Providers/TaskProviders/TaskProvider.php
git commit -m "feat: add abstract TaskProvider base class"
```

---

### Task 2: Add Productive to IntegrationType

**Files:**
- Modify: `app/Enums/IntegrationType.php`

- [ ] **Step 1: Add Productive case to the enum**

Add after the existing cases:

```php
case Productive = 'productive';
```

Add to `label()`:
```php
IntegrationType::Productive => 'Productive',
```

Add to `iconComponent()`:
```php
IntegrationType::Productive => 'icons.productive',
```

Add to `color()`:
```php
IntegrationType::Productive => '#5046E5',
```

- [ ] **Step 2: Create the Productive icon component**

Create `resources/views/components/icons/productive.blade.php` with a simple SVG icon.

- [ ] **Step 3: Commit**

```
git add app/Enums/IntegrationType.php resources/views/components/icons/productive.blade.php
git commit -m "feat: add Productive integration type"
```

---

### Task 3: ProductiveProvider Implementation

**Files:**
- Create: `app/Providers/TaskProviders/ProductiveProvider.php`
- Test: `tests/Feature/TaskProviders/ProductiveProviderTest.php`

- [ ] **Step 1: Write the test**

```php
<?php

use App\Models\Integration;
use App\Models\User;
use App\Providers\TaskProviders\ProductiveProvider;
use Illuminate\Support\Facades\Http;

it('fetches activities from productive api', function () {
    Http::fake([
        'api.productive.io/api/v2/activities*' => Http::response([
            'data' => [
                [
                    'id' => '1',
                    'type' => 'activities',
                    'attributes' => [
                        'event' => 'create',
                        'item_type' => 'comment',
                        'item_name' => '#42: Fix login bug',
                        'parent_type' => 'task',
                        'parent_name' => '#42: Fix login bug',
                        'root_type' => 'project',
                        'root_name' => 'Web App',
                        'created_at' => '2026-04-02T10:00:00.000+02:00',
                        'task_id' => 42,
                    ],
                    'relationships' => [
                        'creator' => ['data' => ['type' => 'people', 'id' => '5']],
                        'comment' => ['data' => ['type' => 'comments', 'id' => '10']],
                    ],
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $integration = Integration::factory()->create([
        'user_id' => $user->id,
        'type' => 'productive',
        'status' => 'connected',
        'configuration' => [
            'api_token' => 'test-token',
            'organization_id' => '123',
            'person_id' => '5',
        ],
    ]);

    $provider = new ProductiveProvider;
    $items = $provider->fetch($integration);

    expect($items)->toHaveCount(1)
        ->and($items[0]['id'])->toBe('1')
        ->and($items[0]['attributes']['item_name'])->toBe('#42: Fix login bug');
});

it('formats activity for AI analysis', function () {
    $provider = new ProductiveProvider;
    $formatted = $provider->format([
        'id' => '1',
        'type' => 'activities',
        'attributes' => [
            'event' => 'create',
            'item_type' => 'comment',
            'item_name' => '#42: Fix login bug',
            'parent_type' => 'task',
            'parent_name' => '#42: Fix login bug',
            'root_type' => 'project',
            'root_name' => 'Web App',
            'created_at' => '2026-04-02T10:00:00.000+02:00',
            'task_id' => 42,
        ],
        'relationships' => [
            'creator' => ['data' => ['type' => 'people', 'id' => '5']],
        ],
    ]);

    expect($formatted)->toContain('Fix login bug')
        ->toContain('Web App')
        ->toContain('comment');
});

it('returns correct channel name', function () {
    $provider = new ProductiveProvider;
    expect($provider->channel())->toBe('Productive');
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --filter=ProductiveProvider
```

- [ ] **Step 3: Implement ProductiveProvider**

```php
<?php

namespace App\Providers\TaskProviders;

use App\Models\Integration;
use Illuminate\Support\Facades\Http;

class ProductiveProvider extends TaskProvider
{
    public function fetch(Integration $integration): array
    {
        $config = $integration->configuration;
        $lastPolled = $this->getLastPolledAt($integration);

        $query = [
            'filter[person_id]' => $config['person_id'],
            'page[size]' => 50,
        ];

        if ($lastPolled) {
            $query['filter[after]'] = $lastPolled;
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/vnd.api+json',
            'X-Auth-Token' => $config['api_token'],
            'X-Organization-Id' => $config['organization_id'],
        ])
            ->timeout(15)
            ->get('https://api.productive.io/api/v2/activities', $query);

        if ($response->failed()) {
            logger()->warning('Productive API fetch failed', [
                'status' => $response->status(),
                'integration_id' => $integration->id,
            ]);

            return [];
        }

        return $response->json('data', []);
    }

    public function channel(): string
    {
        return 'Productive';
    }

    public function format(array $item): string
    {
        $attrs = $item['attributes'] ?? [];

        $event = $attrs['event'] ?? 'unknown';
        $itemType = $attrs['item_type'] ?? 'unknown';
        $itemName = $attrs['item_name'] ?? 'Untitled';
        $parentName = $attrs['parent_name'] ?? '';
        $rootName = $attrs['root_name'] ?? '';
        $createdAt = $attrs['created_at'] ?? '';
        $taskId = $attrs['task_id'] ?? null;

        $lines = [
            "Source: Productive ({$this->channel()})",
            "Type: {$event} on {$itemType}",
            "Item: {$itemName}",
        ];

        if ($parentName && $parentName !== $itemName) {
            $lines[] = "Task: {$parentName}";
        }

        if ($rootName) {
            $lines[] = "Project: {$rootName}";
        }

        if ($taskId) {
            $lines[] = "Task ID: #{$taskId}";
        }

        if ($createdAt) {
            $lines[] = "When: {$createdAt}";
        }

        return implode("\n", $lines);
    }
}
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --filter=ProductiveProvider
```

- [ ] **Step 5: Commit**

```
git add app/Providers/TaskProviders/ProductiveProvider.php tests/Feature/TaskProviders/ProductiveProviderTest.php
git commit -m "feat: implement ProductiveProvider for activity polling"
```

---

### Task 4: InboxAnalyzer AI Agent

**Files:**
- Create: `app/Ai/Agents/InboxAnalyzer.php`
- Test: `tests/Feature/Ai/InboxAnalyzerTest.php`

- [ ] **Step 1: Write the test**

```php
<?php

use App\Ai\Agents\InboxAnalyzer;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;

it('builds context with existing tasks and inbox items', function () {
    $user = User::factory()->create();

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Fix login bug',
        'source_url' => 'https://app.productive.io/tasks/42',
        'status' => 'pending',
        'priority' => 'high',
    ]);

    $formattedItem = "Source: Productive\nType: create on comment\nItem: #42: Fix login bug\nProject: Web App";

    $context = InboxAnalyzer::buildContext($user, $formattedItem, 'Productive', 'https://app.productive.io/tasks/42');

    expect($context)
        ->toContain('Fix login bug')
        ->toContain('#42')
        ->toContain('Productive')
        ->toContain('Existing Tasks');
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --filter=InboxAnalyzer
```

- [ ] **Step 3: Implement InboxAnalyzer**

```php
<?php

namespace App\Ai\Agents;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::Anthropic)]
#[MaxTokens(1024)]
#[Temperature(0.2)]
class InboxAnalyzer implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        private User $user,
        private string $context,
    ) {}

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You are an AI assistant that analyzes incoming notifications from external tools (Productive, Jira, Gmail, GitHub, Slack, etc.) to determine if they represent actionable tasks for the user.

For each item, decide ONE action:

1. **create_inbox** — This is a NEW actionable task for the user. Something they need to do.
   Examples: assigned ticket, PR review request, direct question, action item from a meeting.

2. **update_task** — This matches an EXISTING task (by source URL or context). The priority or details may need updating.
   Examples: ticket priority changed, blocker added, deadline moved up.
   Return the matching task_id.

3. **skip** — Not actionable. Automated notifications, FYI messages, newsletters, status updates that don't require action.
   Examples: CI passed, someone else's commit, automated reminders, read-only updates.

When creating or updating:
- Set priority based on urgency: production issues = urgent, blockers = high, normal work = medium, nice-to-have = low
- Estimate duration realistically: quick review = 15-30min, bug fix = 60min, feature work = 120min+
- Title should be concise and actionable (start with a verb when possible)
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->enum(['create_inbox', 'update_task', 'skip'])->required(),
            'match_task_id' => $schema->integer()->nullable(),
            'title' => $schema->string()->nullable(),
            'priority' => $schema->string()->enum(['urgent', 'high', 'medium', 'low'])->nullable(),
            'estimated_duration' => $schema->integer()->nullable(),
            'reasoning' => $schema->string()->required(),
        ];
    }

    public static function buildContext(User $user, string $formattedItem, string $channel, ?string $sourceUrl = null): string
    {
        $context = "## Incoming Notification from {$channel}\n";
        $context .= $formattedItem."\n\n";

        if ($sourceUrl) {
            $context .= "Source URL: {$sourceUrl}\n\n";
        }

        // Existing tasks for deduplication
        $existingTasks = $user->tasks()
            ->whereIn('status', ['pending', 'scheduled'])
            ->select('id', 'title', 'source_url', 'priority', 'status')
            ->limit(50)
            ->get();

        $context .= "## Existing Tasks\n";
        if ($existingTasks->isEmpty()) {
            $context .= "No existing tasks.\n\n";
        } else {
            foreach ($existingTasks as $task) {
                $url = $task->source_url ? " ({$task->source_url})" : '';
                $context .= "- [ID: {$task->id}] [{$task->priority->value}] \"{$task->title}\"{$url}\n";
            }
            $context .= "\n";
        }

        // Existing inbox items to avoid duplicates
        $pendingInbox = $user->inboxItems()
            ->where('status', 'pending')
            ->select('id', 'preview_text', 'source_url')
            ->limit(20)
            ->get();

        if ($pendingInbox->isNotEmpty()) {
            $context .= "## Pending Inbox Items (avoid duplicates)\n";
            foreach ($pendingInbox as $item) {
                $context .= "- \"{$item->preview_text}\"\n";
            }
            $context .= "\n";
        }

        $context .= 'Analyze the notification and decide: create_inbox (new task), update_task (existing task changed), or skip (not actionable).';

        return $context;
    }
}
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --filter=InboxAnalyzer
```

- [ ] **Step 5: Commit**

```
git add app/Ai/Agents/InboxAnalyzer.php tests/Feature/Ai/InboxAnalyzerTest.php
git commit -m "feat: add InboxAnalyzer AI agent for notification analysis"
```

---

### Task 5: PollProvidersJob

**Files:**
- Create: `app/Jobs/PollProvidersJob.php`
- Test: `tests/Feature/Jobs/PollProvidersJobTest.php`

- [ ] **Step 1: Write the test**

```php
<?php

use App\Ai\Agents\InboxAnalyzer;
use App\Enums\IntegrationStatus;
use App\Jobs\PollProvidersJob;
use App\Models\InboxItem;
use App\Models\Integration;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Http;

it('creates inbox item when AI says create_inbox', function () {
    Http::fake([
        'api.productive.io/*' => Http::response([
            'data' => [
                [
                    'id' => '1',
                    'type' => 'activities',
                    'attributes' => [
                        'event' => 'create',
                        'item_type' => 'comment',
                        'item_name' => '#99: Deploy hotfix',
                        'parent_type' => 'task',
                        'parent_name' => '#99: Deploy hotfix',
                        'root_type' => 'project',
                        'root_name' => 'Production',
                        'created_at' => now()->toIso8601String(),
                        'task_id' => 99,
                    ],
                    'relationships' => [
                        'creator' => ['data' => ['type' => 'people', 'id' => '5']],
                    ],
                ],
            ],
        ]),
    ]);

    InboxAnalyzer::fake(fn () => [
        'action' => 'create_inbox',
        'match_task_id' => null,
        'title' => 'Deploy hotfix for production',
        'priority' => 'urgent',
        'estimated_duration' => 30,
        'reasoning' => 'Production issue needs immediate attention',
    ]);

    $user = User::factory()->create();
    $integration = Integration::factory()->create([
        'user_id' => $user->id,
        'type' => 'productive',
        'status' => IntegrationStatus::Connected,
        'configuration' => [
            'api_token' => 'test',
            'organization_id' => '1',
            'person_id' => '5',
        ],
    ]);

    PollProvidersJob::dispatchSync($user);

    expect(InboxItem::where('user_id', $user->id)->count())->toBe(1);

    $item = InboxItem::where('user_id', $user->id)->first();
    expect($item->preview_text)->toBe('Deploy hotfix for production')
        ->and($item->ai_suggested_priority)->toBe('urgent')
        ->and($item->integration_id)->toBe($integration->id)
        ->and($item->channel_name)->toBe('Productive');
});

it('updates existing task when AI says update_task', function () {
    Http::fake([
        'api.productive.io/*' => Http::response([
            'data' => [
                [
                    'id' => '2',
                    'type' => 'activities',
                    'attributes' => [
                        'event' => 'update',
                        'item_type' => 'task',
                        'item_name' => '#50: Fix auth flow',
                        'parent_type' => 'project',
                        'parent_name' => 'Web App',
                        'root_type' => 'project',
                        'root_name' => 'Web App',
                        'created_at' => now()->toIso8601String(),
                        'task_id' => 50,
                    ],
                    'relationships' => [],
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create();

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Fix auth flow',
        'priority' => 'medium',
        'status' => 'pending',
    ]);

    InboxAnalyzer::fake(fn () => [
        'action' => 'update_task',
        'match_task_id' => $task->id,
        'title' => null,
        'priority' => 'high',
        'estimated_duration' => null,
        'reasoning' => 'Priority escalated in Productive',
    ]);

    $integration = Integration::factory()->create([
        'user_id' => $user->id,
        'type' => 'productive',
        'status' => IntegrationStatus::Connected,
        'configuration' => [
            'api_token' => 'test',
            'organization_id' => '1',
            'person_id' => '5',
        ],
    ]);

    PollProvidersJob::dispatchSync($user);

    $task->refresh();
    expect($task->priority->value)->toBe('high');
});

it('skips items when AI says skip', function () {
    Http::fake([
        'api.productive.io/*' => Http::response([
            'data' => [
                [
                    'id' => '3',
                    'type' => 'activities',
                    'attributes' => [
                        'event' => 'update',
                        'item_type' => 'task',
                        'item_name' => 'Automated deploy',
                        'parent_type' => 'project',
                        'parent_name' => 'CI',
                        'root_type' => 'project',
                        'root_name' => 'CI',
                        'created_at' => now()->toIso8601String(),
                        'task_id' => null,
                    ],
                    'relationships' => [],
                ],
            ],
        ]),
    ]);

    InboxAnalyzer::fake(fn () => [
        'action' => 'skip',
        'match_task_id' => null,
        'title' => null,
        'priority' => null,
        'estimated_duration' => null,
        'reasoning' => 'Automated CI notification, not actionable',
    ]);

    $user = User::factory()->create();
    Integration::factory()->create([
        'user_id' => $user->id,
        'type' => 'productive',
        'status' => IntegrationStatus::Connected,
        'configuration' => [
            'api_token' => 'test',
            'organization_id' => '1',
            'person_id' => '5',
        ],
    ]);

    PollProvidersJob::dispatchSync($user);

    expect(InboxItem::where('user_id', $user->id)->count())->toBe(0);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --filter=PollProvidersJob
```

- [ ] **Step 3: Implement PollProvidersJob**

```php
<?php

namespace App\Jobs;

use App\Ai\Agents\InboxAnalyzer;
use App\Enums\InboxItemStatus;
use App\Enums\IntegrationStatus;
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
                $this->processItem($provider, $integration, $item);
            }

            $provider->updateLastPolledAt($integration);
        }
    }

    private function processItem(TaskProvider $provider, $integration, array $item): void
    {
        $formatted = $provider->format($item);
        $sourceUrl = $this->extractSourceUrl($item, $integration);

        $context = InboxAnalyzer::buildContext($this->user, $formatted, $provider->channel(), $sourceUrl);
        $agent = new InboxAnalyzer($this->user, $context);
        $result = $agent->prompt($context);

        match ($result['action']) {
            'create_inbox' => $this->createInboxItem($integration, $result, $sourceUrl, $provider->channel()),
            'update_task' => $this->updateTask($result),
            default => null, // skip
        };
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
            'status' => InboxItemStatus::Pending,
        ]);
    }

    private function updateTask(array $result): void
    {
        if (! $result['match_task_id']) {
            return;
        }

        $task = $this->user->tasks()->find($result['match_task_id']);

        if (! $task) {
            return;
        }

        $updates = [];

        if ($result['priority'] && $result['priority'] !== $task->priority->value) {
            $updates['priority'] = $result['priority'];
        }

        if ($result['estimated_duration'] && ! $task->estimated_duration) {
            $updates['estimated_duration'] = $result['estimated_duration'];
        }

        if (! empty($updates)) {
            $task->update($updates);

            // Reschedule if priority changed and task is scheduled
            if (isset($updates['priority']) && $task->status->value === 'scheduled' && $task->is_ai_scheduled) {
                ScheduleTasksJob::dispatch($this->user);
            }
        }
    }

    private function extractSourceUrl(array $item, $integration): ?string
    {
        $taskId = $item['attributes']['task_id'] ?? null;
        $orgId = $integration->configuration['organization_id'] ?? null;

        if ($taskId && $orgId) {
            return "https://app.productive.io/{$orgId}/tasks/{$taskId}";
        }

        return null;
    }
}
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --filter=PollProvidersJob
```

- [ ] **Step 5: Commit**

```
git add app/Jobs/PollProvidersJob.php tests/Feature/Jobs/PollProvidersJobTest.php
git commit -m "feat: add PollProvidersJob for provider polling and AI analysis"
```

---

### Task 6: Schedule the Polling Command

**Files:**
- Modify: `routes/console.php`

- [ ] **Step 1: Add scheduler entry**

Add to `routes/console.php`:

```php
use App\Jobs\PollProvidersJob;
use App\Enums\IntegrationStatus;
use App\Models\User;

Schedule::call(function () {
    User::whereHas('integrations', function ($query) {
        $query->where('status', IntegrationStatus::Connected);
    })->each(function (User $user) {
        PollProvidersJob::dispatch($user);
    });
})->everyFiveMinutes()->name('poll-task-providers');
```

- [ ] **Step 2: Run all tests**

```bash
php artisan test --compact
```

- [ ] **Step 3: Commit**

```
git add routes/console.php
git commit -m "feat: schedule provider polling every 5 minutes"
```

---

### Task 7: Update TaskProvider Registry for Future Providers

**Files:**
- Modify: `app/Providers/TaskProviders/TaskProvider.php`

- [ ] **Step 1: Ensure the registry is extensible**

The `for()` method already uses a match statement. Future providers (Gmail, GitHub, Jira, Slack) just add a case:

```php
public static function for(IntegrationType $type): ?static
{
    return match ($type) {
        IntegrationType::Productive => new ProductiveProvider,
        // IntegrationType::Gmail => new GmailProvider,
        // IntegrationType::GitHub => new GitHubProvider,
        // IntegrationType::Jira => new JiraProvider,
        // IntegrationType::Slack => new SlackProvider,
        default => null,
    };
}
```

- [ ] **Step 2: Run full test suite and format**

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact
```

- [ ] **Step 3: Final commit**

```
git add -A
git commit -m "feat: complete task provider system with Productive integration"
```
