<div>
    <div class="flex items-center gap-3 px-8 pt-6">
        <div class="flex size-10 items-center justify-center rounded-lg bg-neutral-100 dark:bg-neutral-800">
            <x-icons.productive class="size-6" />
        </div>
        <div>
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Productive</h2>
            <p class="text-xs text-neutral-500 dark:text-neutral-400">Connect your Productive account to import notifications</p>
        </div>
    </div>

    <div class="space-y-4 px-8 py-6">
        @if ($error)
            <div class="rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-950/20 dark:text-red-400">{{ $error }}</div>
        @endif

        <x-input.label label="API Token">
            <x-input.text type="password" wire:model="apiToken" placeholder="Your Productive API token" class="font-medium" />
        </x-input.label>
        <p class="mt-1.5 text-[11px] text-neutral-400 dark:text-neutral-500">
            Find your token in Productive: Settings &rarr; API integrations &rarr; Generate new token
        </p>
    </div>

    <div class="flex items-center justify-between border-t border-neutral-200 px-8 py-4 dark:border-neutral-800">
        <div>
            @if ($isConnected)
                <button wire:click="disconnect" wire:confirm="Are you sure you want to disconnect Productive?"
                        class="text-xs font-medium text-red-500 hover:text-red-600 dark:text-red-400">
                    Disconnect
                </button>
            @endif
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="$dispatch('closeModal')" class="px-4 py-2 text-sm font-medium text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-neutral-200">
                Cancel
            </button>
            <button wire:click="connect" class="rounded-lg bg-accent-600 px-5 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-accent-700">
                {{ $isConnected ? 'Update' : 'Connect' }}
            </button>
        </div>
    </div>
</div>
