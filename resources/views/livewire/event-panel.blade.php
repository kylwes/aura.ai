<div x-data="{ popover: false, popoverStyle: '' }"
     x-on:open-create-event-panel.window="if ($wire.collapsed) { popover = true; } else { popover = false; }"
     x-on:open-edit-event-panel.window="if ($wire.collapsed) { popover = true; } else { popover = false; }"
     @event-popover-position.window="
         const d = $event.detail;
         const pw = 348;
         const gap = 8;
         const spaceRight = window.innerWidth - d.right;
         let left, top;
         if (spaceRight >= pw + gap) {
             left = d.right + gap;
         } else {
             left = d.left - pw - gap;
         }
         left = Math.max(8, Math.min(left, window.innerWidth - pw - 8));
         top = Math.min(d.top, window.innerHeight - 400);
         top = Math.max(8, top);
         popoverStyle = 'left:' + left + 'px;top:' + top + 'px;';
     "
     @keydown.escape.window="if (popover) { popover = false; $wire.close(); } else if ($wire.open) { $wire.close(); }"
     class="relative shrink-0">

    {{-- PANEL MODE (expanded sidebar) --}}
    <div class="flex h-full flex-col border-l border-neutral-200 bg-white transition-all duration-200 dark:border-neutral-800 dark:bg-neutral-900"
         :class="$wire.collapsed ? 'w-0 overflow-hidden border-l-0' : 'w-[340px]'">

        @if ($open)
            <div x-show="!$wire.collapsed" class="flex w-[340px] flex-1 flex-col">
                <x-event-form :$isEditing :$formattedStart :$formattedEnd :$formattedDate :$durationLabel>
                    <x-slot:actions>
                        <button wire:click="close" class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </x-slot:actions>
                </x-event-form>
            </div>
        @else
            {{-- Default state: shortcuts --}}
            <div x-show="!$wire.collapsed" class="w-[340px]">
                <div class="border-b border-neutral-200 p-4 dark:border-neutral-800">
                    <h2 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">Shortcuts</h2>
                </div>
                <div class="flex-1 overflow-auto p-4">
                    <div class="space-y-1">
                        <div class="flex items-center justify-between rounded-lg px-2 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                            <span>Create event</span>
                            <span class="text-[10px] font-medium text-neutral-400 dark:text-neutral-500">Drag on calendar</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg px-2 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                            <span>Edit event</span>
                            <span class="text-[10px] font-medium text-neutral-400 dark:text-neutral-500">Click event</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg px-2 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                            <span>Move event</span>
                            <span class="text-[10px] font-medium text-neutral-400 dark:text-neutral-500">Drag event</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg px-2 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                            <span>Resize event</span>
                            <span class="text-[10px] font-medium text-neutral-400 dark:text-neutral-500">Drag edge</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg px-2 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                            <span>Today</span>
                            <kbd class="rounded bg-neutral-100 px-1.5 py-0.5 text-[11px] font-medium text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400">T</kbd>
                        </div>
                        <div class="flex items-center justify-between rounded-lg px-2 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                            <span>Day view</span>
                            <kbd class="rounded bg-neutral-100 px-1.5 py-0.5 text-[11px] font-medium text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400">D</kbd>
                        </div>
                        <div class="flex items-center justify-between rounded-lg px-2 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                            <span>Week view</span>
                            <kbd class="rounded bg-neutral-100 px-1.5 py-0.5 text-[11px] font-medium text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400">W</kbd>
                        </div>
                        <div class="flex items-center justify-between rounded-lg px-2 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                            <span>Month view</span>
                            <kbd class="rounded bg-neutral-100 px-1.5 py-0.5 text-[11px] font-medium text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400">M</kbd>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- POPOVER MODE (floating card when panel is collapsed) --}}
    @if ($open)
        <div x-show="$wire.collapsed && popover" x-cloak
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed z-50 flex w-[340px] flex-col rounded-xl bg-white shadow-2xl ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800"
             :style="popoverStyle"
             @click.away="if (!$event.target.closest('[data-item-type]')) { popover = false; $wire.close(); }">

            <x-event-form :$isEditing :$formattedStart :$formattedEnd :$formattedDate :$durationLabel>
                <x-slot:actions>
                    <button @click="popover = false; $wire.close();"
                            class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </x-slot:actions>
            </x-event-form>
        </div>
    @endif
</div>
