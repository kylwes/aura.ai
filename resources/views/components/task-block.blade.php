@props(['task', 'block' => null, 'variant' => 'regular'])

@php
    $isAi = $variant === 'ai' || $task->is_ai_scheduled;
    $blockMinutes = $block ? $block->scheduled_start->diffInMinutes($block->scheduled_end) : ($task->estimated_duration ?? 60);
    $durationMinutes = $blockMinutes;
    $isCompact = $durationMinutes <= 45;
    $hasProject = $task->project_id && $task->relationLoaded('project') && $task->project;
    $projectColor = $hasProject ? $task->project->color : null;
    $isDraft = $isAi && ! $task->is_pinned;
    $stripes = "repeating-linear-gradient(-45deg, transparent, transparent 4px, var(--stripe-color) 4px, var(--stripe-color) 5px)";

    $baseClasses = 'group relative cursor-pointer rounded-lg transition-all h-full';
    $paddingClasses = $isCompact ? 'px-2.5 flex items-center gap-1.5' : 'px-3 py-1.5';

    // Locked = solid, filled, shadow, full opacity
    // Draft = dashed, striped, no shadow, faded
    if ($hasProject && $task->is_pinned) {
        $variantClasses = 'border border-solid shadow-sm ring-1 hover:shadow-md';
        $inlineStyle = "border-color: {$projectColor}80; background-color: {$projectColor}18; --tw-ring-color: {$projectColor}20;";
    } elseif ($hasProject && $isDraft) {
        $variantClasses = 'border border-dashed';
        $inlineStyle = "border-color: {$projectColor}40; background-color: {$projectColor}10; --stripe-color: {$projectColor}15; background-image: {$stripes};";
    } elseif ($hasProject) {
        $variantClasses = 'border border-solid shadow-sm hover:shadow-md';
        $inlineStyle = "border-color: {$projectColor}50; background-color: {$projectColor}15;";
    } elseif ($task->is_pinned) {
        $variantClasses = 'border border-solid border-neutral-300 bg-neutral-50 shadow-sm ring-1 ring-neutral-300/20 dark:border-neutral-600 dark:bg-neutral-900 dark:ring-neutral-600/10 hover:shadow-md';
        $inlineStyle = '';
    } elseif ($isDraft) {
        $variantClasses = 'border border-dashed border-neutral-300/30 bg-neutral-50 dark:border-neutral-600/25 dark:bg-neutral-900';
        $inlineStyle = "--stripe-color: rgba(140,140,140,0.08); background-image: {$stripes};";
    } else {
        $variantClasses = 'border-l-3 ' . $task->priority->borderColor() . ' bg-neutral-100 shadow-sm dark:bg-neutral-800 hover:shadow-md';
        $inlineStyle = '';
    }
@endphp

<div {{ $attributes->merge(['class' => "$baseClasses $paddingClasses $variantClasses"]) }}
     @if ($inlineStyle) style="{{ $inlineStyle }}" @endif>

    <div class="flex items-center gap-1.5 min-w-0 {{ $isDraft ? 'opacity-75' : '' }}">
        @if ($task->integration)
            <x-source-icon :type="$task->integration->type" size="sm" class="shrink-0" />
        @endif

        <p class="truncate font-semibold text-neutral-900 dark:text-neutral-100 {{ $isCompact ? 'text-xs' : 'text-sm' }}">
            {{ $task->title }}
        </p>

        @if ($task->is_pinned)
            <svg class="size-3 shrink-0 text-neutral-400 dark:text-neutral-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
        @elseif ($isDraft)
            <x-icons.sparkle class="size-3 shrink-0 text-neutral-400 dark:text-neutral-500" />
        @endif
    </div>

    @php
        $displayDuration = $blockMinutes;
        $hours = intdiv($displayDuration, 60);
        $minutes = $displayDuration % 60;
        $durationLabel = $hours > 0 && $minutes > 0 ? "{$hours}h {$minutes}m" : ($hours > 0 ? "{$hours}h" : "{$minutes}m");
    @endphp

    @if (!$isCompact)
        <span class="time-label text-xs {{ $isDraft ? 'text-neutral-400 dark:text-neutral-500' : 'text-neutral-500 dark:text-neutral-400' }}">{{ $durationLabel }}</span>
    @else
        <span class="time-label shrink-0 text-[10px] {{ $isDraft ? 'text-neutral-400 dark:text-neutral-500' : 'text-neutral-500 dark:text-neutral-400' }}">{{ $durationLabel }}</span>
    @endif

</div>
