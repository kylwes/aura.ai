@props(['type' => 'text'])

<input type="{{ $type }}"
       {{ $attributes->merge(['class' => 'mt-1 w-full rounded-lg border-0 bg-neutral-100 px-3 py-2.5 text-sm text-neutral-900 placeholder-neutral-400 outline-none transition-shadow focus:ring-2 focus:ring-accent-500/40 dark:bg-neutral-800 dark:text-neutral-100']) }}>
