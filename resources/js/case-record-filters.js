// Shared, delegated controls also work after either records table is refreshed.
let activeForm = null;
document.addEventListener('click', event => {
    const choice = event.target.closest('[data-case-page-size]');
    if (choice) {
        const control = choice.closest('.case-page-size-control');
        const input = control.querySelector('input');
        control.querySelector('[data-case-page-size-options]').setAttribute('aria-expanded', 'false');
        choice.closest('[popover]').hidePopover();
        choice.closest('[popover]').hidden = true;
        input.value = choice.dataset.casePageSize;
        input.dispatchEvent(new Event('change', {bubbles:true}));
        return;
    }
    const button = event.target.closest('[data-case-page-size-options]');
    const wasOpen = button?.getAttribute('aria-expanded') === 'true';
    document.querySelectorAll('.case-page-size-menu:popover-open').forEach(menu => {
        menu.hidePopover(); menu.hidden = true;
        menu.parentElement.querySelector('button[data-case-page-size-options]').setAttribute('aria-expanded', 'false');
    });
    if (!button || wasOpen) return;
    const menu = button.closest('.case-page-size-control').querySelector('.case-page-size-menu');
    const rect = button.closest('.case-page-size-control').getBoundingClientRect();
    menu.hidden = false; menu.showPopover();
    button.setAttribute('aria-expanded', 'true');
    Object.entries({position:'fixed', inset:'auto', margin:'0', top:`${rect.bottom + 6}px`, left:`${Math.max(12, rect.right - 120)}px`, width:'120px', minWidth:'0'}).forEach(([key,value]) => menu.style.setProperty(key.replace(/[A-Z]/g, x => `-${x.toLowerCase()}`),value,'important'));
});

const positionPanel = () => {
    if (!activeForm) return;
    const button = activeForm.querySelector('[data-case-more-toggle]');
    const panel = activeForm.querySelector('.case-compact-drawer');
    const content = activeForm.querySelector('[data-case-more-panel]');
    if (!button || !panel || !content) return;
    const rect = button.getBoundingClientRect();
    const gutter = 16;
    const gap = 12;
    // Match the compact Filter popover used in Service Management. The long
    // filter list scrolls inside the panel instead of widening over the table.
    // The application shell reserves horizontal space for its fixed sidebar.
    // Place the drawer within the page-content viewport so it never covers the
    // sidebar or gets shifted by the page-content entrance transform.
    const pageContent = activeForm.closest('.page-content') || document.querySelector('.page-content');
    const pageRect = pageContent
        ? pageContent.getBoundingClientRect()
        : {left: 0, right: window.innerWidth};
    const boundsLeft = Math.max(0, pageRect.left);
    const boundsRight = Math.min(window.innerWidth, pageRect.right);
    const boundsWidth = Math.max(0, boundsRight - boundsLeft);
    const width = Math.max(1, Math.min(352, boundsWidth - gutter * 2));
    const viewportHeight = window.innerHeight;
    const availableHeight = Math.min(Math.floor(viewportHeight * 0.65), 512);
    const left = Math.max(boundsLeft + gutter, Math.min(rect.right - width, boundsRight - width - gutter));
    const top = rect.bottom + 7;
    const panelMaxHeight = Math.max(200, Math.min(availableHeight, viewportHeight - top - gutter));
    // The width token belongs to the drawer itself: CSS resolves its width on
    // that element, and custom properties do not inherit from the popover child.
    panel.style.setProperty('--filter-width', `${width}px`);
    panel.style.setProperty('--filter-top', `${top}px`);
    panel.style.setProperty('--filter-left', `${left}px`);
    panel.style.setProperty('--filter-max-height', `${panelMaxHeight}px`);
};
const closePanel = (restoreFocus = true) => {
    if (!activeForm) return;
    const form = activeForm;
    const panel = form.querySelector('[data-case-more-panel]');
    window.setCaseCustomDateOpen?.(form, false);
    panel.querySelectorAll('.filter-select-menu').forEach(menu => {
        if (menu.matches(':popover-open')) menu.hidePopover();
        menu.hidden = true;
        const wrap = menu.closest('[data-filter-select-wrap]');
        wrap?.classList.remove('is-open');
        wrap?.querySelector('select')?.setAttribute('aria-expanded', 'false');
    });
    if (panel.matches(':popover-open')) panel.hidePopover();
    panel.hidden = true;
    const toggle = form.querySelector('[data-case-more-toggle]');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.classList.toggle('active', Number(toggle.dataset.filterCount) > 0);

    activeForm = null;
    if (restoreFocus) toggle?.focus({preventScroll:true});
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
    panel.hidden = false;
    // The top layer avoids offsets/clipping from the sidebar's containing blocks.
    panel.setAttribute('popover', 'manual');
    panel.showPopover();
    control.setAttribute('aria-expanded', 'true');
    control.classList.add('active');


    positionPanel();
    panel.querySelector('button, select, input')?.focus({preventScroll:true});
}, true);

document.addEventListener('click', event => {
    if (!activeForm) return;
    const target = event.target instanceof Element ? event.target : null;
    const panel = activeForm.querySelector('[data-case-more-panel]');
    const toggle = activeForm.querySelector('[data-case-more-toggle]');
    if (target && (panel?.contains(target) || toggle?.contains(target) || target.closest('.filter-select-menu, .case-date-dialog, [data-case-custom-panel]'))) return;
    // Keep unapplied selections when the user closes the panel and reopens it.
    closePanel(false);
}, true);

window.addEventListener('keydown', event => {
    if (!activeForm) return;
    if (event.key === 'Escape') {
        if (activeForm.querySelector('.filter-select-menu:popover-open, [data-case-custom-panel]:popover-open') || document.querySelector('.case-date-dialog[open]')) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        closePanel();
    }
}, true);
window.addEventListener('submit', event => {
    if (event.target === activeForm) closePanel(false);
}, true);
window.addEventListener('resize', positionPanel);
document.addEventListener('scroll', positionPanel, true);
const panelLayoutObserver = new ResizeObserver(positionPanel);
panelLayoutObserver.observe(document.documentElement);
document.addEventListener('transitionend', positionPanel);
window.addEventListener('popstate', () => closePanel(false));
