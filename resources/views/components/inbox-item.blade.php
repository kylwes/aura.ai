@props(['item'])

@php
    $isUnread = $item->status === \App\Enums\InboxItemStatus::Pending;
@endphp

<div {{ $attributes->merge(['class' => 'rounded-lg bg-white p-4 ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800' . ($isUnread ? ' border-l-3 border-accent-500' : '')]) }}>
    <div class="flex items-start gap-3">
        @if ($item->integration)
            <x-source-icon :type="$item->integration->type" size="md" />
        @endif

        <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between">
                <p class="text-xs text-neutral-500 dark:text-neutral-400">
                    @if ($item->integration)
                        {{ $item->integration->type->label() }} &rarr; {{ $item->channel_name }}
                    @else
                        {{ $item->channel_name }}
                    @endif
                </p>
                <span class="text-xs text-neutral-400 dark:text-neutral-500">{{ $item->created_at->diffForHumans(short: true) }}</span>
            </div>

            <p class="mt-1 text-sm text-neutral-700 dark:text-neutral-300 line-clamp-2">{{ $item->preview_text }}</p>

            <div class="mt-2 flex items-center gap-2">
                @if ($item->ai_suggested_priority)
                    <x-priority-badge :priority="\App\Enums\TaskPriority::from($item->ai_suggested_priority)" />
                @endif
                @if ($item->ai_confidence)
                    <x-confidence-indicator :level="$item->ai_confidence" />
                @endif
            </div>

            <div class="mt-3 flex items-center gap-2">
                <button class="text-xs font-medium text-accent-600 hover:text-accent-700 dark:text-accent-400 dark:hover:text-accent-300">Accept as task</button>
                <span class="text-neutral-300 dark:text-neutral-700">|</span>
                <button class="text-xs text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">Snooze</button>
                <span class="text-neutral-300 dark:text-neutral-700">|</span>
                <button class="text-xs text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">Dismiss</button>
                <span class="text-neutral-300 dark:text-neutral-700">|</span>
                <button class="text-xs text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">Edit priority</button>
            </div>
        </div>
    </div>
</div>
