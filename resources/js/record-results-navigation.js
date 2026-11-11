// Existing directory/audit GET controls refresh results without navigating the shell.
const requests = new WeakMap();
const rootSelector = '[data-record-results-page]';
async function load(root, url, push = true) {
    requests.get(root)?.abort();
    const request = new AbortController(); requests.set(root, request);
    root.querySelector('[data-record-results-error]')?.remove();
    const parts = [...root.querySelectorAll('[data-record-results]')];
    const releases = new Map();
    const syncVisibleResults = () => parts.forEach(part => {
        if (part.dataset.recordResults === 'footer') return;
        if (part.getClientRects().length && !releases.has(part)) releases.set(part,window.PanelSkeleton?.begin(part));
        else if (!part.getClientRects().length && releases.has(part)) { releases.get(part)?.(); releases.delete(part); }
    });
    const viewObserver = new ResizeObserver(syncVisibleResults);
    const visibilityObserver = new MutationObserver(changes => {
        if (changes.some(change => parts.includes(change.target))) syncVisibleResults();
    });
    visibilityObserver.observe(root,{subtree:true,attributes:true,attributeFilter:['style','hidden']});
    parts.forEach(part => viewObserver.observe(part));
    syncVisibleResults();
    const stopLoading = () => { viewObserver.disconnect(); visibilityObserver.disconnect(); releases.forEach(release => release?.()); releases.clear(); };
    try {
        const response = await fetch(url, {signal:request.signal, credentials:'same-origin', headers:{'X-Requested-With':'XMLHttpRequest', Accept:'text/html'}});
        if (!response.ok) throw new Error('Results request failed.');
        const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
        const next = [...doc.querySelectorAll(rootSelector)].find(node => node.dataset.recordResultsPage === root.dataset.recordResultsPage);
        if (!next || parts.some(part => !next.querySelector(`[data-record-results="${part.dataset.recordResults}"]`))) throw new Error('Results content is missing.');
        if (request.signal.aborted) return;
        stopLoading();
        if (root.dataset.recordResultsPage === 'audit') document.querySelectorAll('.audit-log-dialog:not([open])').forEach(dialog => dialog.remove());
        for (const part of parts) {
            const replacement = next.querySelector(`[data-record-results="${part.dataset.recordResults}"]`);
            if (part.dataset.recordResults === 'footer') {
                const hidden = replacement.classList.contains('is-pagination-hidden');
                part.classList.toggle('is-pagination-hidden', hidden);
                part.toggleAttribute('aria-hidden', hidden);
            }
            [...part.children].forEach(child => window.Alpine?.destroyTree?.(child));
            part.replaceChildren(...replacement.childNodes);
            [...part.children].forEach(child => window.Alpine?.initTree?.(child));
        }
        // User dialogs contain permission-checked forms and fresh record metadata.
        const dialogs = document.querySelector(`[data-record-results-dialogs="${root.dataset.recordResultsPage}"]`);
        const nextDialogs = doc.querySelector(`[data-record-results-dialogs="${root.dataset.recordResultsPage}"]`);
        if (dialogs && nextDialogs) {
            nextDialogs.querySelectorAll('script').forEach(script => script.remove());
            dialogs.replaceChildren(...nextDialogs.childNodes);
        }
        root.querySelectorAll('form[method="GET" i] [name]').forEach(control => {
            if (push && control === document.activeElement && control.matches('input[type="text"],input[type="search"]')) return;
            const source = [...next.querySelectorAll('[name]')].find(node => node.name === control.name && node.type === control.type && (!control.matches('input[type="checkbox"],input[type="radio"]') || node.value === control.value));
            if (source) { control.value = source.value; if (control.type === 'checkbox' || control.type === 'radio') control.checked = source.checked; }
            else if (control.matches('input[type="hidden"]') && (['assignment', 'admin_scope', 'per_page', 'highlight_branch', 'return_to'].includes(control.name) || (root.dataset.recordResultsPage === 'audit' && ['audit_log_id', 'action', 'entity_type'].includes(control.name)))) control.remove();
        });
        const currentForm = root.querySelector('[data-worklist-form]');
        const nextForm = next.querySelector('[data-worklist-form]');
        if (currentForm && nextForm) {
            nextForm.querySelectorAll('input[type="hidden"]').forEach(source => {
                if (![...currentForm.elements].some(control => control.name === source.name)) currentForm.append(source.cloneNode(true));
            });
        }
        root.querySelectorAll('[data-branch-status-filter]').forEach(button => {
            const selected = button.dataset.branchStatusFilter === (root.querySelector('[name="status"]')?.value || '');
            button.classList.toggle('is-selected', selected); button.setAttribute('aria-pressed', String(selected));
        });
        const currentClear = root.querySelector('.filter-clear-action');
        const nextClear = next.querySelector('.filter-clear-action');
        if (currentClear && !nextClear) currentClear.remove();
        else if (currentClear && nextClear) currentClear.href = nextClear.href;
        else if (nextClear) {
            const sibling = root.querySelector('.user-directory-view-toggle, .audit-system-actions a[target="_blank"]');
            if (sibling) sibling.before(nextClear.cloneNode(true));
        }
        root.querySelectorAll('form a[target="_blank"]').forEach(link => {
            const counterpart = [...next.querySelectorAll('form a[target="_blank"]')].find(node => node.textContent.trim() === link.textContent.trim());
            if (counterpart) link.href = counterpart.href;
        });
        const applied=root.querySelector('[data-worklist-applied]'), incomingApplied=next.querySelector('[data-worklist-applied]');
        if(applied&&incomingApplied)applied.replaceChildren(...incomingApplied.childNodes);
        const count=root.querySelector('[data-worklist-count]'), incomingCount=next.querySelector('[data-worklist-count]');
        if(count&&incomingCount){count.textContent=incomingCount.textContent;count.hidden=incomingCount.hidden;}
        if (push) history.pushState({}, '', url);
        document.dispatchEvent(new CustomEvent('record-results:updated', {detail:{root,url}}));
        document.dispatchEvent(new CustomEvent('panel-ui:reset'));
    } catch (error) {
        if (error.name !== 'AbortError' && !request.signal.aborted) {
            const message = document.createElement('p'); message.className = 'flash-error';
            message.dataset.recordResultsError = ''; message.setAttribute('role','alert');
            message.textContent = 'Unable to update records. Please try again.'; root.prepend(message);
        }
    } finally { stopLoading(); if (requests.get(root) === request) requests.delete(root); }
}
document.addEventListener('submit', event => {
    const form = event.target;
    const root = form.closest?.(rootSelector);
    if (!root || !form.matches('form') || form.method.toLowerCase() !== 'get' || event.defaultPrevented) return;
    const url = new URL(form.action, location.href); url.search = new URLSearchParams(new FormData(form)).toString();
    if (url.origin !== location.origin) return;
    event.preventDefault(); void load(root, url.href);
}, true);
document.addEventListener('click', event => {
    const link = event.target.closest?.(`${rootSelector} .table-system-pagination a[href], ${rootSelector} .filter-clear-action[href], ${rootSelector} .table-toolbar-reset[href], ${rootSelector} .audit-filter-reset[href], ${rootSelector} .worklist-chip-remove[href], ${rootSelector} .worklist-chip[href]`);
    if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || link.target === '_blank') return;
    const root = link.closest(rootSelector), url = new URL(link.href,location.href);
    const form = root.querySelector('form[method="GET" i]');
    if (!form || url.origin !== location.origin || url.pathname !== new URL(form.action).pathname) return;
    event.preventDefault(); void load(root,url.href);
});
window.addEventListener('popstate', () => document.querySelectorAll(rootSelector).forEach(root => void load(root,location.href,false)));
window.loadRecordResults=(root,url,push=true)=>load(root,String(url),push);
