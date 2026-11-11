<script>
(() => {
    const panels = [
        { selector: '[data-activity-panel="cases"]', pager: '.dashboard-activity-pagination', param: 'recent_cases_page', label: 'recent cases' },
        { selector: '[data-balances-results]', pager: '.staff-balances-pagination', param: 'balances_page', label: 'outstanding balances', fragment: 'balances' },
    ];
    const currentUrl = () => new URL(location.href);
    const pageFor = (url, config) => url.searchParams.get(config.param) ?? '1';
    panels.forEach(config => {
        config.panel = document.querySelector(config.selector);
        config.page = pageFor(currentUrl(), config);
    });

    async function updatePanel(config, url, trigger = null) {
        config.pending?.abort();
        const controller = new AbortController();
        config.pending = controller;
        const { panel } = config;
        const tableRegion = config.fragment && panel.querySelector('.staff-balances-scroll');
        const releaseSkeleton = tableRegion ? window.PanelSkeleton?.begin(tableRegion) : null;
        if (!tableRegion) panel.setAttribute('aria-busy', 'true');
        try {
            const headers = { 'X-Requested-With': 'XMLHttpRequest' };
            if (config.fragment) headers['X-Staff-Dashboard-Fragment'] = config.fragment;
            const response = await fetch(url, { signal: controller.signal, headers });
            if (!response.ok) throw new Error('Dashboard request failed');
            const html = await response.text();
            if (controller.signal.aborted) return;

            const next = new DOMParser().parseFromString(html, 'text/html');
            const restoreFocus = trigger === document.activeElement;
            if (tableRegion) {
                const currentRows = tableRegion.querySelector('tbody');
                const currentPager = panel.querySelector(config.pager);
                const newRows = next.querySelector('.staff-balances-table tbody');
                const newPager = next.querySelector(config.pager);
                if (response.headers.get('X-Staff-Dashboard-Fragment') !== config.fragment ||
                    !currentRows || !currentPager || !newRows || !newPager) {
                    throw new Error('Balances fragment missing');
                }
                // Keep the scroller and its measured overlay mounted for the handoff.
                currentRows.replaceChildren(...newRows.childNodes);
                currentPager.replaceWith(newPager);
                tableRegion.scrollTop = 0;
                panel.querySelector('.activity-page-error')?.remove();
            } else {
                const content = next.querySelector(config.selector);
                if (!content) throw new Error('Dashboard panel missing');
                panel.innerHTML = content.innerHTML;
            }
            config.page = pageFor(new URL(response.url), config);
            if (trigger) {
                const nextUrl = currentUrl();
                nextUrl.searchParams.set(config.param, config.page);
                if (nextUrl.href !== location.href) history.pushState({}, '', nextUrl);
            }
            if (restoreFocus) {
                const links = [...panel.querySelectorAll(config.pager + ' a')];
                const target = links.find(link => link.textContent === trigger.textContent) ?? links[0] ?? panel;
                target.focus({ preventScroll: true });
            }
        } catch (error) {
            if (!controller.signal.aborted) {
                panel.querySelector('.activity-page-error')?.remove();
                const message = document.createElement('p');
                message.className = 'activity-page-error';
                message.setAttribute('role', 'alert');
                message.textContent = `Could not update ${config.label}. Please try again.`;
                panel.append(message);
            }
        } finally {
            releaseSkeleton?.();
            if (config.pending === controller) panel.removeAttribute('aria-busy');
        }
    }
    document.addEventListener('click', event => {
        if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const link = event.target.closest('a');
        const config = link && panels.find(item => item.panel?.contains(link) && link.closest(item.pager));
        if (!config) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        const url = currentUrl();
        url.searchParams.set(config.param, pageFor(new URL(link.href), config));
        updatePanel(config, url, link);
    }, true);
    window.addEventListener('popstate', () => {
        const url = currentUrl();
        panels.forEach(config => {
            if (!config.panel) return;
            config.pending?.abort();
            if (pageFor(url, config) !== config.page) updatePanel(config, url);
        });
    });
})();
</script>
