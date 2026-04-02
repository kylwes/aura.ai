<div class="flex-1 overflow-hidden">
    @if ($currentView === 'week')
        <x-calendar.week-view :$days :$hours :$events :$taskBlocks :$projectBlocks :$anchorDate :$weekDaysCount :$selectedDate />
    @elseif ($currentView === 'day')
        <x-calendar.day-view :$day :$hours :$events :$taskBlocks :$projectBlocks />
    @elseif ($currentView === 'month')
        <x-calendar.month-view :$monthGroups :$events :$taskBlocks :$anchorDate :$selectedDate />
    @endif
</div>
