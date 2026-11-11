<script>
(() => {
    const selector = '[data-deceased-records]';
    let pending;
    const load = async (url, push = true) => {
        const page = document.querySelector(selector);
        if (!page) return;
        pending?.abort();
        const request = new AbortController(); pending = request;
        page.querySelector('[data-deceased-tab-error]')?.remove();
        page.classList.add('is-updating');
        try {
            const response = await fetch(url, {signal:request.signal, credentials:'same-origin', headers:{'X-Requested-With':'XMLHttpRequest', Accept:'text/html'}});
            if (!response.ok) throw new Error('Unable to load deceased records.');
            const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
            const next = doc.querySelector(selector);
            if (!next?.querySelector('.table-system-list')) throw new Error('Deceased records content is missing.');
            if (request.signal.aborted) return;
            for (const part of ['.table-system-quick-tabs','.table-system-list','.table-system-pagination']) {
                const current = page.querySelector(part), replacement = next.querySelector(part);
                if (current && replacement) current.replaceWith(replacement);
            }
            if (push) history.pushState({}, '', url);
            document.dispatchEvent(new CustomEvent('panel-ui:reset'));
        } catch (error) {
            if (error.name !== 'AbortError' && !request.signal.aborted) {
                const message = document.createElement('p');
                message.dataset.deceasedTabError = ''; message.className = 'flash-error';
                message.setAttribute('role', 'alert'); message.textContent = 'Unable to load deceased records. Please try the tab again.';
                page.prepend(message);
            }
        } finally { if (pending === request) { pending = null; page.classList.remove('is-updating'); } }
    };
    document.addEventListener('click', event => {
        const link = event.target.closest(`${selector} .table-system-quick-tabs a[href]`);
        if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const url = new URL(link.href, location.href);
        if (url.origin !== location.origin) return;
        event.preventDefault();
        if (url.href !== location.href) void load(url.href);
    });
    window.addEventListener('popstate', () => { if (document.querySelector(selector)) void load(location.href, false); });
})();
</script>
