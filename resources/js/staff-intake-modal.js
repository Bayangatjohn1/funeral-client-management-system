export function installStaffIntakeModal() {
    const dialog = document.getElementById('staff-intake-dialog');
    if (!dialog || typeof dialog.showModal !== 'function') return;

    const frame = dialog.querySelector('[data-intake-frame]');
    const confirmation = document.getElementById('staff-intake-confirmation');
    const body = frame.parentElement;
    const page = document.querySelector('.page-content');
    let scrollPosition;
    let openingTrigger;
    let releaseLoading;
    let closing = false;
    let switching = false;
    let backdropPressed = false;

    function fadeForm(from, to) {
        if (matchMedia('(prefers-reduced-motion: reduce)').matches) return null;
        return body.animate([{ opacity: from }, { opacity: to }], {
            duration: 150,
            easing: 'ease-in-out',
            fill: 'both',
        });
    }

    function stopLoading() {
        releaseLoading?.();
        releaseLoading = null;
    }

    async function close() {
        if (!dialog.open || closing) return;
        closing = true;
        if (dialog.classList.contains('is-confirming')) {
            dialog.close();
            return;
        }
        dialog.classList.add('is-closing');
        await Promise.allSettled(dialog.getAnimations().map(animation => animation.finished));
        dialog.close();
    }

    function requestClose() {
        if (closing || switching || confirmation?.open) return;
        try {
            if (frame.contentWindow?.requestStaffIntakeClose) {
                frame.contentWindow.requestStaffIntakeClose();
                return;
            }
        } catch {
            // A failed or redirected frame may not expose the intake wizard.
        }
        void close();
    }

    dialog.querySelector('[data-intake-close]')?.addEventListener('click', requestClose);

    window.prepareStaffIntakeSuccess = source => {
        if (source !== frame.contentWindow || !dialog.open) return;
        stopLoading();
        dialog.classList.add('is-saved');
    };

    window.closeStaffIntake = source => {
        if (source === frame.contentWindow) void close();
    };

    window.showStaffIntakeConfirmation = source => {
        if (source !== frame.contentWindow || !dialog.open || closing || !confirmation) return false;
        if (!confirmation.open && !switching) void openConfirmation();
        return true;
    };

    async function openConfirmation() {
        switching = true;
        const fade = fadeForm(1, 0);
        await fade?.finished.catch(() => {});
        if (dialog.open && !closing) {
            dialog.classList.add('is-confirming');
            window.recordDialogMotion.open(confirmation);
        }
        fade?.cancel();
        switching = false;
    }

    async function resolveConfirmation(decision) {
        if (!confirmation.open || closing || switching) return;
        switching = true;
        await window.recordDialogMotion.close(confirmation);
        if (decision !== 'discard') {
            dialog.classList.remove('is-confirming');
        }
        const fade = decision === 'keep' ? fadeForm(0, 1) : null;
        frame.contentWindow?.resolveStaffIntakeConfirmation?.(decision);
        await fade?.finished.catch(() => {});
        fade?.cancel();
        switching = false;
    }

    confirmation?.querySelectorAll('[data-intake-decision]').forEach(button => {
        button.addEventListener('click', () => resolveConfirmation(button.dataset.intakeDecision));
    });
    confirmation?.addEventListener('cancel', event => {
        event.preventDefault();
        resolveConfirmation('keep');
    });

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-intake-trigger]');
        if (!trigger) return;
        if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        if (dialog.open) return;
        openingTrigger = trigger;
        scrollPosition = { page: page?.scrollTop ?? 0, top: window.scrollY, left: window.scrollX };
        dialog.showModal();
        releaseLoading = window.PanelSkeleton?.begin(body);
        frame.src = trigger.dataset.intakeTrigger;
    });
    frame.addEventListener('load', () => {
        try {
            if (frame.contentWindow.location.href === 'about:blank') return;
        } catch {}
        stopLoading();
    });
    dialog.addEventListener('cancel', event => {
        event.preventDefault();
        requestClose();
    });
    dialog.addEventListener('pointerdown', event => { backdropPressed = event.target === dialog; });
    dialog.addEventListener('click', event => {
        if (backdropPressed && event.target === dialog) requestClose();
        backdropPressed = false;
    });
    dialog.addEventListener('close', () => {
        stopLoading();
        if (confirmation?.open) confirmation.close();
        frame.removeAttribute('src');
        dialog.classList.remove('is-closing', 'is-confirming', 'is-saved');
        closing = false;
        switching = false;
        openingTrigger?.focus({ preventScroll: true });
        if (page) page.scrollTop = scrollPosition.page;
        window.scrollTo(scrollPosition.left, scrollPosition.top);
    });
}
