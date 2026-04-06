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
                @if ($item->ai_estimated_duration)
                    @php
                        $h = intdiv($item->ai_estimated_duration, 60);
                        $m = $item->ai_estimated_duration % 60;
                        $durLabel = $h > 0 && $m > 0 ? "{$h}h {$m}m" : ($h > 0 ? "{$h}h" : "{$m}m");
                    @endphp
                    <span class="inline-flex items-center gap-1 text-[10px] text-neutral-400 dark:text-neutral-500">
                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        ~{{ $durLabel }}
                    </span>
                @endif
                @if ($item->ai_suggested_project_id && $item->suggestedProject)
                    <span class="inline-flex items-center gap-1 rounded-full px-1.5 py-0.5 text-[10px] font-medium"
                          style="background-color: {{ $item->suggestedProject->color }}15; color: {{ $item->suggestedProject->color }};">
                        <span class="size-1.5 rounded-full" style="background-color: {{ $item->suggestedProject->color }};"></span>
                        {{ $item->suggestedProject->title }}
                    </span>
                @endif
                @if ($item->ai_confidence)
                    <x-confidence-indicator :level="$item->ai_confidence" />
                @endif
            </div>

            <div class="mt-3 flex items-center gap-1">
                {{-- Accept as task --}}
                <button wire:click="accept({{ $item->id }})"
                        title="Accept as task"
                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-accent-600 transition-colors hover:bg-accent-50 dark:text-accent-400 dark:hover:bg-accent-950/30">
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    Accept
                </button>

                {{-- Snooze --}}
                <button wire:click="snooze({{ $item->id }})"
                        title="Snooze for 2 hours"
                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs text-neutral-500 transition-colors hover:bg-neutral-100 hover:text-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-neutral-200">
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    Snooze
                </button>

                {{-- Dismiss --}}
                <button wire:click="dismiss({{ $item->id }})"
                        title="Dismiss"
                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs text-neutral-500 transition-colors hover:bg-red-50 hover:text-red-600 dark:text-neutral-400 dark:hover:bg-red-950/30 dark:hover:text-red-400">
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    Dismiss
                </button>
            </div>
        </div>
    </div>
</div>
