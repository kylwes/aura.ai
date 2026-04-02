# Day Override Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow users to override their recurring work schedule for specific dates (custom hours or day off), with calendar visual feedback and auto-rescheduling.

**Architecture:** New `DayOverride` model stores per-user, per-date schedule overrides. A `User::effectiveScheduleFor()` method resolves override vs recurring schedule. Two new Livewire modals (single-day and bulk) are accessible via a 3-dot menu on day headers. `PlannerPage` passes effective schedules to blade views for dimmed-hour styling. `TaskScheduler::computeAvailableSlots()` and `Settings::rescheduleIfNeeded()` are updated to respect overrides.

**Tech Stack:** Laravel 13, Livewire 4, Pest 4, Tailwind v4, LivewireUI Modal

---

## File Structure

| Action | File | Responsibility |
|--------|------|----------------|
| Create | `database/migrations/..._create_day_overrides_table.php` | Schema for day_overrides table |
| Create | `app/Models/DayOverride.php` | Eloquent model |
| Modify | `app/Models/User.php` | Add `dayOverrides()` relationship + `effectiveScheduleFor()` |
| Create | `app/Livewire/DaySettingsModal.php` | Single-day override modal logic |
| Create | `resources/views/livewire/day-settings-modal.blade.php` | Single-day override modal view |
| Create | `app/Livewire/BulkOverrideModal.php` | Bulk override modal logic |
| Create | `resources/views/livewire/bulk-override-modal.blade.php` | Bulk override modal view |
| Modify | `app/Livewire/Pages/PlannerPage.php` | Load overrides, pass effective schedules |
| Modify | `resources/views/components/calendar/week-view.blade.php` | 3-dot menu + dimmed hours |
| Modify | `resources/views/components/calendar/day-view.blade.php` | 3-dot menu + dimmed hours |
| Modify | `app/Ai/Agents/TaskScheduler.php:204-323` | Respect overrides in `computeAvailableSlots()` |
| Modify | `app/Livewire/Settings.php:106-145` | Respect overrides in `rescheduleIfNeeded()` |
| Create | `tests/Feature/Models/DayOverrideTest.php` | Model + effectiveScheduleFor tests |
| Create | `tests/Feature/Livewire/DaySettingsModalTest.php` | Single-day modal tests |
| Create | `tests/Feature/Livewire/BulkOverrideModalTest.php` | Bulk override modal tests |
| Create | `tests/Feature/DayOverrideSchedulingTest.php` | Auto-reschedule + slot computation tests |

---

### Task 1: Migration & Model

**Files:**
- Create: `database/migrations/..._create_day_overrides_table.php`
- Create: `app/Models/DayOverride.php`
- Create: `tests/Feature/Models/DayOverrideTest.php`

- [ ] **Step 1: Write the failing test for DayOverride model**

```php
// tests/Feature/Models/DayOverrideTest.php
<?php

use App\Models\DayOverride;
use App\Models\User;

it('belongs to a user', function () {
    $user = User::factory()->create();
    $override = DayOverride::create([
        'user_id' => $user->id,
        'date' => '2026-04-05',
        'is_day_off' => false,
        'start' => '07:00',
        'end' => '15:00',
        'lunch_start' => '12:00',
        'lunch_end' => '12:30',
    ]);

    expect($override->user)->toBeInstanceOf(User::class)
        ->and($override->user->id)->toBe($user->id);
});

it('enforces unique user_id + date', function () {
    $user = User::factory()->create();
    DayOverride::create([
        'user_id' => $user->id,
        'date' => '2026-04-05',
        'is_day_off' => false,
        'start' => '07:00',
        'end' => '15:00',
    ]);

    DayOverride::create([
        'user_id' => $user->id,
        'date' => '2026-04-05',
        'is_day_off' => true,
    ]);
})->throws(\Illuminate\Database\QueryException::class);

it('casts is_day_off to boolean and date to date', function () {
    $user = User::factory()->create();
    $override = DayOverride::create([
        'user_id' => $user->id,
        'date' => '2026-04-05',
        'is_day_off' => true,
    ]);

    $override->refresh();
    expect($override->is_day_off)->toBeTrue()
        ->and($override->date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=DayOverrideTest`
Expected: FAIL — class/table not found

- [ ] **Step 3: Create migration**

Run: `php artisan make:migration create_day_overrides_table --no-interaction`

Then replace the generated file contents with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('day_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->boolean('is_day_off')->default(false);
            $table->time('start')->nullable();
            $table->time('end')->nullable();
            $table->time('lunch_start')->nullable();
            $table->time('lunch_end')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('day_overrides');
    }
};
```

- [ ] **Step 4: Run migration**

Run: `php artisan migrate`

- [ ] **Step 5: Create DayOverride model**

```php
// app/Models/DayOverride.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'date', 'is_day_off', 'start', 'end', 'lunch_start', 'lunch_end'])]
class DayOverride extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_day_off' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --compact --filter=DayOverrideTest`
Expected: PASS (3 tests)

- [ ] **Step 7: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 8: Commit**

```bash
git add database/migrations/*create_day_overrides_table.php app/Models/DayOverride.php tests/Feature/Models/DayOverrideTest.php
git commit -m "feat: add DayOverride model and migration"
```

---

### Task 2: User Model — Relationship & effectiveScheduleFor()

**Files:**
- Modify: `app/Models/User.php`
- Modify: `tests/Feature/Models/DayOverrideTest.php`

- [ ] **Step 1: Write failing tests for User relationship and effectiveScheduleFor**

Append to `tests/Feature/Models/DayOverrideTest.php`:

```php
it('is accessible via user dayOverrides relationship', function () {
    $user = User::factory()->create();
    DayOverride::create([
        'user_id' => $user->id,
        'date' => '2026-04-05',
        'is_day_off' => false,
        'start' => '07:00',
        'end' => '15:00',
    ]);

    expect($user->dayOverrides)->toHaveCount(1)
        ->and($user->dayOverrides->first()->start)->toBe('07:00');
});

it('effectiveScheduleFor returns override when one exists', function () {
    $user = User::factory()->create();
    $user->ensureWorkSchedule();

    // April 5, 2026 is a Sunday (ISO day 7), which is disabled by default
    // But the override enables it with custom hours
    DayOverride::create([
        'user_id' => $user->id,
        'date' => '2026-04-05',
        'is_day_off' => false,
        'start' => '07:00',
        'end' => '15:00',
        'lunch_start' => '12:00',
        'lunch_end' => '12:30',
    ]);

    $schedule = $user->effectiveScheduleFor(\Illuminate\Support\Carbon::parse('2026-04-05'));

    expect($schedule['enabled'])->toBeTrue()
        ->and($schedule['start'])->toBe('07:00')
        ->and($schedule['end'])->toBe('15:00')
        ->and($schedule['lunch_start'])->toBe('12:00')
        ->and($schedule['lunch_end'])->toBe('12:30');
});

it('effectiveScheduleFor falls back to WorkSchedule when no override', function () {
    $user = User::factory()->create();
    $user->ensureWorkSchedule();

    // April 6, 2026 is a Monday (ISO day 1), default 09:00-17:30
    $schedule = $user->effectiveScheduleFor(\Illuminate\Support\Carbon::parse('2026-04-06'));

    expect($schedule['enabled'])->toBeTrue()
        ->and($schedule['start'])->toBe('09:00')
        ->and($schedule['end'])->toBe('17:30');
});

it('effectiveScheduleFor returns day off when override is_day_off', function () {
    $user = User::factory()->create();
    $user->ensureWorkSchedule();

    DayOverride::create([
        'user_id' => $user->id,
        'date' => '2026-04-06', // Monday
        'is_day_off' => true,
    ]);

    $schedule = $user->effectiveScheduleFor(\Illuminate\Support\Carbon::parse('2026-04-06'));

    expect($schedule['enabled'])->toBeFalse()
        ->and($schedule['start'])->toBeNull()
        ->and($schedule['end'])->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=DayOverrideTest`
Expected: FAIL — `dayOverrides` and `effectiveScheduleFor` not defined

- [ ] **Step 3: Add relationship and method to User model**

In `app/Models/User.php`, add the import at the top:

```php
use Illuminate\Support\Carbon;
```

Add the `DayOverride` import (if not auto-imported).

