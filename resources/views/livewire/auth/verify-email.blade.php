<div class="text-center">
    <svg class="mx-auto size-12 text-accent-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
    <h2 class="mt-4 text-lg font-semibold text-neutral-900 dark:text-neutral-100">Check your email</h2>
    <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">We've sent a verification link to your email address.</p>
    @if (session('message'))
        <p class="mt-4 text-sm text-green-600 dark:text-green-400">{{ session('message') }}</p>
    @endif
    <button wire:click="resend" class="mt-6 text-sm font-medium text-accent-600 hover:text-accent-700 dark:text-accent-400">Resend verification email</button>
</div>
