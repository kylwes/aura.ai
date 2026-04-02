<div class="flex h-full w-full flex-col">
    {{-- Toolbar --}}
    <div class="flex items-center justify-between border-b border-neutral-200 bg-white px-6 py-3 dark:border-neutral-800 dark:bg-neutral-900">
        <div class="flex items-center gap-3">
            <h1 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">Projects</h1>
            <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-[10px] font-medium text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400">{{ $projects->count() }}</span>
        </div>

        <button x-data @click="Livewire.dispatch('openModal', { component: 'project-modal' })"
                class="inline-flex items-center gap-1.5 rounded-lg bg-accent-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm transition-colors hover:bg-accent-700">
            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            New project
        </button>
    </div>

    {{-- Content --}}
    <div class="flex-1 overflow-auto">
        <div class="mx-auto max-w-3xl px-6 py-4">
            @forelse ($projects as $project)
                @php
                    $color = $project->color ?? '#6366f1';
                    $scheduleDays = $project->schedules
                        ->pluck('day')
                        ->unique()
                        ->sort()
                        ->map(fn ($day) => match ($day) {
                            1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu',
                            5 => 'Fri', 6 => 'Sat', 7 => 'Sun', default => '',
                        })
                        ->filter()
                        ->values();
                @endphp

                <button wire:key="project-{{ $project->id }}"
                        x-data
                        @click="Livewire.dispatch('openModal', { component: 'project-modal', arguments: { projectId: {{ $project->id }} } })"
                        class="flex w-full cursor-pointer items-center gap-3 border-b border-neutral-100 px-2 py-3.5 text-left transition-colors hover:bg-neutral-50/60 dark:border-neutral-800/60 dark:hover:bg-neutral-800/30">

                    {{-- Color dot --}}
                    <span class="size-2.5 shrink-0 rounded-full" style="background-color: {{ $color }};"></span>

                    {{-- Title + description --}}
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ $project->title }}</p>
                        @if ($project->description)
                            <p class="mt-0.5 truncate text-xs text-neutral-400 dark:text-neutral-500">{{ Str::limit($project->description, 100) }}</p>
                        @endif
                    </div>

                    {{-- Meta --}}
                    <div class="flex shrink-0 items-center gap-2.5">
                        @if ($scheduleDays->isNotEmpty())
                            <span class="text-[10px] text-neutral-400 dark:text-neutral-500">{{ $scheduleDays->implode(', ') }}</span>
                        @endif

                        @if ($project->starts_at || $project->ends_at)
                            <span class="text-[10px] text-neutral-400 dark:text-neutral-500">
                                @if ($project->starts_at && $project->ends_at)
                                    {{ $project->starts_at->format('M j') }} – {{ $project->ends_at->format('M j') }}
                                @elseif ($project->starts_at)
                                    From {{ $project->starts_at->format('M j') }}
                                @else
                                    Until {{ $project->ends_at->format('M j') }}
                                @endif
                            </span>
                        @endif

                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium"
                              style="background-color: {{ $color }}15; color: {{ $color }};">
                            {{ $project->tasks_count }} {{ Str::plural('task', $project->tasks_count) }}
                        </span>
                    </div>
                </button>
            @empty
                <div class="flex flex-col items-center justify-center py-20 text-center">
                    <svg class="size-12 text-neutral-300 dark:text-neutral-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z"/></svg>
                    <p class="mt-3 text-sm font-medium text-neutral-500 dark:text-neutral-400">No projects yet</p>
                    <p class="mt-1 text-xs text-neutral-400 dark:text-neutral-500">Create a project to organize your tasks</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
