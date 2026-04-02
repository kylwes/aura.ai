<div>
    <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Reset your password</h2>
    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Choose a new password for your account</p>
    <form wire:submit="resetPassword" class="mt-6 space-y-4">
        <div>
            <label for="rp-password" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">New password</label>
            <input wire:model="password" id="rp-password" type="password" required class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
            @error('password') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="rp-password-confirm" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Confirm password</label>
            <input wire:model="password_confirmation" id="rp-password-confirm" type="password" required class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
        </div>
        @error('email') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
        <button type="submit" class="w-full rounded-lg bg-accent-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-accent-700 transition-colors">Reset password</button>
    </form>
</div>
