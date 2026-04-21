<div>
    {{-- Header --}}
    <div class="flex items-center justify-between px-8 pt-6">
        <div class="flex items-center gap-2">
            <div class="h-6 w-1 rounded-full bg-accent-600"></div>
            <h2 class="text-xl font-semibold text-neutral-900 dark:text-neutral-100">New Task</h2>
        </div>
        <button wire:click="$dispatch('closeModal')" class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300">
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
        </button>
    </div>

    {{-- Body --}}
    <div class="space-y-5 px-8 py-6">
        {{-- Title --}}
        <x-input.label label="Title">
            <x-input.text wire:model="title" autofocus placeholder="What needs to be done?" class="font-medium" />
        </x-input.label>
        @error('title')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror

        {{-- Priority --}}
        <x-input.label label="Priority">
            <div class="mt-2 flex items-center gap-2">
                @foreach (\App\Enums\TaskPriority::cases() as $p)
                    <button wire:click="$set('priority', '{{ $p->value }}')"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition-colors
                                {{ $priority === $p->value
                                    ? 'border-neutral-300 bg-neutral-100 text-neutral-900 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100'
                                    : 'border-transparent text-neutral-500 hover:bg-neutral-50 dark:text-neutral-400 dark:hover:bg-neutral-800' }}">
                        <span class="size-2 rounded-full {{ $p->bgColor() }}"></span>
                        {{ $p->label() }}
                    </button>
                @endforeach
            </div>
        </x-input.label>

        {{-- Project --}}
        @php $projects = auth()->user()->projects()->orderBy('title')->get(); @endphp
        @if ($projects->isNotEmpty())
            <x-input.label label="Project">
                <x-input.select wire:model="projectId" class="font-medium">
                    <option value="">No project</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->title }}</option>
                    @endforeach
                </x-input.select>
            </x-input.label>
        @endif

        <div class="flex items-start gap-6">
            {{-- Estimated duration --}}
            <div class="flex-1">
                <x-input.label label="Estimated time">
                    <div class="mt-2 flex items-center gap-1.5"
                         x-data="{ h: {{ $estimatedDuration ? intdiv($estimatedDuration, 60) : 0 }}, m: {{ $estimatedDuration ? $estimatedDuration % 60 : 0 }}, save() { $wire.estimatedDuration = this.h * 60 + this.m } }">
                        <x-input.number x-model.number="h" @change="save()" min="0" max="23" placeholder="1" />
                        <span class="text-xs text-neutral-400 dark:text-neutral-500">h</span>
                        <x-input.number x-model.number="m" @change="save()" min="0" max="59" step="15" placeholder="0" />
                        <span class="text-xs text-neutral-400 dark:text-neutral-500">m</span>
                    </div>
                </x-input.label>
            </div>

            {{-- Deadline --}}
            <div class="flex-1">
                <x-input.label label="Deadline">
                    <x-input.date wire:model="deadline" />
                </x-input.label>
            </div>
        </div>

        {{-- Recurrence --}}
        <div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model.live="isRecurring"
                       class="size-4 rounded border-neutral-300 text-accent-600 focus:ring-accent-500 dark:border-neutral-600 dark:bg-neutral-800">
                <span class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Repeats</span>
            </label>

            @if ($isRecurring)
                <div class="mt-2 space-y-3 rounded-lg bg-neutral-100 p-3 dark:bg-neutral-800">
                    {{-- Type --}}
                    <div class="flex items-center gap-1.5">
                        @foreach (['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $type => $label)
                            <button type="button"
                                    wire:click="$set('recurrenceType', '{{ $type }}')"
                                    class="rounded-full px-3 py-1 text-xs font-medium transition-colors {{ $recurrenceType === $type ? 'bg-accent-100 text-accent-700 dark:bg-accent-900/40 dark:text-accent-400' : 'text-neutral-500 hover:bg-neutral-200 dark:text-neutral-400 dark:hover:bg-neutral-700' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Day picker for weekly --}}
                    @if ($recurrenceType === 'weekly')
                        <div class="flex items-center gap-1">
                            @foreach ([1 => 'Mo', 2 => 'Tu', 3 => 'We', 4 => 'Th', 5 => 'Fr', 6 => 'Sa', 7 => 'Su'] as $dayNum => $dayLabel)
                                <button type="button"
                                        wire:click="toggleDay({{ $dayNum }})"
                                        class="flex size-8 items-center justify-center rounded-full text-[10px] font-semibold transition-colors
                                            {{ in_array($dayNum, $recurrenceDays) ? 'bg-accent-600 text-white' : 'bg-neutral-200 text-neutral-500 hover:bg-neutral-300 dark:bg-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-600' }}">
                                    {{ $dayLabel }}
                                </button>
                            @endforeach
                        </div>
                    @endif

                    {{-- End date --}}
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] text-neutral-400 dark:text-neutral-500">Until</span>
                        <input type="date" wire:model="recurrenceEndDate"
                               class="rounded-lg border-0 bg-neutral-200 px-2 py-1 text-xs text-neutral-700 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-700 dark:text-neutral-300 dark:[color-scheme:dark]">
                        <span class="text-[10px] text-neutral-400 dark:text-neutral-500">(or leave empty for forever)</span>
                    </div>
                </div>
            @endif
        </div>

        {{-- Description --}}
        <x-input.label label="Description">
            <x-input.textarea wire:model="description" placeholder="Add details..." />
        </x-input.label>
    </div>

    {{-- Footer --}}
    <div class="flex items-center justify-end gap-3 border-t border-neutral-200 px-8 py-4 dark:border-neutral-800">
        <button wire:click="$dispatch('closeModal')" class="px-4 py-2 text-sm font-medium text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-neutral-200">
            Cancel
        </button>
        <button wire:click="save" class="rounded-lg bg-accent-600 px-5 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-accent-700">
            Create
        </button>
    </div>
</div>
