<aside class="flex w-[260px] flex-col border-r border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
    {{-- Mini month calendar --}}
    <div class="border-b border-neutral-200 p-4 dark:border-neutral-800">
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">{{ $viewMonth->format('F Y') }}</span>
            <div class="flex gap-1">
                <button wire:click="previousMonth" class="rounded p-0.5 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                </button>
                <button wire:click="nextMonth" class="rounded p-0.5 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </button>
            </div>
        </div>
        <div class="grid grid-cols-7 gap-0 text-center">
            @foreach (['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'] as $day)
                <span class="py-1 text-[10px] font-medium text-neutral-400 dark:text-neutral-500">{{ $day }}</span>
            @endforeach
            @foreach ($calendarDays as $day)
                <button wire:click="goToDate('{{ $day['date']->format('Y-m-d') }}')"
                        class="flex size-7 items-center justify-center rounded-full text-xs transition-colors
                    {{ $day['isToday'] ? 'bg-accent-600 font-semibold text-white' : '' }}
                    {{ ! $day['isToday'] && $day['inMonth'] ? 'text-neutral-700 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800' : '' }}
                    {{ ! $day['inMonth'] ? 'text-neutral-300 hover:text-neutral-500 dark:text-neutral-600 dark:hover:text-neutral-400' : '' }}">
                    {{ $day['date']->day }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Project layers --}}
    @if ($projects->isNotEmpty())
        <div class="border-b border-neutral-200 px-4 py-3 dark:border-neutral-800">
            <div class="mb-2">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-neutral-400 dark:text-neutral-500">Projects</h3>
            </div>
            <div class="space-y-0.5">
                @foreach ($projects as $project)
                    @php $isHidden = in_array($project->id, $hiddenProjectIds); @endphp
                    <div class="group relative flex items-center gap-1.5 rounded px-1.5 py-1 transition-colors hover:bg-neutral-100 dark:hover:bg-neutral-800"
                         x-data="{ menuOpen: false }">
                        <span class="size-2.5 shrink-0 rounded-full transition-opacity {{ $isHidden ? 'opacity-30' : '' }}"
                              style="background-color: {{ $project->color ?? '#6366f1' }}"></span>
                        <span class="min-w-0 flex-1 truncate text-[11px] transition-opacity {{ $isHidden ? 'text-neutral-400 line-through opacity-50 dark:text-neutral-600' : 'text-neutral-700 dark:text-neutral-300' }}">
                            {{ $project->title }}
                        </span>
                        {{-- Eye toggle --}}
                        <button wire:click="toggleProjectVisibility({{ $project->id }})"
                                class="shrink-0 rounded p-0.5 text-neutral-400 opacity-0 transition-opacity hover:text-neutral-600 group-hover:opacity-100 dark:hover:text-neutral-300 {{ $isHidden ? '!opacity-100 !text-neutral-300 dark:!text-neutral-600' : '' }}"
                                title="{{ $isHidden ? 'Show' : 'Hide' }}">
                            @if ($isHidden)
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12c1.292 4.338 5.31 7.5 10.066 7.5.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                            @else
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.64 0 8.577 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.64 0-8.577-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            @endif
                        </button>
                        {{-- 3-dot menu --}}
                        <button @click.stop="menuOpen = !menuOpen"
                                class="shrink-0 rounded p-0.5 text-neutral-400 opacity-0 transition-opacity hover:text-neutral-600 group-hover:opacity-100 dark:hover:text-neutral-300"
                                :class="{ '!opacity-100': menuOpen }">
                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z"/></svg>
                        </button>
                        {{-- Dropdown menu --}}
                        <div x-show="menuOpen"
                             x-transition.opacity.duration.150ms
                             @click.outside="menuOpen = false"
                             class="absolute right-0 top-full z-50 mt-0.5 w-36 rounded-lg border border-neutral-200 bg-white py-1 shadow-lg dark:border-neutral-700 dark:bg-neutral-800">
                            <button @click="Livewire.dispatch('openModal', { component: 'project-modal', arguments: { projectId: {{ $project->id }} } }); menuOpen = false"
                                    class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-[11px] text-neutral-700 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-700">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                                Edit project
                            </button>
                            <a href="{{ route('tasks') }}?project={{ $project->id }}"
                               wire:navigate
                               @click="menuOpen = false"
                               class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-[11px] text-neutral-700 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-700">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                View tasks
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Unscheduled tasks queue --}}
    <div class="flex-1 overflow-auto p-4">
        <div class="mb-2 flex items-center justify-between">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-neutral-400 dark:text-neutral-500">Unscheduled Tasks</h3>
            <button x-data @click="Livewire.dispatch('openModal', { component: 'create-task-modal' })"
                    class="rounded p-0.5 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300"
                    title="New task">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            </button>
        </div>
        <div class="space-y-1">
            @forelse ($unscheduledTasks as $task)
                <div class="group cursor-grab rounded-md px-2 py-1.5 transition-colors hover:bg-neutral-100 active:cursor-grabbing dark:hover:bg-neutral-800"
                     draggable="true"
                     x-data
                     @dragstart="
                         $event.dataTransfer.setData('application/x-task-id', '{{ $task->id }}');
                         $event.dataTransfer.effectAllowed = 'move';
                         Alpine.store('dragTask', { id: {{ $task->id }}, duration: {{ $task->estimated_duration ?? 60 }} });
                     "
                     @dragend="Alpine.store('dragTask', { id: null, duration: 0 })"
                     @click="Livewire.dispatch('openModal', { component: 'task-detail-modal', arguments: { taskId: {{ $task->id }} } })">
                    <p class="line-clamp-1 text-[11px] font-medium text-neutral-800 dark:text-neutral-200">{{ $task->title }}</p>
                    <div class="mt-0.5 flex items-center gap-1.5">
                        @if ($task->integration)
                            <x-source-icon :type="$task->integration->type" size="sm" />
                        @endif
                        @if ($task->parent_task_id)
                            <span class="text-[9px] text-neutral-400 dark:text-neutral-500" title="Recurring">🔁</span>
                        @endif
                        <x-priority-badge :priority="$task->priority" class="text-[9px]" />
                        @if ($task->estimated_duration)
                            <span class="text-[9px] text-neutral-400 dark:text-neutral-500">{{ $task->formattedDuration() }}</span>
                        @endif
                        @if ($task->project)
                            <span class="size-1.5 rounded-full" style="background-color: {{ $task->project->color }};"></span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="py-4 text-center text-[10px] text-neutral-400 dark:text-neutral-500">No unscheduled tasks</p>
            @endforelse
        </div>
    </div>
</aside>
