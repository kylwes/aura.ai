# Task Provider System

## Purpose

Automatically poll external data sources (Gmail, Jira, GitHub, Slack, etc.) for new items, use AI to determine if they represent tasks for the user, deduplicate against existing tasks, and either create inbox items or update existing tasks.

## Architecture

```
Integration (OAuth tokens, last_polled_at)
    ↓
PollProvidersCommand (scheduled every 5 min)
    ↓ per connected integration
TaskProvider::fetch() → raw items
    ↓
TaskProvider::format() → AI-readable strings
    ↓
InboxAnalyzer AI Agent → per item decides:
    - skip: not a task
    - create_inbox: new InboxItem for user review
    - update_task: update existing task's priority/description
    ↓
InboxItem or Task update
```

## Components

### 1. TaskProvider (Abstract Base Class)

`app/Providers/TaskProviders/TaskProvider.php`

```php
abstract class TaskProvider
{
    abstract public function fetch(Integration $integration): array;
    abstract public function channel(): string;
    abstract public function format(array $item): string;
}
```

- `fetch()` — calls the external API, returns raw items since `last_polled_at`
- `channel()` — human-readable name (e.g. "Gmail", "GitHub")
- `format()` — converts a raw item into a string for the AI to analyze

Base class handles:
- Tracking `last_polled_at` in `Integration.configuration`
- Provider registry (maps IntegrationType to provider class)

### 2. Concrete Providers

Initial providers to implement:
- `GmailProvider` — fetches unread emails via Gmail API
- `GitHubProvider` — fetches notifications/mentions via GitHub API
- `JiraProvider` — fetches assigned/mentioned tickets via Jira API
- `SlackProvider` — fetches DMs/mentions via Slack API

Each provider is a simple class: fetch from API, format for AI.

### 3. InboxAnalyzer AI Agent

`app/Ai/Agents/InboxAnalyzer.php`

Receives per item:
- Formatted item from provider
- User's existing tasks (titles, source_urls, priorities, statuses)
- User's existing pending inbox items (to avoid duplicates)

Returns structured output:
```json
{
  "action": "create_inbox" | "update_task" | "skip",
  "match_task_id": null | 123,
  "title": "Review PR #456 for auth refactor",
  "priority": "high",
  "estimated_duration": 30,
  "reasoning": "PR review requested by teammate, blocking their work"
}
```

Actions:
- `skip` — not actionable (newsletters, FYI messages, automated alerts)
- `create_inbox` — new task candidate → InboxItem with AI priority
- `update_task` — matches existing task → update priority/description if context changed

### 4. PollProvidersCommand

`app/Console/Commands/PollProvidersCommand.php`

Scheduled every 5 minutes. For each user with connected integrations:
1. Get all connected integrations
2. For each, resolve the matching TaskProvider
3. Call `fetch()` to get new items
4. For each item, call `format()` then send to InboxAnalyzer
5. Execute the AI's decision (create inbox item or update task)
6. Update `last_polled_at`

### 5. Provider Registry

Maps `IntegrationType` to provider class. Lives on the abstract `TaskProvider`:

```php
public static function for(IntegrationType $type): ?static
```

Returns `null` for types without a provider (e.g. GoogleCalendar uses its own sync).

## Data Flow

### New item → InboxItem
1. Provider fetches email/ticket/PR
2. AI says "create_inbox" with title, priority, duration
3. InboxItem created with `integration_id`, `source_url`, `ai_suggested_priority`
4. User sees it in inbox panel, accepts → becomes Task → gets scheduled

### Existing task update
1. Provider fetches updated ticket (e.g. Jira priority changed to Critical)
2. AI matches it to existing task by `source_url` or context
3. AI says "update_task" with new priority
4. Task priority updated, reschedule triggered if needed

### Duplicate detection
1. Provider fetches email about same PR that's already a task
2. AI sees matching task in context, says "skip"
3. No action taken

## Integration Configuration

Add to `Integration.configuration` JSON:
- `last_polled_at` — ISO datetime of last successful poll
- Provider-specific config (e.g. Gmail label filters, Jira project keys)

## Scheduling

In `routes/console.php` or `app/Console/Kernel.php`:
```php
Schedule::command('providers:poll')->everyFiveMinutes();
```

## Error Handling

- API failures: log and skip, retry on next poll
- Rate limits: respect `Retry-After` headers, skip provider until cooldown
- AI failures: log item, leave for next poll cycle
- OAuth expired: mark integration as needing re-auth

## Future Enhancements

- Webhook support for real-time updates (no polling needed)
- Per-provider polling frequency configuration
- Provider-specific filters (e.g. only certain Gmail labels, specific Jira projects)
