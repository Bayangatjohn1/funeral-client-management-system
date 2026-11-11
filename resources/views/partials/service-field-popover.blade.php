<style>
    .service-field-chip { display:inline-flex; align-items:center; gap:8px; padding:6px 11px; border:0; border-radius:8px; background:#dcebe2; color:#234a36; text-align:left; }
    .service-field-chip:is(:hover,[aria-expanded=true]) { background:#c9e0d2; }
    .service-field-chip .service-field-chevron { margin-inline-start:auto; transition:rotate 150ms ease; }
    .service-field-chip[aria-expanded=true] .service-field-chevron { rotate:180deg; }
    @media(prefers-reduced-motion:reduce) { .service-field-chevron { transition:none; } }
    html[data-theme=dark] .service-field-chip { background:#294538; color:#e3f5eb; }
    html[data-theme=dark] .service-field-chip:is(:hover,[aria-expanded=true]) { background:#375c49; }
    .service-field-adjust { background:transparent; border:0; color:inherit; text-decoration:underline; text-underline-offset:3px; }
    .service-field-adjust:hover { color:var(--brand,#367555); }
    [data-service-tooltip] { position:relative; }
    [data-service-tooltip]:not([aria-expanded=true]):is(:hover,:focus-visible)::after {
        content:attr(data-service-tooltip); position:absolute; z-index:2; left:0; bottom:calc(100% + 5px); width:max-content;
        padding:4px 7px; border-radius:5px; background:var(--ink,#18382a); color:var(--card,#f4f8f5);
        font:500 11px/1.4 system-ui,sans-serif; text-decoration:none; pointer-events:none;
    }
    .service-field-popover[popover] { position:fixed; inset:auto; margin:0; width:min(420px,calc(100vw - 24px)); overflow:auto; padding:14px; border:0; border-radius:12px; background:var(--card,#f4f8f5); color:var(--ink,#18382a); }
    .service-field-popover[popover]:not(:popover-open) { display:none !important; }
    :root { --service-popover-shadow:0 12px 34px rgb(18 55 38 / 25%), 0 2px 8px rgb(18 55 38 / 12%); }
    html[data-theme=dark] { --service-popover-shadow:0 12px 34px rgb(0 0 0 / 50%), 0 2px 8px rgb(0 0 0 / 30%); }
    .service-field-popover[popover]:popover-open { box-shadow:var(--service-popover-shadow) !important; }
    .service-field-popover .service-field-results { display:grid; gap:4px; margin-top:8px; }
    .service-field-results button { text-align:left; padding:8px; border-radius:7px; }
    .service-field-results button:is(:hover,:focus-visible) { background:var(--surface-muted,#dcebe2); }
    .service-field-results button[aria-pressed=true] { background:var(--service-field-focus-bg); font-weight:650; }
    .service-field-results button[hidden] { display:none !important; }
    .service-field-popover .intake-asset-search { display:flex; align-items:center; gap:4px; border-radius:9px; background:var(--surface-muted,#eaf2ed); }
    .service-field-popover .intake-asset-search input { flex:1; min-width:0; }
    .service-field-popover .intake-asset-search button { padding:8px 10px; border-radius:7px; }
    .service-field-popover .intake-asset-search button:last-child { background:#a9c9b5; color:#123524; }
    html[data-theme=dark] .service-field-popover .intake-asset-search button:last-child { background:#416b54; color:#ecf8ef; }
    .service-field-popover .intake-asset-search [hidden] { display:none !important; }
    .service-popover-footer { display:flex; justify-content:flex-end; margin-top:12px; padding-top:9px; border-top:1px solid var(--border,#c2d5c9); }
    .service-popover-footer .service-popover-done { min-height:30px; padding:4px 12px; font-size:12px; font-weight:650; }
    :root { --service-field-focus-bg:#e1eee5; --service-field-focus-ring:#719a81; }
    html[data-theme=dark] { --service-field-focus-bg:#2e4a3b; --service-field-focus-ring:#8ab79b; }
    html[data-theme] body :is(#packageForm,#intakeWizardForm) :is(.service-field-popover,.inline-upgrade-options,#optional_add_ons_panel) :is(input:not([type=hidden],#optional_add_ons_search),select,textarea):focus {
        background:var(--service-field-focus-bg) !important; outline:1px solid var(--service-field-focus-ring) !important; outline-offset:0; box-shadow:none !important;
    }
</style>
<script>
    window.addServicePopoverDone = (panel, trigger) => {
        const footer = document.createElement('div');
        footer.className = 'service-popover-footer';
        const done = document.createElement('button');
        done.type = 'button';
        done.className = 'service-field-chip service-popover-done';
        done.textContent = 'Done';
        done.addEventListener('click', () => {
            panel.hidePopover();
            trigger.focus({ preventScroll:true });
        });
        footer.append(done);
        panel.append(footer);
    };
    window.bindServiceFieldPopover = (trigger, panel, onOpen, boundary = null) => {
        const position = () => {
            if (!panel.matches(':popover-open')) return;
            if (!trigger.getClientRects().length || trigger.disabled) { panel.hidePopover(); return; }
            const anchor = trigger.getBoundingClientRect();
            if (boundary) {
                const bounds = boundary.getBoundingClientRect();
                const viewport = window.visualViewport;
                const viewportLeft = viewport?.offsetLeft ?? 0;
                const viewportTop = viewport?.offsetTop ?? 0;
                const left = Math.max(viewportLeft, bounds.left) + 8;
                const right = Math.min(viewportLeft + (viewport?.width ?? innerWidth), bounds.right) - 8;
                const top = Math.max(viewportTop, bounds.top) + 8;
                const bottom = Math.min(viewportTop + (viewport?.height ?? innerHeight), bounds.bottom) - 8;
                if (anchor.bottom <= top || anchor.top >= bottom || right <= left) {
                    panel.hidePopover();
                    return;
                }

                panel.style.maxWidth = `${right - left}px`;
                panel.style.maxHeight = '';
                const below = Math.max(0, bottom - anchor.bottom - 6);
                const above = Math.max(0, anchor.top - top - 6);
                const openAbove = panel.scrollHeight > below && above > below;
                panel.style.maxHeight = `${openAbove ? above : below}px`;
                const preferredLeft = anchor.left + panel.offsetWidth <= right
                    ? anchor.left : anchor.right - panel.offsetWidth;
                panel.style.left = `${Math.max(left, Math.min(preferredLeft, right - panel.offsetWidth))}px`;
                panel.style.top = `${openAbove ? anchor.top - panel.offsetHeight - 6 : anchor.bottom + 6}px`;
                return;
            }
            const below = innerHeight - anchor.bottom - 18;
            const above = anchor.top - 18;
            const openAbove = below < Math.min(panel.scrollHeight, 240) && above > below;
            panel.style.maxHeight = Math.max(0, openAbove ? above : below) + 'px';
            panel.style.left = Math.max(12, Math.min(anchor.right - panel.offsetWidth, innerWidth - panel.offsetWidth - 12)) + 'px';
            panel.style.top = (openAbove ? anchor.top - panel.offsetHeight - 6 : anchor.bottom + 6) + 'px';
        };
        const open = () => {
            if (panel.matches(':popover-open') || trigger.disabled) return;
            panel.showPopover({ source:trigger });
            if (onOpen) onOpen();
            else [...panel.querySelectorAll('input:not([type=hidden]):not(:disabled), select:not(:disabled), textarea:not(:disabled)')]
                .find(field => field.getClientRects().length)?.focus({ preventScroll:true });
            position();
        };
        const repositionOnScroll = event => {
            if (!(event.target instanceof Node) || !panel.contains(event.target)) position();
        };
        window.addServicePopoverDone(panel, trigger);
        trigger.setAttribute('aria-expanded', 'false');
        trigger.addEventListener('click', () => panel.matches(':popover-open') ? panel.hidePopover() : open());
        panel.addEventListener('toggle', () => {
            const active = panel.matches(':popover-open');
            trigger.setAttribute('aria-expanded', String(active));
            if (active) {
                window.addEventListener('scroll', repositionOnScroll, true);
                window.addEventListener('resize', position);
                if (boundary) {
                    window.visualViewport?.addEventListener('resize', position);
                    window.visualViewport?.addEventListener('scroll', position);
                }
            } else {
                window.removeEventListener('scroll', repositionOnScroll, true);
                window.removeEventListener('resize', position);
                if (boundary) {
                    window.visualViewport?.removeEventListener('resize', position);
                    window.visualViewport?.removeEventListener('scroll', position);
                }
            }
        });
        return open;
    };
    window.bindServiceFieldSelection = (select, label, boundary = null, { renderChoice, focusSearch = true, worklistSearch = false } = {}) => {
        if (!select || typeof HTMLElement.prototype.showPopover !== 'function') return;
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'service-field-chip';
        trigger.dataset.serviceTooltip = label;
        const caption = document.createElement('span');
        const chevron = document.createElement('i');
        chevron.className = 'bi bi-chevron-down service-field-chevron';
        chevron.setAttribute('aria-hidden','true');
        trigger.append(caption, chevron);
        select.dataset.filterSelectReady = '1';
        const panel = document.createElement('div');
        panel.className = 'service-field-popover';
        panel.id = select.id + '-options';
        panel.setAttribute('popover', 'auto');
        panel.setAttribute('aria-label', label);
        trigger.setAttribute('aria-controls', panel.id);
        const search = document.createElement('input');
        search.type = 'search';
        search.className = 'form-input';
        search.placeholder = select.dataset.searchPlaceholder || 'Search options';
        search.setAttribute('aria-label', select.dataset.searchLabel || 'Search ' + label.toLowerCase());
        if (select.dataset.searchPlaceholder) search.dataset.sharedSearchReady = '1';
        const searchBar = document.createElement('div');
        searchBar.className = worklistSearch ? 'worklist-search-control shared-record-search' : 'intake-asset-search';
        const clear = document.createElement('button');
        clear.type = 'button';
        clear.textContent = '×';
        clear.setAttribute('aria-label', 'Clear search');
        clear.hidden = true;
        const searchButton = document.createElement('button');
        searchButton.type = 'button';
        searchButton.textContent = 'Search';
        if (worklistSearch) {
            panel.setAttribute('data-worklist-consistent', '');
            const icon = document.createElement('i');
            icon.className = 'bi bi-search';
            icon.setAttribute('aria-hidden', 'true');
            searchBar.append(icon);
            clear.className = 'worklist-search-clear';
            searchButton.className = 'worklist-search-submit';
        }
        searchBar.append(search, clear, searchButton);
        const results = document.createElement('div');
        results.className = 'service-field-results';
        panel.append(searchBar, results);
        const render = () => {
            const query = search.value.trim().toLowerCase();
            clear.hidden = !search.value;
            const options = [...select.options].filter(option => option.value && !option.disabled
                && (option.dataset.searchTerms ?? [option.textContent, option.dataset.material].join(' ')).toLowerCase().includes(query));
            results.replaceChildren(...options.map(option => {
                const choice = document.createElement('button');
                choice.type = 'button';
                if (renderChoice) choice.append(renderChoice(option, query));
                else choice.textContent = option.textContent.trim();
                choice.setAttribute('aria-pressed', String(select.value === option.value));
                choice.onclick = () => {
                    select.value = option.value;
                    select.dispatchEvent(new Event('change', { bubbles:true }));
                    panel.hidePopover();
                    trigger.focus({ preventScroll:true });
                };
                return choice;
            }));
            if (!options.length) results.textContent = query ? 'No matching options.' : 'No available options.';
        };
        const sync = () => {
            caption.textContent = select.value ? select.selectedOptions[0]?.textContent.trim() : label;
            trigger.disabled = select.disabled;
        };
        select.after(trigger, panel);
        select.hidden = true;
        select.style.setProperty('display','none','important');
        select.addEventListener('change', sync);
        trigger.addEventListener('focus', sync);
        new MutationObserver(sync).observe(select, { attributes:true, attributeFilter:['disabled'], childList:true, subtree:true });
        search.addEventListener('input', render);
        searchButton.addEventListener('click', render);
        clear.addEventListener('click', () => {
            search.value = '';
            render();
            search.focus({ preventScroll:true });
        });
        search.addEventListener('keydown', event => {
            if (event.isComposing) return;
            if (event.key === 'Enter' || event.key === 'ArrowDown') {
                event.preventDefault();
                if (event.key === 'Enter') render();
                else results.querySelector('button')?.focus();
            }
        });
        if (worklistSearch) results.addEventListener('keydown', event => {
            const choice = event.target.closest('button');
            if (!choice || !['ArrowDown', 'ArrowUp'].includes(event.key)) return;
            event.preventDefault();
            if (event.key === 'ArrowDown') choice.nextElementSibling?.focus();
            else (choice.previousElementSibling ?? search).focus();
        });
        const open = window.bindServiceFieldPopover(trigger, panel, () => {
            search.value = '';
            render();
            if (focusSearch) search.focus({ preventScroll:true });
            else {
                panel.tabIndex = -1;
                panel.focus({ preventScroll:true });
            }
        }, boundary);
        select.addEventListener('invalid', () => requestAnimationFrame(() => {
            if (trigger.getClientRects().length) open();
        }));
        sync();
        return { trigger, panel, sync, open };
    };
</script>
