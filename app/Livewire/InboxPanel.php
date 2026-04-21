<?php

namespace App\Livewire;

use App\Enums\InboxItemStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Jobs\PollProvidersJob;
use App\Jobs\ScheduleTasksJob;
use App\Models\InboxItem;
use Livewire\Attributes\On;
use Livewire\Component;

class InboxPanel extends Component
{
    public string $activeTab = 'inbox';

    public ?string $sourceFilter = null;

    public ?string $priorityFilter = null;

    public int $activityPerPage = 20;

    public int $activityPage = 1;

    #[On('toggle-inbox')]
    public function toggle(): void
    {
        $this->dispatch('inbox-toggled');
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->activityPage = 1;
    }

    public function refresh(): void
    {
        PollProvidersJob::dispatch(auth()->user());
        $this->dispatch('toast', type: 'info', title: 'Fetching new items', body: 'Checking your integrations for new items');
    }

    public function loadMore(): void
    {
        $this->activityPage++;
    }

    public function accept(int $itemId): void
    {
        $item = InboxItem::where('user_id', auth()->id())->findOrFail($itemId);
        auth()->user()->tasks()->create([
            'integration_id' => $item->integration_id,
            'project_id' => $item->ai_suggested_project_id,
            'title' => str($item->preview_text)->limit(80),
            'description' => $item->preview_text,
            'source_url' => $item->source_url,
            'source_reference' => $item->channel_name,
            'priority' => $item->ai_suggested_priority ?? TaskPriority::Medium->value,
            'estimated_duration' => $item->ai_estimated_duration,
            'status' => TaskStatus::Pending,
        ]);
        $item->update(['status' => InboxItemStatus::Accepted]);

        $this->dispatch('task-created');
        $this->dispatch('toast', type: 'success', title: 'Task created', body: str($item->preview_text)->limit(50));

        ScheduleTasksJob::debounce(auth()->user());
    }

    public function dismiss(int $itemId): void
    {
        InboxItem::where('user_id', auth()->id())
            ->findOrFail($itemId)
            ->update(['status' => InboxItemStatus::Dismissed]);
    }

    public function snooze(int $itemId): void
    {
        InboxItem::where('user_id', auth()->id())
            ->findOrFail($itemId)
            ->update([
                'status' => InboxItemStatus::Snoozed,
                'snoozed_until' => now()->addHours(2),
            ]);
    }

    public function acceptAll(): void
    {
        $items = $this->getItems();
        foreach ($items as $item) {
            $this->accept($item->id);
        }
    }

    public function render()
    {
        $data = ['items' => $this->getItems()];

        if ($this->activeTab === 'activity') {
            $data['activityItems'] = $this->getActivityItems();
        }

        return view('livewire.inbox-panel', $data);
    }

    private function getItems()
    {
        $query = auth()->user()->inboxItems()
            ->where('status', InboxItemStatus::Pending)
            ->with(['integration', 'suggestedProject'])
            ->latest();
        if ($this->sourceFilter) {
            $query->whereHas('integration', fn ($q) => $q->where('type', $this->sourceFilter));
        }
        if ($this->priorityFilter) {
            $query->where('ai_suggested_priority', $this->priorityFilter);
        }

        return $query->get();
    }

    private function getActivityItems()
    {
        return auth()->user()->inboxItems()
            ->with(['integration', 'suggestedProject'])
            ->latest()
            ->paginate($this->activityPerPage, page: $this->activityPage);
    }
}
