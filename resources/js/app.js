import './bootstrap';
import './planner';

document.addEventListener('alpine:init', () => {
    Alpine.store('dragTask', { id: null, duration: 0 });

    Alpine.store('darkMode', {
        on: document.body.classList.contains('dark'),

        toggle() {
            this.on = !this.on;
            document.body.classList.toggle('dark', this.on);
        },

        init() {
            document.body.classList.toggle('dark', this.on);
        },
    });
});
