<div class="pointer-events-none absolute inset-x-0 z-[15]"
     x-data="{ top: 0, timeLabel: '' }"
     x-init="
        const updatePosition = () => {
            const now = new Date();
            top = (now.getHours() * 60) + now.getMinutes();
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            timeLabel = h + ':' + m;
        };
        updatePosition();
        setInterval(updatePosition, 60000);
     "
     :style="'top: ' + top + 'px'">
    <div class="relative flex items-center">
        <div class="sticky left-0 z-10 w-[60px] shrink-0 pr-1.5 text-right">
            <span class="-mt-4 block text-[10px] font-medium text-red-400/80 dark:text-red-500/70" x-text="timeLabel"></span>
        </div>
        <div class="h-px flex-1 bg-red-400/40 dark:bg-red-500/30"></div>
    </div>
</div>