Add after the `workSchedules()` method (line 77):

```php
public function dayOverrides(): HasMany
{
    return $this->hasMany(DayOverride::class)->orderBy('date');
}

/**
 * Get the effective schedule for a specific date.
 * DayOverride takes precedence over the recurring WorkSchedule.
 *
 * @return array{enabled: bool, start: ?string, end: ?string, lunch_start: ?string, lunch_end: ?string}
 */
public function effectiveScheduleFor(Carbon $date): array
{
    $override = $this->dayOverrides()->where('date', $date->toDateString())->first();

    if ($override) {
        return [
            'enabled' => ! $override->is_day_off,
            'start' => $override->is_day_off ? null : $override->start,
            'end' => $override->is_day_off ? null : $override->end,
            'lunch_start' => $override->is_day_off ? null : $override->lunch_start,
            'lunch_end' => $override->is_day_off ? null : $override->lunch_end,
        ];
    }

    $workSchedule = $this->workSchedules()->where('day', $date->dayOfWeekIso)->first();

    if (! $workSchedule || ! $workSchedule->enabled) {
        return [
            'enabled' => false,
            'start' => null,
            'end' => null,
            'lunch_start' => null,
            'lunch_end' => null,
        ];
    }

    return [
        'enabled' => true,
        'start' => $workSchedule->start,
        'end' => $workSchedule->end,
        'lunch_start' => $workSchedule->lunch_start,
        'lunch_end' => $workSchedule->lunch_end,
    ];
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter=DayOverrideTest`
Expected: PASS (all 7 tests)

- [ ] **Step 5: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 6: Commit**

```bash
git add app/Models/User.php tests/Feature/Models/DayOverrideTest.php
git commit -m "feat: add dayOverrides relationship and effectiveScheduleFor to User"
```

---

### Task 3: DaySettingsModal — Single-Day Override

**Files:**
- Create: `app/Livewire/DaySettingsModal.php`
- Create: `resources/views/livewire/day-settings-modal.blade.php`
- Create: `tests/Feature/Livewire/DaySettingsModalTest.php`

- [ ] **Step 1: Write failing tests**

```php
// tests/Feature/Livewire/DaySettingsModalTest.php
<?php

use App\Livewire\DaySettingsModal;
use App\Models\DayOverride;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->ensureWorkSchedule();
    $this->actingAs($this->user);
});

it('mounts with work schedule defaults when no override exists', function () {
    // 2026-04-06 is Monday, default 09:00-17:30
    Livewire::test(DaySettingsModal::class, ['date' => '2026-04-06'])
        ->assertSet('date', '2026-04-06')
        ->assertSet('isDayOff', false)
        ->assertSet('start', '09:00')
        ->assertSet('end', '17:30')
        ->assertSet('lunchStart', '12:00')
        ->assertSet('lunchEnd', '13:00');
});

it('mounts with existing override values', function () {
    DayOverride::create([
        'user_id' => $this->user->id,
        'date' => '2026-04-06',
        'is_day_off' => false,
        'start' => '07:00',
        'end' => '15:00',
        'lunch_start' => '12:00',
        'lunch_end' => '12:30',
    ]);

    Livewire::test(DaySettingsModal::class, ['date' => '2026-04-06'])
        ->assertSet('start', '07:00')
        ->assertSet('end', '15:00')
        ->assertSet('lunchEnd', '12:30')
        ->assertSet('hasExistingOverride', true);
});

it('saves a new day override', function () {
    Livewire::test(DaySettingsModal::class, ['date' => '2026-04-06'])
        ->set('start', '07:00')
        ->set('end', '15:00')
        ->call('save');

    $override = DayOverride::where('user_id', $this->user->id)
        ->where('date', '2026-04-06')
        ->first();

    expect($override)->not->toBeNull()
        ->and($override->start)->toBe('07:00')
        ->and($override->end)->toBe('15:00');
});

it('updates an existing override', function () {
    DayOverride::create([
        'user_id' => $this->user->id,
        'date' => '2026-04-06',
        'is_day_off' => false,
        'start' => '07:00',
        'end' => '15:00',
    ]);

    Livewire::test(DaySettingsModal::class, ['date' => '2026-04-06'])
        ->set('start', '08:00')
        ->set('end', '16:00')
        ->call('save');

    $override = DayOverride::where('user_id', $this->user->id)
        ->where('date', '2026-04-06')
        ->first();

    expect($override->start)->toBe('08:00')
        ->and($override->end)->toBe('16:00');
});

it('saves a day off override', function () {
    Livewire::test(DaySettingsModal::class, ['date' => '2026-04-06'])
        ->set('isDayOff', true)
        ->call('save');

    $override = DayOverride::where('user_id', $this->user->id)
        ->where('date', '2026-04-06')
        ->first();

    expect($override->is_day_off)->toBeTrue()
        ->and($override->start)->toBeNull();
});

it('resets override to default', function () {
    DayOverride::create([
        'user_id' => $this->user->id,
        'date' => '2026-04-06',
        'is_day_off' => false,
        'start' => '07:00',
        'end' => '15:00',
    ]);

    Livewire::test(DaySettingsModal::class, ['date' => '2026-04-06'])
        ->call('resetToDefault');

    expect(DayOverride::where('user_id', $this->user->id)->where('date', '2026-04-06')->exists())->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=DaySettingsModalTest`
Expected: FAIL — class not found

- [ ] **Step 3: Create DaySettingsModal Livewire component**

```php
// app/Livewire/DaySettingsModal.php
<?php

namespace App\Livewire;

use App\Jobs\ScheduleTasksJob;
use App\Models\DayOverride;
use Illuminate\Support\Carbon;
use LivewireUI\Modal\ModalComponent;

class DaySettingsModal extends ModalComponent
{
    public string $date;

    public bool $isDayOff = false;

    public ?string $start = null;

    public ?string $end = null;

    public ?string $lunchStart = null;

    public ?string $lunchEnd = null;

    public bool $hasExistingOverride = false;

    public function mount(string $date): void
    {
        $this->date = $date;

        $user = auth()->user();
        $override = $user->dayOverrides()->where('date', $date)->first();

        if ($override) {
            $this->hasExistingOverride = true;
            $this->isDayOff = $override->is_day_off;
            $this->start = $override->start;
            $this->end = $override->end;
            $this->lunchStart = $override->lunch_start;
            $this->lunchEnd = $override->lunch_end;
        } else {
            $schedule = $user->effectiveScheduleFor(Carbon::parse($date));
            $this->isDayOff = ! $schedule['enabled'];
            $this->start = $schedule['start'];
            $this->end = $schedule['end'];
            $this->lunchStart = $schedule['lunch_start'];
            $this->lunchEnd = $schedule['lunch_end'];
        }
    }

    public function save(): void
    {
        $user = auth()->user();

        $data = [
            'is_day_off' => $this->isDayOff,
            'start' => $this->isDayOff ? null : $this->start,
            'end' => $this->isDayOff ? null : $this->end,
            'lunch_start' => $this->isDayOff ? null : $this->lunchStart,
            'lunch_end' => $this->isDayOff ? null : $this->lunchEnd,
        ];

        DayOverride::updateOrCreate(
            ['user_id' => $user->id, 'date' => $this->date],
            $data,
        );

        $this->rescheduleAffectedTasks($user, [$this->date]);

        $this->dispatch('day-override-saved');
        $this->forceClose()->closeModal();
    }

    public function resetToDefault(): void
    {
        $user = auth()->user();

        DayOverride::where('user_id', $user->id)
            ->where('date', $this->date)
            ->delete();

        $this->rescheduleAffectedTasks($user, [$this->date]);

        $this->dispatch('day-override-saved');
        $this->forceClose()->closeModal();
    }

    public static function modalMaxWidth(): string
    {
        return 'md';
    }

    public function render()
    {
        return view('livewire.day-settings-modal');
    }

    /** @param array<string> $dates */
    private function rescheduleAffectedTasks(mixed $user, array $dates): void
    {
        $tz = $user->timezone ?? 'UTC';

        $hasAffectedTasks = false;
        foreach ($dates as $dateStr) {
            $schedule = $user->effectiveScheduleFor(Carbon::parse($dateStr));
            $dayStart = Carbon::parse($dateStr, $tz)->startOfDay();
            $dayEnd = $dayStart->copy()->endOfDay();

            $affectedTasks = $user->tasks()
                ->where('status', 'scheduled')
                ->where('is_ai_scheduled', true)
                ->where('is_pinned', false)
                ->where('scheduled_start', '>=', $dayStart->utc())
                ->where('scheduled_start', '<=', $dayEnd->utc())
                ->get()
                ->filter(function ($task) use ($schedule, $tz) {
                    if (! $schedule['enabled']) {
                        return true;
                    }

                    $start = $task->scheduled_start->copy()->setTimezone($tz);
                    $end = $task->scheduled_end->copy()->setTimezone($tz);
                    $workStart = $start->copy()->setTimeFromTimeString($schedule['start']);
                    $workEnd = $start->copy()->setTimeFromTimeString($schedule['end']);

                    return $start->lessThan($workStart) || $end->greaterThan($workEnd);
                });

            if ($affectedTasks->isNotEmpty()) {
                $hasAffectedTasks = true;
                $affectedTasks->each(fn ($task) => $task->update([
                    'status' => 'pending',
                    'scheduled_start' => null,
                    'scheduled_end' => null,
                    'is_ai_scheduled' => false,
                    'ai_reasoning' => null,
                ]));
            }
        }

        if ($hasAffectedTasks) {
            ScheduleTasksJob::dispatch($user);
        }
    }
}
```

