@props(['priority'])

@php
    $dotColor = match($priority->value) {
        'urgent' => 'bg-priority-urgent',
        'high' => 'bg-priority-high',
        'medium' => 'bg-priority-medium',
        'low' => 'bg-priority-low',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 text-xs font-medium']) }}>
    <span class="size-2 rounded-full {{ $dotColor }}"></span>
    {{ $priority->label() }}
</span>
