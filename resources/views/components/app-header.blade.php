<header class="flex w-full shrink-0 items-center gap-6 border-b border-neutral-200 bg-white px-4 py-2 dark:border-neutral-800 dark:bg-neutral-900">
    <a href="/" wire:navigate class="flex items-center gap-2 text-lg font-bold text-neutral-900 dark:text-neutral-100">
        <x-icons.sparkle class="size-6 text-accent-600" />
        <span>Aura</span>
    </a>

    <nav class="flex items-center gap-1">
        <a href="{{ route('planner') }}" wire:navigate
           class="rounded-lg px-3 py-1.5 text-sm font-medium transition-colors {{ request()->routeIs('planner') ? 'bg-accent-50 text-accent-700 dark:bg-accent-900/30 dark:text-accent-400' : 'text-neutral-500 hover:text-neutral-700 hover:bg-neutral-50 dark:text-neutral-400 dark:hover:text-neutral-200 dark:hover:bg-neutral-800' }}">
            Planner
        </a>
        <a href="{{ route('tasks') }}" wire:navigate
           class="rounded-lg px-3 py-1.5 text-sm font-medium transition-colors {{ request()->routeIs('tasks') ? 'bg-accent-50 text-accent-700 dark:bg-accent-900/30 dark:text-accent-400' : 'text-neutral-500 hover:text-neutral-700 hover:bg-neutral-50 dark:text-neutral-400 dark:hover:text-neutral-200 dark:hover:bg-neutral-800' }}">
            Tasks
        </a>
        <a href="{{ route('projects') }}" wire:navigate
           class="rounded-lg px-3 py-1.5 text-sm font-medium transition-colors {{ request()->routeIs('projects') ? 'bg-accent-50 text-accent-700 dark:bg-accent-900/30 dark:text-accent-400' : 'text-neutral-500 hover:text-neutral-700 hover:bg-neutral-50 dark:text-neutral-400 dark:hover:text-neutral-200 dark:hover:bg-neutral-800' }}">
            Projects
        </a>
    </nav>

    <div class="ml-auto flex items-center gap-2">
        <livewire:dark-mode-toggle />

        {{-- Notification bell --}}
        <button x-data @click="$dispatch('toggle-inbox')"
                class="relative rounded-lg p-2 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300">
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
            @php $inboxCount = auth()->user()->inboxItems()->where('status', 'pending')->count(); @endphp
            @if ($inboxCount > 0)
                <span class="absolute -right-0.5 -top-0.5 flex size-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
                    {{ $inboxCount > 9 ? '9+' : $inboxCount }}
                </span>
            @endif
        </button>

        {{-- User avatar --}}
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="flex size-8 items-center justify-center rounded-full bg-accent-100 text-sm font-semibold text-accent-700 dark:bg-accent-900 dark:text-accent-300">
                {{ substr(auth()->user()->name, 0, 1) }}
            </button>
            <div x-show="open" @click.away="open = false" x-transition
                 class="absolute right-0 mt-2 w-48 rounded-lg bg-white py-1 shadow-lg ring-1 ring-neutral-200 dark:bg-neutral-800 dark:ring-neutral-700 z-50">
                <a href="/profile" wire:navigate class="block px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50 dark:text-neutral-300 dark:hover:bg-neutral-700">Profile</a>
                <a href="/settings" wire:navigate class="block px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50 dark:text-neutral-300 dark:hover:bg-neutral-700">Settings</a>
                <hr class="my-1 border-neutral-200 dark:border-neutral-700">
                <form method="POST" action="/logout">
                    @csrf
                    <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-neutral-700 hover:bg-neutral-50 dark:text-neutral-300 dark:hover:bg-neutral-700">Sign out</button>
                </form>
            </div>
        </div>
    </div>
</header>
