# Calendar Drag Interactions Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add drag-to-move, drag-to-resize, and improve drag-to-create for calendar events and tasks in the week and day views, with optimistic updates.

**Architecture:** A unified `calendarDrag` Alpine component replaces the separate `weekDragCreate` and `dayDragCreate` components. It detects what the user grabbed (empty cell, block body, resize handle) and enters the appropriate mode. Event/task blocks get data attributes and resize handles. Four new Livewire methods on PlannerPage handle persistence. Optimistic updates move blocks immediately; Livewire morph reconciles on success.

**Tech Stack:** Alpine.js (drag logic), Livewire 4 (persistence), Tailwind CSS v4 (styling)

---

### Task 1: Add Livewire methods for move/resize on PlannerPage

**Files:**
- Modify: `app/Livewire/Pages/PlannerPage.php:65-80`
- Test: `tests/Feature/Livewire/CalendarTest.php`

- [ ] **Step 1: Write failing tests for moveEvent**

Add to `tests/Feature/Livewire/CalendarTest.php`:

```php
it('moves an event to a new time and date', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    $event = CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'starts_at' => '2026-04-02 10:00:00',
        'ends_at' => '2026-04-02 11:00:00',
    ]);

    Livewire::actingAs($user)
        ->test(Calendar::class)
        ->call('moveEvent', $event->id, '2026-04-03', 840)
        ->assertDispatched('calendar-event-created');

    $event->refresh();
    expect($event->starts_at->format('Y-m-d H:i'))->toBe('2026-04-03 14:00')
        ->and($event->ends_at->format('Y-m-d H:i'))->toBe('2026-04-03 15:00');
});

it('resizes an event to a new end time', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    $event = CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'starts_at' => '2026-04-02 10:00:00',
        'ends_at' => '2026-04-02 11:00:00',
    ]);

    Livewire::actingAs($user)
        ->test(Calendar::class)
        ->call('resizeEvent', $event->id, 720)
        ->assertDispatched('calendar-event-created');

    $event->refresh();
    expect($event->ends_at->format('Y-m-d H:i'))->toBe('2026-04-02 12:00');
});

it('moves a scheduled task to a new time and date', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'scheduled',
        'scheduled_start' => '2026-04-02 10:00:00',
        'scheduled_end' => '2026-04-02 11:00:00',
    ]);

    Livewire::actingAs($user)
        ->test(Calendar::class)
        ->call('moveTask', $task->id, '2026-04-03', 540)
        ->assertDispatched('task-scheduled');

    $task->refresh();
    expect($task->scheduled_start->format('Y-m-d H:i'))->toBe('2026-04-03 09:00')
        ->and($task->scheduled_end->format('Y-m-d H:i'))->toBe('2026-04-03 10:00');
});

it('resizes a scheduled task to a new end time', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'scheduled',
        'scheduled_start' => '2026-04-02 10:00:00',
        'scheduled_end' => '2026-04-02 11:00:00',
    ]);

    Livewire::actingAs($user)
        ->test(Calendar::class)
        ->call('resizeTask', $task->id, 780)
        ->assertDispatched('task-scheduled');

    $task->refresh();
    expect($task->scheduled_end->format('Y-m-d H:i'))->toBe('2026-04-02 13:00');
});

it('cannot move another users event', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    $other = User::factory()->create();
    $event = CalendarEvent::factory()->create([
        'user_id' => $other->id,
        'starts_at' => '2026-04-02 10:00:00',
        'ends_at' => '2026-04-02 11:00:00',
    ]);

    Livewire::actingAs($user)
        ->test(Calendar::class)
        ->call('moveEvent', $event->id, '2026-04-03', 840);
})->throws(ModelNotFoundException::class);
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter="moves an event|resizes an event|moves a scheduled|resizes a scheduled|cannot move another"`
Expected: FAIL — methods don't exist yet

- [ ] **Step 3: Implement the four methods on PlannerPage**

Add these methods to `app/Livewire/Pages/PlannerPage.php` after the `scheduleTask` method (around line 80):

