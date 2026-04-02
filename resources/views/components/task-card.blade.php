@props(['task'])

@php
    $isCompleted = $task->status === \App\Enums\TaskStatus::Completed;
    $hasProject = $task->project_id && $task->relationLoaded('project') && $task->project;
    $borderColor = $isCompleted ? 'border-neutral-200 dark:border-neutral-700' : ($hasProject ? '' : $task->priority->borderColor());
    $borderStyle = (!$isCompleted && $hasProject) ? 'border-left-color: ' . $task->project->color . ';' : '';
    $tz = auth()->user()->timezone ?? 'UTC';
@endphp

<div draggable="true"
     data-task-id="{{ $task->id }}"
     x-on:dragstart="onDragStart($event, {{ $task->id }})"
     x-on:dragend="onDragEnd($event)"
     x-data @click="Livewire.dispatch('openModal', { component: 'task-detail-modal', arguments: { taskId: {{ $task->id }} } })"
     class="group cursor-pointer rounded-xl border-l-3 bg-white p-3 shadow-sm ring-1 ring-neutral-200/60 transition-all hover:shadow-md hover:ring-neutral-300/80 active:scale-[0.98] dark:bg-neutral-800 dark:ring-neutral-700/60 dark:hover:ring-neutral-600/80 {{ $borderColor }}"
     @if ($borderStyle) style="{{ $borderStyle }}" @endif>

    {{-- Title --}}
    <p class="line-clamp-2 text-sm font-medium leading-snug {{ $isCompleted ? 'text-neutral-400 line-through dark:text-neutral-500' : 'text-neutral-900 dark:text-neutral-100' }}">
        {{ $task->title }}
    </p>

    {{-- Meta --}}
    <div class="mt-2 flex flex-wrap items-center gap-2">
        @if ($task->integration)
            <x-source-icon :type="$task->integration->type" size="sm" />
        @endif

        @if ($task->project)
            <span class="inline-flex items-center gap-1 rounded-full px-1.5 py-0.5 text-[10px] font-medium"
                  style="background-color: {{ $task->project->color }}15; color: {{ $task->project->color }};">
                <span class="size-1.5 rounded-full" style="background-color: {{ $task->project->color }};"></span>
                {{ $task->project->title }}
            </span>
        @endif

        @if (! $isCompleted)
            <x-priority-badge :priority="$task->priority" class="text-[10px] text-neutral-500 dark:text-neutral-400" />
        @endif

        @if ($task->estimated_duration)
            <span class="inline-flex items-center gap-1 text-[10px] text-neutral-400 dark:text-neutral-500">
                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                {{ $task->formattedDuration() }}
            </span>
        @endif

        @if ($task->deadline)
            <span class="inline-flex items-center gap-1 text-[10px] {{ $task->deadline->isPast() && ! $isCompleted ? 'text-priority-urgent' : 'text-neutral-400 dark:text-neutral-500' }}">
                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v9.75"/></svg>
                {{ $task->deadline->format('M j') }}
            </span>
        @endif

        @if ($task->scheduled_start && $task->status === \App\Enums\TaskStatus::Scheduled)
            <span class="inline-flex items-center gap-1 text-[10px] text-accent-500 dark:text-accent-400">
                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v9.75"/></svg>
                {{ $task->scheduled_start->copy()->setTimezone($tz)->format('M j, g:ia') }}
            </span>
        @endif
    </div>
</div>
