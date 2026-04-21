<div>
    {{-- Header --}}
    <div class="flex items-center justify-between px-8 pt-6">
        <div class="flex items-center gap-2">
            <div class="h-6 w-1 rounded-full" style="background-color: {{ $color }}"></div>
            <h2 class="text-xl font-semibold text-neutral-900 dark:text-neutral-100">
                {{ $projectId ? 'Edit Project' : 'New Project' }}
            </h2>
        </div>
        <button wire:click="$dispatch('closeModal')" class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300">
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
        </button>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 border-b border-neutral-200 px-8 pt-4 dark:border-neutral-800">
        <button wire:click="$set('tab', 'details')"
                class="rounded-t-lg px-4 py-2 text-sm font-medium transition-colors {{ $tab === 'details' ? 'border-b-2 border-accent-500 text-accent-600 dark:text-accent-400' : 'text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200' }}">
            Details
        </button>
        <button wire:click="$set('tab', 'schedule')"
                class="rounded-t-lg px-4 py-2 text-sm font-medium transition-colors {{ $tab === 'schedule' ? 'border-b-2 border-accent-500 text-accent-600 dark:text-accent-400' : 'text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200' }}">
            Weekly Schedule
            @if (count($schedules) > 0)
                <span class="ml-1 text-[10px] text-accent-500">{{ count($schedules) }}d</span>
            @endif
        </button>
    </div>

    {{-- Body --}}
    <div class="px-8 py-6" style="min-height: 340px;">
        @if ($tab === 'details')
            <div class="space-y-4">
                {{-- Title --}}
                <x-input.label label="Title">
                    <x-input.text wire:model="title" autofocus placeholder="Project name" class="font-medium" />
                </x-input.label>
                @error('title')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror

                {{-- Color --}}
                <x-input.label label="Color">
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        @foreach (['#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#f97316', '#eab308', '#22c55e', '#06b6d4', '#3b82f6', '#6b7280'] as $preset)
                            <button type="button"
                                    wire:click="$set('color', '{{ $preset }}')"
                                    class="size-7 rounded-full transition-transform hover:scale-110 focus:outline-none
                                        {{ $color === $preset ? 'ring-2 ring-offset-2 ring-offset-white dark:ring-offset-neutral-900' : '' }}"
                                    style="background-color: {{ $preset }};">
                            </button>
                        @endforeach

                        {{-- Custom color picker --}}
                        <label class="relative size-7 cursor-pointer rounded-full bg-gradient-to-br from-red-400 via-green-400 to-blue-500 transition-transform hover:scale-110
                            {{ !in_array($color, ['#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#f97316', '#eab308', '#22c55e', '#06b6d4', '#3b82f6', '#6b7280']) ? 'ring-2 ring-offset-2 ring-offset-white dark:ring-offset-neutral-900' : '' }}">
                            <input type="color"
                                   value="{{ $color }}"
                                   x-on:input="$wire.set('color', $event.target.value)"
                                   class="absolute inset-0 cursor-pointer opacity-0">
                        </label>
                    </div>
                </x-input.label>
                @error('color')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror

                {{-- Date range --}}
                <div class="grid grid-cols-2 gap-3">
                    <x-input.label label="Start date" optional>
                        <x-input.date wire:model="startsAt" />
                    </x-input.label>
                    <x-input.label label="End date" optional>
                        <x-input.date wire:model="endsAt" />
                    </x-input.label>
                </div>

                {{-- Description --}}
                <x-input.label label="Description" optional>
                    <x-input.textarea wire:model="description" placeholder="What is this project about?" />
                </x-input.label>
            </div>
        @else
            {{-- Schedule tab --}}
            <div wire:ignore x-data="scheduleGrid(@js($schedules), @js($color))">
                {{-- Day headers --}}
                <div class="mb-2 grid grid-cols-[40px_repeat(5,1fr)] gap-x-1 px-2">
                    <div></div>
                    @foreach ([1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri'] as $dayNum => $dayLabel)
                        <div class="text-center text-xs font-semibold text-neutral-600 dark:text-neutral-300">
                            {{ $dayLabel }}
                            <template x-if="hasSchedule({{ $dayNum }})">
                                <button class="ml-0.5 text-[10px] font-normal opacity-60 hover:opacity-100"
                                        @click="removeDay({{ $dayNum }})">
                                    <svg class="inline size-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                            </template>
                        </div>
                    @endforeach
                </div>

                {{-- Time grid --}}
                <div class="relative grid grid-cols-[40px_repeat(5,1fr)] gap-x-1 rounded-lg bg-neutral-100 p-2 dark:bg-neutral-800" x-ref="grid">
                    @for ($hour = 7; $hour <= 19; $hour++)
                        {{-- Hour label --}}
                        <div class="flex h-6 items-center justify-end pr-2 text-[10px] text-neutral-400 dark:text-neutral-500">
                            {{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00
                        </div>

                        @foreach ([1, 2, 3, 4, 5] as $dayNum)
                            <div data-day="{{ $dayNum }}" data-hour="{{ $hour }}"
                                 class="h-6 cursor-pointer rounded-sm border border-neutral-200/30 transition-colors hover:bg-neutral-200/60 dark:border-neutral-700/30 dark:hover:bg-neutral-700/40"
                                 @mousedown.prevent="startDrag({{ $dayNum }}, {{ $hour }})"
                                 @mouseenter="onDrag({{ $dayNum }}, {{ $hour }})">
                            </div>
                        @endforeach
                    @endfor

                    {{-- Schedule overlays --}}
                    @foreach ([1, 2, 3, 4, 5] as $dayNum)
                        <template x-if="hasSchedule({{ $dayNum }}) && !isDraggingDay({{ $dayNum }})">
                            <div class="pointer-events-none absolute z-[1] flex rounded-md"
                                 :class="overlayHeight({{ $dayNum }}) <= 24 ? 'items-center' : 'items-start'"
                                 :style="overlayStyle({{ $dayNum }})">
                                <p class="truncate px-2 text-[10px] font-medium"
                                   :class="overlayHeight({{ $dayNum }}) > 24 ? 'pt-1' : ''"
                                   :style="'color: ' + color"
                                   x-text="scheduleLabel({{ $dayNum }})"></p>
                            </div>
                        </template>
                    @endforeach

                    {{-- Drag preview overlay --}}
                    <template x-if="dragging">
                        <div class="pointer-events-none absolute z-[2] flex rounded-md"
                             :class="dragPreviewHeight() <= 24 ? 'items-center' : 'items-start'"
                             :style="dragPreviewStyle()">
                            <p class="truncate px-2 text-[10px] font-medium"
                               :class="dragPreviewHeight() > 24 ? 'pt-1' : ''"
                               :style="'color: ' + color"
                               x-text="dragLabel()"></p>
                        </div>
                    </template>
                </div>

                <p class="mt-2 text-[10px] text-neutral-400 dark:text-neutral-500">Drag to select working hours per day</p>
            </div>
        @endif
    </div>

    {{-- Footer --}}
    <div class="flex items-center justify-between border-t border-neutral-200 px-8 py-4 dark:border-neutral-800">
        <div>
            @if ($projectId)
                <button wire:click="delete"
                        wire:confirm="Delete this project? Tasks assigned to it will be unlinked."
                        class="px-4 py-2 text-sm font-medium text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                    Delete
                </button>
            @endif
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="$dispatch('closeModal')" class="px-4 py-2 text-sm font-medium text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-neutral-200">
                Cancel
            </button>
            <button wire:click="save" class="rounded-lg bg-accent-600 px-5 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-accent-700">
                {{ $projectId ? 'Save' : 'Create' }}
            </button>
        </div>
    </div>
</div>

@script
<script>
Alpine.data('scheduleGrid', (initialSchedules, initialColor) => ({
    selected: { ...initialSchedules },
    color: initialColor,
    dragging: false,
    dragDay: null,
    dragStartHour: null,
    dragCurrentHour: null,
    _mouseupHandler: null,

    init() {
        this._mouseupHandler = () => this.endDrag();
        document.addEventListener('mouseup', this._mouseupHandler);
    },

    destroy() {
        if (this._mouseupHandler) {
            document.removeEventListener('mouseup', this._mouseupHandler);
        }
    },

    startDrag(day, hour) {
        this.dragging = true;
        this.dragDay = day;
        this.dragStartHour = hour;
        this.dragCurrentHour = hour;
    },

    onDrag(day, hour) {
        if (!this.dragging || day !== this.dragDay) return;
        this.dragCurrentHour = hour;
    },

    endDrag() {
        if (!this.dragging) return;

        const day = this.dragDay;
        const startH = Math.min(this.dragStartHour, this.dragCurrentHour);
        const endH = Math.max(this.dragStartHour, this.dragCurrentHour) + 1;
        const start = String(startH).padStart(2, '0') + ':00';
        const end = String(endH).padStart(2, '0') + ':00';

        this.dragging = false;
        this.selected = { ...this.selected, [day]: { start, end } };
        this.$wire.updateSchedule(day, start, end);
    },

    removeDay(day) {
        const { [day]: _, ...rest } = this.selected;
        this.selected = rest;
        this.$wire.removeSchedule(day);
    },

    hasSchedule(day) {
        return !!this.selected[day];
    },

    isDraggingDay(day) {
        return this.dragging && this.dragDay === day;
    },

    scheduleLabel(day) {
        const s = this.selected[day];
        if (!s) return '';
        return s.start.substring(0, 5) + ' \u2013 ' + s.end.substring(0, 5);
    },

    dragLabel() {
        if (!this.dragging) return '';
        const s = Math.min(this.dragStartHour, this.dragCurrentHour);
        const e = Math.max(this.dragStartHour, this.dragCurrentHour) + 1;
        return String(s).padStart(2, '0') + ':00 \u2013 ' + String(e).padStart(2, '0') + ':00';
    },

    _cellRect(day, hour) {
        const grid = this.$refs.grid;
        if (!grid) return null;
        const cell = grid.querySelector('[data-day="' + day + '"][data-hour="' + hour + '"]');
        if (!cell) return null;
        const gridRect = grid.getBoundingClientRect();
        const cellRect = cell.getBoundingClientRect();
        return {
            top: cellRect.top - gridRect.top,
            left: cellRect.left - gridRect.left,
            width: cellRect.width,
            height: cellRect.height,
        };
    },

    _overlayGeometry(day, startH, endH) {
        const topCell = this._cellRect(day, startH);
        const botCell = this._cellRect(day, endH - 1);
        if (!topCell || !botCell) return null;
        return {
            top: topCell.top,
            left: topCell.left,
            width: topCell.width,
            height: (botCell.top + botCell.height) - topCell.top,
        };
    },

    _buildStyle(geo) {
        if (!geo) return 'display:none';
        return 'top:' + geo.top + 'px;left:' + geo.left + 'px;width:' + geo.width + 'px;height:' + geo.height + 'px;'
            + 'background-color:' + this.color + '20;'
            + 'outline:1px solid ' + this.color + '50;outline-offset:-1px;';
    },

    overlayStyle(day) {
        const sched = this.selected[day];
        if (!sched) return 'display:none';
        return this._buildStyle(this._overlayGeometry(day, parseInt(sched.start), parseInt(sched.end)));
    },

    overlayHeight(day) {
        const sched = this.selected[day];
        if (!sched) return 0;
        const geo = this._overlayGeometry(day, parseInt(sched.start), parseInt(sched.end));
        return geo ? geo.height : 0;
    },

    dragPreviewStyle() {
        if (!this.dragging) return 'display:none';
        const s = Math.min(this.dragStartHour, this.dragCurrentHour);
        const e = Math.max(this.dragStartHour, this.dragCurrentHour);
        return this._buildStyle(this._overlayGeometry(this.dragDay, s, e + 1));
    },

    dragPreviewHeight() {
        if (!this.dragging) return 0;
        const s = Math.min(this.dragStartHour, this.dragCurrentHour);
        const e = Math.max(this.dragStartHour, this.dragCurrentHour);
        const geo = this._overlayGeometry(this.dragDay, s, e + 1);
        return geo ? geo.height : 0;
    },
}));
</script>
@endscript
