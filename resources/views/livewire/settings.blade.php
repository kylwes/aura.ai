<div class="flex h-full min-h-0 max-w-5xl gap-8 px-6 py-8">
    {{-- Sidebar --}}
    <nav class="w-60 shrink-0">
        <h1 class="mb-6 text-2xl font-bold text-neutral-900 dark:text-neutral-100">Settings</h1>
        <ul class="space-y-1">
            <li>
                <button wire:click="$set('activeTab', 'integrations')"
                        class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium transition-colors {{ $activeTab === 'integrations' ? 'bg-neutral-100 text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100' : 'text-neutral-500 hover:bg-neutral-50 hover:text-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800/50 dark:hover:text-neutral-300' }}">
                    Integrations
                </button>
            </li>
            <li>
                <button wire:click="$set('activeTab', 'preferences')"
                        class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium transition-colors {{ $activeTab === 'preferences' ? 'bg-neutral-100 text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100' : 'text-neutral-500 hover:bg-neutral-50 hover:text-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800/50 dark:hover:text-neutral-300' }}">
                    Preferences
                </button>
            </li>
        </ul>
    </nav>

    {{-- Content --}}
    <div class="min-w-0 flex-1 overflow-y-auto">
        @if ($activeTab === 'integrations')
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Integrations</h2>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Connect your calendars and services</p>
            </div>

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
                @foreach ($integrationTypes as $type)
                    @php $integration = $connectedIntegrations->get($type->value); @endphp
                    <x-integration-card :type="$type" :status="$integration?->status" :integration="$integration" />
                @endforeach
            </div>

            @php $googleIntegration = $connectedIntegrations->get('google_calendar'); @endphp
            @if ($googleIntegration && $googleIntegration->status === \App\Enums\IntegrationStatus::Connected)
                <div class="mt-6 rounded-xl bg-white p-6 ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800">
                    <div class="mb-4 flex items-center gap-3">
                        <x-icons.google-calendar class="size-6" />
                        <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">Google Calendar Settings</h3>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                @php $lastSynced = isset($googleIntegration->configuration['last_synced_at']) ? \Illuminate\Support\Carbon::parse($googleIntegration->configuration['last_synced_at'])->diffForHumans() : 'Never'; @endphp
                                <p class="text-xs text-neutral-500 dark:text-neutral-400">Last synced: {{ $lastSynced }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <button wire:click="syncGoogleCalendar" class="text-xs font-medium text-accent-600 hover:text-accent-700 dark:text-accent-400">Sync now</button>
                                <button wire:click="disconnectGoogleCalendar" wire:confirm="Are you sure you want to disconnect Google Calendar?" class="text-xs font-medium text-red-500 hover:text-red-600 dark:text-red-400">Disconnect</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif

        @if ($activeTab === 'preferences')
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Preferences</h2>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Configure your working hours and AI scheduling behavior</p>
            </div>

            @if (session('message'))
                <div class="mb-6 rounded-lg bg-green-50 p-3 text-sm text-green-700 dark:bg-green-950/20 dark:text-green-400">{{ session('message') }}</div>
            @endif

            <div class="space-y-8">
                {{-- Working Hours Schedule --}}
                <div class="rounded-xl bg-white p-6 ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800">
                    <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">Working Hours</h3>
                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Set your working hours and lunch break per day. Changes save automatically.</p>

                    <div class="mt-5 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-neutral-100 text-left text-xs font-medium text-neutral-500 dark:border-neutral-800 dark:text-neutral-400">
                                    <th class="pb-2 pr-4">Day</th>
                                    <th class="pb-2 pr-4">Enabled</th>
                                    <th class="pb-2 pr-4">Hours</th>
                                    <th class="pb-2">Lunch</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                                @foreach ($schedules as $index => $schedule)
                                    <tr wire:key="schedule-{{ $schedule['day'] }}" class="{{ !$schedule['enabled'] ? 'opacity-40' : '' }} transition-opacity">
                                        <td class="py-3 pr-4 font-medium text-neutral-900 dark:text-neutral-100">
                                            {{ $schedule['day_name'] }}
                                        </td>
                                        <td class="py-3 pr-4">
                                            <label class="relative inline-flex cursor-pointer items-center">
                                                <input type="checkbox"
                                                       wire:model.live="schedules.{{ $index }}.enabled"
                                                       class="peer sr-only">
                                                <div class="h-5 w-9 rounded-full bg-neutral-200 after:absolute after:left-[2px] after:top-[2px] after:size-4 after:rounded-full after:bg-white after:transition-all peer-checked:bg-accent-600 peer-checked:after:translate-x-full dark:bg-neutral-700"></div>
                                            </label>
                                        </td>
                                        <td class="py-3 pr-4">
                                            @if ($schedule['enabled'])
                                                <div class="flex items-center gap-2">
                                                    <input type="time"
                                                           wire:model.live="schedules.{{ $index }}.start"
                                                           class="rounded-lg border border-neutral-200 bg-white px-2.5 py-1.5 text-xs dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
                                                    <span class="text-neutral-400">-</span>
                                                    <input type="time"
                                                           wire:model.live="schedules.{{ $index }}.end"
                                                           class="rounded-lg border border-neutral-200 bg-white px-2.5 py-1.5 text-xs dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
                                                </div>
                                            @else
                                                <span class="text-xs text-neutral-400">&mdash;</span>
                                            @endif
                                        </td>
                                        <td class="py-3">
                                            @if ($schedule['enabled'])
                                                <div class="flex items-center gap-2">
                                                    <input type="time"
                                                           wire:model.live="schedules.{{ $index }}.lunch_start"
                                                           class="rounded-lg border border-neutral-200 bg-white px-2.5 py-1.5 text-xs dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100"
                                                           placeholder="None">
                                                    <span class="text-neutral-400">-</span>
                                                    <input type="time"
                                                           wire:model.live="schedules.{{ $index }}.lunch_end"
                                                           class="rounded-lg border border-neutral-200 bg-white px-2.5 py-1.5 text-xs dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100"
                                                           placeholder="None">
                                                </div>
                                            @else
                                                <span class="text-xs text-neutral-400">&mdash;</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Focus Time --}}
                <div class="rounded-xl bg-white p-6 ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">Focus Time</h3>
                            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Block time for deep work</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" wire:model.live="focusTimeEnabled" class="peer sr-only">
                            <div class="h-5 w-9 rounded-full bg-neutral-200 after:absolute after:left-[2px] after:top-[2px] after:size-4 after:rounded-full after:bg-white after:transition-all peer-checked:bg-accent-600 peer-checked:after:translate-x-full dark:bg-neutral-700"></div>
                        </label>
                    </div>
                    @if ($focusTimeEnabled)
                        <div class="mt-4 flex items-center gap-4">
                            <div>
                                <label class="text-xs text-neutral-500 dark:text-neutral-400">Start</label>
                                <input type="time" wire:model="focusTimeStart" class="mt-1 block rounded-lg border border-neutral-200 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
                            </div>
                            <span class="mt-5 text-neutral-400">-</span>
                            <div>
                                <label class="text-xs text-neutral-500 dark:text-neutral-400">End</label>
                                <input type="time" wire:model="focusTimeEnd" class="mt-1 block rounded-lg border border-neutral-200 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-neutral-700 dark:text-neutral-300">Protect focus time</p>
                                <p class="text-[10px] text-neutral-400 dark:text-neutral-500">AI won't schedule other tasks during focus time</p>
                            </div>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" wire:model.live="focusTimeProtected" class="peer sr-only">
                                <div class="h-5 w-9 rounded-full bg-neutral-200 after:absolute after:left-[2px] after:top-[2px] after:size-4 after:rounded-full after:bg-white after:transition-all peer-checked:bg-accent-600 peer-checked:after:translate-x-full dark:bg-neutral-700"></div>
                            </label>
                        </div>
                    @endif
                </div>

                {{-- Task Scheduling --}}
                <div class="rounded-xl bg-white p-6 ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800">
                    <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">Task Scheduling</h3>
                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Control how the AI schedules your tasks</p>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="text-xs text-neutral-500 dark:text-neutral-400">Max task duration (minutes)</label>
                            <input type="range" wire:model.live="maxTaskDuration" min="30" max="240" step="30" class="mt-2 w-full accent-accent-600">
                            <span class="text-xs text-neutral-600 dark:text-neutral-400">{{ $maxTaskDuration }} min</span>
                        </div>
                        <div>
                            <label class="text-xs text-neutral-500 dark:text-neutral-400">Buffer between tasks</label>
                            <div class="mt-2 flex gap-2">
                                @foreach ([5, 10, 15, 30] as $minutes)
                                    <button wire:click="$set('bufferTime', {{ $minutes }})"
                                            class="rounded-lg px-3 py-1.5 text-xs font-medium transition-colors {{ $bufferTime === $minutes ? 'bg-accent-100 text-accent-700 dark:bg-accent-900 dark:text-accent-300' : 'bg-neutral-100 text-neutral-500 hover:text-neutral-700 dark:bg-neutral-800 dark:text-neutral-400' }}">
                                        {{ $minutes }}m
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <button wire:click="savePreferences" class="rounded-lg bg-accent-600 px-6 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-accent-700">
                    Save preferences
                </button>
            </div>
        @endif
    </div>
</div>
