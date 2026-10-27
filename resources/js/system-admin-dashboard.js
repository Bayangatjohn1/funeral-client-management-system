const dashboard = document.querySelector('.sa');
if (dashboard) {
    const attentionToggle = dashboard.querySelector('[data-sa-attention-toggle]');
    attentionToggle?.addEventListener('click', () => {
        const list = dashboard.querySelector('#sa-attention-list');
        const expanded = attentionToggle.getAttribute('aria-expanded') !== 'true';
        list.hidden = !expanded;
        attentionToggle.setAttribute('aria-expanded', String(expanded));
        attentionToggle.innerHTML = expanded
            ? 'Hide issues <i class="bi bi-chevron-up" aria-hidden="true"></i>'
            : `Review ${list.querySelectorAll('.sa-alert').length} ${list.querySelectorAll('.sa-alert').length === 1 ? 'issue' : 'issues'} <i class="bi bi-chevron-down" aria-hidden="true"></i>`;
    });
    const tooltip = document.createElement('div');
    tooltip.id = 'sa-overview-tooltip';
    tooltip.className = 'sa-tooltip';
    tooltip.setAttribute('role', 'tooltip');
    tooltip.hidden = true;
    document.body.append(tooltip);
    let tipTrigger;
    let dismissTimer;
    const hideTip = () => { clearTimeout(dismissTimer); tooltip.hidden = true; tipTrigger?.removeAttribute('aria-describedby'); tipTrigger = null; };
    const positionTip = () => {
        if (!tipTrigger || tooltip.hidden) return;
        const rect = tipTrigger.getBoundingClientRect();
        const width = tooltip.offsetWidth;
        const height = tooltip.offsetHeight;
        tooltip.style.left = `${Math.max(8, Math.min(window.innerWidth - width - 8, rect.left + rect.width / 2 - width / 2))}px`;
        tooltip.style.top = `${Math.max(8, rect.top >= height + 16 ? rect.top - height - 8 : Math.min(window.innerHeight - height - 8, rect.bottom + 8))}px`;
    };
    const showTip = trigger => {
        hideTip();
        tipTrigger = trigger;
        tooltip.textContent = trigger.dataset.saTip;
        trigger.setAttribute('aria-describedby', tooltip.id);
        tooltip.hidden = false;
        positionTip();
    };
    const scheduleHide = () => { dismissTimer = setTimeout(hideTip, 150); };
    dashboard.querySelectorAll('[data-sa-tip]').forEach(trigger => {
        trigger.addEventListener('mouseenter', () => showTip(trigger));
        trigger.addEventListener('mouseleave', scheduleHide);
        trigger.addEventListener('focus', () => showTip(trigger));
        trigger.addEventListener('blur', hideTip);
    });
    tooltip.addEventListener('mouseenter', () => clearTimeout(dismissTimer));
    tooltip.addEventListener('mouseleave', scheduleHide);
    document.addEventListener('keydown', event => { if (event.key === 'Escape') hideTip(); });
    window.addEventListener('resize', positionTip);
    document.addEventListener('scroll', hideTip, true);

    dashboard.querySelectorAll('[data-sa-dialog]').forEach(trigger => {
        const dialog = document.getElementById(trigger.dataset.saDialog);
        // The native top layer covers the sidebar; moving to body also avoids card clipping and inheritance traps.
        document.body.append(dialog);
        let pointerStartedOutside = false;
        const outside = event => {
            const rect = dialog.getBoundingClientRect();
            return event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom;
        };
        trigger.addEventListener('click', () => { hideTip(); dialog.showModal(); document.body.classList.add('sa-modal-open'); });
        dialog.querySelector('[data-sa-close]').addEventListener('click', () => dialog.close());
        dialog.addEventListener('keydown', event => {
            if (event.key !== 'Tab') return;
            const controls = [...dialog.querySelectorAll('a[href], button:not([disabled])')].filter(control => control.getClientRects().length);
            const first = controls[0];
            const last = controls.at(-1);
            if (event.shiftKey && (document.activeElement === first || !dialog.contains(document.activeElement))) {
                event.preventDefault(); last?.focus();
            } else if (!event.shiftKey && (document.activeElement === last || !dialog.contains(document.activeElement))) {
                event.preventDefault(); first?.focus();
            }
        });
        dialog.addEventListener('pointerdown', event => { pointerStartedOutside = event.target === dialog && outside(event); });
        dialog.addEventListener('click', event => { if (pointerStartedOutside && event.target === dialog && outside(event)) dialog.close(); pointerStartedOutside = false; });
        dialog.addEventListener('close', () => { document.body.classList.remove('sa-modal-open'); trigger.focus({preventScroll:true}); hideTip(); });
    });
    document.querySelectorAll('.sa a[href], .sa-dialog a[href]').forEach(link => {
        const destination = new URL(link.href);
        if (destination.origin === window.location.origin && destination.pathname !== '/admin') {
            destination.searchParams.set('return_to', `${window.location.origin}/admin`);
            link.href = destination.href;
        }
    });

    const branchFilter = dashboard.querySelector('#sa-branch-filter');
    const activityFilter = dashboard.querySelector('#sa-activity-filter');
    activityFilter?.addEventListener('change', () => {
        const rows = [...dashboard.querySelectorAll('[data-sa-activity-group]')];
        rows.forEach(row => { row.hidden = activityFilter.value !== 'all' && row.dataset.saActivityGroup !== activityFilter.value; });
        const count = rows.filter(row => !row.hidden).length;
        dashboard.querySelector('[data-sa-activity-status]').textContent = `${count} ${count === 1 ? 'event' : 'events'} from the latest five`;
        dashboard.querySelector('[data-sa-activity-empty]').hidden = rows.some(row => !row.hidden);
    });
    const branchRows = [...dashboard.querySelectorAll('[data-sa-branch-state]')];
    const updateBranches = () => {
        const matches = branchRows.filter(row => branchFilter.value === 'all' || row.dataset.saBranchState === branchFilter.value);
        branchRows.forEach(row => { row.hidden = true; });
        matches.forEach(row => { row.hidden = false; });
        dashboard.querySelector('[data-sa-branch-status]').textContent = `${matches.length} ${matches.length === 1 ? 'branch' : 'branches'}`;
        dashboard.querySelector('[data-sa-branch-empty]').hidden = matches.length > 0;
    };
    branchFilter.addEventListener('change', updateBranches);
    updateBranches();
    dashboard.querySelector('a[href="#sa-coverage-title"]')?.addEventListener('click', () => {
        branchFilter.value = 'needs-admin';
        updateBranches();
    });
}
