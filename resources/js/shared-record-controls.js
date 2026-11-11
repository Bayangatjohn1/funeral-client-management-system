// Presentation-only adoption of the Service Management controls.
export function filterOptionIcon(select, option) {
    if (option.dataset.icon) return option.dataset.icon;
    const value = `${option.value} ${option.textContent}`.toLowerCase().replaceAll('_', ' ');
    const field = (select.name || '').toLowerCase();
    if (/sort|order/.test(field)) {
        if (/oldest/.test(value)) return 'sort-up';
        if (/newest|latest/.test(value)) return 'sort-down';
        if (/branch/.test(value)) return 'building';
        if (/role/.test(value)) return 'person-badge';
        if (/amount|price|balance/.test(value)) return /desc/.test(value) ? 'sort-numeric-down-alt' : 'sort-numeric-down';
        return /desc|z.to.a|z.a/.test(value) ? 'sort-alpha-up' : 'sort-alpha-down';
    }
    if (/\ball\b|\bany\b/.test(value) || !option.value) {
        if (/branch/.test(field)) return 'buildings';
        if (/payment/.test(field)) return 'cash-stack';
        if (/verif/.test(field)) return 'shield';
        if (/role|user|encoded/.test(field)) return 'people';
        if (/package/.test(field)) return 'box-seam';
        if (/date|period/.test(field)) return 'calendar3';
        return 'collection';
    }
    if (/branch/.test(field)) return 'building';
    if (/package/.test(field)) return 'box-seam';
    if (/encoded|user/.test(field)) return 'person';
    if (/role/.test(field)) {
        if (/owner/.test(value)) return 'person-check';
        if (/admin/.test(value)) return 'person-gear';
        return 'person-badge';
    }
    if (/verif/.test(field)) {
        if (/disput/.test(value)) return 'shield-exclamation';
        if (/pending/.test(value)) return 'clock';
        return 'shield-check';
    }
    if (/payment/.test(field)) {
        if (/unpaid/.test(value)) return 'cash-coin';
        if (/partial/.test(value)) return 'pie-chart';
        if (/balance/.test(value)) return 'wallet2';
        return 'check-circle';
    }
    if (/unavailable/.test(value)) return 'pause-circle';
    if (/inactive|disabled|deactivated/.test(value)) return 'slash-circle';
    if (/archiv/.test(value)) return 'archive';
    if (/draft/.test(value)) return 'pencil';
    if (/pending|scheduled/.test(value)) return 'clock';
    if (/cancel|reject/.test(value)) return 'x-circle';
    if (/complet|success/.test(value)) return 'check2-all';
    if (/active|available|enabled/.test(value)) return 'check-circle';
    if (/date|period|month|year/.test(field)) {
        if (/today/.test(value)) return 'calendar-day';
        if (/week/.test(value)) return 'calendar-week';
        if (/month/.test(value)) return 'calendar-month';
        if (/custom|range/.test(value)) return 'calendar-range';
        return 'calendar3';
    }
    if (/service|categor|type/.test(field)) {
        if (/transport|hearse|retriev/.test(value)) return 'truck';
        if (/flower|floral/.test(value)) return 'flower1';
        if (/food|refresh/.test(value)) return 'cup-hot';
        if (/document/.test(value)) return 'file-earmark-text';
        if (/media/.test(value)) return 'camera-video';
        if (/staff/.test(value)) return 'person-badge';
        if (/casket|coffin/.test(value)) return 'box';
        if (/freebie/.test(value)) return 'gift';
        if (/custom/.test(value)) return 'sliders';
        if (/regular|standard/.test(value)) return 'box-seam';
        if (/facilit|viewing/.test(value)) return 'house';
        return 'tag';
    }
    if (/per.page|page.size/.test(field)) return 'list-ol';
    return 'list-ul';
}

