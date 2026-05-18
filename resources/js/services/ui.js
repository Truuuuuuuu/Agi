export function toast(msg, type = 'info') {
    const id = Date.now() + Math.random();

    this.toasts.push({
        id,
        msg,
        type
    });

    setTimeout(() => {
        this.toasts = this.toasts.filter(
            t => t.id !== id
        );
    }, 4000);
}

export function fmtBytes(b) {
    if (b < 1024) return b + ' B';

    if (b < 1048576) {
        return (b / 1024).toFixed(1) + ' KB';
    }

    return (b / 1048576).toFixed(1) + ' MB';
}