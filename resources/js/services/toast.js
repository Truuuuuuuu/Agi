export function toastManager() {
    return {
        toasts: [],

        init() {
            window.toast = this.show.bind(this);
        },

        show(message, type = 'info') {
            const id = Date.now();

            this.toasts.push({
                id,
                message,
                type,
                show: true
            });

            setTimeout(() => this.remove(id), 3000);
        },

        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    };
}