<div class="flex-1 overflow-hidden">
    @if ($currentView === 'week')
        <x-calendar.week-view :$days :$hours :$events :$tasks :$anchorDate />
    @elseif ($currentView === 'day')
        <x-calendar.day-view :$day :$hours :$events :$tasks />
    @elseif ($currentView === 'month')
        <x-calendar.month-view :$monthGroups :$events :$tasks :$anchorDate />
    @endif
</div>

@script
<script>
function dispatchLabel(label) {
    window.dispatchEvent(new CustomEvent('calendar-label-update', { detail: { label } }));
}

function monthYearFromDate(dateStr) {
    const d = new Date(dateStr + 'T12:00:00');
    return d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
}

Alpine.data('weekScroll', () => ({
    loading: false,

    init() {
        this.$nextTick(() => {
            const anchor = this.$refs.grid?.querySelector('[data-anchor]');
            if (anchor && this.$refs.scroller) {
                this.$refs.scroller.scrollLeft = anchor.offsetLeft;
            }
            // Scroll to ~8am vertically (each hour row = 60px, plus header)
            if (this.$refs.scroller) {
                this.$refs.scroller.scrollTop = 8 * 60;
            }
            this.updateLabel();
        });
    },

    updateLabel() {
        if (!this.$refs.scroller || !this.$refs.grid) return;
        const centerX = this.$refs.scroller.scrollLeft + this.$refs.scroller.clientWidth / 2;
        const headers = this.$refs.grid.querySelectorAll('[data-date]');
        for (const h of headers) {
            if (h.offsetLeft + h.offsetWidth > centerX) {
                dispatchLabel(monthYearFromDate(h.dataset.date));
                return;
            }
        }
    },

    async onScroll() {
        this.updateLabel();
        if (this.loading || !this.$refs.scroller) return;

        const { scrollLeft, scrollWidth, clientWidth } = this.$refs.scroller;

        if (scrollLeft < clientWidth) {
            this.loading = true;
            try {
                const oldWidth = this.$refs.grid.scrollWidth;
                const oldLeft = this.$refs.scroller.scrollLeft;
                await this.$wire.loadMore('past');
                this.$nextTick(() => {
                    const newWidth = this.$refs.grid.scrollWidth;
                    this.$refs.scroller.scrollLeft = oldLeft + (newWidth - oldWidth);
                });
            } finally {
                this.$nextTick(() => { this.loading = false; });
            }
        } else if (scrollWidth - scrollLeft - clientWidth < clientWidth) {
            this.loading = true;
            try {
                await this.$wire.loadMore('future');
            } finally {
                this.$nextTick(() => { this.loading = false; });
            }
        }
    },
}));

Alpine.data('weekDragCreate', () => ({
    isDragging: false,
    dragDate: null,
    dragStartMinute: 0,
    dragEndMinute: 0,
    previewTop: 0,
    previewHeight: 0,
    previewLeft: 0,
    previewWidth: 0,
    columnInfo: null,

    snapTo15(min) {
        return Math.round(min / 15) * 15;
    },

    getContentY(e) {
        return e.clientY - this.$el.getBoundingClientRect().top;
    },

    minutesFromY(y) {
        return Math.max(0, Math.min(1440, this.snapTo15(y)));
    },

    formatTimeRange() {
        const start = Math.min(this.dragStartMinute, this.dragEndMinute);
        let end = Math.max(this.dragStartMinute, this.dragEndMinute);
        if (end === start) end = start + 15;
        const fmt = (m) => {
            const h = Math.floor(m / 60);
            const min = m % 60;
            return String(h).padStart(2, '0') + ':' + String(min).padStart(2, '0');
        };
        return fmt(start) + ' – ' + fmt(end);
    },

    onMouseDown(e) {
        if (e.button !== 0) return;
        if (e.target.closest('[wire\\:key^="we-"], [wire\\:key^="wt-"]')) return;

        const cell = e.target.closest('[data-date][data-hour]');
        if (!cell) return;

        e.preventDefault();

        this.dragDate = cell.dataset.date;
        const y = this.getContentY(e);
        this.dragStartMinute = this.minutesFromY(y);
        this.dragEndMinute = this.dragStartMinute;

        const cellRect = cell.getBoundingClientRect();
        const elRect = this.$el.getBoundingClientRect();
        this.columnInfo = {
            left: cellRect.left - elRect.left,
            width: cellRect.width,
        };

        this.updatePreview();
        this.isDragging = true;
    },

    onMouseMove(e) {
        if (!this.isDragging) return;
        e.preventDefault();

        const y = this.getContentY(e);
        this.dragEndMinute = this.minutesFromY(y);
        this.updatePreview();
    },

    onMouseUp(e) {
        if (!this.isDragging) return;
        this.isDragging = false;

        const startMin = Math.min(this.dragStartMinute, this.dragEndMinute);
        let endMin = Math.max(this.dragStartMinute, this.dragEndMinute);
        if (endMin === startMin) endMin = startMin + 15;

        this.$dispatch('open-create-event-modal', {
            date: this.dragDate,
            startMinutes: startMin,
            endMinutes: endMin,
        });
    },

    updatePreview() {
        const start = Math.min(this.dragStartMinute, this.dragEndMinute);
        const end = Math.max(this.dragStartMinute, this.dragEndMinute);
        this.previewTop = start;
        this.previewHeight = Math.max(15, end - start);
        this.previewLeft = (this.columnInfo?.left ?? 0) + 4;
        this.previewWidth = (this.columnInfo?.width ?? 100) - 8;
    },
}));

Alpine.data('monthScroll', () => ({
    loading: false,

    init() {
        this.$nextTick(() => {
            const anchor = this.$refs.scroller?.querySelector('[data-anchor]');
            if (anchor && this.$refs.scroller) {
                this.$refs.scroller.scrollTop = anchor.offsetTop;
            }
            this.updateLabel();
        });
    },

    updateLabel() {
        if (!this.$refs.scroller) return;
        const viewportTop = this.$refs.scroller.scrollTop;
        const viewportMid = viewportTop + this.$refs.scroller.clientHeight / 3;
        const groups = this.$refs.scroller.querySelectorAll('[data-month-label]');
        let label = '';
        for (const group of groups) {
            if (group.offsetTop + group.offsetHeight > viewportMid) {
                label = group.dataset.monthLabel;
                break;
            }
        }
        if (label) dispatchLabel(label);
    },

    async onScroll() {
        this.updateLabel();
        if (this.loading || !this.$refs.scroller) return;

        const { scrollTop, scrollHeight, clientHeight } = this.$refs.scroller;

        if (scrollTop < clientHeight * 0.5) {
            this.loading = true;
            try {
                const oldHeight = scrollHeight;
                const oldTop = scrollTop;
                await this.$wire.loadMore('past');
                this.$nextTick(() => {
                    const newHeight = this.$refs.scroller.scrollHeight;
                    this.$refs.scroller.scrollTop = oldTop + (newHeight - oldHeight);
                });
            } finally {
                this.$nextTick(() => { this.loading = false; });
            }
        } else if (scrollHeight - scrollTop - clientHeight < clientHeight * 0.5) {
            this.loading = true;
            try {
                await this.$wire.loadMore('future');
            } finally {
                this.$nextTick(() => { this.loading = false; });
            }
        }
    },
}));
</script>
@endscript
