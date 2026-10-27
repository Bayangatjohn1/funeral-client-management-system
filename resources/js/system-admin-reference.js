// Shared interaction accessibility for System Admin's native dialogs.
if (document.body.classList.contains('system-admin-ui')) {
    const syncDialogScroll = () => {
        const open = Boolean(document.querySelector('dialog[open]'));
        document.body.classList.toggle('system-admin-modal-open', open);
        document.documentElement.classList.toggle('system-admin-modal-open', open);
    };
    new MutationObserver(syncDialogScroll).observe(document.body, {
        subtree: true, childList: true, attributes: true, attributeFilter: ['open'],
    });
    syncDialogScroll();
    document.addEventListener('keydown', event => {
        if (event.key !== 'Tab' || event.defaultPrevented) return;
        const dialog = event.target.closest('dialog[open],.prof-overlay.is-open');
        if (!dialog) return;
        const controls = [...dialog.querySelectorAll('a[href],button,input,select,textarea,iframe,[tabindex]')]
            .filter(el => !el.disabled && el.tabIndex >= 0 && el.getClientRects().length);
        if (!controls.length) { event.preventDefault(); return; }
        const first = controls[0], last = controls.at(-1);
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault(); last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault(); first.focus();
        }
    });
}
