@props(['label', 'optional' => false, 'required' => false])

<div>
    <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">
        {{ $label }}
        @if ($required)
            <span class="text-red-400">*</span>
        @elseif ($optional)
            <span class="normal-case tracking-normal text-neutral-300 dark:text-neutral-600">&mdash; optional</span>
        @endif
    </label>
    {{ $slot }}
</div>