- [ ] **Step 4: Create the blade view**

```blade
{{-- resources/views/livewire/day-settings-modal.blade.php --}}
<div>
    {{-- Header --}}
    <div class="flex items-center justify-between px-8 pt-6">
        <div class="flex items-center gap-2">
            <div class="h-6 w-1 rounded-full bg-accent-600"></div>
            <h2 class="text-xl font-semibold text-neutral-900 dark:text-neutral-100">Day Settings</h2>
        </div>
        <button wire:click="$dispatch('closeModal')" class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300">
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
        </button>
    </div>

    {{-- Body --}}
    <div class="space-y-5 px-8 py-6">
        {{-- Date display --}}
        <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">
            {{ \Illuminate\Support\Carbon::parse($date)->format('l, F j, Y') }}
        </p>

        {{-- Working day / Day off toggle --}}
        <div>
            <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Status</label>
            <div class="mt-2 flex items-center gap-2">
                <button wire:click="$set('isDayOff', false)"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition-colors
                            {{ ! $isDayOff
                                ? 'border-neutral-300 bg-neutral-100 text-neutral-900 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100'
                                : 'border-transparent text-neutral-500 hover:bg-neutral-50 dark:text-neutral-400 dark:hover:bg-neutral-800' }}">
                    <span class="size-2 rounded-full bg-green-500"></span>
                    Working day
                </button>
                <button wire:click="$set('isDayOff', true)"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition-colors
                            {{ $isDayOff
                                ? 'border-neutral-300 bg-neutral-100 text-neutral-900 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100'
                                : 'border-transparent text-neutral-500 hover:bg-neutral-50 dark:text-neutral-400 dark:hover:bg-neutral-800' }}">
                    <span class="size-2 rounded-full bg-neutral-400"></span>
                    Day off
                </button>
            </div>
        </div>

        @if (! $isDayOff)
            {{-- Work hours --}}
            <div class="flex items-start gap-6">
                <div class="flex-1">
                    <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Start</label>
                    <input type="time" wire:model="start"
                           class="mt-1 w-full rounded-lg border-0 bg-neutral-100 px-3 py-2.5 text-sm font-medium text-neutral-900 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-800 dark:text-neutral-100">
                </div>
                <div class="flex-1">
                    <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">End</label>
                    <input type="time" wire:model="end"
                           class="mt-1 w-full rounded-lg border-0 bg-neutral-100 px-3 py-2.5 text-sm font-medium text-neutral-900 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-800 dark:text-neutral-100">
                </div>
            </div>

            {{-- Lunch break --}}
            <div class="flex items-start gap-6">
                <div class="flex-1">
                    <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Lunch start</label>
                    <input type="time" wire:model="lunchStart"
                           class="mt-1 w-full rounded-lg border-0 bg-neutral-100 px-3 py-2.5 text-sm font-medium text-neutral-900 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-800 dark:text-neutral-100">
                </div>
                <div class="flex-1">
                    <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Lunch end</label>
                    <input type="time" wire:model="lunchEnd"
                           class="mt-1 w-full rounded-lg border-0 bg-neutral-100 px-3 py-2.5 text-sm font-medium text-neutral-900 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-800 dark:text-neutral-100">
                </div>
            </div>
        @endif
    </div>

    {{-- Footer --}}
    <div class="flex items-center justify-between border-t border-neutral-200 px-8 py-4 dark:border-neutral-800">
        <div>
            @if ($hasExistingOverride)
                <button wire:click="resetToDefault"
                        wire:confirm="This will revert to your default schedule for this day."
                        class="text-xs font-medium text-red-500 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300">
                    Reset to default
                </button>
            @endif
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="$dispatch('closeModal')" class="px-4 py-2 text-sm font-medium text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-neutral-200">
                Cancel
            </button>
            <button wire:click="save" class="rounded-lg bg-accent-600 px-5 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-accent-700">
                Save
            </button>
        </div>
    </div>
</div>
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --compact --filter=DaySettingsModalTest`
Expected: PASS (6 tests)

- [ ] **Step 6: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 7: Commit**

```bash
git add app/Livewire/DaySettingsModal.php resources/views/livewire/day-settings-modal.blade.php tests/Feature/Livewire/DaySettingsModalTest.php
git commit -m "feat: add DaySettingsModal for single-day schedule overrides"
```

---

### Task 4: BulkOverrideModal

**Files:**
- Create: `app/Livewire/BulkOverrideModal.php`
- Create: `resources/views/livewire/bulk-override-modal.blade.php`
- Create: `tests/Feature/Livewire/BulkOverrideModalTest.php`

- [ ] **Step 1: Write failing tests**

```php
// tests/Feature/Livewire/BulkOverrideModalTest.php
<?php

use App\Livewire\BulkOverrideModal;
use App\Models\DayOverride;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->ensureWorkSchedule();
    $this->actingAs($this->user);
});

it('mounts with default work schedule values', function () {
    Livewire::test(BulkOverrideModal::class, ['date' => '2026-04-06'])
        ->assertSet('isDayOff', false)
        ->assertSet('start', '09:00')
        ->assertSet('end', '17:30')
        ->assertSet('mode', 'range');
});

it('saves overrides for a date range', function () {
    Livewire::test(BulkOverrideModal::class, ['date' => '2026-04-06'])
        ->set('mode', 'range')
        ->set('rangeStart', '2026-04-06')
        ->set('rangeEnd', '2026-04-08')
        ->set('start', '07:00')
        ->set('end', '15:00')
        ->call('save');

    $overrides = DayOverride::where('user_id', $this->user->id)
        ->orderBy('date')
        ->get();

    expect($overrides)->toHaveCount(3)
        ->and($overrides[0]->date->toDateString())->toBe('2026-04-06')
        ->and($overrides[1]->date->toDateString())->toBe('2026-04-07')
        ->and($overrides[2]->date->toDateString())->toBe('2026-04-08')
        ->and($overrides[0]->start)->toBe('07:00');
});

it('saves overrides for individually picked dates', function () {
    Livewire::test(BulkOverrideModal::class, ['date' => '2026-04-06'])
        ->set('mode', 'pick')
        ->set('pickedDates', ['2026-04-06', '2026-04-10', '2026-04-15'])
        ->set('start', '08:00')
        ->set('end', '14:00')
        ->set('isDayOff', false)
        ->call('save');

    $overrides = DayOverride::where('user_id', $this->user->id)
        ->orderBy('date')
        ->get();

    expect($overrides)->toHaveCount(3)
        ->and($overrides->pluck('date')->map->toDateString()->toArray())->toBe(['2026-04-06', '2026-04-10', '2026-04-15'])
        ->and($overrides[0]->start)->toBe('08:00');
});

it('replaces existing overrides in range', function () {
    DayOverride::create([
        'user_id' => $this->user->id,
        'date' => '2026-04-06',
        'is_day_off' => false,
        'start' => '10:00',
        'end' => '18:00',
    ]);

    Livewire::test(BulkOverrideModal::class, ['date' => '2026-04-06'])
        ->set('mode', 'range')
        ->set('rangeStart', '2026-04-06')
        ->set('rangeEnd', '2026-04-06')
        ->set('start', '07:00')
        ->set('end', '15:00')
        ->call('save');

    $override = DayOverride::where('user_id', $this->user->id)
        ->where('date', '2026-04-06')
        ->first();

    expect($override->start)->toBe('07:00');
});

it('saves day off for bulk dates', function () {
    Livewire::test(BulkOverrideModal::class, ['date' => '2026-04-06'])
        ->set('mode', 'range')
        ->set('rangeStart', '2026-04-06')
        ->set('rangeEnd', '2026-04-07')
        ->set('isDayOff', true)
        ->call('save');

    $overrides = DayOverride::where('user_id', $this->user->id)->get();

    expect($overrides)->toHaveCount(2)
        ->and($overrides->every(fn ($o) => $o->is_day_off))->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=BulkOverrideModalTest`
