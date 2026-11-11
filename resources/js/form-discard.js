// One top-layer confirmation; keep the original form DOM and validation state intact.
const baselines = new WeakMap();
const initialErrors = new WeakSet();
const snapshot = form => {
    // FormData omits unchecked and disabled controls. Package service toggles
    // disable dependent fields, so those edits could otherwise look pristine.
    if (form.elements) {
        return JSON.stringify([...form.elements]
            .filter(control => control.name && !['_token', '_service_modal'].includes(control.name))
            .map(control => {
                let value = control.value;
                if (control.type === 'checkbox' || control.type === 'radio') value = [control.checked, control.value];
                else if (control.tagName === 'SELECT' && control.multiple) value = [...control.selectedOptions].map(option => option.value);
                else if (control.type === 'file') value = [...(control.files || [])].map(file => [file.name, file.size, file.lastModified]);
                return [control.name, control.type || control.tagName, control.disabled, value];
            }));
    }
    // Keep lightweight form doubles and older callers compatible.
    return JSON.stringify([...new FormData(form).entries()].filter(([key]) => !['_token', '_service_modal'].includes(key)));
};
window.registerDiscardForm = form => {
    if (!form || baselines.has(form)) return;
    baselines.set(form, snapshot(form));
    if (form.querySelector?.('[aria-invalid="true"]') || [...(form.querySelectorAll?.('.form-error,[data-field-error]') ?? [])].some(error => error.textContent.trim() && !error.hidden && getComputedStyle(error).display !== 'none')) initialErrors.add(form);
};
let pending;
window.confirmFormDiscard = (formSurface = document.activeElement?.closest('dialog[open]')) => {
    const frame = window.frameElement;
    const parentDialog = frame?.closest('dialog[open]');
    if (parentDialog && typeof window.parent.confirmFormDiscard === 'function') {
        const focused = document.activeElement;
        return window.parent.confirmFormDiscard(parentDialog).then(discarded => {
            if (!discarded && focused?.isConnected) focused.focus({ preventScroll: true });
            return discarded;
        });
    }
    if (pending) return pending;
    const dialog = document.createElement('dialog');
    dialog.className = 'service-discard-dialog service-feedback-dialog';
    dialog.setAttribute('aria-labelledby', 'shared-discard-title');
    dialog.setAttribute('aria-describedby', 'shared-discard-copy');
    dialog.innerHTML = '<span class="service-discard-dialog__icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></span><h2 id="shared-discard-title">Discard unsaved changes?</h2><p id="shared-discard-copy">Your changes have not been saved.</p><footer><button type="button" data-keep autofocus>Keep Editing</button><button type="button" data-discard>Discard Changes</button></footer>';
    const focused = document.activeElement;
    const keep = dialog.querySelector('[data-keep]');
    const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const fadeForm = (from, to) => !reduced && formSurface?.animate([
        { opacity: from },
        { opacity: to },
    ], { duration: 150, easing: 'ease-in-out', fill: 'both' });
    document.body.append(dialog);
    let settle;
    pending = new Promise(resolve => {
        settle = () => {
            if (!dialog.isConnected) return;
            const discarded = dialog.returnValue === 'discard';
            dialog.remove(); pending = null;
            if (formSurface?.isConnected) {
                const restore = () => formSurface.classList.remove('is-discard-confirming');
                if (discarded) {
                    if (formSurface.open) formSurface.addEventListener('close', restore, { once: true });
                    else window.addEventListener('pageshow', restore, { once: true });
                } else {
                    restore();
                    const fade = fadeForm(0, 1);
                    if (fade) fade.finished.catch(() => {}).finally(() => fade.cancel());
                }
            }
            if (!discarded && focused?.isConnected) focused.focus({ preventScroll: true });
            resolve(discarded);
        };
        dialog.addEventListener('close', settle, { once: true });
    });
    let closing = false;
    const finish = async choice => {
        if (closing) return;
        closing = true;
        dialog.returnValue = choice;
        if (window.recordDialogMotion) await window.recordDialogMotion.close(dialog);
        else dialog.close(choice);
        if (!dialog.open) settle();
    };
    keep.onclick = () => finish('keep');
    dialog.querySelector('[data-discard]').onclick = () => finish('discard');
    dialog.addEventListener('cancel', event => { event.preventDefault(); finish('keep'); });
    dialog.addEventListener('click', event => {
        if (event.target !== dialog) return;
        const rect = dialog.getBoundingClientRect();
        if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) finish('keep');
    });
    const show = () => {
        if (formSurface?.isConnected) formSurface.classList.add('is-discard-confirming');
        if (window.recordDialogMotion) window.recordDialogMotion.open(dialog);
        else dialog.showModal();
        keep.focus();
    };
    const fade = fadeForm(1, 0);
    if (fade) {
        fade.finished.catch(() => {}).then(() => {
            show();
            fade.cancel();
        });
    } else {
        show();
    }
    return pending;
};
window.canDiscardForm = async form => {
    if (!form) return true;
    window.registerDiscardForm(form);
    if (!initialErrors.has(form) && snapshot(form) === baselines.get(form)) return true;
    const surface = form.closest?.('dialog[open]') || form.closest?.('.user-create-page, .user-edit-page');
    if (!await window.confirmFormDiscard(surface)) return false;
    form.reset();
    initialErrors.delete(form);
    form.querySelectorAll('[aria-invalid]').forEach(field => field.removeAttribute('aria-invalid'));
    form.querySelectorAll('[data-field-error]').forEach(error => { error.textContent = ''; error.classList.add('hidden'); });
    if (form.id === 'branchCreateForm') {
        form.querySelectorAll('input,select,textarea').forEach(field => {
            field.classList.remove('border-rose-300','bg-rose-50','focus:border-rose-500','focus:ring-rose-500');
        });
    }
    return true;
};
const register = () => document.querySelectorAll('#branchCreateForm,#branchEditForm,#userCreateForm,#userEditForm,#packageForm').forEach(window.registerDiscardForm);
register();
new MutationObserver(register).observe(document.body, { childList:true, subtree:true });
document.addEventListener('click', async event => {
    const cancel = event.target.closest('[data-record-form-cancel]');
    if (!cancel) return;
    event.preventDefault(); event.stopImmediatePropagation();
    const form=cancel.closest('form');
    if (await window.canDiscardForm(form)) window.location.assign(cancel.href);
}, true);
