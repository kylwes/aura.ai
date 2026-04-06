<div class="flex flex-1 flex-col overflow-hidden">
    <livewire:stale-task-nudge />

    <div class="flex-1 overflow-hidden">
        @if ($currentView === 'week')
            <x-calendar.week-view :$days :$hours :$events :$taskBlocks :$projectBlocks :$eventsByCell :$taskBlocksByCell :$projectBlocksByCell :$anchorDate :$weekDaysCount :$selectedDate :$overrideDates />
        @elseif ($currentView === 'day')
            <x-calendar.day-view :$day :$hours :$events :$taskBlocks :$projectBlocks :$eventsByCell :$taskBlocksByCell :$projectBlocksByCell :$overrideDates />
        @elseif ($currentView === 'month')
            <x-calendar.month-view :$monthGroups :$events :$taskBlocks :$eventsByDate :$taskBlocksByDate :$anchorDate :$selectedDate :$overrideDates />
        @endif
    </div>
</div>