Expected: FAIL — class not found

- [ ] **Step 3: Create BulkOverrideModal Livewire component**

```php
// app/Livewire/BulkOverrideModal.php
<?php

namespace App\Livewire;

use App\Jobs\ScheduleTasksJob;
use App\Models\DayOverride;
use Illuminate\Support\Carbon;
use LivewireUI\Modal\ModalComponent;

class BulkOverrideModal extends ModalComponent
{
    public string $mode = 'range';

    public ?string $rangeStart = null;

    public ?string $rangeEnd = null;

    /** @var array<string> */
    public array $pickedDates = [];

    public bool $isDayOff = false;

    public ?string $start = null;

    public ?string $end = null;

    public ?string $lunchStart = null;

    public ?string $lunchEnd = null;

    /** Current month for the date picker calendar */
    public string $pickerMonth;

    public function mount(string $date): void
    {
        $this->rangeStart = $date;
        $this->rangeEnd = $date;
        $this->pickerMonth = Carbon::parse($date)->startOfMonth()->toDateString();

        $user = auth()->user();
        $schedule = $user->effectiveScheduleFor(Carbon::parse($date));
        $this->isDayOff = ! $schedule['enabled'];
        $this->start = $schedule['start'] ?? '09:00';
        $this->end = $schedule['end'] ?? '17:30';
        $this->lunchStart = $schedule['lunch_start'] ?? '12:00';
        $this->lunchEnd = $schedule['lunch_end'] ?? '13:00';
    }

    public function toggleDate(string $date): void
    {
        if (in_array($date, $this->pickedDates)) {
            $this->pickedDates = array_values(array_diff($this->pickedDates, [$date]));
        } else {
            $this->pickedDates[] = $date;
        }
    }

    public function previousMonth(): void
    {
        $this->pickerMonth = Carbon::parse($this->pickerMonth)->subMonth()->toDateString();
    }

    public function nextMonth(): void
    {
        $this->pickerMonth = Carbon::parse($this->pickerMonth)->addMonth()->toDateString();
    }

    public function save(): void
    {
        $dates = $this->resolveDates();

        if (empty($dates)) {
            return;
        }

        $user = auth()->user();

        $data = [
            'is_day_off' => $this->isDayOff,
            'start' => $this->isDayOff ? null : $this->start,
            'end' => $this->isDayOff ? null : $this->end,
            'lunch_start' => $this->isDayOff ? null : $this->lunchStart,
            'lunch_end' => $this->isDayOff ? null : $this->lunchEnd,
        ];

        foreach ($dates as $date) {
            DayOverride::updateOrCreate(
                ['user_id' => $user->id, 'date' => $date],
                $data,
            );
        }

        $this->rescheduleAffectedTasks($user, $dates);

        $this->dispatch('day-override-saved');
        $this->forceClose()->closeModal();
    }

    public static function modalMaxWidth(): string
    {
        return 'lg';
    }

    public function render()
    {
        $pickerStart = Carbon::parse($this->pickerMonth)->startOfMonth()->startOfWeek();
        $pickerEnd = Carbon::parse($this->pickerMonth)->endOfMonth()->endOfWeek();

        $calendarDays = collect();
        $cursor = $pickerStart->copy();
        while ($cursor->lte($pickerEnd)) {
            $calendarDays->push($cursor->copy());
            $cursor->addDay();
        }

        return view('livewire.bulk-override-modal', [
            'calendarDays' => $calendarDays,
            'pickerMonthLabel' => Carbon::parse($this->pickerMonth)->format('F Y'),
        ]);
    }

    /** @return array<string> */
    private function resolveDates(): array
    {
        if ($this->mode === 'pick') {
            sort($this->pickedDates);

            return $this->pickedDates;
        }

        if (! $this->rangeStart || ! $this->rangeEnd) {
            return [];
        }

        $dates = [];
        $cursor = Carbon::parse($this->rangeStart);
        $end = Carbon::parse($this->rangeEnd);

        while ($cursor->lte($end)) {
            $dates[] = $cursor->toDateString();
            $cursor->addDay();
        }

        return $dates;
    }

    /** @param array<string> $dates */
    private function rescheduleAffectedTasks(mixed $user, array $dates): void
    {
        $tz = $user->timezone ?? 'UTC';

        $hasAffectedTasks = false;
        foreach ($dates as $dateStr) {
            $schedule = $user->effectiveScheduleFor(Carbon::parse($dateStr));
            $dayStart = Carbon::parse($dateStr, $tz)->startOfDay();
            $dayEnd = $dayStart->copy()->endOfDay();

            $affectedTasks = $user->tasks()
                ->where('status', 'scheduled')
                ->where('is_ai_scheduled', true)
                ->where('is_pinned', false)
                ->where('scheduled_start', '>=', $dayStart->utc())
                ->where('scheduled_start', '<=', $dayEnd->utc())
                ->get()
                ->filter(function ($task) use ($schedule, $tz) {
                    if (! $schedule['enabled']) {
                        return true;
                    }

                    $start = $task->scheduled_start->copy()->setTimezone($tz);
                    $end = $task->scheduled_end->copy()->setTimezone($tz);
                    $workStart = $start->copy()->setTimeFromTimeString($schedule['start']);
                    $workEnd = $start->copy()->setTimeFromTimeString($schedule['end']);

                    return $start->lessThan($workStart) || $end->greaterThan($workEnd);
                });

            if ($affectedTasks->isNotEmpty()) {
                $hasAffectedTasks = true;
                $affectedTasks->each(fn ($task) => $task->update([
                    'status' => 'pending',
                    'scheduled_start' => null,
                    'scheduled_end' => null,
                    'is_ai_scheduled' => false,
                    'ai_reasoning' => null,
                ]));
            }
        }

        if ($hasAffectedTasks) {
            ScheduleTasksJob::dispatch($user);
        }
    }
}
```

- [ ] **Step 4: Create the blade view**

