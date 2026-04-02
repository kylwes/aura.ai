@props(['day', 'hours', 'events', 'taskBlocks', 'projectBlocks'])

<div class="h-full overflow-auto"
     x-data
     x-init="$nextTick(() => { $el.scrollTop = 8 * 60 })">
    {{-- Day header --}}
    <div class="sticky top-0 z-10 grid grid-cols-[60px_1fr] border-b border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <div class="border-r border-neutral-200 dark:border-neutral-800"></div>
        <div class="px-4 py-3 {{ $day->isToday() ? 'bg-accent-50/50 dark:bg-accent-950/20' : '' }}">
            <p class="text-xs font-medium text-neutral-400 dark:text-neutral-500">{{ $day->format('l') }}</p>
            <p class="mt-0.5 text-lg font-semibold {{ $day->isToday() ? 'text-accent-600 dark:text-accent-400' : 'text-neutral-900 dark:text-neutral-100' }}">{{ $day->format('F j, Y') }}</p>
        </div>
    </div>

    {{-- Time grid --}}
    <div class="relative grid grid-cols-[60px_1fr]"
         x-data="calendarDrag({ view: 'day' })"
         x-ref="gridContent"
         @mousedown="onMouseDown($event)"
         @mousemove.window="onMouseMove($event)"
         @mouseup.window="onMouseUp($event)"
         :class="{ 'select-none': mode }">

        {{-- Hour labels --}}
        @foreach ($hours as $hour)
            <div wire:key="day-hour-{{ $hour }}"
                 class="relative border-r border-neutral-200 dark:border-neutral-800" style="grid-row: {{ $loop->iteration }};">
                <span class="absolute -top-2.5 right-2 text-[10px] font-medium text-neutral-400 dark:text-neutral-500">
                    {{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00
                </span>
            </div>

            {{-- Hour cell --}}
            <div wire:key="day-cell-{{ $hour }}"
                 data-date="{{ $day->format('Y-m-d') }}"
                 data-hour="{{ $hour }}"
                 class="relative min-h-[60px] border-b border-neutral-100 dark:border-neutral-800/50 {{ $day->isToday() ? 'bg-accent-50/30 dark:bg-accent-950/10' : '' }}"
                 style="grid-row: {{ $loop->iteration }};">

                @php $userTz = auth()->user()->timezone ?? 'UTC'; @endphp
                @foreach ($projectBlocks->filter(fn ($b) => $b->scheduled_start->copy()->setTimezone($userTz)->isSameDay($day) && $b->scheduled_start->copy()->setTimezone($userTz)->hour === $hour) as $pBlock)
                    <x-calendar.project-block :block="$pBlock" size="md" />
                @endforeach

                @foreach ($events->filter(fn ($e) => $e->starts_at->copy()->setTimezone($userTz)->hour === $hour) as $event)
                    <x-calendar.event-block :$event size="md" />
                @endforeach

                @foreach ($taskBlocks->filter(fn ($b) => $b->scheduled_start->copy()->setTimezone($userTz)->hour === $hour) as $block)
                    <x-calendar.task-block-wrapper :block="$block" :task="$block->task" size="md" />
                @endforeach
            </div>
        @endforeach

        {{-- Drag selection highlight --}}
        <div x-show="showPreview" x-cloak
             class="pointer-events-none absolute right-0 left-[60px] z-[10] flex rounded-md bg-accent-500/20 ring-1 ring-accent-500/40 dark:bg-accent-400/15 dark:ring-accent-400/30"
             :class="previewHeight <= 15 ? 'items-center' : 'items-start'"
             :style="'top:' + previewTop + 'px; height:' + previewHeight + 'px;'">
            <p class="px-2 text-[10px] font-medium text-accent-700 dark:text-accent-300" :class="previewHeight > 15 ? 'pt-1' : ''" x-text="formatTimeRange()"></p>
        </div>

        @if ($day->isToday())
            <div class="pointer-events-none absolute left-[60px] right-0 z-[15]"
                 x-data="{ top: 0 }"
                 x-init="
                    const updatePosition = () => {
                        const now = new Date();
                        top = (now.getHours() * 60) + now.getMinutes();
                    };
                    updatePosition();
                    setInterval(updatePosition, 60000);
                 "
                 :style="'top: ' + top + 'px'">
                <div class="flex items-center">
                    <div class="size-2 rounded-full bg-red-500"></div>
                    <div class="h-px flex-1 bg-red-500"></div>
                </div>
            </div>
        @endif
    </div>
</div>
