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

        <div class="border-b border-neutral-200 p-4 dark:border-neutral-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Inbox</h2>
                    <span class="rounded-full bg-accent-100 px-2 py-0.5 text-xs font-medium text-accent-700 dark:bg-accent-900 dark:text-accent-300">
                        {{ $items->count() }} new
                    </span>
                </div>
                <button @click="open = false" class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="mt-3 flex flex-wrap gap-1.5">
                @foreach ([null => 'All', 'jira' => 'Jira', 'slack' => 'Slack', 'gmail' => 'Gmail', 'github' => 'GitHub'] as $value => $label)
                    <button wire:click="$set('sourceFilter', {{ $value === null ? 'null' : "'$value'" }})"
                            class="rounded-full px-2.5 py-1 text-xs font-medium transition-colors {{ $sourceFilter === $value ? 'bg-accent-100 text-accent-700 dark:bg-accent-900 dark:text-accent-300' : 'bg-neutral-100 text-neutral-500 hover:text-neutral-700 dark:bg-neutral-800 dark:text-neutral-400' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

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
    </div>
</div>