```blade
{{-- resources/views/livewire/bulk-override-modal.blade.php --}}
<div>
    {{-- Header --}}
    <div class="flex items-center justify-between px-8 pt-6">
        <div class="flex items-center gap-2">
            <div class="h-6 w-1 rounded-full bg-accent-600"></div>
            <h2 class="text-xl font-semibold text-neutral-900 dark:text-neutral-100">Bulk Override</h2>
        </div>
        <button wire:click="$dispatch('closeModal')" class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300">
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
        </button>
    </div>

    {{-- Body --}}
    <div class="space-y-5 px-8 py-6">
        {{-- Mode toggle --}}
        <div>
            <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Selection mode</label>
            <div class="mt-2 flex items-center gap-2">
                <button wire:click="$set('mode', 'range')"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition-colors
                            {{ $mode === 'range'
                                ? 'border-neutral-300 bg-neutral-100 text-neutral-900 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100'
                                : 'border-transparent text-neutral-500 hover:bg-neutral-50 dark:text-neutral-400 dark:hover:bg-neutral-800' }}">
                    Date range
                </button>
                <button wire:click="$set('mode', 'pick')"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition-colors
                            {{ $mode === 'pick'
                                ? 'border-neutral-300 bg-neutral-100 text-neutral-900 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100'
                                : 'border-transparent text-neutral-500 hover:bg-neutral-50 dark:text-neutral-400 dark:hover:bg-neutral-800' }}">
                    Pick dates
                </button>
            </div>
        </div>

        {{-- Date range inputs --}}
        @if ($mode === 'range')
            <div class="flex items-start gap-6">
                <div class="flex-1">
                    <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">From</label>
                    <input type="date" wire:model="rangeStart"
                           class="mt-1 w-full rounded-lg border border-neutral-200 bg-transparent px-3 py-2.5 text-sm text-neutral-700 focus:border-accent-500 focus:ring-1 focus:ring-accent-500 dark:border-neutral-700 dark:text-neutral-300">
                </div>
                <div class="flex-1">
                    <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Until</label>
                    <input type="date" wire:model="rangeEnd"
                           class="mt-1 w-full rounded-lg border border-neutral-200 bg-transparent px-3 py-2.5 text-sm text-neutral-700 focus:border-accent-500 focus:ring-1 focus:ring-accent-500 dark:border-neutral-700 dark:text-neutral-300">
                </div>
            </div>
        @endif

        {{-- Date picker calendar --}}
        @if ($mode === 'pick')
            <div>
                <div class="flex items-center justify-between">
                    <button wire:click="previousMonth" class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                    </button>
                    <span class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">{{ $pickerMonthLabel }}</span>
                    <button wire:click="nextMonth" class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                    </button>
                </div>

                <div class="mt-2 grid grid-cols-7 gap-1 text-center">
                    @foreach (['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'] as $dayLabel)
                        <div class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500 py-1">{{ $dayLabel }}</div>
                    @endforeach

                    @foreach ($calendarDays as $calDay)
                        @php
                            $isCurrentMonth = $calDay->month === \Illuminate\Support\Carbon::parse($pickerMonth)->month;
                            $isPicked = in_array($calDay->toDateString(), $pickedDates);
                        @endphp
                        <button wire:click="toggleDate('{{ $calDay->toDateString() }}')"
                                type="button"
                                class="rounded-lg py-1.5 text-xs font-medium transition-colors
                                    {{ $isPicked
                                        ? 'bg-accent-600 text-white'
                                        : ($isCurrentMonth
                                            ? 'text-neutral-700 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800'
                                            : 'text-neutral-300 dark:text-neutral-600') }}">
                            {{ $calDay->format('j') }}
                        </button>
                    @endforeach
                </div>

                @if (! empty($pickedDates))
                    <p class="mt-2 text-xs text-neutral-500 dark:text-neutral-400">
                        {{ count($pickedDates) }} {{ count($pickedDates) === 1 ? 'date' : 'dates' }} selected
                    </p>
                @endif
            </div>
        @endif

        <div class="border-t border-neutral-200 pt-5 dark:border-neutral-800">
            {{-- Working day / Day off toggle --}}
            <div>
                <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Status</label>
                <div class="mt-2 flex items-center gap-2">
                    <button wire:click="$set('isDayOff', false)"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition-colors
                                {{ ! $isDayOff
                                    ? 'border-neutral-300 bg-neutral-100 text-neutral-900 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100'
                                    : 'border-transparent text-neutral-500 hover:bg-neutral-50 dark:text-neutral-400 dark:hover:bg-neutral-800' }}">
                        <span class="size-2 rounded-full bg-green-500"></span>
                        Working day
                    </button>
                    <button wire:click="$set('isDayOff', true)"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition-colors
                                {{ $isDayOff
                                    ? 'border-neutral-300 bg-neutral-100 text-neutral-900 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100'
                                    : 'border-transparent text-neutral-500 hover:bg-neutral-50 dark:text-neutral-400 dark:hover:bg-neutral-800' }}">
                        <span class="size-2 rounded-full bg-neutral-400"></span>
                        Day off
                    </button>
                </div>
            </div>

            @if (! $isDayOff)
                {{-- Work hours --}}
                <div class="mt-5 flex items-start gap-6">
                    <div class="flex-1">
                        <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Start</label>
                        <input type="time" wire:model="start"
                               class="mt-1 w-full rounded-lg border-0 bg-neutral-100 px-3 py-2.5 text-sm font-medium text-neutral-900 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-800 dark:text-neutral-100">
                    </div>
                    <div class="flex-1">
                        <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">End</label>
                        <input type="time" wire:model="end"
                               class="mt-1 w-full rounded-lg border-0 bg-neutral-100 px-3 py-2.5 text-sm font-medium text-neutral-900 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-800 dark:text-neutral-100">
                    </div>
                </div>

                {{-- Lunch break --}}
                <div class="mt-5 flex items-start gap-6">
                    <div class="flex-1">
                        <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Lunch start</label>
                        <input type="time" wire:model="lunchStart"
                               class="mt-1 w-full rounded-lg border-0 bg-neutral-100 px-3 py-2.5 text-sm font-medium text-neutral-900 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-800 dark:text-neutral-100">
                    </div>
                    <div class="flex-1">
                        <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Lunch end</label>
                        <input type="time" wire:model="lunchEnd"
                               class="mt-1 w-full rounded-lg border-0 bg-neutral-100 px-3 py-2.5 text-sm font-medium text-neutral-900 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-800 dark:text-neutral-100">
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Footer --}}
    <div class="flex items-center justify-end gap-3 border-t border-neutral-200 px-8 py-4 dark:border-neutral-800">
        <button wire:click="$dispatch('closeModal')" class="px-4 py-2 text-sm font-medium text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-neutral-200">
            Cancel
        </button>
        <button wire:click="save" class="rounded-lg bg-accent-600 px-5 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-accent-700">
            Save
        </button>
    </div>
</div>
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --compact --filter=BulkOverrideModalTest`
Expected: PASS (5 tests)

- [ ] **Step 6: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 7: Commit**

```bash
git add app/Livewire/BulkOverrideModal.php resources/views/livewire/bulk-override-modal.blade.php tests/Feature/Livewire/BulkOverrideModalTest.php
git commit -m "feat: add BulkOverrideModal for multi-date schedule overrides"
```

---

### Task 5: Calendar Day Headers — 3-Dot Menu + Override Indicator

**Files:**
- Modify: `resources/views/components/calendar/week-view.blade.php:23-39`
- Modify: `resources/views/components/calendar/day-view.blade.php:7-13`

- [ ] **Step 1: Update week-view day headers with 3-dot menu and override indicator**

In `resources/views/components/calendar/week-view.blade.php`, replace the day header `<div>` content inside the `@foreach ($days as $dayIndex => $day)` loop (lines 28-38) with:

Replace the existing day header div (lines 28-38):

```blade
<div wire:key="wh-{{ $day->format('Y-m-d') }}"
     data-date="{{ $day->format('Y-m-d') }}"
     @if ($day->format('Y-m-d') === $anchorDate) data-anchor @endif
     style="width: var(--col-width, {{ 100 / $weekDaysCount }}vw)"
     class="group/header relative flex shrink-0 items-center justify-center gap-1.5 border-r border-neutral-200 py-2 last:border-r-0 dark:border-neutral-800
            snap-start
            {{ $isSelected ? 'bg-accent-100/80 dark:bg-accent-900/30' : ($day->isToday() ? 'bg-accent-50/50 dark:bg-accent-950/20' : ($day->isWeekend() ? 'bg-neutral-100/60 dark:bg-neutral-800/40' : '')) }}
            {{ $isGroupStart ? 'border-l border-neutral-300 dark:border-neutral-700' : '' }}">
    <span class="text-xs font-medium {{ $isSelected ? 'text-accent-600 dark:text-accent-400' : 'text-neutral-400 dark:text-neutral-500' }}">{{ $day->format('D') }}</span>
    <span class="text-sm font-semibold {{ $isSelected ? 'text-accent-700 dark:text-accent-300' : ($day->isToday() ? 'text-accent-600 dark:text-accent-400' : 'text-neutral-900 dark:text-neutral-100') }}">{{ $day->format('j') }}</span>

    {{-- Override indicator dot --}}
    @if (isset($effectiveSchedules[$day->format('Y-m-d')]['has_override']) && $effectiveSchedules[$day->format('Y-m-d')]['has_override'])
        <span class="size-1.5 rounded-full bg-accent-500"></span>
    @endif

    {{-- 3-dot menu --}}
    <div x-data="{ open: false }" class="absolute right-1 top-1 opacity-0 transition-opacity group-hover/header:opacity-100"
         :class="{ '!opacity-100': open }">
        <button @click="open = !open" class="rounded p-0.5 text-neutral-400 hover:bg-neutral-200 hover:text-neutral-600 dark:hover:bg-neutral-700 dark:hover:text-neutral-300">
            <svg class="size-3.5" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="4" r="1.5"/><circle cx="10" cy="10" r="1.5"/><circle cx="10" cy="16" r="1.5"/></svg>
        </button>
        <div x-show="open" x-cloak @click.outside="open = false"
             class="absolute right-0 z-50 mt-1 w-36 rounded-lg border border-neutral-200 bg-white py-1 shadow-lg dark:border-neutral-700 dark:bg-neutral-800">
            <button @click="open = false; $dispatch('openModal', { component: 'day-settings-modal', arguments: { date: '{{ $day->format('Y-m-d') }}' } })"
                    class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-xs font-medium text-neutral-700 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-700">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                Day settings
            </button>
            <button @click="open = false; $dispatch('openModal', { component: 'bulk-override-modal', arguments: { date: '{{ $day->format('Y-m-d') }}' } })"
                    class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-xs font-medium text-neutral-700 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-700">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v9.75"/></svg>
                Bulk override
            </button>
        </div>
    </div>
</div>
```

