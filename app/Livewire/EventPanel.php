<?php

namespace App\Livewire;

use App\Models\CalendarEvent;
use App\Settings\UserPreferences;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class EventPanel extends Component
{
    public bool $open = false;

    public bool $collapsed = false;

    public ?int $eventId = null;

    public string $date = '';

    public int $startMinutes = 0;

    public int $endMinutes = 0;

    public string $title = '';

    public string $description = '';

    public function mount(UserPreferences $preferences): void
    {
        $this->collapsed = $preferences->event_panel_collapsed;
    }

    #[On('toggle-event-panel')]
    public function toggleCollapsed(): void
    {
        $this->collapsed = ! $this->collapsed;

        $preferences = app(UserPreferences::class);
        $preferences->event_panel_collapsed = $this->collapsed;
        $preferences->save();
    }

    #[On('open-create-event-panel')]
    public function create(string $date, int $startMinutes, int $endMinutes): void
    {
        $this->resetState();
        $this->date = $date;
        $this->startMinutes = $startMinutes;
        $this->endMinutes = $endMinutes;
        $this->open = true;
    }

    #[On('open-edit-event-panel')]
    public function edit(int $eventId): void
    {
        $event = CalendarEvent::where('user_id', auth()->id())->findOrFail($eventId);

        $tz = auth()->user()->timezone ?? 'UTC';
        $localStart = $event->starts_at->copy()->setTimezone($tz);
        $localEnd = $event->ends_at->copy()->setTimezone($tz);

        $this->resetState();
        $this->eventId = $event->id;
        $this->date = $localStart->toDateString();
        $this->startMinutes = $localStart->hour * 60 + $localStart->minute;
        $this->endMinutes = $localEnd->hour * 60 + $localEnd->minute;
        $this->title = $event->title;
        $this->description = $event->description ?? '';
        $this->open = true;
    }

    public function updated(string $property): void
    {
        if (! $this->open || ! in_array($property, ['title', 'description'])) {
            return;
        }

        $this->persist();
    }

    public function delete(): void
    {
        if ($this->eventId) {
            CalendarEvent::where('user_id', auth()->id())
                ->where('id', $this->eventId)
                ->delete();

            $this->dispatch('calendar-event-created');
            $this->close();
        }
    }

    public function close(): void
    {
        // Clean up empty untitled events on close
        if ($this->eventId && $this->title === '') {
            CalendarEvent::where('user_id', auth()->id())
                ->where('id', $this->eventId)
                ->delete();
        }

        $this->open = false;
        $this->resetState();
        $this->dispatch('calendar-event-created');
        $this->dispatch('event-panel-closed');
    }

    private function persist(): void
    {
        $tz = auth()->user()->timezone ?? 'UTC';
        $startsAt = Carbon::parse($this->date, $tz)->startOfDay()->addMinutes($this->startMinutes)->utc();
        $endsAt = Carbon::parse($this->date, $tz)->startOfDay()->addMinutes($this->endMinutes)->utc();

        if ($this->eventId) {
            CalendarEvent::where('user_id', auth()->id())
                ->where('id', $this->eventId)
                ->update([
                    'title' => $this->title,
                    'description' => $this->description ?: null,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                ]);
        } else {
            $event = auth()->user()->calendarEvents()->create([
                'title' => $this->title ?: 'Untitled event',
                'description' => $this->description ?: null,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'is_all_day' => false,
            ]);
            $this->eventId = $event->id;
        }

        $this->dispatch('calendar-event-created');
    }

    public function render()
    {
        $formattedStart = null;
        $formattedEnd = null;
        $formattedDate = null;
        $durationLabel = null;

        if ($this->date) {
            $formattedDate = Carbon::parse($this->date)->format('l, F j');
            $formattedStart = sprintf('%02d:%02d', intdiv($this->startMinutes, 60), $this->startMinutes % 60);
            $formattedEnd = sprintf('%02d:%02d', intdiv($this->endMinutes, 60), $this->endMinutes % 60);

            $duration = $this->endMinutes - $this->startMinutes;
            $durationLabel = $duration >= 60
                ? floor($duration / 60).'h'.($duration % 60 ? ' '.($duration % 60).'min' : '')
                : $duration.'min';
        }

        return view('livewire.event-panel', [
            'formattedDate' => $formattedDate,
            'formattedStart' => $formattedStart,
            'formattedEnd' => $formattedEnd,
            'durationLabel' => $durationLabel,
            'isEditing' => $this->eventId !== null,
        ]);
    }

    private function resetState(): void
    {
        $this->eventId = null;
        $this->date = '';
        $this->startMinutes = 0;
        $this->endMinutes = 0;
        $this->title = '';
        $this->description = '';
    }
}