export function initSharedRecordControls() {
    if (!document.body.classList.contains('panel-shell-body')) return;
    document.querySelectorAll('.records-page tr, .master-records-page tr, .directory-page tr, .service-management-hub tr, .payment-monitoring-page tr').forEach(row => {
        const view = row.querySelector('.primary-view-action, [data-pm-history-detail]');
        if (!view || view.closest('tr') !== row || view.matches(':disabled,[aria-disabled="true"]')) return;
        row.setAttribute('data-clickable-row', '');
        if (!row.hasAttribute('tabindex')) row.tabIndex = 0;
        view.setAttribute('data-row-view-trigger', '');
        view.classList.add('record-row-view');
    });
    const searchSelector = 'input[type="search"], input[name="q"], input[data-live-search-input], input[data-case-search], input[data-pm-search], input[data-table-search]';
    document.querySelectorAll(searchSelector).forEach(input => {
        if (input.dataset.sharedSearchReady || !input.closest('form')) return;
        input.dataset.sharedSearchReady = '1';
        let wrap = input.parentElement;
        if (wrap.tagName === 'LABEL') {
            const fieldWrap = document.createElement('span');
            fieldWrap.style.display = 'block';
            input.before(fieldWrap); fieldWrap.append(input); wrap = fieldWrap;
        }
        wrap.classList.add('shared-record-search');
        input.classList.add('shared-record-search-input');
        if (!input.getAttribute('aria-label') && !input.labels?.length) input.setAttribute('aria-label', 'Search records');
        let icon = wrap.querySelector('.bi-search');
        if (!icon) { icon = document.createElement('i'); icon.className = 'bi bi-search'; icon.setAttribute('aria-hidden', 'true'); wrap.prepend(icon); }
        icon.classList.add('shared-record-search-icon');
        let clear = wrap.querySelector('[data-live-search-clear], [data-service-clear-search], [data-case-search-clear], [data-pm-search-clear], button[aria-label="Clear search"]');
        if (!clear) {
            clear = document.createElement('button'); clear.type = 'button'; clear.setAttribute('aria-label', 'Clear search'); wrap.append(clear);
            clear.addEventListener('click', () => { input.value = ''; input.dispatchEvent(new Event('input', { bubbles:true })); input.focus(); });
        }
        clear.classList.add('shared-record-search-clear');
        clear.replaceChildren();
        const x = document.createElement('i'); x.className = 'bi bi-x'; x.setAttribute('aria-hidden', 'true'); clear.append(x);
        const sync = () => { clear.hidden = input.value.length === 0; };
        input.addEventListener('input', sync); sync();
    });
    document.querySelectorAll('[data-filter-select-wrap]').forEach(wrap => {
        wrap.classList.add('shared-record-filter');
        const select = wrap.querySelector('select');
        const chevron = wrap.querySelector('.table-toolbar-select-icon, [data-filter-select-icon], [class*="chev"], .payments-filter-dropdown-icon, .pf-control-icon, .aoc-select-icon, .case-page-size-chevron');
        chevron?.classList.add('shared-record-chevron');
        if (/per_page|page_size/.test(select?.name || '')) { wrap.classList.add('shared-record-page-size'); return; }
        if (select?.hasAttribute('data-no-leading-filter-icon')) return;
        if (!wrap.querySelector('.shared-record-filter-icon')) {
            let leading = [...wrap.children].find(el => el.matches('i.bi') && el !== chevron) || wrap.querySelector('.table-toolbar-leading-icon, .bi-funnel, .bi-sort-alpha-down, .bi-sort-alpha-up');
            if (!leading) { leading = document.createElement('i'); leading.className = 'bi bi-' + (/sort|order/.test(select?.name || '') ? 'sort-alpha-down' : 'funnel'); leading.setAttribute('aria-hidden', 'true'); wrap.prepend(leading); }
            leading.classList.add('shared-record-filter-icon');
            // The closed filter always uses the funnel; option icons describe choices.
            const isSort = /sort|order/.test(select?.name || '') || select?.matches('[data-table-sort], .table-toolbar-sort');
            if (!isSort) {
                [...leading.classList].filter(name => name.startsWith('bi-')).forEach(name => leading.classList.remove(name));
                leading.classList.add('bi', 'bi-funnel');
            }
        }
    });
    document.querySelectorAll('.row-hover-action, .branch-reference-icon, .case-row-action, .table-col-actions button, .table-col-actions a, .row-hover-actions button, .row-hover-actions a, .user-directory-action, .user-reference-icon, .pm-row-action, .user-view-button, .open-branch-view, .records-view-case').forEach(action => {
        action.classList.add('shared-record-action');
        const label = action.getAttribute('title') || action.getAttribute('aria-label') || action.textContent.trim();
        if (!action.dataset.actionTooltip && label) action.dataset.actionTooltip = label;
        action.removeAttribute('title');
        action.querySelectorAll('.user-view-tooltip, [role=tooltip]').forEach(tip => tip.remove());
        const icon = action.querySelector('i.bi');
        if (!icon) return;
        if (icon.classList.contains('bi-pencil-square')) { icon.classList.remove('bi-pencil-square'); icon.classList.add('bi-pencil'); }
        if (icon.classList.contains('bi-arrow-repeat')) { icon.classList.remove('bi-arrow-repeat'); icon.classList.add('bi-arrow-counterclockwise'); }
    });
}

