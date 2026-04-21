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
        <x-input.label label="Status">
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
        </x-input.label>

        @if (! $isDayOff)
            {{-- Work hours --}}
            <div class="flex items-start gap-6">
                <div class="flex-1">
                    <x-input.label label="Start">
                        <x-input.text type="time" wire:model="start" class="font-medium" />
                    </x-input.label>
                </div>
                <div class="flex-1">
                    <x-input.label label="End">
                        <x-input.text type="time" wire:model="end" class="font-medium" />
                    </x-input.label>
                </div>
            </div>

            {{-- Lunch break --}}
            <div class="flex items-start gap-6">
                <div class="flex-1">
                    <x-input.label label="Lunch start">
                        <x-input.text type="time" wire:model="lunchStart" class="font-medium" />
                    </x-input.label>
                </div>
                <div class="flex-1">
                    <x-input.label label="Lunch end">
                        <x-input.text type="time" wire:model="lunchEnd" class="font-medium" />
                    </x-input.label>
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
