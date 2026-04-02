# Task Page Kanban Board Design

## Overview

Add a kanban board view to the task page alongside the existing list view. Users toggle between views via a segmented control in the toolbar. The preference is persisted in `UserPreferences`.

## Toolbar

The toolbar gets a segmented control (List | Board) on the left, before the filter pills. All existing filters (All, Active, Completed, Urgent) and search work identically on both views.

```
[List | Board]   [All] [Active] [Completed] [Urgent]          [Search...] [+ New task]
```

## Board View

Three status columns, each independently scrollable:

- **Pending** — tasks with status `pending`
- **Scheduled** — tasks with status `scheduled`
- **Completed** — tasks with status `completed`

Snoozed and dismissed tasks are excluded from the board (same as current behavior — they only appear in the "All" filter of the list).

### Column headers

Column name + count badge. Tasks within each column are sorted by priority (urgent first), then deadline.

### Cards

Compact rounded boxes with:
- Left 3px border colored by priority (`priority-urgent`, `priority-high`, `priority-medium`, `priority-low` tokens)
- Title (truncated to 2 lines)
- Meta row: duration badge, deadline (red if overdue), scheduled time (scheduled column), source icon (if from integration)
- Completed cards: muted text, strikethrough title, no priority border
- No checkbox on cards — status changes via drag or detail modal

Clicking a card opens the existing `task-detail-modal`.

### Drag-and-drop

- Drag cards between columns to change status
- Dragging to Completed marks task as completed
- Dragging from Completed to Pending reopens it
- Dragging to/from Scheduled only updates status (no time slot assignment)
- Uses native HTML Drag API (no external library)
- Visual feedback: drop zone highlight on column hover during drag

## Technical Implementation

### UserPreferences

Add `task_view` property (string: `'list'` or `'board'`, default `'list'`).

Migration: add `task_view` to the preferences settings table.

### TaskPage Livewire component

- Add `string $view` property, initialized from `UserPreferences` in `mount()`
- Add `switchView(string $view)` method — persists to `UserPreferences`
- Add `updateTaskStatus(int $taskId, string $status)` method for drag-and-drop
- In `render()`, group tasks by status for the board view
- Existing `completeTask()` and `reopenTask()` stay for the list view

### Blade views

- `livewire/pages/task-page.blade.php` — toggle between list and board via `@if ($view === 'board')`
- `components/task-card.blade.php` — new board card component
- Board layout uses CSS grid: `grid-cols-3` with `gap-4`, each column `overflow-y-auto`

### Alpine.js

- `kanbanBoard` Alpine data component on the board wrapper
- Uses `draggable="true"`, `@dragstart`, `@dragover.prevent`, `@drop` on cards and columns
- On drop: calls `$wire.updateTaskStatus(taskId, newStatus)`
- Drop zone visual: column gets a highlight ring during `dragover`

### Toast integration

- `updateTaskStatus()` dispatches a toast on status change

## Files to create/modify

- **Modify**: `app/Settings/UserPreferences.php` — add `task_view` property
- **Create**: settings migration for `task_view` default
- **Modify**: `app/Livewire/Pages/TaskPage.php` — add view toggle, board methods
- **Modify**: `resources/views/livewire/pages/task-page.blade.php` — add toggle + board view
- **Create**: `resources/views/components/task-card.blade.php` — board card component
