<div class="mx-auto max-w-2xl px-6 py-8">
    <h1 class="text-2xl font-bold text-neutral-900 dark:text-neutral-100">Profile</h1>

    <div class="mt-8 rounded-xl bg-white p-6 ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800">
        <div class="flex items-center gap-4 mb-6">
            <div class="flex size-16 items-center justify-center rounded-full bg-accent-100 text-2xl font-bold text-accent-700 dark:bg-accent-900 dark:text-accent-300">
                {{ substr($name, 0, 1) }}
            </div>
            <div>
                <p class="font-semibold text-neutral-900 dark:text-neutral-100">{{ $name }}</p>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ $email }}</p>
            </div>
        </div>

        @if (session('profile-message'))
            <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-700 dark:bg-green-950/20 dark:text-green-400">{{ session('profile-message') }}</div>
        @endif

        <form wire:submit="updateProfile" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Name</label>
                <input wire:model="name" type="text" class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
                @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Email</label>
                <input wire:model="email" type="email" class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
                @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Timezone</label>
                <select wire:model="timezone" class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
                    @foreach (timezone_identifiers_list() as $tz)
                        <option value="{{ $tz }}">{{ $tz }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-accent-600 px-5 py-2 text-sm font-medium text-white hover:bg-accent-700 transition-colors">Save</button>
        </form>
    </div>

    <div class="mt-6 rounded-xl bg-white p-6 ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800">
        <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">Change password</h3>
        @if (session('password-message'))
            <div class="mt-3 rounded-lg bg-green-50 p-3 text-sm text-green-700 dark:bg-green-950/20 dark:text-green-400">{{ session('password-message') }}</div>
        @endif
        <form wire:submit="updatePassword" class="mt-4 space-y-4">
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Current password</label>
                <input wire:model="current_password" type="password" class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
                @error('current_password') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">New password</label>
                <input wire:model="new_password" type="password" class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
                @error('new_password') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Confirm new password</label>
                <input wire:model="new_password_confirmation" type="password" class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
            </div>
            <button type="submit" class="rounded-lg bg-accent-600 px-5 py-2 text-sm font-medium text-white hover:bg-accent-700 transition-colors">Update password</button>
        </form>
    </div>

    <div class="mt-6 rounded-xl border border-red-200 bg-white p-6 dark:border-red-900 dark:bg-neutral-900">
        <h3 class="text-sm font-semibold text-red-600 dark:text-red-400">Danger Zone</h3>
        <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Once you delete your account, there is no going back.</p>
        <button class="mt-4 rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-950/20">Delete account</button>
    </div>
</div>
