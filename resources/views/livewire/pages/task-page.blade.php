<div class="flex h-full w-full flex-col">
    {{-- Toolbar --}}
    <div class="flex items-center justify-between border-b border-neutral-200 bg-white px-6 py-3 dark:border-neutral-800 dark:bg-neutral-900">
        <div class="flex items-center gap-4">
            {{-- View toggle --}}
            <div class="flex rounded-lg bg-neutral-100 p-0.5 dark:bg-neutral-800">
                <button wire:click="switchView('list')"
                        class="rounded-md px-2.5 py-1 text-xs font-medium transition-colors {{ $view === 'list' ? 'bg-white text-neutral-900 shadow-sm dark:bg-neutral-700 dark:text-neutral-100' : 'text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200' }}">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"/></svg>
                </button>
                <button wire:click="switchView('board')"
                        class="rounded-md px-2.5 py-1 text-xs font-medium transition-colors {{ $view === 'board' ? 'bg-white text-neutral-900 shadow-sm dark:bg-neutral-700 dark:text-neutral-100' : 'text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200' }}">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125Z"/></svg>
                </button>
            </div>

            {{-- Filters --}}
            @foreach (['all' => 'All', 'pending' => 'Active', 'completed' => 'Completed', 'urgent' => 'Urgent'] as $value => $label)
                <button wire:click="$set('filter', '{{ $value }}')"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium transition-colors {{ $filter === $value ? 'bg-accent-50 text-accent-700 dark:bg-accent-900/30 dark:text-accent-400' : 'text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200' }}">
                    {{ $label }}
                    @if (isset($counts[$value]))
                        <span class="ml-1 text-[10px] {{ $filter === $value ? 'text-accent-500' : 'text-neutral-400 dark:text-neutral-500' }}">{{ $counts[$value] }}</span>
                    @endif
                </button>
            @endforeach

            {{-- Project filter --}}
            @if ($projects->isNotEmpty())
                <select wire:model.live="project"
                        class="h-8 rounded-lg border-0 bg-neutral-100 px-2.5 pr-7 text-xs font-medium text-neutral-700 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-800 dark:text-neutral-300">
                    <option value="">All projects</option>
                    @foreach ($projects as $p)
                        <option value="{{ $p->id }}">
                            {{ $p->title }}
                        </option>
                    @endforeach
                </select>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <div class="relative">
                <svg class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input wire:model.live.debounce.300ms="search"
                       type="text"
                       placeholder="Search tasks..."
                       class="h-8 w-56 rounded-lg border border-neutral-200 bg-neutral-50 py-1 pl-8 pr-3 text-xs text-neutral-700 placeholder-neutral-400 focus:border-accent-300 focus:outline-none focus:ring-1 focus:ring-accent-300 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300 dark:placeholder-neutral-500 dark:focus:border-accent-600 dark:focus:ring-accent-600">
            </div>

            <button x-data @click="Livewire.dispatch('openModal', { component: 'create-task-modal', arguments: { projectId: {{ $project ?: 'null' }} } })"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-accent-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm transition-colors hover:bg-accent-700">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                New task
            </button>
        </div>
    </div>

    {{-- Content --}}
    @if ($view === 'board')
        {{-- Board view --}}
        <div class="flex-1 overflow-hidden"
             x-data="{
                 dragTaskId: null,
                 dragOverColumn: null,
                 onDragStart(e, taskId) {
                     this.dragTaskId = taskId;
                     e.dataTransfer.effectAllowed = 'move';
                     e.dataTransfer.setData('text/plain', taskId);
                     e.target.classList.add('opacity-50');
                 },
                 onDragEnd(e) {
                     e.target.classList.remove('opacity-50');
                     this.dragTaskId = null;
                     this.dragOverColumn = null;
                 },
                 onDragOver(e, column) {
                     e.preventDefault();
                     this.dragOverColumn = column;
                 },
                 onDragLeave(e, column) {
                     if (!e.currentTarget.contains(e.relatedTarget)) {
                         this.dragOverColumn = null;
                     }
                 },
                 onDrop(e, column) {
                     e.preventDefault();
                     this.dragOverColumn = null;
                     if (this.dragTaskId) {
                         $wire.updateTaskStatus(this.dragTaskId, column);
                     }
                 }
             }">
            <div class="grid h-full grid-cols-3 gap-0 divide-x divide-neutral-200 dark:divide-neutral-800">
                @foreach ($boardColumns as $column)
                    <div class="flex flex-col overflow-hidden"
                         x-on:dragover="onDragOver($event, '{{ $column['key'] }}')"
                         x-on:dragleave="onDragLeave($event, '{{ $column['key'] }}')"
                         x-on:drop="onDrop($event, '{{ $column['key'] }}')">

                        {{-- Column header --}}
                        <div class="flex shrink-0 items-center gap-2 px-5 py-3">
                            @php
                                $dotColor = match($column['key']) {
                                    'pending' => 'bg-priority-medium',
                                    'scheduled' => 'bg-accent-500',
                                    'completed' => 'bg-priority-low',
                                };
                            @endphp
                            <span class="size-2 rounded-full {{ $dotColor }}"></span>
                            <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">{{ $column['label'] }}</h3>
                            <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-[10px] font-medium text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400">{{ $column['tasks']->count() }}</span>
                        </div>

                        {{-- Column body --}}
                        <div class="flex-1 overflow-y-auto px-3 pb-4 transition-colors"
                             :class="dragOverColumn === '{{ $column['key'] }}' && dragTaskId ? 'bg-accent-50/50 dark:bg-accent-950/20' : ''">
                            <div class="space-y-2">
                                @forelse ($column['tasks'] as $task)
                                    <x-task-card :$task />
                                @empty
                                    <div class="flex flex-col items-center py-10 text-center">
                                        <p class="text-xs text-neutral-400 dark:text-neutral-500">No tasks</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        {{-- List view --}}
        <div class="flex-1 overflow-auto">
            <div class="mx-auto max-w-3xl px-6 py-4">
                @forelse ($tasks as $task)
                    @php
                        $isCompleted = $task->status === \App\Enums\TaskStatus::Completed;
                        $tz = auth()->user()->timezone ?? 'UTC';
                    @endphp
                    <div wire:key="task-{{ $task->id }}"
                         class="group flex items-center gap-3 border-b border-neutral-100 px-2 py-3 transition-colors hover:bg-neutral-50/60 dark:border-neutral-800/60 dark:hover:bg-neutral-800/30">

                        {{-- Checkbox --}}
                        @if ($isCompleted)
                            <button wire:click="reopenTask({{ $task->id }})"
                                    class="flex size-[18px] shrink-0 items-center justify-center rounded-full border-2 border-accent-500 bg-accent-500 text-white transition-colors hover:border-accent-600 hover:bg-accent-600">
                                <svg class="size-2.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            </button>
                        @else
                            <button wire:click="completeTask({{ $task->id }})"
                                    class="flex size-[18px] shrink-0 items-center justify-center rounded-full border-2 border-neutral-300 transition-colors hover:border-accent-500 dark:border-neutral-600 dark:hover:border-accent-500">
                            </button>
                        @endif

                        {{-- Content --}}
                        <button x-data @click="Livewire.dispatch('openModal', { component: 'task-detail-modal', arguments: { taskId: {{ $task->id }} } })"
                                class="flex flex-1 items-center gap-3 text-left">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm {{ $isCompleted ? 'text-neutral-400 line-through dark:text-neutral-500' : 'text-neutral-900 dark:text-neutral-100' }}">
                                    {{ $task->title }}
                                </p>
                                @if ($task->description && ! $isCompleted)
                                    <p class="mt-0.5 truncate text-xs text-neutral-400 dark:text-neutral-500">{{ Str::limit($task->description, 90) }}</p>
                                @endif
                            </div>

                            <div class="flex shrink-0 items-center gap-2.5">
                                @if ($task->project)
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium"
                                          style="background-color: {{ $task->project->color }}15; color: {{ $task->project->color }};">
                                        <span class="size-1.5 rounded-full" style="background-color: {{ $task->project->color }};"></span>
                                        {{ $task->project->title }}
                                    </span>
                                @endif
                                @if ($task->integration)
                                    <x-source-icon :type="$task->integration->type" size="sm" />
                                @endif
                                @if (! $isCompleted)
                                    <x-priority-badge :priority="$task->priority" />
                                @endif
                                @if ($task->estimated_duration)
                                    <span class="text-[10px] text-neutral-400 dark:text-neutral-500">{{ $task->formattedDuration() }}</span>
                                @endif
                                @if ($task->deadline)
                                    <span class="text-[10px] {{ $task->deadline->isPast() && ! $isCompleted ? 'text-priority-urgent' : 'text-neutral-400 dark:text-neutral-500' }}">
                                        {{ $task->deadline->format('M j') }}
                                    </span>
                                @endif
                                @if ($task->scheduled_start)
                                    <span class="text-[10px] text-accent-500 dark:text-accent-400">
                                        {{ $task->scheduled_start->copy()->setTimezone($tz)->format('M j, g:ia') }}
                                    </span>
                                @endif
                            </div>
                        </button>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-20 text-center">
                        <svg class="size-12 text-neutral-300 dark:text-neutral-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        <p class="mt-3 text-sm font-medium text-neutral-500 dark:text-neutral-400">No tasks found</p>
                        <p class="mt-1 text-xs text-neutral-400 dark:text-neutral-500">Create a new task to get started</p>
                    </div>
                @endforelse
            </div>
        </div>
    @endif
</div>