```php
public function moveEvent(int $eventId, string $date, int $startMinutes): void
{
    $event = auth()->user()->calendarEvents()->findOrFail($eventId);
    $duration = $event->starts_at->diffInMinutes($event->ends_at);
    $newStart = Carbon::parse($date)->startOfDay()->addMinutes($startMinutes);

    $event->update([
        'starts_at' => $newStart,
        'ends_at' => $newStart->copy()->addMinutes($duration),
    ]);

    $this->dispatch('calendar-event-created');
}

public function resizeEvent(int $eventId, int $endMinutes): void
{
    $event = auth()->user()->calendarEvents()->findOrFail($eventId);

    $event->update([
        'ends_at' => $event->starts_at->copy()->startOfDay()->addMinutes($endMinutes),
    ]);

    $this->dispatch('calendar-event-created');
}

public function moveTask(int $taskId, string $date, int $startMinutes): void
{
    $task = auth()->user()->tasks()->where('status', TaskStatus::Scheduled)->findOrFail($taskId);
    $duration = $task->scheduled_start->diffInMinutes($task->scheduled_end);
    $newStart = Carbon::parse($date)->startOfDay()->addMinutes($startMinutes);

    $task->update([
        'scheduled_start' => $newStart,
        'scheduled_end' => $newStart->copy()->addMinutes($duration),
        'is_ai_scheduled' => false,
    ]);

    $this->dispatch('task-scheduled');
}

public function resizeTask(int $taskId, int $endMinutes): void
{
    $task = auth()->user()->tasks()->where('status', TaskStatus::Scheduled)->findOrFail($taskId);

    $task->update([
        'scheduled_end' => $task->scheduled_start->copy()->startOfDay()->addMinutes($endMinutes),
        'is_ai_scheduled' => false,
    ]);

    $this->dispatch('task-scheduled');
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --compact --filter="moves an event|resizes an event|moves a scheduled|resizes a scheduled|cannot move another"`
Expected: PASS

- [ ] **Step 5: Run Pint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Livewire/Pages/PlannerPage.php tests/Feature/Livewire/CalendarTest.php
git commit -m "feat: add moveEvent, resizeEvent, moveTask, resizeTask to PlannerPage"
```

---

### Task 2: Add data attributes and resize handles to event/task blocks

**Files:**
- Modify: `resources/views/components/calendar/event-block.blade.php`
- Modify: `resources/views/components/calendar/task-block-wrapper.blade.php`

- [ ] **Step 1: Update event-block.blade.php**

Replace the entire file content with:

```blade
@props(['event', 'size' => 'sm'])

@php
    $durationMinutes = $event->starts_at->diffInMinutes($event->ends_at);
    $heightPx = max(30, ($durationMinutes / 60) * 60);
    $topOffset = $event->starts_at->minute;
    $startMin = $event->starts_at->hour * 60 + $event->starts_at->minute;
    $endMin = $event->ends_at->hour * 60 + $event->ends_at->minute;
@endphp

<div wire:key="we-{{ $event->id }}"
     data-item-type="event"
     data-item-id="{{ $event->id }}"
     data-item-date="{{ $event->starts_at->format('Y-m-d') }}"
     data-item-start="{{ $startMin }}"
     data-item-end="{{ $endMin }}"
     class="absolute z-[5] cursor-pointer rounded-lg border-l-3 border-neutral-400 bg-neutral-100 shadow-sm transition-shadow hover:shadow-md dark:border-neutral-600 dark:bg-neutral-800
            {{ $size === 'sm' ? 'inset-x-1 px-2 py-1' : 'inset-x-2 max-w-xl px-3 py-2' }}"
     style="top: {{ $topOffset }}px; height: {{ $heightPx }}px;">
    <p class="truncate font-semibold text-neutral-900 dark:text-neutral-100 {{ $size === 'sm' ? 'text-xs' : 'text-sm' }}">{{ $event->title }}</p>
    <p class="{{ $size === 'sm' ? 'text-[10px]' : 'text-xs' }} text-neutral-500 dark:text-neutral-400">{{ $event->starts_at->format('H:i') }} – {{ $event->ends_at->format('H:i') }}</p>
    <div class="resize-handle absolute inset-x-0 bottom-0 h-2 cursor-s-resize rounded-b-lg"></div>
