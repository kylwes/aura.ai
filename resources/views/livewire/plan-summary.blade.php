<div class="mx-auto max-w-5xl px-6 py-8">
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-2">
            <x-icons.sparkle class="size-6 text-accent-600" />
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-neutral-100">AI Schedule Proposal</h1>
        </div>
        <p class="text-sm text-neutral-500 dark:text-neutral-400">Here's how I'd organize your upcoming tasks. Review and approve.</p>
        <div class="mt-4 flex items-center gap-3">
            <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-medium text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400">{{ $totalTasks }} tasks scheduled</span>
            <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-medium text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400">{{ $totalDuration }} total</span>
        </div>
    </div>

    <div class="space-y-3">
        @forelse ($tasks as $task)
            <x-plan-diff-row :task="$task" :approved="isset($approved[$task->id])" />
        @empty
            <div class="rounded-xl bg-white p-12 text-center ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800">
                <x-icons.sparkle class="mx-auto size-10 text-neutral-300 dark:text-neutral-600" />
                <p class="mt-3 text-sm font-medium text-neutral-500 dark:text-neutral-400">No tasks to schedule</p>
                <p class="mt-1 text-xs text-neutral-400 dark:text-neutral-500">Accept items from your inbox first</p>
            </div>
        @endforelse
    </div>

    @if ($tasks->isNotEmpty())
        <div class="sticky bottom-0 mt-6 flex items-center justify-between rounded-xl bg-white/80 p-4 shadow-lg ring-1 ring-neutral-200 backdrop-blur-sm dark:bg-neutral-900/80 dark:ring-neutral-800">
            <button class="rounded-lg border border-neutral-200 px-4 py-2 text-sm font-medium text-neutral-600 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800">Redo</button>
            <button wire:click="approveAll" class="inline-flex items-center gap-1.5 rounded-lg bg-accent-600 px-6 py-2 text-sm font-medium text-white shadow-sm hover:bg-accent-700 transition-colors">
                <x-icons.sparkle class="size-3.5" />
                Approve All
            </button>
        </div>
    @endif
</div>