- [ ] **Step 2: Update day-view header with 3-dot menu**

In `resources/views/components/calendar/day-view.blade.php`, replace the day header (lines 7-13) with:

```blade
<div class="sticky top-0 z-10 grid grid-cols-[60px_1fr] border-b border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
    <div class="border-r border-neutral-200 dark:border-neutral-800"></div>
    <div class="group/header relative px-4 py-3 {{ $day->isToday() ? 'bg-accent-50/50 dark:bg-accent-950/20' : '' }}">
        <div class="flex items-center gap-2">
            <div>
                <p class="text-xs font-medium text-neutral-400 dark:text-neutral-500">{{ $day->format('l') }}</p>
                <p class="mt-0.5 text-lg font-semibold {{ $day->isToday() ? 'text-accent-600 dark:text-accent-400' : 'text-neutral-900 dark:text-neutral-100' }}">{{ $day->format('F j, Y') }}</p>
            </div>
            @if (isset($effectiveSchedules[$day->format('Y-m-d')]['has_override']) && $effectiveSchedules[$day->format('Y-m-d')]['has_override'])
                <span class="size-1.5 rounded-full bg-accent-500"></span>
            @endif
        </div>

        {{-- 3-dot menu --}}
        <div x-data="{ open: false }" class="absolute right-4 top-1/2 -translate-y-1/2 opacity-0 transition-opacity group-hover/header:opacity-100"
             :class="{ '!opacity-100': open }">
            <button @click="open = !open" class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-200 hover:text-neutral-600 dark:hover:bg-neutral-700 dark:hover:text-neutral-300">
                <svg class="size-4" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="4" r="1.5"/><circle cx="10" cy="10" r="1.5"/><circle cx="10" cy="16" r="1.5"/></svg>
            </button>
            <div x-show="open" x-cloak @click.outside="open = false"
                 class="absolute right-0 z-50 mt-1 w-36 rounded-lg border border-neutral-200 bg-white py-1 shadow-lg dark:border-neutral-700 dark:bg-neutral-800">
                <button @click="open = false; $dispatch('openModal', { component: 'day-settings-modal', arguments: { date: '{{ $day->format('Y-m-d') }}' } })"
                        class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-xs font-medium text-neutral-700 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-700">
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                    Day settings
                </button>
                <button @click="open = false; $dispatch('openModal', { component: 'bulk-override-modal', arguments: { date: '{{ $day->format('Y-m-d') }}' } })"
                        class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-xs font-medium text-neutral-700 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-700">
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v9.75"/></svg>
                    Bulk override
                </button>
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 3: Add `effectiveSchedules` prop to both views**

In `resources/views/components/calendar/week-view.blade.php`, update the `@props` line (line 1):

```blade
@props(['days', 'hours', 'events', 'taskBlocks', 'projectBlocks', 'anchorDate', 'weekDaysCount' => 7, 'selectedDate' => null, 'effectiveSchedules' => []])
```

In `resources/views/components/calendar/day-view.blade.php`, update the `@props` line (line 1):

```blade
@props(['day', 'hours', 'events', 'taskBlocks', 'projectBlocks', 'effectiveSchedules' => []])
```

- [ ] **Step 4: Commit**

```bash
git add resources/views/components/calendar/week-view.blade.php resources/views/components/calendar/day-view.blade.php
git commit -m "feat: add 3-dot menu and override indicator to calendar day headers"
```

---

### Task 6: PlannerPage — Load Overrides & Pass Effective Schedules

**Files:**
- Modify: `app/Livewire/Pages/PlannerPage.php:255-382`
- Modify: `resources/views/livewire/pages/planner-page.blade.php`

- [ ] **Step 1: Add DayOverride import and day-override-saved listener to PlannerPage**

In `app/Livewire/Pages/PlannerPage.php`:

Add to the imports at top:

```php
use App\Models\DayOverride;
```

Add `'day-override-saved' => '$refresh'` to the `getListeners()` array (around line 42):

```php
public function getListeners(): array
{
    return [
        'echo-private:App.Models.User.'.auth()->id().',OverlapsResolved' => '$refresh',
        'echo-private:App.Models.User.'.auth()->id().',ScheduleCompleted' => 'onScheduleCompleted',
        'day-override-saved' => '$refresh',
    ];
}
```

- [ ] **Step 2: Build effective schedules in render() and pass to views**

In the `render()` method, after the `$workSchedules` line (line 291), add the effective schedule computation. Insert after `$workSchedules = $user->workSchedules()->get()->keyBy('day');`:

```php
// Load day overrides for the visible range
$dayOverrides = $user->dayOverrides()
    ->where('date', '>=', $rangeStart->toDateString())
    ->where('date', '<=', $rangeEnd->toDateString())
    ->get()
    ->keyBy(fn ($o) => $o->date->toDateString());

// Build effective schedule per visible day
$effectiveSchedules = [];
$visibleDays = match ($this->currentView) {
    'day' => collect([$this->currentDate->copy()]),
    'week' => $viewData['allDays'],
    default => collect(),
};

foreach ($visibleDays as $visibleDay) {
    $dateStr = $visibleDay->format('Y-m-d');
    $override = $dayOverrides->get($dateStr);

    if ($override) {
        $effectiveSchedules[$dateStr] = [
            'has_override' => true,
            'enabled' => ! $override->is_day_off,
            'start' => $override->is_day_off ? null : $override->start,
            'end' => $override->is_day_off ? null : $override->end,
            'lunch_start' => $override->is_day_off ? null : $override->lunch_start,
            'lunch_end' => $override->is_day_off ? null : $override->lunch_end,
        ];
    } else {
        $ws = $workSchedules->get($visibleDay->dayOfWeekIso);
        $effectiveSchedules[$dateStr] = [
            'has_override' => false,
            'enabled' => $ws && $ws->enabled,
            'start' => $ws?->start,
            'end' => $ws?->end,
            'lunch_start' => $ws?->lunch_start,
            'lunch_end' => $ws?->lunch_end,
        ];
    }
}
```

Then update the lunch break lookup in the virtual project block generation (around line 321-326). Replace:

```php
// Get lunch break for this day
$workSchedule = $workSchedules->get($isoDay);
$lunchStart = ($workSchedule && $workSchedule->lunch_start)
    ? Carbon::parse($dateStr, $tz)->setTimeFromTimeString($workSchedule->lunch_start)->utc()
    : null;
$lunchEnd = ($workSchedule && $workSchedule->lunch_end)
    ? Carbon::parse($dateStr, $tz)->setTimeFromTimeString($workSchedule->lunch_end)->utc()
    : null;
```

With:

```php
// Get lunch break for this day (override takes precedence)
$dayEffective = $effectiveSchedules[$dateStr] ?? null;
$effectiveLunchStart = $dayEffective['lunch_start'] ?? null;
$effectiveLunchEnd = $dayEffective['lunch_end'] ?? null;

