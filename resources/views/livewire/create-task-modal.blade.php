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
        <div>
            <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Title</label>
            <input type="text"
                   wire:model="title"
                   autofocus
                   placeholder="What needs to be done?"
                   class="mt-1 w-full rounded-lg border-0 bg-neutral-100 px-3 py-2.5 text-sm font-medium text-neutral-900 placeholder-neutral-400 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-800 dark:text-neutral-100 dark:placeholder-neutral-500">
            @error('title')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Priority --}}
        <div>
            <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Priority</label>
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
        </div>

        {{-- Project --}}
        @php $projects = auth()->user()->projects()->orderBy('title')->get(); @endphp
        @if ($projects->isNotEmpty())
            <div>
                <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Project</label>
                <select wire:model="projectId"
                        class="mt-1 w-full rounded-lg border-0 bg-neutral-100 px-3 py-2.5 text-sm font-medium text-neutral-700 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-800 dark:text-neutral-300">
                    <option value="">No project</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->title }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="flex items-start gap-6">
            {{-- Estimated duration --}}
            <div class="flex-1">
                <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Estimated time</label>
                <div class="mt-2 flex items-center gap-1.5"
                     x-data="{ h: {{ $estimatedDuration ? intdiv($estimatedDuration, 60) : 0 }}, m: {{ $estimatedDuration ? $estimatedDuration % 60 : 0 }}, save() { $wire.estimatedDuration = this.h * 60 + this.m } }">
                    <input type="number" x-model.number="h" @change="save()" min="0" max="23" placeholder="1"
                           class="w-14 rounded-lg border-0 bg-neutral-100 px-2 py-1.5 text-center text-sm text-neutral-700 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-800 dark:text-neutral-300">
                    <span class="text-xs text-neutral-400 dark:text-neutral-500">h</span>
                    <input type="number" x-model.number="m" @change="save()" min="0" max="59" step="15" placeholder="0"
                           class="w-14 rounded-lg border-0 bg-neutral-100 px-2 py-1.5 text-center text-sm text-neutral-700 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-800 dark:text-neutral-300">
                    <span class="text-xs text-neutral-400 dark:text-neutral-500">m</span>
                </div>
            </div>

            {{-- Deadline --}}
            <div class="flex-1">
                <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Deadline</label>
                <input type="date"
                       wire:model="deadline"
                       class="mt-2 w-full rounded-lg border border-neutral-200 bg-transparent px-3 py-1.5 text-sm text-neutral-700 focus:border-accent-500 focus:ring-1 focus:ring-accent-500 dark:border-neutral-700 dark:text-neutral-300">
            </div>
        </div>

        {{-- Description --}}
        <div>
            <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Description</label>
            <textarea wire:model="description"
                      rows="3"
                      placeholder="Add details..."
                      class="mt-1 w-full resize-none rounded-lg border-0 bg-neutral-100 px-3 py-2.5 text-sm text-neutral-700 placeholder-neutral-400 focus:ring-2 focus:ring-accent-500 dark:bg-neutral-800 dark:text-neutral-300 dark:placeholder-neutral-500"></textarea>
        </div>
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
