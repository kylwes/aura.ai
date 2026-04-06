<div>
    @if ($task)
        {{-- Header --}}
        <div class="flex items-center justify-end px-8 pt-4">
            <button wire:click="$dispatch('closeModal')" class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="space-y-5 px-8 py-6">
            {{-- Title --}}
            <div>
                <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Title</label>
                <input type="text"
                       value="{{ $task->title }}"
                       wire:change="updateField('title', $event.target.value)"
                       class="mt-1 w-full rounded-lg border-0 bg-neutral-100 px-3 py-2.5 text-sm font-medium text-neutral-900 placeholder-neutral-400 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-800 dark:text-neutral-100">
            </div>

            {{-- Description --}}
            <div>
                <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Description <span class="normal-case tracking-normal text-neutral-300 dark:text-neutral-600">— optional</span></label>
                <textarea wire:change="updateField('description', $event.target.value)"
                          rows="3"
                          placeholder="Add a description..."
                          class="mt-1 w-full resize-none rounded-lg border-0 bg-neutral-100 px-3 py-2.5 text-sm text-neutral-700 placeholder-neutral-400 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-800 dark:text-neutral-300 dark:placeholder-neutral-500">{{ $task->description }}</textarea>
            </div>

            {{-- Priority + Duration --}}
            <div class="flex items-start gap-6">
                <div class="flex-1">
                    <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Priority</label>
                    <div class="mt-2 flex items-center gap-1.5">
                        @foreach (\App\Enums\TaskPriority::cases() as $p)
                            <button wire:click="setPriority('{{ $p->value }}')"
                                    class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition-colors
                                        {{ $task->priority === $p
                                            ? 'border-neutral-300 bg-neutral-100 text-neutral-900 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100'
                                            : 'border-transparent text-neutral-400 hover:bg-neutral-50 dark:text-neutral-500 dark:hover:bg-neutral-800' }}">
                                <span class="size-2 rounded-full {{ $p->bgColor() }}"></span>
                                {{ $p->label() }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Estimated time</label>
                    @php
                        $durH = $task->estimated_duration ? intdiv($task->estimated_duration, 60) : 0;
                        $durM = $task->estimated_duration ? $task->estimated_duration % 60 : 0;
                    @endphp
                    <div class="mt-2 flex items-center gap-1.5"
                         x-data="{ h: {{ $durH }}, m: {{ $durM }}, save() { $wire.updateField('estimated_duration', String(this.h * 60 + this.m)) } }">
                        <input type="number" x-model.number="h" @change="save()" min="0" max="23" placeholder="0"
                               class="w-14 rounded-lg border-0 bg-neutral-100 px-2 py-1.5 text-center text-sm text-neutral-900 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-800 dark:text-neutral-100">
                        <span class="text-xs text-neutral-400 dark:text-neutral-500">h</span>
                        <input type="number" x-model.number="m" @change="save()" min="0" max="59" step="15" placeholder="0"
                               class="w-14 rounded-lg border-0 bg-neutral-100 px-2 py-1.5 text-center text-sm text-neutral-900 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-800 dark:text-neutral-100">
                        <span class="text-xs text-neutral-400 dark:text-neutral-500">m</span>
                    </div>
                </div>
            </div>

            {{-- Project + Deadline --}}
            <div class="flex items-start gap-6">
                <div class="flex-1">
                    <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Project <span class="normal-case tracking-normal text-neutral-300 dark:text-neutral-600">— optional</span></label>
                    @php $projects = auth()->user()->projects()->orderBy('title')->get(); @endphp
                    <select wire:change="updateField('project_id', $event.target.value)"
                            class="mt-1 w-full rounded-lg border-0 bg-neutral-100 px-3 py-2 text-sm text-neutral-700 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-800 dark:text-neutral-300">
                        <option value="">No project</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" {{ $task->project_id == $project->id ? 'selected' : '' }}>{{ $project->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="w-44">
                    <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Deadline <span class="normal-case tracking-normal text-neutral-300 dark:text-neutral-600">— optional</span></label>
                    <input type="date"
                           value="{{ $task->deadline?->format('Y-m-d') }}"
                           wire:change="updateField('deadline', $event.target.value)"
                           class="mt-1 w-full rounded-lg border-0 bg-neutral-100 px-3 py-2 text-sm text-neutral-700 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-800 dark:text-neutral-300 dark:[color-scheme:dark]">
                </div>
            </div>

            {{-- Source link --}}
            @if ($task->source_url || $task->source_reference)
                <div>
                    <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Source</label>
                    <div class="mt-1.5">
                        @if ($task->source_url)
                            <a href="{{ $task->source_url }}" target="_blank" class="inline-flex items-center gap-1.5 text-sm font-medium text-accent-600 hover:text-accent-700 dark:text-accent-400 dark:hover:text-accent-300">
                                @if ($task->integration)
                                    <x-source-icon :type="$task->integration->type" size="sm" />
                                @endif
                                {{ $task->source_reference ?? 'Open source' }}
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                            </a>
                        @else
                            <span class="text-sm text-neutral-500 dark:text-neutral-400">{{ $task->source_reference }}</span>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Recurrence info --}}
            @if ($task->parent_task_id && $task->parentTask)
                <div class="flex items-center gap-2 rounded-lg bg-neutral-100 px-3 py-2 dark:bg-neutral-800">
                    <span class="text-sm">🔁</span>
                    <span class="text-xs text-neutral-500 dark:text-neutral-400">
                        Recurring:
                        @if ($task->parentTask->recurrence_type === 'daily')
                            every day
                        @elseif ($task->parentTask->recurrence_type === 'weekly')
                            every {{ collect($task->parentTask->recurrence_days ?? [])->map(fn ($d) => match($d) { 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun' })->implode(', ') }}
                        @elseif ($task->parentTask->recurrence_type === 'monthly')
                            monthly
                        @endif
                    </span>
                </div>
            @endif

            {{-- Dependencies --}}
            <div>
                <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Depends on</label>

                @if (count($dependencyIds) > 0)
                    <div class="mt-2 space-y-1">
                        @foreach (auth()->user()->tasks()->whereIn('id', $dependencyIds)->get() as $dep)
                            <div class="flex items-center justify-between rounded-lg bg-neutral-100 px-2.5 py-1.5 dark:bg-neutral-800">
                                <span class="truncate text-xs font-medium text-neutral-700 dark:text-neutral-300">{{ $dep->title }}</span>
                                <button wire:click="removeDependency({{ $dep->id }})" class="ml-2 shrink-0 text-neutral-400 hover:text-red-500">
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Add dependency search --}}
                <div class="relative mt-2">
                    <input type="text"
                           wire:model.live.debounce.300ms="dependencySearch"
                           placeholder="Search tasks to add..."
                           class="w-full rounded-lg border-0 bg-neutral-100 px-3 py-2 text-xs text-neutral-900 placeholder-neutral-400 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-800 dark:text-neutral-100">

                    @if (strlen($dependencySearch) >= 2)
                        @php
                            $searchResults = auth()->user()->tasks()
                                ->where('id', '!=', $task->id)
                                ->whereNotIn('id', $dependencyIds)
                                ->where('title', 'like', '%' . $dependencySearch . '%')
                                ->limit(5)
                                ->get();
                        @endphp
                        @if ($searchResults->isNotEmpty())
                            <div class="absolute left-0 right-0 top-full z-10 mt-1 rounded-lg bg-white py-1 shadow-lg ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800">
                                @foreach ($searchResults as $result)
                                    <button wire:click="addDependency({{ $result->id }})"
                                            class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-xs hover:bg-neutral-50 dark:hover:bg-neutral-800">
                                        <span class="truncate font-medium text-neutral-700 dark:text-neutral-300">{{ $result->title }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- AI Reasoning --}}
            @if ($task->ai_reasoning)
                <div class="rounded-lg bg-accent-50 p-4 dark:bg-accent-950/20">
                    <button wire:click="$toggle('showAiReasoning')" class="flex w-full items-center justify-between">
                        <div class="flex items-center gap-2">
                            <x-icons.sparkle class="size-4 text-accent-600 dark:text-accent-400" />
                            <span class="text-xs font-semibold text-accent-700 dark:text-accent-300">AI Reasoning</span>
                        </div>
                        <svg class="size-4 text-accent-400 transition-transform {{ $showAiReasoning ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    @if ($showAiReasoning)
                        <p class="mt-2 text-sm leading-relaxed text-neutral-600 dark:text-neutral-400">{{ $task->ai_reasoning }}</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-between border-t border-neutral-200 px-8 py-4 dark:border-neutral-800">
            <div class="flex items-center gap-1">
                <button wire:click="togglePin" title="{{ $task->is_pinned ? 'Unpin task' : 'Pin task' }}"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors {{ $task->is_pinned ? 'bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400' : 'text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300' }}">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                    {{ $task->is_pinned ? 'Pinned' : 'Pin' }}
                </button>

                <button wire:click="markDone" title="Mark as done"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-neutral-400 transition-colors hover:bg-green-50 hover:text-green-600 dark:hover:bg-green-900/20 dark:hover:text-green-400">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    Done
                </button>

                @if ($task->status === \App\Enums\TaskStatus::OnHold)
                    <button wire:click="resume" title="Resume task"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-amber-500 transition-colors hover:bg-amber-50 dark:hover:bg-amber-900/20">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z"/></svg>
                        Resume
                    </button>
                @else
                    <button wire:click="putOnHold" title="Put on hold — waiting for someone else"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-neutral-400 transition-colors hover:bg-amber-50 hover:text-amber-600 dark:hover:bg-amber-900/20 dark:hover:text-amber-400">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5"/></svg>
                        On hold
                    </button>
                @endif

                <button wire:click="dismiss" wire:confirm="Are you sure you want to dismiss this task?" title="Dismiss"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-neutral-400 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    Dismiss
                </button>
            </div>

            <div class="flex items-center gap-2">
                @if ($task->scheduled_start)
                    @php $tz = auth()->user()->timezone ?? 'UTC'; @endphp
                    <a href="{{ route('planner') }}?date={{ $task->scheduled_start->copy()->setTimezone($tz)->format('Y-m-d') }}"
                       wire:navigate
                       @click="$dispatch('closeModal')"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-200 px-4 py-2 text-sm font-medium text-neutral-700 transition-colors hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v9.75"/></svg>
                        {{ $task->scheduled_start->copy()->setTimezone($tz)->format('M j') }}
                    </a>
                @endif

                <button wire:click="reschedule" class="inline-flex items-center gap-1.5 rounded-lg bg-accent-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-accent-700">
                    Reschedule
                    <x-icons.sparkle class="size-3.5" />
                </button>
            </div>
        </div>
    @endif
</div>
