// Shared, delegated controls also work after either records table is refreshed.
let activeForm = null;
let previousOverflow = '';
const positionPanel = () => {
    if (!activeForm) return;
    const button = activeForm.querySelector('[data-case-more-toggle]');
    const panel = activeForm.querySelector('.case-compact-drawer');
    const rect = button.getBoundingClientRect();
    const width = Math.min(480, window.innerWidth - 32);
    const height = Math.min(panel.scrollHeight, window.innerHeight - 32);
    const top = Math.max(16, Math.min(rect.bottom + 8, window.innerHeight - height - 16));
    panel.style.setProperty('--filter-top', `${top}px`);
    panel.style.setProperty('--filter-left', `${Math.max(16, Math.min(rect.right - width, window.innerWidth - width - 16))}px`);
    panel.style.setProperty('--filter-height', `${window.innerHeight - top - 16}px`);
};
const closePanel = (restoreFocus = true, discard = true) => {
    if (!activeForm) return;
    const form = activeForm;
    const panel = form.querySelector('[data-case-more-panel]');
    if (discard) panel.querySelectorAll('input, select').forEach(field => {
        field.value = field.dataset.appliedValue ?? field.value;
    });
    if (panel.matches(':popover-open')) panel.hidePopover();
    panel.hidden = true;
    const toggle = form.querySelector('[data-case-more-toggle]');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.classList.toggle('active', Number(toggle.dataset.filterCount) > 0);
    document.body.style.overflow = previousOverflow;
    activeForm = null;
    if (restoreFocus) toggle.focus();
};

window.addEventListener('click', event => {
    const target = event.target instanceof Element ? event.target : null;
    const control = target?.closest('[data-case-more-toggle], [data-case-more-dismiss], [data-case-more-reset]');
    const form = control?.closest('.case-record-filters');
    if (!form) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    if (control.matches('[data-case-more-reset]')) {
        form.querySelectorAll('[data-case-more-panel] input, [data-case-more-panel] select').forEach(field => field.value = '');
        return;
    }
    if (control.matches('[data-case-more-dismiss]') || activeForm === form) {
        closePanel();
        return;
    }
    closePanel(false);
    activeForm = form;
    const panel = form.querySelector('[data-case-more-panel]');
    panel.querySelectorAll('input, select').forEach(field => field.dataset.appliedValue = field.value);
    panel.hidden = false;
    // The top layer avoids offsets/clipping from the sidebar's containing blocks.
    panel.setAttribute('popover', 'manual');
    panel.showPopover();
    control.setAttribute('aria-expanded', 'true');
    control.classList.add('active');
    previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    positionPanel();
    panel.querySelector('button, select, input')?.focus();
}, true);

window.addEventListener('keydown', event => {
    if (!activeForm) return;
    if (event.key === 'Escape') {
        event.preventDefault();
        event.stopImmediatePropagation();
        closePanel();
    } else if (event.key === 'Tab') {
        const fields = [...activeForm.querySelectorAll('[data-case-more-panel] button, [data-case-more-panel] select, [data-case-more-panel] input')]
            .filter(field => !field.disabled && field.getClientRects().length);
        const first = fields[0], last = fields.at(-1);
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
    }
}, true);
window.addEventListener('submit', event => {
    if (event.target === activeForm) closePanel(false, false);
}, true);
window.addEventListener('resize', positionPanel);
const panelLayoutObserver = new ResizeObserver(positionPanel);
panelLayoutObserver.observe(document.documentElement);
document.addEventListener('transitionend', positionPanel);
window.addEventListener('popstate', () => closePanel(false));
