@props(['isEditing' => false, 'formattedStart', 'formattedEnd', 'formattedDate', 'durationLabel'])

<div class="flex-1 overflow-auto px-4 pb-4 pt-3">
    {{-- Title + actions --}}
    <div class="flex items-start gap-2">
        <input type="text"
               wire:model.live.debounce.500ms="title"
               autofocus
               placeholder="Event title"
               x-data
               x-init="$watch('$wire.eventId', () => {})"
               @input="
                   window.dispatchEvent(new CustomEvent('event-title-sync', {
                       detail: { id: $wire.eventId, title: $el.value }
                   }))
               "
               class="min-w-0 flex-1 border-0 bg-transparent p-0 text-base font-semibold text-neutral-900 placeholder-neutral-400 focus:ring-0 dark:text-neutral-100 dark:placeholder-neutral-500">
        <div class="flex shrink-0 items-center gap-0.5">
            @if ($isEditing)
                <button wire:click="delete"
                        wire:confirm="Are you sure you want to delete this event?"
                        class="rounded-lg p-1.5 text-neutral-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30 dark:hover:text-red-400">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                </button>
            @endif
            {{ $actions ?? '' }}
        </div>
    </div>

    {{-- Time + date --}}
    <div class="mt-3 space-y-1.5">
        <div class="flex items-center gap-2 text-sm">
            <svg class="size-4 shrink-0 text-neutral-400 dark:text-neutral-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            <span class="font-medium text-neutral-900 dark:text-neutral-100">{{ $formattedStart }}</span>
            <span class="text-neutral-300 dark:text-neutral-600">&ndash;</span>
            <span class="font-medium text-neutral-900 dark:text-neutral-100">{{ $formattedEnd }}</span>
            <span class="text-xs text-neutral-400 dark:text-neutral-500">{{ $durationLabel }}</span>
        </div>

        <div class="flex items-center gap-2 text-sm text-neutral-500 dark:text-neutral-400">
            <svg class="size-4 shrink-0 text-neutral-400 dark:text-neutral-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v9.75"/></svg>
            <span>{{ $formattedDate }}</span>
        </div>
    </div>

    {{-- Description --}}
    <div class="mt-3">
        <div class="flex items-start gap-2">
            <svg class="mt-0.5 size-4 shrink-0 text-neutral-400 dark:text-neutral-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/></svg>
            <textarea wire:model.live.debounce.500ms="description"
                      rows="3"
                      placeholder="Add a description..."
                      class="w-full resize-none border-0 bg-transparent p-0 text-sm text-neutral-700 placeholder-neutral-400 focus:ring-0 dark:text-neutral-300 dark:placeholder-neutral-500"></textarea>
        </div>
    </div>
</div>
