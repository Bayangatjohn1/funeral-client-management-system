<script>
    (() => {
        const pageSelector = '.master-records-page';
        let pendingRequest = null;

        const loadTab = async (url, selectedLink = null, pushState = true) => {
            const page = document.querySelector(pageSelector);
            const currentCard = page?.querySelector('.table-system-card');
            if (!page || !currentCard) return;

            pendingRequest?.abort();
            const request = new AbortController();
            pendingRequest = request;
            page.classList.add('is-updating');
            currentCard.setAttribute('aria-busy', 'true');
            currentCard.querySelector('[data-case-tab-error]')?.remove();
            const previousTabs = [...currentCard.querySelectorAll('.case-records-tabs a')].map(tab => [tab, tab.className, tab.getAttribute('aria-selected')]);

            if (selectedLink) {
                currentCard.querySelectorAll('.case-records-tabs a').forEach((tab) => {
                    const selected = tab === selectedLink;
                    tab.classList.toggle('table-quick-tab-active', selected);
                    tab.setAttribute('aria-selected', String(selected));
                });
            }

            try {
                const response = await fetch(url.href, {
                    signal: request.signal,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error(`Case records request failed: ${response.status}`);

                const documentFromResponse = new DOMParser().parseFromString(await response.text(), 'text/html');
                const nextCard = documentFromResponse.querySelector(`${pageSelector} .table-system-card`);
                if (!nextCard) throw new Error('Master Case Records was not found in the response.');
                if (request.signal.aborted) return;

                currentCard.replaceWith(nextCard);
                if (pushState) window.history.pushState({}, '', url.href);
                document.dispatchEvent(new CustomEvent('panel-ui:reset'));
                document.dispatchEvent(new CustomEvent('master-case-records:updated'));
            } catch (error) {
                if (error.name !== 'AbortError' && !request.signal.aborted) {
                    previousTabs.forEach(([tab, className, selected]) => {
                        tab.className = className;
                        if (selected === null) tab.removeAttribute('aria-selected'); else tab.setAttribute('aria-selected', selected);
                    });
                    const message = document.createElement('p');
                    message.dataset.caseTabError = '';
                    message.className = 'flash-error';
                    message.setAttribute('role', 'alert');
                    message.textContent = 'Unable to load case records. Please try the tab again.';
                    currentCard.prepend(message);
                }
            } finally {
                if (pendingRequest === request) {
                    pendingRequest = null;
                    page.classList.remove('is-updating');
                    page.querySelector('.table-system-card')?.removeAttribute('aria-busy');
                }
            }
        };

        window.loadMasterCaseSearch = url => loadTab(new URL(url,window.location.href));

        document.addEventListener('click', (event) => {
            const link = event.target instanceof Element
                ? event.target.closest(`${pageSelector} .case-records-tabs a[href]`)
                : null;
            if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

            const url = new URL(link.href, window.location.href);
            if (url.origin !== window.location.origin) return;
            event.preventDefault();
            event.stopPropagation();
            if (url.href !== window.location.href) loadTab(url, link);
        }, true);

        window.addEventListener('popstate', () => {
            loadTab(new URL(window.location.href), null, false);
        });
    })();

</script>