</div>
```

- [ ] **Step 2: Update task-block-wrapper.blade.php**

Replace the entire file content with:

```blade
@props(['task', 'size' => 'sm'])

@php
    $durationMinutes = $task->scheduled_start->diffInMinutes($task->scheduled_end);
    $heightPx = max(30, ($durationMinutes / 60) * 60);
    $topOffset = $task->scheduled_start->minute;
    $startMin = $task->scheduled_start->hour * 60 + $task->scheduled_start->minute;
    $endMin = $task->scheduled_end->hour * 60 + $task->scheduled_end->minute;
@endphp

<div wire:key="wt-{{ $task->id }}"
     data-item-type="task"
     data-item-id="{{ $task->id }}"
     data-item-date="{{ $task->scheduled_start->format('Y-m-d') }}"
     data-item-start="{{ $startMin }}"
     data-item-end="{{ $endMin }}"
     class="absolute z-[5] cursor-pointer {{ $size === 'sm' ? 'inset-x-1' : 'inset-x-2 max-w-xl' }}"
     style="top: {{ $topOffset }}px; height: {{ $heightPx }}px;">
    <x-task-block :task="$task" />
    <div class="resize-handle absolute inset-x-0 bottom-0 h-2 cursor-s-resize rounded-b-lg"></div>
</div>
```

- [ ] **Step 3: Run full test suite**

Run: `php artisan test --compact`
Expected: All tests PASS (data attributes and resize handles are additive)

- [ ] **Step 4: Commit**

```bash
git add resources/views/components/calendar/event-block.blade.php resources/views/components/calendar/task-block-wrapper.blade.php
git commit -m "feat: add data attributes and resize handles to calendar blocks"
```

---

### Task 3: Build unified `calendarDrag` Alpine component and update views

**Files:**
- Modify: `resources/views/livewire/pages/planner-page.blade.php` (replace `weekDragCreate` and `dayDragCreate` with `calendarDrag`)
- Modify: `resources/views/components/calendar/week-view.blade.php` (use `calendarDrag`)
- Modify: `resources/views/components/calendar/day-view.blade.php` (use `calendarDrag`)

- [ ] **Step 1: Update week-view.blade.php to use calendarDrag**

In `resources/views/components/calendar/week-view.blade.php`, replace the time grid div (line 36-44):

```blade
            {{-- Time grid --}}
            <div class="relative flex"
                 x-data="calendarDrag({ view: 'week' })"
                 @mousedown="onMouseDown($event)"
                 @mousemove.window="onMouseMove($event)"
                 @mouseup.window="onMouseUp($event)"
                 :class="{ 'select-none': mode }">
```

Remove the old `@dragover.prevent`, `@dragleave`, `@drop.prevent` handlers and the `cursor-crosshair` class.

- [ ] **Step 2: Update day-view.blade.php to use calendarDrag**

In `resources/views/components/calendar/day-view.blade.php`, replace the time grid div (lines 16-22):

```blade
    {{-- Time grid --}}
    <div class="relative grid grid-cols-[60px_1fr]"
         x-data="calendarDrag({ view: 'day' })"
         x-ref="gridContent"
         @mousedown="onMouseDown($event)"
         @mousemove.window="onMouseMove($event)"
         @mouseup.window="onMouseUp($event)"
         :class="{ 'select-none': mode }">
