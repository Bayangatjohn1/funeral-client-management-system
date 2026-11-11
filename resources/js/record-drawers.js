// Quick case review keeps the original full-details link as its fallback.
export function installRecordDrawers() {
    const dialog = document.getElementById('caseSummaryDrawer');
    if (!dialog || dialog.dataset.drawerReady) return;
    dialog.dataset.drawerReady = '1';
    const body = dialog.querySelector('[data-case-summary-body]');
    const full = dialog.querySelector('[data-case-summary-full]');
    let trigger, selectedRow, request, loadingTimer, generation = 0;
    const clearSelection = () => {
        selectedRow?.classList.remove('is-viewing-record');
        selectedRow?.removeAttribute('aria-current');
        trigger?.setAttribute('aria-expanded', 'false');
        selectedRow = null;
    };
    const load = async () => {
        request?.abort();
        clearTimeout(loadingTimer);
        request = new AbortController();
        const version = ++generation;
        body.replaceChildren();
        body.scrollTop = 0;
        body.setAttribute('aria-busy', 'true');
        loadingTimer = setTimeout(() => {
            if (version !== generation || !dialog.open) return;
            body.innerHTML = '<div class="record-drawer-message" role="status"><p>Loading case summary…</p></div>';
        }, 200);
        try {
            const url = new URL(full.href, window.location.origin);
            url.searchParams.set('drawer_summary', '1');
            const response = await fetch(url, { signal:request.signal, credentials:'same-origin', headers:{'X-Requested-With':'XMLHttpRequest'} });
            if (!response.ok) throw new Error('Case summary unavailable');
            const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
            const summary = doc.querySelector('#caseRecordSummary');
            if (!summary) throw new Error('Case summary unavailable');
            if (version !== generation || !dialog.open) return;
            body.replaceChildren(document.importNode(summary, true));
        } catch (error) {
            if (error.name === 'AbortError' || version !== generation || !dialog.open) return;
            body.innerHTML = '<div class="record-drawer-message" role="alert"><i class="bi bi-exclamation-circle" aria-hidden="true"></i><p>Unable to load the summary. Try again or open the full details.</p><button type="button" class="record-drawer-secondary" data-case-summary-retry>Try again</button></div>';
        } finally {
            if (version === generation) {
                clearTimeout(loadingTimer);
                body.removeAttribute('aria-busy');
            }
        }
    };
    const close = () => window.recordDialogMotion.close(dialog);
    document.addEventListener('click', event => {
        const link = event.target.closest?.('[data-case-summary-open]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        event.stopPropagation();
        clearSelection();
        trigger = link;
        selectedRow = link.closest('tr');
        selectedRow?.classList.add('is-viewing-record');
        selectedRow?.setAttribute('aria-current', 'true');
        trigger.setAttribute('aria-expanded', 'true');
        full.href = link.href;
        document.dispatchEvent(new CustomEvent('panel-ui:reset'));
        load();
        window.recordDialogMotion.open(dialog);
        document.body.classList.add('record-drawer-open');
        dialog.querySelector('[data-case-summary-close]')?.focus({preventScroll:true});
    }, true);
    dialog.addEventListener('click', event => {
        if (event.target.closest('[data-case-summary-close]')) close();
        if (event.target.closest('[data-case-summary-retry]')) load();
        if (event.target === dialog) {
            const rect = dialog.getBoundingClientRect();
            if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) close();
        }
    });
    dialog.addEventListener('cancel', event => { event.preventDefault(); close(); });
    dialog.addEventListener('close', () => {
        clearSelection();
        generation++;request?.abort();
        clearTimeout(loadingTimer);
        body.removeAttribute('aria-busy');
        document.body.classList.remove('record-drawer-open');
        trigger?.isConnected && trigger.focus({preventScroll:true});
    });
}
