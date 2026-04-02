<div x-data="toast()" x-on:toast.window="show($event.detail)"
     class="pointer-events-none fixed bottom-6 right-6 z-50 flex flex-col items-end gap-2">
    <template x-for="(t, i) in toasts" :key="t.id">
        <div x-show="t.visible"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-y-2 opacity-0"
             x-transition:enter-end="translate-y-0 opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-y-0 opacity-100"
             x-transition:leave-end="translate-y-2 opacity-0"
             class="pointer-events-auto flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-lg ring-1 ring-neutral-200/60 dark:bg-neutral-800 dark:ring-neutral-700/60">
            <span class="flex size-7 shrink-0 items-center justify-center rounded-full"
                  :class="t.type === 'success' ? 'bg-priority-low/15 text-priority-low' : t.type === 'error' ? 'bg-priority-urgent/15 text-priority-urgent' : 'bg-accent-500/15 text-accent-500'">
                <svg x-show="t.type === 'success'" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                <svg x-show="t.type === 'error'" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                <svg x-show="t.type === 'info'" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
            </span>
            <div class="min-w-0">
                <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100" x-text="t.title"></p>
                <p x-show="t.body" class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400" x-text="t.body"></p>
            </div>
        </div>
    </template>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('toast', () => ({
        toasts: [],
        counter: 0,
        show({ type = 'success', title, body = '', duration = 4000 }) {
            const id = ++this.counter;
            this.toasts.push({ id, type, title, body, visible: true });
            setTimeout(() => {
                const t = this.toasts.find(t => t.id === id);
                if (t) t.visible = false;
                setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 300);
            }, duration);
        }
    }));
});
</script>
