# Calendar Drag Interactions Design

## Overview

Add drag-to-move, drag-to-resize, and improve drag-to-create for calendar events and tasks in the week and day views. All interactions use optimistic updates — the block visually snaps to its new position immediately, and reverts if the server call fails.

## Interactions

### 1. Drag to create (existing, improved)

- Drag on empty cell to define a time range
- Preview overlay stays visible after mouseup
- EventPanel opens for title/description input
- **New:** The preview can be resized (bottom edge) or moved (drag body) before saving

### 2. Drag to move

- Grab an existing event or task block body
- Drag to a different time slot and/or day column (week view)
- Ghost overlay follows the cursor, snapped to 15-minute increments
- On drop: block style updates immediately (optimistic), Livewire call persists
- On failure: block reverts to original position

### 3. Drag to resize

- Grab the bottom edge handle of an event or task block
- Drag up or down to change duration, snapped to 15-minute increments
- Minimum duration: 15 minutes
- On drop: height updates immediately (optimistic), Livewire call persists
- On failure: height reverts to original

## Architecture

### Unified `calendarDrag` Alpine component

Replaces the current `weekDragCreate` and `dayDragCreate` components. One component handles all three interaction modes. It wraps the time grid area in both week and day views.

**Mode detection on mousedown:**

| Target | Mode |
|--------|------|
| Empty cell (no event/task) | `create` |
| Event/task block body | `move` |
| Event/task block `.resize-handle` element | `resize` |

**State:**

```
mode: null | 'create' | 'move' | 'resize'
showPreview: false
itemType: null | 'event' | 'task'
itemId: null | int
originalDate: string
originalStartMinute: int
originalEndMinute: int
dragDate: string
dragStartMinute: int
dragEndMinute: int
previewTop/previewHeight/previewLeft/previewWidth: int
```

**Snapping:** All values snap to 15-minute increments.

**Preview overlay:** The same accent-colored preview block used for create is reused for move/resize. For move, it shows the full block at the cursor position. For resize, it extends/shrinks from the current top.

### Data attributes on blocks

Event and task blocks need data attributes so the Alpine component can identify them:

```html
<!-- event-block -->
<div data-item-type="event"
     data-item-id="{{ $event->id }}"
     data-item-date="{{ $event->starts_at->format('Y-m-d') }}"
     data-item-start="{{ $event->starts_at->hour * 60 + $event->starts_at->minute }}"
     data-item-end="{{ $event->ends_at->hour * 60 + $event->ends_at->minute }}">

<!-- task-block-wrapper -->
<div data-item-type="task"
     data-item-id="{{ $task->id }}"
     data-item-date="{{ $task->scheduled_start->format('Y-m-d') }}"
     data-item-start="{{ $task->scheduled_start->hour * 60 + $task->scheduled_start->minute }}"
     data-item-end="{{ $task->scheduled_end->hour * 60 + $task->scheduled_end->minute }}">
```

### Resize handle

A small div at the bottom of each event/task block:

```html
<div class="resize-handle absolute inset-x-0 bottom-0 h-2 cursor-s-resize"></div>
```

### Click vs drag disambiguation

- `mousedown` records position; `mouseup` within 5px and <200ms = click (open panel/modal)
- Movement beyond 5px = drag start (suppresses click)

### PlannerPage Livewire methods

```php
public function moveEvent(int $eventId, string $date, int $startMinutes): void
public function resizeEvent(int $eventId, int $endMinutes): void
public function moveTask(int $taskId, string $date, int $startMinutes): void
public function resizeTask(int $taskId, int $endMinutes): void
```

Each method:
1. Finds the item owned by `auth()->user()`
2. Calculates new `starts_at`/`ends_at` (move preserves duration)
3. Updates the database
4. Dispatches `calendar-event-created` or `task-scheduled` to trigger re-render

### Optimistic updates

On drop, Alpine immediately sets the block element's `style.top` and `style.height` (and moves it to the correct day column for cross-day moves). The `$wire` call fires async. On Livewire morph, the server-rendered values take over naturally. If the call throws, the element's style reverts to the original values stored at drag start.

### Preview persistence for create mode

Same as current behavior: `showPreview` stays true after mouseup, clears on `event-panel-closed` or `calendar-event-created`.

## Files changed

| File | Change |
|------|--------|
| `event-block.blade.php` | Add data attributes, resize handle, remove `x-data @click.stop` (handled by parent) |
| `task-block-wrapper.blade.php` | Add data attributes, resize handle, remove `x-data @click.stop` (handled by parent) |
| `week-view.blade.php` | Replace `weekDragCreate` with `calendarDrag` on the time grid |
| `day-view.blade.php` | Replace `dayDragCreate` with `calendarDrag` on the time grid |
| `planner-page.blade.php` | Replace `weekDragCreate`/`dayDragCreate` Alpine data with unified `calendarDrag`. Remove old Alpine data definitions. |
| `PlannerPage.php` | Add `moveEvent`, `resizeEvent`, `moveTask`, `resizeTask` methods |
| `CreateEventModalTest.php` | Update test for preview clear events |
| `CalendarTest.php` | Add tests for move/resize methods |

## Out of scope

- Month view drag (cells too small)
- Multi-day event spanning
- Undo/redo
- Touch/mobile drag support