```

- [ ] **Step 3: Replace Alpine data in planner-page.blade.php**

Replace the entire `weekDragCreate` and `dayDragCreate` Alpine.data blocks (lines 81-283) with one `calendarDrag` block:

```javascript
Alpine.data('calendarDrag', (config) => ({
    view: config.view, // 'week' or 'day'
    mode: null, // null | 'create' | 'move' | 'resize'
    showPreview: false,
    itemType: null, // 'event' | 'task'
    itemId: null,
    itemEl: null, // the DOM element being dragged
    originalDate: null,
    originalStartMinute: 0,
    originalEndMinute: 0,
    dragDate: null,
    dragStartMinute: 0,
    dragEndMinute: 0,
    previewTop: 0,
    previewHeight: 0,
    previewLeft: 0,
    previewWidth: 0,
    columnInfo: null,
    mouseDownX: 0,
    mouseDownY: 0,
    mouseDownTime: 0,
    dragStarted: false,

    init() {
        Livewire.on('calendar-event-created', () => this.clearPreview());
        Livewire.on('task-scheduled', () => this.clearPreview());
        Livewire.on('event-panel-closed', () => this.clearPreview());
    },

    clearPreview() {
        if (this.mode === 'create') {
            this.showPreview = false;
        }
        this.mode = null;
        this.itemEl = null;
        this.dragStarted = false;
    },

    snapTo15(min) {
        return Math.round(min / 15) * 15;
    },

    getContentY(e) {
        if (this.view === 'day') {
            const grid = this.$refs.gridContent ?? this.$el;
            return e.clientY - grid.getBoundingClientRect().top;
        }
        return e.clientY - this.$el.getBoundingClientRect().top;
    },

    minutesFromY(y) {
        return Math.max(0, Math.min(1440, this.snapTo15(y)));
    },

    formatTimeRange() {
        const start = Math.min(this.dragStartMinute, this.dragEndMinute);
        let end = Math.max(this.dragStartMinute, this.dragEndMinute);
        if (end === start) end = start + 15;
        const fmt = (m) => {
            const h = Math.floor(m / 60);
            const min = m % 60;
            return String(h).padStart(2, '0') + ':' + String(min).padStart(2, '0');
        };
        return fmt(start) + ' \u2013 ' + fmt(end);
    },

    getColumnInfo(date) {
        if (this.view === 'day') return null;
        const col = this.$refs.dayColumns;
        if (!col) return null;
        const cell = col.querySelector(`[data-date="${date}"][data-hour]`);
        if (!cell) return null;
        const colRect = col.getBoundingClientRect();
        const cellRect = cell.getBoundingClientRect();
        return { left: cellRect.left - colRect.left, width: cellRect.width };
    },

    onMouseDown(e) {
        if (e.button !== 0) return;

        this.mouseDownX = e.clientX;
        this.mouseDownY = e.clientY;
        this.mouseDownTime = Date.now();
        this.dragStarted = false;

        const resizeHandle = e.target.closest('.resize-handle');
        const itemBlock = e.target.closest('[data-item-type]');

        if (resizeHandle && itemBlock) {
            // Resize mode
            e.preventDefault();
            this.mode = 'resize';
            this.itemType = itemBlock.dataset.itemType;
            this.itemId = parseInt(itemBlock.dataset.itemId);
            this.itemEl = itemBlock;
            this.originalDate = itemBlock.dataset.itemDate;
            this.originalStartMinute = parseInt(itemBlock.dataset.itemStart);
            this.originalEndMinute = parseInt(itemBlock.dataset.itemEnd);
            this.dragDate = this.originalDate;
            this.dragStartMinute = this.originalStartMinute;
            this.dragEndMinute = this.originalEndMinute;
            this.columnInfo = this.getColumnInfo(this.originalDate);
            return;
        }

        if (itemBlock) {
            // Move mode (pending — wait for drag threshold)
            e.preventDefault();
            this.mode = 'move';
            this.itemType = itemBlock.dataset.itemType;
            this.itemId = parseInt(itemBlock.dataset.itemId);
            this.itemEl = itemBlock;
            this.originalDate = itemBlock.dataset.itemDate;
            this.originalStartMinute = parseInt(itemBlock.dataset.itemStart);
            this.originalEndMinute = parseInt(itemBlock.dataset.itemEnd);
            this.dragDate = this.originalDate;
            this.dragStartMinute = this.originalStartMinute;
            this.dragEndMinute = this.originalEndMinute;
            this.columnInfo = this.getColumnInfo(this.originalDate);
            return;
        }

        // Create mode
        const cell = e.target.closest('[data-date][data-hour]');
        if (!cell) return;

        e.preventDefault();
        this.mode = 'create';
        this.itemType = null;
        this.itemId = null;
        this.itemEl = null;
        this.dragDate = cell.dataset.date;
        const y = this.getContentY(e);
        this.dragStartMinute = this.minutesFromY(y);
        this.dragEndMinute = this.dragStartMinute;
        this.columnInfo = this.getColumnInfo(this.dragDate);
        this.updatePreview();
        this.showPreview = true;
        this.dragStarted = true;
    },

    onMouseMove(e) {
        if (!this.mode) return;
        e.preventDefault();

        // Check drag threshold for move/resize (5px)
        if (!this.dragStarted) {
            const dx = e.clientX - this.mouseDownX;
            const dy = e.clientY - this.mouseDownY;
            if (Math.sqrt(dx * dx + dy * dy) < 5) return;
            this.dragStarted = true;
            if (this.mode === 'move' && this.itemEl) {
                this.itemEl.style.opacity = '0.3';
            }
            this.showPreview = true;
            this.updatePreview();
        }

        const y = this.getContentY(e);
        const minute = this.minutesFromY(y);

        if (this.mode === 'create') {
            this.dragEndMinute = minute;
        } else if (this.mode === 'resize') {
            this.dragEndMinute = Math.max(this.dragStartMinute + 15, minute);
        } else if (this.mode === 'move') {
            const duration = this.originalEndMinute - this.originalStartMinute;
            this.dragStartMinute = minute;
            this.dragEndMinute = minute + duration;

            // Detect column change in week view
            if (this.view === 'week' && this.$refs.dayColumns) {
                const colRect = this.$refs.dayColumns.getBoundingClientRect();
                const x = e.clientX - colRect.left;
                const cells = this.$refs.dayColumns.querySelectorAll('[data-date][data-hour="0"]');
                for (const cell of cells) {
                    const cellRect = cell.getBoundingClientRect();
                    const cellLeft = cellRect.left - colRect.left;
                    if (x >= cellLeft && x < cellLeft + cellRect.width) {
                        this.dragDate = cell.dataset.date;
                        this.columnInfo = { left: cellLeft, width: cellRect.width };
                        break;
                    }
                }
            }
        }

        this.updatePreview();
    },

    onMouseUp(e) {
        if (!this.mode) return;

        // Click detection: no significant movement and short duration
        if (!this.dragStarted) {
            const elapsed = Date.now() - this.mouseDownTime;
            if (elapsed < 300) {
                // This was a click, not a drag
                if (this.itemType === 'event' && this.itemId) {
                    Livewire.dispatch('open-edit-event-panel', { eventId: this.itemId });
                } else if (this.itemType === 'task' && this.itemId) {
                    Livewire.dispatch('openModal', { component: 'task-detail-modal', arguments: { taskId: this.itemId } });
                }
                this.mode = null;
                this.itemEl = null;
                return;
            }
        }

        if (this.mode === 'create') {
            // Keep preview visible, open event panel
            const startMin = Math.min(this.dragStartMinute, this.dragEndMinute);
            let endMin = Math.max(this.dragStartMinute, this.dragEndMinute);
            if (endMin === startMin) endMin = startMin + 15;
            this.mode = null;

            Livewire.dispatch('open-create-event-panel', {
                date: this.dragDate,
                startMinutes: startMin,
                endMinutes: endMin,
            });
            return;
        }

        if (this.mode === 'move' && this.dragStarted) {
            // Optimistic: snap block to new position
            if (this.itemEl) {
                this.itemEl.style.opacity = '';
                this.itemEl.style.top = (this.dragStartMinute % 60) + 'px';
                const duration = this.originalEndMinute - this.originalStartMinute;
                this.itemEl.style.height = Math.max(30, (duration / 60) * 60) + 'px';
            }
            this.showPreview = false;

            if (this.itemType === 'event') {
                this.$wire.moveEvent(this.itemId, this.dragDate, this.dragStartMinute);
            } else {
                this.$wire.moveTask(this.itemId, this.dragDate, this.dragStartMinute);
            }
        }

        if (this.mode === 'resize' && this.dragStarted) {
            // Optimistic: update block height
            if (this.itemEl) {
                const duration = this.dragEndMinute - this.dragStartMinute;
                this.itemEl.style.height = Math.max(30, (duration / 60) * 60) + 'px';
            }
            this.showPreview = false;

            if (this.itemType === 'event') {
                this.$wire.resizeEvent(this.itemId, this.dragEndMinute);
            } else {
                this.$wire.resizeTask(this.itemId, this.dragEndMinute);
            }
        }

        this.mode = null;
        this.itemEl = null;
        this.dragStarted = false;
    },

    updatePreview() {
        const start = Math.min(this.dragStartMinute, this.dragEndMinute);
        const end = Math.max(this.dragStartMinute, this.dragEndMinute);
        this.previewTop = start;
        this.previewHeight = Math.max(15, end - start);
        if (this.view === 'week') {
            this.previewLeft = (this.columnInfo?.left ?? 0) + 4;
            this.previewWidth = (this.columnInfo?.width ?? 100) - 8;
        }
    },
}));
```

- [ ] **Step 4: Remove click handlers from event-block and task-block-wrapper**

In `resources/views/components/calendar/event-block.blade.php`, remove the `x-data @click.stop="Livewire.dispatch('open-edit-event-panel', ...)"` from the outer div (the calendarDrag component now handles clicks).

In `resources/views/components/calendar/task-block-wrapper.blade.php`, remove the `x-data @click.stop="Livewire.dispatch('openModal', ...)"` from the outer div.

- [ ] **Step 5: Run full test suite**

Run: `php artisan test --compact`
Expected: All tests PASS

- [ ] **Step 6: Run Pint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add resources/views/livewire/pages/planner-page.blade.php resources/views/components/calendar/week-view.blade.php resources/views/components/calendar/day-view.blade.php resources/views/components/calendar/event-block.blade.php resources/views/components/calendar/task-block-wrapper.blade.php
git commit -m "feat: unified calendarDrag Alpine component with create, move, and resize modes"
```