if (! $effectiveLunchStart) {
    $workSchedule = $workSchedules->get($isoDay);
    $effectiveLunchStart = $workSchedule?->lunch_start;
    $effectiveLunchEnd = $workSchedule?->lunch_end;
}

$lunchStart = $effectiveLunchStart
    ? Carbon::parse($dateStr, $tz)->setTimeFromTimeString($effectiveLunchStart)->utc()
    : null;
$lunchEnd = $effectiveLunchEnd
    ? Carbon::parse($dateStr, $tz)->setTimeFromTimeString($effectiveLunchEnd)->utc()
    : null;
```

Finally, add `$effectiveSchedules` to the return array (around line 374):

```php
return view('livewire.pages.planner-page', [
    'events' => $events,
    'taskBlocks' => $taskBlocks,
    'projectBlocks' => $projectBlocks,
    'currentView' => $this->currentView,
    'weekDaysCount' => $this->weekDaysCount,
    'selectedDate' => $this->selectedDate,
    'effectiveSchedules' => $effectiveSchedules,
    ...$viewData,
]);
```

- [ ] **Step 3: Pass effectiveSchedules to blade components**

In `resources/views/livewire/pages/planner-page.blade.php`, update the component calls:

```blade
<div class="flex-1 overflow-hidden">
    @if ($currentView === 'week')
        <x-calendar.week-view :$days :$hours :$events :$taskBlocks :$projectBlocks :$anchorDate :$weekDaysCount :$selectedDate :$effectiveSchedules />
    @elseif ($currentView === 'day')
        <x-calendar.day-view :$day :$hours :$events :$taskBlocks :$projectBlocks :$effectiveSchedules />
    @elseif ($currentView === 'month')
        <x-calendar.month-view :$monthGroups :$events :$taskBlocks :$anchorDate :$selectedDate />
    @endif
</div>
```

- [ ] **Step 4: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/Pages/PlannerPage.php resources/views/livewire/pages/planner-page.blade.php
git commit -m "feat: load day overrides and pass effective schedules to calendar views"
```

---

### Task 7: Dimmed Non-Working Hours

**Files:**
- Modify: `resources/views/components/calendar/week-view.blade.php:78-84`
- Modify: `resources/views/components/calendar/day-view.blade.php:34-37`

- [ ] **Step 1: Add dimmed styling to week-view hour cells**

In `resources/views/components/calendar/week-view.blade.php`, inside the hour cell div (around line 78-84), add a computed dimmed class. Replace the cell class logic:

Find the existing cell `<div>` (around line 78):

```blade
<div wire:key="wc-{{ $day->format('Y-m-d') }}-{{ $hour }}"
     data-date="{{ $day->format('Y-m-d') }}"
     data-hour="{{ $hour }}"
     style="width: var(--col-width, {{ 100 / $weekDaysCount }}vw)"
     class="relative h-[60px] shrink-0 border-b border-r border-neutral-100 last:border-r-0 dark:border-neutral-800/50
            {{ $isSelected ? 'bg-accent-50/50 dark:bg-accent-950/15' : ($day->isToday() ? 'bg-accent-50/30 dark:bg-accent-950/10' : ($day->isWeekend() ? 'bg-neutral-100/40 dark:bg-neutral-800/30' : '')) }}
            {{ $isGroupStart ? 'border-l border-neutral-200 dark:border-neutral-700' : '' }}">
```

Replace with:

```blade
@php
    $daySchedule = $effectiveSchedules[$day->format('Y-m-d')] ?? null;
    $isDimmed = false;
    if ($daySchedule) {
        if (! $daySchedule['enabled']) {
            $isDimmed = true;
        } elseif ($daySchedule['start'] && $daySchedule['end']) {
            $schedStart = (int) substr($daySchedule['start'], 0, 2);
            $schedEnd = (int) substr($daySchedule['end'], 0, 2);
            $isDimmed = $hour < $schedStart || $hour >= $schedEnd;
        }
    }
@endphp
<div wire:key="wc-{{ $day->format('Y-m-d') }}-{{ $hour }}"
     data-date="{{ $day->format('Y-m-d') }}"
     data-hour="{{ $hour }}"
     style="width: var(--col-width, {{ 100 / $weekDaysCount }}vw)"
     class="relative h-[60px] shrink-0 border-b border-r border-neutral-100 last:border-r-0 dark:border-neutral-800/50
            {{ $isDimmed ? 'bg-neutral-100/70 dark:bg-neutral-800/50' : ($isSelected ? 'bg-accent-50/50 dark:bg-accent-950/15' : ($day->isToday() ? 'bg-accent-50/30 dark:bg-accent-950/10' : ($day->isWeekend() ? 'bg-neutral-100/40 dark:bg-neutral-800/30' : ''))) }}
            {{ $isGroupStart ? 'border-l border-neutral-200 dark:border-neutral-700' : '' }}">
```

- [ ] **Step 2: Add dimmed styling to day-view hour cells**

In `resources/views/components/calendar/day-view.blade.php`, before the hour cell `<div>` (around line 34), add the dimmed computation. Replace:

```blade
<div wire:key="day-cell-{{ $hour }}"
     data-date="{{ $day->format('Y-m-d') }}"
     data-hour="{{ $hour }}"
     class="relative min-h-[60px] border-b border-neutral-100 dark:border-neutral-800/50 {{ $day->isToday() ? 'bg-accent-50/30 dark:bg-accent-950/10' : '' }}"
     style="grid-row: {{ $loop->iteration }};">
```

With:

```blade
@php
    $daySchedule = $effectiveSchedules[$day->format('Y-m-d')] ?? null;
    $isDimmed = false;
    if ($daySchedule) {
        if (! $daySchedule['enabled']) {
            $isDimmed = true;
        } elseif ($daySchedule['start'] && $daySchedule['end']) {
            $schedStart = (int) substr($daySchedule['start'], 0, 2);
            $schedEnd = (int) substr($daySchedule['end'], 0, 2);
            $isDimmed = $hour < $schedStart || $hour >= $schedEnd;
        }
    }
@endphp
<div wire:key="day-cell-{{ $hour }}"
     data-date="{{ $day->format('Y-m-d') }}"
     data-hour="{{ $hour }}"
     class="relative min-h-[60px] border-b border-neutral-100 dark:border-neutral-800/50
            {{ $isDimmed ? 'bg-neutral-100/70 dark:bg-neutral-800/50' : ($day->isToday() ? 'bg-accent-50/30 dark:bg-accent-950/10' : '') }}"
     style="grid-row: {{ $loop->iteration }};">
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/components/calendar/week-view.blade.php resources/views/components/calendar/day-view.blade.php
git commit -m "feat: dim non-working hours based on effective schedule per day"
```

---

### Task 8: TaskScheduler — Respect Day Overrides in Available Slots

**Files:**
- Modify: `app/Ai/Agents/TaskScheduler.php:204-323`
- Create: `tests/Feature/DayOverrideSchedulingTest.php`

- [ ] **Step 1: Write failing test for override-aware slot computation**

```php
// tests/Feature/DayOverrideSchedulingTest.php
<?php

use App\Ai\Agents\TaskScheduler;
use App\Models\DayOverride;
use App\Models\User;
use Illuminate\Support\Carbon;

it('computeAvailableSlots uses day override instead of work schedule', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-06 06:00', 'UTC')); // Monday

    $user = User::factory()->create(['timezone' => 'UTC']);
    $user->ensureWorkSchedule(); // Mon default: 09:00-17:30

    DayOverride::create([
        'user_id' => $user->id,
        'date' => '2026-04-06',
        'is_day_off' => false,
        'start' => '07:00',
        'end' => '15:00',
        'lunch_start' => '12:00',
        'lunch_end' => '12:30',
    ]);

    $slots = TaskScheduler::computeAvailableSlots($user);

    // First slot should start at 07:00 on Monday (override), not 09:00
    $mondaySlots = collect($slots)->filter(fn ($s) => $s['date'] === '2026-04-06');

    expect($mondaySlots->first()['start'])->toBe('07:00')
        ->and($mondaySlots->last()['end'])->toBe('15:00');
});

it('computeAvailableSlots skips day-off override', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-06 06:00', 'UTC')); // Monday

    $user = User::factory()->create(['timezone' => 'UTC']);
    $user->ensureWorkSchedule();

    DayOverride::create([
        'user_id' => $user->id,
        'date' => '2026-04-06',
        'is_day_off' => true,
    ]);

    $slots = TaskScheduler::computeAvailableSlots($user);
    $mondaySlots = collect($slots)->filter(fn ($s) => $s['date'] === '2026-04-06');

    expect($mondaySlots)->toBeEmpty();
});

it('computeAvailableSlots enables a normally-disabled day via override', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-05 06:00', 'UTC')); // Sunday

    $user = User::factory()->create(['timezone' => 'UTC']);
    $user->ensureWorkSchedule(); // Sunday is disabled by default

    DayOverride::create([
        'user_id' => $user->id,
        'date' => '2026-04-05',
        'is_day_off' => false,
        'start' => '10:00',
        'end' => '14:00',
    ]);

    $slots = TaskScheduler::computeAvailableSlots($user);
    $sundaySlots = collect($slots)->filter(fn ($s) => $s['date'] === '2026-04-05');

    expect($sundaySlots)->not->toBeEmpty()
        ->and($sundaySlots->first()['start'])->toBe('10:00');
});

afterEach(function () {
    Carbon::setTestNow();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=DayOverrideSchedulingTest`
