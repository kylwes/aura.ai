@props(['type', 'size' => 'md'])

@php
    $sizeClass = match($size) {
        'sm' => 'size-6',
        'md' => 'size-8',
        'lg' => 'size-10',
    };
    $iconSize = match($size) {
        'sm' => 'size-3.5',
        'md' => 'size-4',
        'lg' => 'size-5',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center justify-center rounded-lg $sizeClass"]) }}
      style="background-color: {{ $type->color() }}15;">
    <x-dynamic-component :component="$type->iconComponent()" :class="$iconSize" />
</span>