---

### Task 4: Manual browser testing

This task is manual verification since drag interactions can't be tested via Livewire's test harness.

- [ ] **Step 1: Test drag-to-create in week view**
    - Drag on empty cell in week view
    - Verify accent preview appears at correct position
    - Verify event panel opens on release
    - Verify preview stays visible while panel is open
    - Type a title — verify event appears on calendar
    - Close panel — verify preview disappears

- [ ] **Step 2: Test drag-to-create in day view**
    - Same as above but in day view
    - Verify Y offset is correct (not shifted by header)

- [ ] **Step 3: Test drag-to-move event in week view**
    - Click and drag an existing event block
    - Verify original block fades (opacity 0.3)
    - Verify accent preview follows cursor
    - Drag to different day column
    - Release — verify block snaps to new position
    - Refresh — verify persistence

- [ ] **Step 4: Test drag-to-move task in week view**
    - Same as events but with a scheduled task block

- [ ] **Step 5: Test drag-to-resize event**
    - Hover bottom edge of event block — verify cursor changes to s-resize
    - Drag down — verify preview extends
    - Release — verify block height updates
    - Refresh — verify persistence

- [ ] **Step 6: Test drag-to-resize task**
    - Same as events but with a scheduled task block

- [ ] **Step 7: Test click still works**
    - Click (don't drag) on event block — verify event panel opens
    - Click on task block — verify task detail modal opens

- [ ] **Step 8: Test day view move/resize**
    - Move and resize in day view
    - Verify Y positioning is correct

- [ ] **Step 9: Final commit**

```bash
git add -A
git commit -m "feat: calendar drag-to-move, drag-to-resize, and improved drag-to-create"
```
