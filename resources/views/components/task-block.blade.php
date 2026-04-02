@props(['task', 'block' => null, 'variant' => 'regular'])

@php
    $isAi = $variant === 'ai' || $task->is_ai_scheduled;
    $blockMinutes = $block ? $block->scheduled_start->diffInMinutes($block->scheduled_end) : ($task->estimated_duration ?? 60);
    $durationMinutes = $blockMinutes;
    $isCompact = $durationMinutes <= 45;
    $baseClasses = 'group relative cursor-pointer rounded-lg transition-all hover:shadow-md hover:brightness-110 h-full';
    $paddingClasses = $isCompact ? 'px-2.5 flex items-center gap-1.5' : 'px-3 py-1.5';
    $hasProject = $task->project_id && $task->relationLoaded('project') && $task->project;
    $projectColor = $hasProject ? $task->project->color : null;

    if ($hasProject && $task->is_pinned) {
        // Pinned project task: solid border in project color
        $variantClasses = 'border border-solid shadow-sm ring-1';
        $projectBorderStyle = "border-color: {$projectColor}80; background-color: {$projectColor}10; --tw-ring-color: {$projectColor}20;";
    } elseif ($hasProject && $isAi) {
        // AI-scheduled project task: dashed border in project color
        $variantClasses = 'border border-dashed';
        $projectBorderStyle = "border-color: {$projectColor}60; background-color: {$projectColor}10;";
    } elseif ($hasProject) {
        // Manually scheduled project task: solid left border
        $variantClasses = 'border-l-3 bg-neutral-100 shadow-sm dark:bg-neutral-800';
        $projectBorderStyle = "border-left-color: {$projectColor};";
    } elseif ($task->is_pinned) {
        $variantClasses = 'border border-solid border-accent-400/80 bg-accent-50/90 shadow-sm ring-1 ring-accent-400/20 dark:border-accent-500/60 dark:bg-accent-950/50 dark:ring-accent-500/10';
        $projectBorderStyle = '';
    } elseif ($isAi) {
        $variantClasses = 'border border-dashed border-accent-400/60 bg-accent-50/80 dark:border-accent-600/40 dark:bg-accent-950/30';
        $projectBorderStyle = '';
    } else {
        $variantClasses = 'border-l-3 ' . $task->priority->borderColor() . ' bg-neutral-100 shadow-sm dark:bg-neutral-800';
        $projectBorderStyle = '';
    }
@endphp

<div {{ $attributes->merge(['class' => "$baseClasses $paddingClasses $variantClasses"]) }}
     @if ($projectBorderStyle) style="{{ $projectBorderStyle }}" @endif>

    <div class="flex items-center gap-1.5 min-w-0">
        @if ($task->integration)
            <x-source-icon :type="$task->integration->type" size="sm" class="shrink-0" />
        @endif

        <p class="truncate font-semibold text-neutral-900 dark:text-neutral-100 {{ $isCompact ? 'text-xs' : 'text-sm' }}">
            {{ $task->title }}
        </p>

        @if ($task->is_pinned)
            <svg class="size-3 shrink-0 text-neutral-400 dark:text-neutral-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
        @elseif ($isAi)
            <x-icons.sparkle class="size-3 shrink-0 text-accent-400" />
        @endif
    </div>

    @php
        $displayDuration = $blockMinutes;
        $hours = intdiv($displayDuration, 60);
        $minutes = $displayDuration % 60;
        $durationLabel = $hours > 0 && $minutes > 0 ? "{$hours}h {$minutes}m" : ($hours > 0 ? "{$hours}h" : "{$minutes}m");
    @endphp

    @if (!$isCompact)
        <span class="time-label text-xs text-neutral-500 dark:text-neutral-400">{{ $durationLabel }}</span>
    @else
        <span class="time-label shrink-0 text-[10px] text-neutral-500 dark:text-neutral-400">{{ $durationLabel }}</span>
    @endif

</div>
