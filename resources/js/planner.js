function dispatchLabel(label) {
    window.dispatchEvent(new CustomEvent('calendar-label-update', { detail: { label } }));
}

function monthYearFromDate(dateStr) {
    const d = new Date(dateStr + 'T12:00:00');
    return d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
}

document.addEventListener('alpine:init', () => {
    Alpine.data('weekScroll', () => ({
        loadingPast: false,
        loadingFuture: false,
        initialized: false,

        init() {
            if (this.initialized) return;
            this.initialized = true;
            this.$nextTick(() => {
                const anchor = this.$refs.grid?.querySelector('[data-anchor]');
                if (anchor && this.$refs.scroller) {
                    this.$refs.scroller.scrollLeft = anchor.offsetLeft;
                }
                if (this.$refs.scroller) {
                    // Scroll to current time minus 2 hours for context
                    const now = new Date();
                    const nowMinutes = now.getHours() * 60 + now.getMinutes();
                    this.$refs.scroller.scrollTop = Math.max(0, nowMinutes - 120);
                }
                this.updateLabel();
                setTimeout(() => this.preloadFuture(), 500);
            });

            // Keyboard shortcuts
            this._keyHandler = (e) => {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;
                if (e.target.closest('[role="dialog"]')) return;

                switch (e.key) {
                    case 'ArrowLeft':
                        e.preventDefault();
                        this.scrollByDays(-1);
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        this.scrollByDays(1);
                        break;
                    case 't':
                        this.scrollToToday();
                        break;
                    case 'n':
                        Livewire.dispatch('openModal', { component: 'create-task-modal' });
                        break;
                    case 's':
                        Livewire.dispatch('auto-schedule');
                        break;
                }
            };
            document.addEventListener('keydown', this._keyHandler);
        },

        destroy() {
            if (this._keyHandler) {
                document.removeEventListener('keydown', this._keyHandler);
            }
        },

        scrollByDays(count) {
            if (!this.$refs.scroller) return;
            const colWidth = parseFloat(getComputedStyle(this.$refs.scroller).getPropertyValue('--col-width')) || 200;
            this.$refs.scroller.scrollBy({ left: colWidth * count, behavior: 'smooth' });
        },

        scrollToToday() {
            const anchor = this.$refs.grid?.querySelector('[data-anchor]');
            if (anchor && this.$refs.scroller) {
                this.$refs.scroller.scrollTo({ left: anchor.offsetLeft, behavior: 'smooth' });
            }
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

        async preloadPast() {
            if (this.loadingPast) return;
            this.loadingPast = true;
            try {
                const oldWidth = this.$refs.grid.scrollWidth;
                const oldLeft = this.$refs.scroller.scrollLeft;
                await this.$wire.loadMore('past');
                this.$nextTick(() => {
                    const newWidth = this.$refs.grid.scrollWidth;
                    this.$refs.scroller.scrollLeft = oldLeft + (newWidth - oldWidth);
                    this.loadingPast = false;
                });
            } catch {
                this.loadingPast = false;
            }
        },

        async preloadFuture() {
            if (this.loadingFuture) return;
            this.loadingFuture = true;
            try {
                await this.$wire.loadMore('future');
                this.$nextTick(() => { this.loadingFuture = false; });
            } catch {
                this.loadingFuture = false;
            }
        },

        async onScroll() {
            this.updateLabel();
            if (!this.$refs.scroller) return;

            const { scrollLeft, scrollWidth, clientWidth } = this.$refs.scroller;

            // Trigger at 50% remaining — gives Livewire time to render before user reaches the edge
            const pastRemaining = scrollLeft;
            const futureRemaining = scrollWidth - scrollLeft - clientWidth;

            if (pastRemaining < clientWidth && !this.loadingPast) {
                this.preloadPast();
            }

            if (futureRemaining < clientWidth && !this.loadingFuture) {
                this.preloadFuture();
            }
        },
    }));

    Alpine.data('calendarDrag', (config) => ({
        view: config.view,
        mode: null,
        showPreview: false,
        itemType: null,
        itemId: null,
        itemEl: null,
        itemTitle: '',
        resizeEdge: null,
        grabOffset: 0,
        originalDate: null,
        originalStartMinute: 0,
        originalEndMinute: 0,
        dragDate: null,
        dragStartMinute: 0,
        dragEndMinute: 0,
        previewTop: 0,
        previewHeight: 0,
        previewLeft: 0,
        previewWidth: 0,
        columnInfo: null,
        cloneEl: null,
        mouseDownX: 0,
        mouseDownY: 0,
        mouseDownTime: 0,
        dragStarted: false,
        pendingClear: false,

        init() {
            Livewire.on('calendar-event-created', () => {
                if (this.mode) { this.pendingClear = true; return; }
                this.clearClone();
            });
            Livewire.on('task-scheduled', () => {
                if (this.mode) { this.pendingClear = true; return; }
                this.clearClone();
            });
            Livewire.on('event-panel-closed', () => this.clearAll());
            Livewire.on('task-rescheduling', (params) => {
                const taskId = Array.isArray(params) ? params[0]?.taskId : params?.taskId;
                if (!taskId) return;
                const container = this.view === 'week' ? this.$refs.dayColumns : (this.$refs.gridContent ?? this.$el);
                if (!container) return;
                container.querySelectorAll('[data-item-type="task"][data-item-id="' + taskId + '"]').forEach(block => {
                    block.classList.add('rescheduling');
                });
            });
            window.addEventListener('event-title-sync', (e) => {
                const { id, title } = e.detail;
                if (this.showPreview) {
                    this.itemTitle = title;
                }
                if (!id) return;
                const container = this.view === 'week' ? this.$refs.dayColumns : (this.$refs.gridContent ?? this.$el);
                if (!container) return;
                container.querySelectorAll('[data-item-type="event"][data-item-id="' + id + '"]').forEach(block => {
                    const p = block.querySelector('p');
                    if (p) p.textContent = title || 'Untitled event';
                });
            });
        },

        flushPendingClear() {
            if (this.pendingClear) {
                this.pendingClear = false;
                this.clearClone();
            }
        },

        clearClone() {
            if (this.cloneEl) { this.cloneEl.remove(); this.cloneEl = null; }
            if (this.itemEl) { this.itemEl.style.display = ''; this.itemEl = null; }
        },

        clearAll() {
            this.showPreview = false;
            this.clearClone();
            this.mode = null;
            this.itemEl = null;
            this.dragStarted = false;
        },

        createClone() {
            if (!this.itemEl) return;
            this.cloneEl = this.itemEl.cloneNode(true);
            this.cloneEl.style.position = 'absolute';
            this.cloneEl.style.zIndex = '20';
            this.cloneEl.style.pointerEvents = 'none';
            this.cloneEl.style.opacity = '0.9';
            this.cloneEl.style.display = '';
            this.cloneEl.style.inset = 'auto';
            this.cloneEl.style.left = '';
            this.cloneEl.style.right = '';
            this.cloneEl.classList.remove('inset-x-1', 'inset-x-2');
            const handle = this.cloneEl.querySelector('.resize-handle');
            if (handle) handle.remove();
            this.cloneEl.removeAttribute('wire:key');
            const container = this.view === 'week' ? this.$refs.dayColumns : (this.$refs.gridContent ?? this.$el);
            container.appendChild(this.cloneEl);
        },

        markOverlappingTasks(startMin, endMin, date, excludeId) {
            const container = this.view === 'week' ? this.$refs.dayColumns : (this.$refs.gridContent ?? this.$el);
            if (!container) return;
            container.querySelectorAll('[data-item-type="task"]').forEach(block => {
                const id = parseInt(block.dataset.itemId);
                if (id === excludeId) return;
                const blockDate = block.dataset.itemDate;
                const blockStart = parseInt(block.dataset.itemStart);
                const blockEnd = parseInt(block.dataset.itemEnd);
                if (blockDate === date && blockStart < endMin && blockEnd > startMin) {
                    block.classList.add('rescheduling');
                }
            });
        },

        broadcastPosition(el) {
            if (!el) return;
            const rect = el.getBoundingClientRect();
            window.dispatchEvent(new CustomEvent('event-popover-position', {
                detail: { top: rect.top, left: rect.left, right: rect.right, width: rect.width }
            }));
        },

        snapTo15(min) { return Math.round(min / 15) * 15; },

        getContentY(e) {
            if (this.view === 'day') {
                const grid = this.$refs.gridContent ?? this.$el;
                return e.clientY - grid.getBoundingClientRect().top;
            }
            return e.clientY - this.$el.getBoundingClientRect().top;
        },

        minutesFromY(y) { return Math.max(0, Math.min(1440, this.snapTo15(y))); },

        formatTimeRange() {
            const start = Math.min(this.dragStartMinute, this.dragEndMinute);
            let end = Math.max(this.dragStartMinute, this.dragEndMinute);
            if (end === start) end = start + 15;
            const fmt = (m) => {
                const h = Math.floor(m / 60);
                const min = m % 60;
                return String(h).padStart(2, '0') + ':' + String(min).padStart(2, '0');
            };
            return fmt(start) + ' \u2013 ' + fmt(end);
        },

        getColumnInfo(date) {
            if (this.view === 'day') return null;
            const col = this.$refs.dayColumns;
            if (!col) return null;
            const cell = col.querySelector('[data-date="' + date + '"][data-hour]');
            if (!cell) return null;
            const colRect = col.getBoundingClientRect();
            const cellRect = cell.getBoundingClientRect();
            return { left: cellRect.left - colRect.left, width: cellRect.width };
        },

        onMouseDown(e) {
            if (e.button !== 0) return;
            this.mouseDownX = e.clientX;
            this.mouseDownY = e.clientY;
            this.mouseDownTime = Date.now();
            this.dragStarted = false;

            const resizeHandle = e.target.closest('.resize-handle');
            const itemBlock = e.target.closest('[data-item-type]');

            if (resizeHandle && itemBlock) {
                e.preventDefault();
                this.mode = 'resize';
                this.resizeEdge = resizeHandle.classList.contains('resize-top') ? 'top' : 'bottom';
                this.itemType = itemBlock.dataset.itemType;
                this.itemId = parseInt(itemBlock.dataset.itemId);
                this.itemEl = itemBlock;
                this.itemTitle = itemBlock.dataset.itemTitle || '';
                this.originalDate = itemBlock.dataset.itemDate;
                this.originalStartMinute = parseInt(itemBlock.dataset.itemStart);
                this.originalEndMinute = parseInt(itemBlock.dataset.itemEnd);
                this.dragDate = this.originalDate;
                this.dragStartMinute = this.originalStartMinute;
                this.dragEndMinute = this.originalEndMinute;
                this.columnInfo = this.getColumnInfo(this.originalDate);
                return;
            }

            if (itemBlock) {
                e.preventDefault();
                this.mode = 'move';
                this.itemType = itemBlock.dataset.itemType;
                this.itemId = parseInt(itemBlock.dataset.itemId);
                this.itemEl = itemBlock;
                this.itemTitle = itemBlock.dataset.itemTitle || '';
                this.originalDate = itemBlock.dataset.itemDate;
                this.originalStartMinute = parseInt(itemBlock.dataset.itemStart);
                this.originalEndMinute = parseInt(itemBlock.dataset.itemEnd);
                this.dragDate = this.originalDate;
                this.dragStartMinute = this.originalStartMinute;
                this.dragEndMinute = this.originalEndMinute;
                this.columnInfo = this.getColumnInfo(this.originalDate);
                const y = this.getContentY(e);
                this.grabOffset = this.minutesFromY(y) - this.originalStartMinute;
                return;
            }

            const cell = e.target.closest('[data-date][data-hour]');
            if (!cell) return;
            e.preventDefault();
            this.mode = 'create';
            this.itemType = null;
            this.itemId = null;
            this.itemEl = null;
            this.itemTitle = '';
            this.dragDate = cell.dataset.date;
            const y = this.getContentY(e);
            this.dragStartMinute = this.minutesFromY(y);
            this.dragEndMinute = this.dragStartMinute;
            this.columnInfo = this.getColumnInfo(this.dragDate);
        },

        onMouseMove(e) {
            if (!this.mode) return;
            e.preventDefault();

            if (!this.dragStarted) {
                const dx = e.clientX - this.mouseDownX;
                const dy = e.clientY - this.mouseDownY;
                if (Math.sqrt(dx * dx + dy * dy) < 5) return;
                this.dragStarted = true;
                if ((this.mode === 'move' || this.mode === 'resize') && this.itemEl) {
                    this.itemEl.style.opacity = '0.3';
                    this.createClone();
                } else {
                    this.showPreview = true;
                }
                this.updatePreview();
            }

            const y = this.getContentY(e);
            const minute = this.minutesFromY(y);

            if (this.mode === 'create') {
                this.dragEndMinute = minute;
            } else if (this.mode === 'resize') {
                if (this.resizeEdge === 'top') {
                    this.dragStartMinute = Math.min(this.dragEndMinute - 15, minute);
                } else {
                    this.dragEndMinute = Math.max(this.dragStartMinute + 15, minute);
                }
            } else if (this.mode === 'move') {
                const duration = this.originalEndMinute - this.originalStartMinute;
                this.dragStartMinute = Math.max(0, minute - this.grabOffset);
                this.dragEndMinute = this.dragStartMinute + duration;

                if (this.view === 'week' && this.$refs.dayColumns) {
                    const colRect = this.$refs.dayColumns.getBoundingClientRect();
                    const x = e.clientX - colRect.left;
                    const cells = this.$refs.dayColumns.querySelectorAll('[data-date][data-hour="0"]');
                    for (const cell of cells) {
                        const cellRect = cell.getBoundingClientRect();
                        const cellLeft = cellRect.left - colRect.left;
                        if (x >= cellLeft && x < cellLeft + cellRect.width) {
                            this.dragDate = cell.dataset.date;
                            this.columnInfo = { left: cellLeft, width: cellRect.width };
                            break;
                        }
                    }
                }
            }

            this.updatePreview();
        },

        onMouseUp(e) {
            if (!this.mode) return;

            if (!this.dragStarted) {
                const elapsed = Date.now() - this.mouseDownTime;
                if (elapsed < 300) {
                    if (this.itemType === 'event' && this.itemId) {
                        this.broadcastPosition(this.itemEl);
                        Livewire.dispatch('open-edit-event-panel', { eventId: this.itemId });
                    } else if (this.itemType === 'task' && this.itemId) {
                        Livewire.dispatch('openModal', { component: 'task-detail-modal', arguments: { taskId: this.itemId } });
                    } else if (this.itemType === 'project-block' && this.itemId) {
                        const projectId = this.itemEl?.dataset.projectId;
                        if (projectId) {
                            Livewire.dispatch('openModal', { component: 'project-modal', arguments: { projectId: parseInt(projectId) } });
                        }
                    }
                    this.mode = null;
                    this.itemEl = null;
                    this.flushPendingClear();
                    return;
                }
            }

            if (this.mode === 'create' && this.dragStarted) {
                const startMin = Math.min(this.dragStartMinute, this.dragEndMinute);
                let endMin = Math.max(this.dragStartMinute, this.dragEndMinute);
                if (endMin === startMin) endMin = startMin + 15;
                this.mode = null;

                const container = this.view === 'week' ? this.$refs.dayColumns : (this.$refs.gridContent ?? this.$el);
                const highlight = container?.querySelector('[x-show="showPreview"]');
                if (highlight) {
                    this.broadcastPosition(highlight);
                } else {
                    const left = (this.columnInfo?.left ?? 0);
                    const width = (this.columnInfo?.width ?? 100);
                    const containerRect = container.getBoundingClientRect();
                    window.dispatchEvent(new CustomEvent('event-popover-position', {
                        detail: { top: containerRect.top + startMin, left: containerRect.left + left, right: containerRect.left + left + width, width }
                    }));
                }

                Livewire.dispatch('open-create-event-panel', { date: this.dragDate, startMinutes: startMin, endMinutes: endMin });
                return;
            }

            if (this.mode === 'move' && this.dragStarted) {
                if (this.itemEl) { this.itemEl.style.opacity = ''; this.itemEl.style.display = 'none'; }
                const duration = this.originalEndMinute - this.originalStartMinute;
                this.markOverlappingTasks(this.dragStartMinute, this.dragStartMinute + duration, this.dragDate, this.itemType === 'task' ? this.itemId : null);

                if (this.itemType === 'event') {
                    this.$wire.moveEvent(this.itemId, this.dragDate, this.dragStartMinute);
                } else if (this.itemType === 'project-block') {
                    this.$wire.moveProjectBlock(this.itemId, this.dragDate, this.dragStartMinute);
                } else {
                    this.$wire.moveTask(this.itemId, this.dragDate, this.dragStartMinute);
                }
                this.mode = null;
                this.dragStarted = false;
                this.flushPendingClear();
                return;
            }

            if (this.mode === 'resize' && this.dragStarted) {
                if (this.itemEl) { this.itemEl.style.display = 'none'; }
                this.markOverlappingTasks(this.dragStartMinute, this.dragEndMinute, this.dragDate, this.itemType === 'task' ? this.itemId : null);

                if (this.itemType === 'event') {
                    this.$wire.resizeEvent(this.itemId, this.dragStartMinute, this.dragEndMinute);
                } else if (this.itemType === 'project-block') {
                    this.$wire.resizeProjectBlock(this.itemId, this.dragStartMinute, this.dragEndMinute);
                } else {
                    this.$wire.resizeTask(this.itemId, this.dragStartMinute, this.dragEndMinute);
                }
                this.mode = null;
                this.dragStarted = false;
                this.flushPendingClear();
                return;
            }

            this.mode = null;
            this.itemEl = null;
            this.dragStarted = false;
            this.flushPendingClear();
        },

        updatePreview() {
            const start = Math.min(this.dragStartMinute, this.dragEndMinute);
            const end = Math.max(this.dragStartMinute, this.dragEndMinute);
            const height = Math.max(15, end - start);

            if (this.cloneEl) {
                this.cloneEl.style.top = start + 'px';
                this.cloneEl.style.height = Math.max(30, (height / 60) * 60) + 'px';
                if (this.view === 'week') {
                    this.cloneEl.style.left = (this.columnInfo?.left ?? 0) + 4 + 'px';
                    this.cloneEl.style.width = (this.columnInfo?.width ?? 100) - 8 + 'px';
                } else {
                    this.cloneEl.style.left = '4px';
                    this.cloneEl.style.right = '4px';
                    this.cloneEl.style.width = '';
                }
                const label = this.cloneEl.querySelector('.time-label');
                if (label) {
                    if (this.itemType === 'task') {
                        const dur = end - start;
                        label.textContent = dur >= 60
                            ? Math.floor(dur / 60) + 'h' + (dur % 60 ? ' ' + (dur % 60) + 'min' : '')
                            : dur + 'min';
                    } else {
                        label.textContent = this.formatTimeRange();
                    }
                }
                return;
            }

            this.previewTop = start;
            this.previewHeight = height;
            if (this.view === 'week') {
                this.previewLeft = (this.columnInfo?.left ?? 0) + 4;
                this.previewWidth = (this.columnInfo?.width ?? 100) - 8;
            }
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
});
