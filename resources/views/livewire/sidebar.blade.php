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
        <div class="space-y-2">
            @forelse ($unscheduledTasks as $task)
                <div class="cursor-grab rounded-lg border border-neutral-200 bg-neutral-50 p-2.5 transition-shadow hover:shadow-sm active:cursor-grabbing dark:border-neutral-700 dark:bg-neutral-800"
                     draggable="true"
                     x-data
                     @dragstart="
                         $event.dataTransfer.setData('application/x-task-id', '{{ $task->id }}');
                         $event.dataTransfer.effectAllowed = 'move';
                         Alpine.store('dragTask', { id: {{ $task->id }}, duration: {{ $task->estimated_duration ?? 60 }} });
                     "
                     @dragend="Alpine.store('dragTask', { id: null, duration: 0 })"
                     @click="Livewire.dispatch('openModal', { component: 'task-detail-modal', arguments: { taskId: {{ $task->id }} } })"
                    <p class="text-xs font-medium text-neutral-900 dark:text-neutral-100 truncate">{{ $task->title }}</p>
                    <div class="mt-1 flex items-center gap-2">
                        @if ($task->integration)
                            <x-source-icon :type="$task->integration->type" size="sm" />
                        @endif
                        <x-priority-badge :priority="$task->priority" />
                        @if ($task->estimated_duration)
                            <span class="text-[10px] text-neutral-400">{{ $task->formattedDuration() }}</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-xs text-neutral-400 dark:text-neutral-500">No unscheduled tasks</p>
            @endforelse
        </div>
    </div>
</aside>
