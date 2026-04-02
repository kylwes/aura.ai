<div>
    <div class="mb-6 flex justify-center gap-2">
        @for ($i = 1; $i <= 3; $i++)
            <div class="size-2 rounded-full {{ $step >= $i ? 'bg-accent-600' : 'bg-neutral-200 dark:bg-neutral-700' }}"></div>
        @endfor
    </div>

    @if ($step === 1)
        <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Welcome to Aura</h2>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Connect your tools to get started</p>
        <div class="mt-6 grid grid-cols-2 gap-3">
            @foreach ($integrationTypes as $type)
                <button class="flex items-center gap-3 rounded-lg border border-neutral-200 p-3 text-left hover:border-accent-300 hover:bg-accent-50 dark:border-neutral-700 dark:hover:border-accent-700 dark:hover:bg-accent-950/20 transition-colors">
                    <x-dynamic-component :component="$type->iconComponent()" class="size-6" />
                    <span class="text-sm font-medium text-neutral-700 dark:text-neutral-300">{{ $type->label() }}</span>
                </button>
            @endforeach
        </div>
    @endif

    @if ($step === 2)
        <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Set your working hours</h2>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">AI will schedule tasks within these times</p>
        <div class="mt-6 flex items-center gap-4">
            <div>
                <label class="text-xs text-neutral-500">Start</label>
                <input type="time" wire:model="workingHoursStart" class="mt-1 block rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
            </div>
            <span class="mt-5 text-neutral-400">–</span>
            <div>
                <label class="text-xs text-neutral-500">End</label>
                <input type="time" wire:model="workingHoursEnd" class="mt-1 block rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
            </div>
        </div>
        <div class="mt-4">
            <label class="text-xs text-neutral-500">Working days</label>
            <div class="mt-2 flex gap-2">
                @foreach (['Mo' => 1, 'Tu' => 2, 'We' => 3, 'Th' => 4, 'Fr' => 5, 'Sa' => 6, 'Su' => 7] as $label => $day)
                    <button wire:click="$toggle('workingDays', {{ $day }})"
                            class="flex size-9 items-center justify-center rounded-full text-xs font-medium transition-colors {{ in_array($day, $workingDays) ? 'bg-accent-600 text-white' : 'bg-neutral-100 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    @if ($step === 3)
        <div class="text-center">
            <x-icons.sparkle class="mx-auto size-12 text-accent-600" />
            <h2 class="mt-4 text-lg font-semibold text-neutral-900 dark:text-neutral-100">You're all set!</h2>
            <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Aura will start organizing your tasks intelligently</p>
        </div>
    @endif

    <div class="mt-8 flex items-center justify-between">
        @if ($step > 1)
            <button wire:click="previousStep" class="text-sm text-neutral-500 hover:text-neutral-700 dark:text-neutral-400">Back</button>
        @else
            <button wire:click="skip" class="text-sm text-neutral-500 hover:text-neutral-700 dark:text-neutral-400">Skip</button>
        @endif

        @if ($step < 3)
            <button wire:click="nextStep" class="rounded-lg bg-accent-600 px-6 py-2 text-sm font-medium text-white hover:bg-accent-700 transition-colors">Next</button>
        @else
            <button wire:click="complete" class="rounded-lg bg-accent-600 px-6 py-2 text-sm font-medium text-white hover:bg-accent-700 transition-colors">Get started</button>
        @endif
    </div>
</div>
