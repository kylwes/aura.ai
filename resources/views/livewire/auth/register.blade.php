<div>
    <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Create your account</h2>
    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Get started with Aura</p>

    <form wire:submit="register" class="mt-6 space-y-4">
        <div>
            <label for="name" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Full name</label>
            <input wire:model="name" id="name" type="text" autocomplete="name" required
                   class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
            @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="reg-email" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Email</label>
            <input wire:model="email" id="reg-email" type="email" autocomplete="email" required
                   class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
            @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="reg-password" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Password</label>
            <input wire:model="password" id="reg-password" type="password" autocomplete="new-password" required
                   class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
            @error('password') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Confirm password</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" required
                   class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
        </div>
        <button type="submit" class="w-full rounded-lg bg-accent-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-accent-700 transition-colors">Create account</button>
    </form>

    <div class="mt-6">
        <div class="relative">
            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-neutral-200 dark:border-neutral-700"></div></div>
            <div class="relative flex justify-center text-xs"><span class="bg-white px-2 text-neutral-400 dark:bg-neutral-900">or continue with</span></div>
        </div>
        <div class="mt-4 grid grid-cols-2 gap-3">
            <button class="inline-flex items-center justify-center gap-2 rounded-lg border border-neutral-200 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800">
                <svg class="size-4" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                Google
            </button>
            <button class="inline-flex items-center justify-center gap-2 rounded-lg border border-neutral-200 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800">
                <x-icons.github class="size-4" />
                GitHub
            </button>
        </div>
    </div>

    <p class="mt-6 text-center text-xs text-neutral-500 dark:text-neutral-400">
        Already have an account? <a href="/login" wire:navigate class="font-medium text-accent-600 hover:text-accent-700 dark:text-accent-400">Sign in</a>
    </p>
</div>
