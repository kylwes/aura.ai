<div x-data="toast()" x-on:toast.window="show($event.detail)"
     @mouseenter="hovering = true" @mouseleave="hovering = false"
     class="pointer-events-none fixed bottom-6 right-6 z-50"
     :style="`height: ${toasts.length > 0 ? 80 + (hovering ? (toasts.length - 1) * 72 : (toasts.length - 1) * 12) : 0}px; transition: height 300ms cubic-bezier(0.16, 1, 0.3, 1);`">
    <template x-for="(t, i) in toasts" :key="t.id">
        <div x-show="t.visible"
             x-transition:enter="transition duration-400"
             x-transition:enter-start="translate-y-6 scale-95 opacity-0"
             x-transition:enter-end="translate-y-0 scale-100 opacity-100"
             x-transition:leave="transition duration-300"
             x-transition:leave-start="scale-100 opacity-100"
             x-transition:leave-end="scale-95 opacity-0"
             class="pointer-events-auto absolute bottom-0 right-0 flex w-max max-w-sm items-center gap-3 rounded-2xl bg-white px-5 py-3.5 shadow-lg shadow-neutral-900/10 ring-1 ring-neutral-200/60 dark:bg-neutral-800 dark:shadow-neutral-950/30 dark:ring-neutral-700/60"
             :style="`
                z-index: ${50 + i};
                transform: translateY(${hovering ? -(toasts.length - 1 - i) * 72 : -(toasts.length - 1 - i) * 12}px) scale(${hovering ? 1 : 1 - (toasts.length - 1 - i) * 0.05});
                opacity: ${(toasts.length - 1 - i) > 2 && !hovering ? 0 : 1};
                transition: transform 300ms cubic-bezier(0.16, 1, 0.3, 1), opacity 300ms cubic-bezier(0.16, 1, 0.3, 1);
             `">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-full"
                  :class="t.type === 'success' ? 'bg-priority-low/15 text-priority-low' : t.type === 'error' ? 'bg-priority-urgent/15 text-priority-urgent' : 'bg-accent-500/15 text-accent-500'">
                <svg x-show="t.type === 'success'" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                <svg x-show="t.type === 'error'" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                <svg x-show="t.type === 'info'" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
            </span>
            <div class="min-w-0">
                <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100" x-text="t.title"></p>
                <p x-show="t.body" class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400" x-text="t.body"></p>
            </div>
            <button @click="dismiss(t.id)" class="ml-1 shrink-0 rounded-lg p-1 text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-700 dark:hover:text-neutral-300">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('toast', () => ({
        toasts: [],
        counter: 0,
        hovering: false,
        show({ type = 'success', title, body = '', duration = 4000 }) {
            const id = ++this.counter;
            this.toasts.push({ id, type, title, body, visible: true });
            setTimeout(() => this.dismiss(id), duration);
        },
        dismiss(id) {
            const t = this.toasts.find(t => t.id === id);
            if (t && t.visible) {
                t.visible = false;
                setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 400);
            }
        }
    }));
});
</script>
