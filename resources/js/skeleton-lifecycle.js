// Reference-counted loading: an older response must not clear a newer loader.
export function createSkeletonLifecycle(show, hide, { delay = 160, timeout = 30000 } = {}) {
    const pending = new Set();
    let revealTimer, expiryTimer, visible = false;
    const reset = () => {
        clearTimeout(revealTimer); clearTimeout(expiryTimer);
        pending.clear();
        if (visible) hide();
        visible = false;
    };
    return {
        get pending() { return pending.size; },
        begin() {
            const token = Symbol();
            const first = !pending.size;
            pending.add(token);
            if (first) {
                if (delay === 0) { visible = true; show(); }
                else revealTimer = setTimeout(() => { if (pending.size) { visible = true; show(); } }, delay);
                // Recover usable content if navigation is cancelled or a request hangs.
                expiryTimer = setTimeout(reset, timeout);
            }
            return () => {
                if (!pending.delete(token)) return;
                if (!pending.size) reset();
            };
        },
        reset,
    };
}
