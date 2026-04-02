<div class="pointer-events-none absolute inset-x-0 z-[5]"
     x-data="{ top: 0 }"
     x-init="
        const updatePosition = () => {
            const now = new Date();
            top = (now.getHours() * 60) + now.getMinutes();
        };
        updatePosition();
        setInterval(updatePosition, 60000);
     "
     :style="'top: ' + top + 'px'">
    <div class="flex items-center">
        <div class="size-2 rounded-full bg-red-500"></div>
        <div class="h-px flex-1 bg-red-500"></div>
    </div>
</div>
