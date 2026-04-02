@props(['label' => 'AI Suggested'])

<span {{ $attributes->merge(['class' => 'ai-shimmer inline-flex items-center gap-1 rounded-full border border-accent-200 px-2 py-0.5 text-xs font-medium text-accent-700 dark:border-accent-800 dark:text-accent-300']) }}>
    <x-icons.sparkle class="size-3" />
    {{ $label }}
</span>
