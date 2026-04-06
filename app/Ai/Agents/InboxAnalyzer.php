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
#[MaxTokens(4096)]
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

You will receive ONE OR MORE notifications to analyze. For EACH item, decide ONE action:

1. **create_inbox** — This is a NEW actionable task for the user. Something they need to do.
   Examples: assigned ticket, PR review request, direct question needing a response, action item, mentioned in a task that needs attention.

2. **update_task** — This matches an EXISTING task (by source URL, title, or context). The priority or details may need updating.
   Examples: ticket priority changed, blocker added, deadline moved up, new urgent comment on existing task.
   Return the matching task_id.

3. **resume_task** — This notification relates to an ON-HOLD task. The person or client the user was waiting on has responded or taken action, so the task can be resumed.
   Examples: client replied with feedback, coworker finished their part, blocker resolved, approval received.
   Return the matching task_id. The task will be moved back to pending and auto-scheduled.

4. **skip** — Not actionable. Automated notifications, FYI messages, status updates that don't require action.
   Examples: CI passed, someone else closed a task, automated reminders, read-only updates, notifications about your own actions.

When creating or updating:
- Set priority based on urgency: production issues = urgent, blockers = high, normal work = medium, nice-to-have = low
- Estimate duration realistically: quick reply/review = 5-15min, small fix = 30min, bug fix = 60min, feature work = 120min+
- Title should be concise and actionable (start with a verb when possible)
- Match to a project if the notification clearly relates to one (by name, context, or source). Set project_id to the matching project's ID, or null if no match.

Return an array of results, one per notification item, in the same order as provided.
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'results' => $schema->array()->items(
                $schema->object([
                    'item_index' => $schema->integer()->required(),
                    'action' => $schema->string()->enum(['create_inbox', 'update_task', 'resume_task', 'skip'])->required(),
                    'match_task_id' => $schema->integer()->nullable(),
                    'project_id' => $schema->integer()->nullable(),
                    'title' => $schema->string()->nullable(),
                    'priority' => $schema->string()->enum(['urgent', 'high', 'medium', 'low'])->nullable(),
                    'estimated_duration' => $schema->integer()->nullable(),
                    'reasoning' => $schema->string()->required(),
                ])
            )->required(),
        ];
    }

    /**
     * Build context for a batch of notification items.
     *
     * @param  array<int, array{formatted: string, channel: string, sourceUrl: ?string}>  $items
     */
    public static function buildBatchContext(User $user, array $items): string
    {
        $context = "## Incoming Notifications\n\n";

        foreach ($items as $index => $item) {
            $context .= "### Item {$index}\n";
            $context .= "Channel: {$item['channel']}\n";
            if ($item['sourceUrl']) {
                $context .= "Source URL: {$item['sourceUrl']}\n";
            }
            $context .= $item['formatted']."\n\n";
        }

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

        $onHoldTasks = $user->tasks()
            ->where('status', 'on_hold')
            ->select('id', 'title', 'source_url', 'priority')
            ->limit(30)
            ->get();

        if ($onHoldTasks->isNotEmpty()) {
            $context .= "## On-Hold Tasks (waiting for someone else — use resume_task if the blocker is resolved)\n";
            foreach ($onHoldTasks as $task) {
                $url = $task->source_url ? " ({$task->source_url})" : '';
                $context .= "- [ID: {$task->id}] [{$task->priority->value}] \"{$task->title}\"{$url}\n";
            }
            $context .= "\n";
        }

        $pendingInbox = $user->inboxItems()
            ->where('status', 'pending')
            ->select('id', 'preview_text', 'source_url')
            ->limit(20)
            ->get();

        if ($pendingInbox->isNotEmpty()) {
            $context .= "## Pending Inbox Items (avoid duplicates)\n";
            foreach ($pendingInbox as $inboxItem) {
                $url = $inboxItem->source_url ? " ({$inboxItem->source_url})" : '';
                $context .= "- \"{$inboxItem->preview_text}\"{$url}\n";
            }
            $context .= "\n";
        }

        $projects = $user->projects()->select('id', 'title', 'description')->get();

        if ($projects->isNotEmpty()) {
            $context .= "## Projects\n";
            foreach ($projects as $project) {
                $desc = $project->description ? " — {$project->description}" : '';
                $context .= "- [ID: {$project->id}] \"{$project->title}\"{$desc}\n";
            }
            $context .= "\n";
        }

        $context .= 'Analyze ALL notifications and return a result for each item_index. Match to a project if applicable.';

        return $context;
    }
}
