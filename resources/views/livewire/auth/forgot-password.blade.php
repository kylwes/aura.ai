<div>
    @if ($sent)
        <div class="text-center">
            <svg class="mx-auto size-12 text-accent-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
            <h2 class="mt-4 text-lg font-semibold text-neutral-900 dark:text-neutral-100">Check your email</h2>
            <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">We've sent a password reset link to {{ $email }}</p>
        </div>
    @else
        <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Forgot your password?</h2>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Enter your email and we'll send you a reset link</p>
        <form wire:submit="sendResetLink" class="mt-6 space-y-4">
            <div>
                <label for="fp-email" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Email</label>
                <input wire:model="email" id="fp-email" type="email" required class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
                @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="w-full rounded-lg bg-accent-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-accent-700 transition-colors">Send reset link</button>
        </form>
    @endif
    <p class="mt-6 text-center text-xs text-neutral-500 dark:text-neutral-400">
        <a href="/login" wire:navigate class="font-medium text-accent-600 hover:text-accent-700 dark:text-accent-400">Back to sign in</a>
    </p>
</div>