Expected: FAIL — slots still use WorkSchedule

- [ ] **Step 3: Update computeAvailableSlots to check day overrides**

In `app/Ai/Agents/TaskScheduler.php`, in the `computeAvailableSlots` method (around line 204), add the `DayOverride` import at the top of the file:

```php
use App\Models\DayOverride;
```

Inside `computeAvailableSlots`, after line 210 (`$workSchedules = $user->workSchedules()->get()->keyBy('day');`), add:

```php
// Pre-load day overrides for the scanning range
$overrides = $user->dayOverrides()
    ->where('date', '>=', $now->toDateString())
    ->where('date', '<=', $now->copy()->addDays(365)->toDateString())
    ->get()
    ->keyBy(fn ($o) => $o->date->toDateString());
```

Then replace the schedule resolution block (around lines 218-221):

```php
$schedule = $workSchedules->get($isoDay);

if (! $schedule || ! $schedule->enabled || ! $schedule->start || ! $schedule->end) {
    continue;
}
```

With:

```php
$dateStr = $day->toDateString();
$override = $overrides->get($dateStr);

if ($override) {
    if ($override->is_day_off || ! $override->start || ! $override->end) {
        continue;
    }
    $dayStartTime = $override->start;
    $dayEndTime = $override->end;
    $lunchStart = $override->lunch_start;
    $lunchEnd = $override->lunch_end;
} else {
    $schedule = $workSchedules->get($isoDay);
    if (! $schedule || ! $schedule->enabled || ! $schedule->start || ! $schedule->end) {
        continue;
    }
    $dayStartTime = $schedule->start;
    $dayEndTime = $schedule->end;
    $lunchStart = $schedule->lunch_start;
    $lunchEnd = $schedule->lunch_end;
}
```

Then update the lines that use `$schedule->start`, `$schedule->end`, `$schedule->lunch_start`, `$schedule->lunch_end` (around lines 225-229):

Replace:

```php
$dayStart = $day->copy()->setTimeFromTimeString($schedule->start);
$dayEnd = $day->copy()->setTimeFromTimeString($schedule->end);
$lunchStart = $schedule->lunch_start;
$lunchEnd = $schedule->lunch_end;
```

With:

```php
$dayStart = $day->copy()->setTimeFromTimeString($dayStartTime);
$dayEnd = $day->copy()->setTimeFromTimeString($dayEndTime);
```

(The `$lunchStart` and `$lunchEnd` variables are already set above.)

- [ ] **Step 4: Also update buildContext to include overrides info**

In the `buildContext` method, after the working schedule section (around line 117), add:

```php
// Include day overrides for the next 14 days
$overrides = $user->dayOverrides()
    ->where('date', '>=', $now->toDateString())
    ->where('date', '<=', $now->copy()->addDays(14)->toDateString())
    ->orderBy('date')
    ->get();

if ($overrides->isNotEmpty()) {
    $context .= "## Day Overrides (these override the weekly schedule for specific dates)\n";
    foreach ($overrides as $override) {
        if ($override->is_day_off) {
            $context .= "- {$override->date->format('Y-m-d')} ({$override->date->format('l')}): DAY OFF\n";
        } else {
            $line = "- {$override->date->format('Y-m-d')} ({$override->date->format('l')}): {$override->start}-{$override->end}";
            if ($override->lunch_start && $override->lunch_end) {
                $line .= " (lunch {$override->lunch_start}-{$override->lunch_end})";
            }
            $context .= $line."\n";
        }
    }
    $context .= "\n";
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --compact --filter=DayOverrideSchedulingTest`
Expected: PASS (3 tests)

- [ ] **Step 6: Run all existing tests to check for regressions**

Run: `php artisan test --compact`
Expected: All tests pass

- [ ] **Step 7: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 8: Commit**

```bash
git add app/Ai/Agents/TaskScheduler.php tests/Feature/DayOverrideSchedulingTest.php
git commit -m "feat: respect day overrides in TaskScheduler slot computation"
```

---

### Task 9: Settings rescheduleIfNeeded — Respect Day Overrides

**Files:**
- Modify: `app/Livewire/Settings.php:106-145`

- [ ] **Step 1: Update rescheduleIfNeeded to check day overrides**

In `app/Livewire/Settings.php`, add the import at top:

```php
use App\Models\DayOverride;
```

In the `rescheduleIfNeeded()` method (line 106), after `$schedules = $user->workSchedules()->get()->keyBy('day');` (line 110), add:

```php
$overrides = $user->dayOverrides()
    ->where('date', '>=', now()->toDateString())
    ->get()
    ->keyBy(fn ($o) => $o->date->toDateString());
```

Then update the filter closure (around line 117) to check overrides first. Replace:

```php
->filter(function ($task) use ($schedules, $tz) {
    $start = $task->scheduled_start->copy()->setTimezone($tz);
    $end = $task->scheduled_end->copy()->setTimezone($tz);
    $daySchedule = $schedules->get($start->dayOfWeekIso);

    if (! $daySchedule || ! $daySchedule->enabled || ! $daySchedule->start || ! $daySchedule->end) {
        return true;
    }

    $workStart = $start->copy()->setTimeFromTimeString($daySchedule->start);
    $workEnd = $start->copy()->setTimeFromTimeString($daySchedule->end);

    return $start->lessThan($workStart) || $end->greaterThan($workEnd);
});
```

With:

```php
->filter(function ($task) use ($schedules, $overrides, $tz) {
    $start = $task->scheduled_start->copy()->setTimezone($tz);
    $end = $task->scheduled_end->copy()->setTimezone($tz);
    $dateStr = $start->toDateString();

    // Check day override first
    $override = $overrides->get($dateStr);
    if ($override) {
        if ($override->is_day_off || ! $override->start || ! $override->end) {
            return true;
        }
        $workStart = $start->copy()->setTimeFromTimeString($override->start);
        $workEnd = $start->copy()->setTimeFromTimeString($override->end);

        return $start->lessThan($workStart) || $end->greaterThan($workEnd);
    }

    // Fall back to recurring schedule
    $daySchedule = $schedules->get($start->dayOfWeekIso);

    if (! $daySchedule || ! $daySchedule->enabled || ! $daySchedule->start || ! $daySchedule->end) {
        return true;
    }

    $workStart = $start->copy()->setTimeFromTimeString($daySchedule->start);
    $workEnd = $start->copy()->setTimeFromTimeString($daySchedule->end);

    return $start->lessThan($workStart) || $end->greaterThan($workEnd);
});
```

- [ ] **Step 2: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 3: Run all tests**

Run: `php artisan test --compact`
Expected: All tests pass

- [ ] **Step 4: Commit**

```bash
git add app/Livewire/Settings.php
git commit -m "feat: respect day overrides in Settings rescheduleIfNeeded"
```

---

### Task 10: Final Integration Test & Cleanup

**Files:**
- All test files

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test --compact`
Expected: All tests pass

- [ ] **Step 2: Run pint on all modified files**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 3: Build frontend assets**

Run: `npm run build`

- [ ] **Step 4: Final commit if any pint changes**

```bash
git add -A
git status
# Only commit if there are changes
git commit -m "chore: format code with pint"
```
