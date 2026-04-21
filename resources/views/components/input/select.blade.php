<select {{ $attributes->merge(['class' => 'mt-1 w-full rounded-lg border-0 bg-neutral-100 px-3 py-2 text-sm text-neutral-700 outline-none transition-shadow focus:ring-2 focus:ring-accent-500/40 dark:bg-neutral-800 dark:text-neutral-300']) }}>
    {{ $slot }}
</select>
