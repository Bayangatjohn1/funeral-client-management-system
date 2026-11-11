// Shared motion keeps centered dialogs and right drawers on their existing flows.
export function installRecordDialogMotion() {
    const runs = new WeakMap();
    const reduced = () => matchMedia('(prefers-reduced-motion: reduce)').matches;
    const panelFor = dialog => dialog.querySelector('.service-modal__panel,#branchModalSheet') || dialog;
    const isDrawer = dialog => dialog.matches('.record-drawer,.is-record-drawer,.service-modal.is-detail');
    const paymentMotion = { duration:260, easing:'cubic-bezier(.22,1,.36,1)' };
    const stop = dialog => {
        const run = runs.get(dialog);
        run?.animation?.cancel();
        const token = {};
        runs.set(dialog, token);
        return token;
    };
    window.recordDialogMotion = {
        open(dialog) {
            const run = stop(dialog);
            if (!dialog.open) dialog.showModal();
            if (!reduced() && dialog.dataset?.paymentDrawer !== undefined) {
                run.animation = panelFor(dialog).animate([{ transform:'translateX(100%)' }, { transform:'translateX(0)' }], paymentMotion);
            } else if (!reduced()) run.animation = panelFor(dialog).animate([
                { opacity:0, transform:isDrawer(dialog) ? 'translateX(100%)' : 'translateY(8px) scale(.95)' },
                { opacity:1, transform:isDrawer(dialog) ? 'translateX(0)' : 'translateY(0) scale(1)' },
            ], { duration:180, easing:'ease-out' });
        },
        async close(dialog) {
            if (!dialog.open) return true;
            const run = stop(dialog);
            if (!reduced()) {
                const payment = dialog.dataset?.paymentDrawer !== undefined;
                run.animation = payment ? panelFor(dialog).animate([
                    { transform:'translateX(0)' }, { transform:'translateX(100%)' },
                ], { ...paymentMotion, fill:'forwards' }) : panelFor(dialog).animate([
                    { opacity:1, transform:isDrawer(dialog) ? 'translateX(0)' : 'translateY(0) scale(1)' },
                    { opacity:0, transform:isDrawer(dialog) ? 'translateX(100%)' : 'translateY(8px) scale(.95)' },
                ], { duration:180, easing:'ease-in' });
                // A suspended document timeline must never leave a dialog stuck open.
                let timer;
                await Promise.race([
                    run.animation.finished.catch(() => {}),
                    new Promise(resolve => { timer = setTimeout(resolve, payment ? 320 : 260); }),
                ]);
                clearTimeout(timer);
            }
            if (runs.get(dialog) !== run) return false;
            dialog.close();
            return true;
        },
    };
}
