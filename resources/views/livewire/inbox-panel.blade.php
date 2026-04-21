<div x-data="{ open: false }"
     @inbox-toggled.window="open = !open"
     @keydown.escape.window="open = false">

    <div x-show="open" x-transition.opacity class="fixed inset-0 z-30 bg-black/20 lg:hidden" @click="open = false"></div>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed right-0 top-0 z-40 flex h-full w-[400px] flex-col border-l border-neutral-200 bg-white shadow-2xl dark:border-neutral-800 dark:bg-neutral-900">

        {{-- Header --}}
        <div class="border-b border-neutral-200 p-4 dark:border-neutral-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Inbox</h2>
                    @if ($activeTab === 'inbox')
                        <span class="rounded-full bg-accent-100 px-2 py-0.5 text-xs font-medium text-accent-700 dark:bg-accent-900 dark:text-accent-300">
                            {{ $items->count() }} new
                        </span>
                    @endif
                </div>
                <div class="flex items-center gap-1">
                    <button wire:click="refresh" title="Refresh"
                            class="rounded-lg p-1.5 text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800">
                        <svg class="size-5" wire:loading.class="animate-spin" wire:target="refresh" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.992 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182M20.016 4.357v4.992"/></svg>
                    </button>
                    <button @click="open = false" class="rounded-lg p-1.5 text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="mt-3 flex gap-1 rounded-lg bg-neutral-100 p-0.5 dark:bg-neutral-800">
                <button wire:click="switchTab('inbox')"
                        class="flex-1 rounded-md px-3 py-1.5 text-xs font-medium transition-colors {{ $activeTab === 'inbox' ? 'bg-white text-neutral-900 shadow-sm dark:bg-neutral-700 dark:text-neutral-100' : 'text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200' }}">
                    Inbox
                </button>
                <button wire:click="switchTab('activity')"
                        class="flex-1 rounded-md px-3 py-1.5 text-xs font-medium transition-colors {{ $activeTab === 'activity' ? 'bg-white text-neutral-900 shadow-sm dark:bg-neutral-700 dark:text-neutral-100' : 'text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200' }}">
                    Activity
                </button>
            </div>

            {{-- Source filter (inbox tab only) --}}
            @if ($activeTab === 'inbox')
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach ([null => 'All', 'jira' => 'Jira', 'slack' => 'Slack', 'gmail' => 'Gmail', 'github' => 'GitHub'] as $value => $label)
                        <button wire:click="$set('sourceFilter', {{ $value === null ? 'null' : "'$value'" }})"
                                class="rounded-full px-2.5 py-1 text-xs font-medium transition-colors {{ $sourceFilter === $value ? 'bg-accent-100 text-accent-700 dark:bg-accent-900 dark:text-accent-300' : 'bg-neutral-100 text-neutral-500 hover:text-neutral-700 dark:bg-neutral-800 dark:text-neutral-400' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Content --}}
        @if ($activeTab === 'inbox')
            <div class="flex-1 space-y-2 overflow-auto p-4">
                @forelse ($items as $item)
                    <x-inbox-item :item="$item" />
                @empty
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <svg class="size-12 text-neutral-300 dark:text-neutral-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        <p class="mt-3 text-sm font-medium text-neutral-500 dark:text-neutral-400">All caught up</p>
                        <p class="mt-1 text-xs text-neutral-400 dark:text-neutral-500">No pending items in your inbox</p>
                    </div>
                @endforelse
            </div>

            @if ($items->isNotEmpty())
                <div class="border-t border-neutral-200 p-4 dark:border-neutral-800">
                    <div class="flex gap-2">
                        <button wire:click="acceptAll"
                                class="flex-1 rounded-lg bg-accent-600 px-4 py-2 text-xs font-medium text-white hover:bg-accent-700">
                            Accept all suggested
                        </button>
                        <button class="flex-1 inline-flex items-center justify-center gap-1 rounded-lg border border-accent-300 px-4 py-2 text-xs font-medium text-accent-600 hover:bg-accent-50 dark:border-accent-700 dark:text-accent-400 dark:hover:bg-accent-950/30">
                            <x-icons.sparkle class="size-3" />
                            Let AI decide
                        </button>
                    </div>
                </div>
            @endif
        @else
            {{-- Activity tab --}}
            <div class="flex-1 overflow-auto p-4">
                @forelse ($activityItems as $item)
                    <div class="mb-3 rounded-xl bg-neutral-50 px-4 py-3 dark:bg-neutral-800/50">
                        <div class="flex items-start gap-3">
                            @if ($item->integration)
                                <x-source-icon :type="$item->integration->type" size="sm" class="mt-0.5 shrink-0" />
                            @else
                                <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-neutral-200 dark:bg-neutral-700">
                                    <svg class="size-3 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                </span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="flex-1 truncate text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ $item->preview_text }}</p>
                                    @php
                                        $actionConfig = match($item->ai_action) {
                                            'create_inbox' => ['label' => 'Created', 'class' => 'bg-accent-100 text-accent-700 dark:bg-accent-900/30 dark:text-accent-400'],
                                            'update_task' => ['label' => 'Updated', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'],
                                            'resume_task' => ['label' => 'Resumed', 'class' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'],
                                            'skip' => ['label' => 'Skipped', 'class' => 'bg-neutral-100 text-neutral-500 dark:bg-neutral-700 dark:text-neutral-400'],
                                            default => ['label' => $item->status->value, 'class' => 'bg-neutral-100 text-neutral-500 dark:bg-neutral-700 dark:text-neutral-400'],
                                        };
                                    @endphp
                                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium {{ $actionConfig['class'] }}">
                                        {{ $actionConfig['label'] }}
                                    </span>
                                </div>

                                <div class="mt-1 flex items-center gap-2 text-[11px] text-neutral-400 dark:text-neutral-500">
                                    @if ($item->channel_name)
                                        <span>{{ ucfirst($item->channel_name) }}</span>
                                        <span>&middot;</span>
                                    @endif
                                    <span>{{ $item->created_at->diffForHumans() }}</span>
                                </div>

                                @if ($item->ai_reasoning)
                                    <div class="mt-2 flex gap-2 rounded-lg bg-white/60 px-3 py-2 dark:bg-neutral-800">
                                        <svg class="mt-0.5 size-3 shrink-0 text-accent-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z"/></svg>
                                        <p class="text-xs leading-relaxed text-neutral-600 dark:text-neutral-400">{{ $item->ai_reasoning }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <svg class="size-12 text-neutral-300 dark:text-neutral-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        <p class="mt-3 text-sm font-medium text-neutral-500 dark:text-neutral-400">No activity yet</p>
                        <p class="mt-1 text-xs text-neutral-400 dark:text-neutral-500">Items from your integrations will appear here</p>
                    </div>
                @endforelse

                @if ($activityItems->hasMorePages())
                    <div class="mt-2 text-center">
                        <button wire:click="loadMore"
                                class="rounded-lg px-4 py-2 text-xs font-medium text-neutral-500 transition-colors hover:bg-neutral-100 hover:text-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-neutral-200">
                            <span wire:loading.remove wire:target="loadMore">Load more</span>
                            <span wire:loading wire:target="loadMore">Loading...</span>
                        </button>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