export function installSharedRecordControls() {
    initSharedRecordControls();
    if (document.documentElement.dataset.sharedRecordControlsInstalled === 'true') return;
    document.documentElement.dataset.sharedRecordControlsInstalled = 'true';
    document.addEventListener('panel-ui:reset', initSharedRecordControls);
    const actionSelector = '.shared-record-action, .record-action-control';
    const existingTooltips = [...document.querySelectorAll('#shared-record-action-tooltip')];
    const tooltip = existingTooltips.shift() || document.createElement('div');
    existingTooltips.forEach(duplicate => duplicate.remove());
    tooltip.className = 'shared-action-tooltip';
    tooltip.id = 'shared-record-action-tooltip';
    tooltip.setAttribute('role', 'tooltip');
    tooltip.hidden = true;
    if (!tooltip.isConnected) document.body.append(tooltip);
    let active = null;
    let suppressedDirectoryAction = null;
    const hideTooltip = () => {
        if (active) {
            const descriptions = (active.getAttribute('aria-describedby') || '').split(' ').filter(id => id && id !== tooltip.id);
            if (descriptions.length) active.setAttribute('aria-describedby', descriptions.join(' ')); else active.removeAttribute('aria-describedby');
        }
        active = null; tooltip.hidden = true;
    };
    const showTooltip = action => {
        if (!action.dataset.actionTooltip) return;
        hideTooltip(); active = action;
        tooltip.textContent = action.dataset.actionTooltip;
        tooltip.hidden = false;
        action.setAttribute('aria-describedby', [action.getAttribute('aria-describedby'), tooltip.id].filter(Boolean).join(' '));
        const rect = action.getBoundingClientRect();
        const preferredLeft = action.dataset.actionTooltipPlacement === 'row-end'
            ? rect.right - tooltip.offsetWidth - 12
            : rect.left + rect.width / 2 - tooltip.offsetWidth / 2;
        tooltip.style.left = Math.max(12, Math.min(innerWidth - tooltip.offsetWidth - 12, preferredLeft)) + 'px';
        tooltip.style.top = (rect.top >= tooltip.offsetHeight + 12 ? rect.top - tooltip.offsetHeight - 8 : rect.bottom + 8) + 'px';
    };
    document.addEventListener('pointerover', event => {
        const action = event.target.closest(actionSelector);
        if (action && !action.contains(event.relatedTarget)) suppressedDirectoryAction=null;
        if (action && action !== active && action !== suppressedDirectoryAction) showTooltip(action);
    });
    document.addEventListener('pointerout', event => {
        if (active && active.contains(event.target) && !active.contains(event.relatedTarget)) hideTooltip();
    });
    document.addEventListener('focusin', event => {
        const action = event.target.closest(actionSelector);
        if (action && action !== suppressedDirectoryAction && (!action.matches('.record-row-view') || action.matches(':hover'))) showTooltip(action);
    });
    document.addEventListener('focusout', hideTooltip);
    document.addEventListener('pointerdown', event => {
        const action = event.target.closest(actionSelector);
        if (!action) return;
        suppressedDirectoryAction = action;
        hideTooltip();
    });
    document.addEventListener('click', event => {
        const action = event.target.closest(actionSelector);
        if (!action) return;
        suppressedDirectoryAction = action;
        hideTooltip();
    });
    const hideTooltipForViewportChange = () => {
        if (active) suppressedDirectoryAction = active;
        hideTooltip();
    };
    document.addEventListener('scroll', hideTooltipForViewportChange, true);
    window.addEventListener('resize', hideTooltipForViewportChange);
    document.addEventListener('keydown', event => { if (event.key === 'Escape') hideTooltip(); if(event.key==='Tab')suppressedDirectoryAction=null; });
    const filterRegion = '.shared-record-search, .shared-record-filter, form[method="get" i], [data-table-toolbar], .uniform-record-filters, .case-record-filters, .reports-module-controls, .reports-filter-card, .ba-workspace-filters, .owner-history-filter-panel, .payment-monitoring-page .pm-filters';
    const pointerFocusStyles = new Map();
    const quietPointerFocus = target => {
        if (!(target instanceof Element) || !target.closest(filterRegion)) return;
        const controls = [target, target.parentElement, target.parentElement?.parentElement].filter(Boolean);
        controls.forEach(control => {
            if (!pointerFocusStyles.has(control)) pointerFocusStyles.set(control, ['outline', 'box-shadow'].map(name => [name, control.style.getPropertyValue(name), control.style.getPropertyPriority(name)]));
            control.style.setProperty('outline', 'none', 'important');
            control.style.setProperty('box-shadow', 'none', 'important');
        });
    };
    document.addEventListener('pointerdown', event => {
        document.documentElement.dataset.filterInput = 'pointer';
        document.body.classList.add('record-controls-pointer');
        quietPointerFocus(event.target);
    }, true);
    document.addEventListener('focusin', event => {
        if (document.documentElement.dataset.filterInput === 'pointer') quietPointerFocus(event.target);
    }, true);
    document.addEventListener('keydown', event => {
        if (event.key === 'Tab' || event.key.startsWith('Arrow')) {
            document.documentElement.dataset.filterInput = 'keyboard';
            document.body.classList.remove('record-controls-pointer');
            pointerFocusStyles.forEach((styles, control) => styles.forEach(([name, value, priority]) => value ? control.style.setProperty(name, value, priority) : control.style.removeProperty(name)));
            pointerFocusStyles.clear();
        }
    }, true);
    let queued = false;
    new MutationObserver(() => {
        if (queued) return;
        queued = true;
        requestAnimationFrame(() => { queued = false; initSharedRecordControls(); });
    }).observe(document.body, { childList:true, subtree:true });
}
