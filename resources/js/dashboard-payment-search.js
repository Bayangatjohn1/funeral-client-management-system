export function installDashboardPaymentSearch() {
    const form = document.querySelector('[data-payment-case-search]');
    if (!form) return;

    const input = form.querySelector('[role="combobox"]');
    const list = form.querySelector('[role="listbox"]');
    const popup = list.parentElement;
    const status = popup.querySelector('[role="status"]');
    let timer;
    let request;
    let activeIndex = -1;

    function dismiss() {
        clearTimeout(timer);
        request?.abort();
        popup.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        list.replaceChildren();
        status.textContent = '';
        activeIndex = -1;
    }

    async function search(term) {
        const controller = new AbortController();
        request = controller;
        const url = new URL(form.dataset.paymentCaseSearch, window.location.href);
        url.searchParams.set('q', term);
        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error('Case search failed');
            const matches = await response.json();
            if (controller.signal.aborted) return;

            matches.forEach((match, index) => {
                const option = document.createElement('a');
                option.id = `${list.id}-${index}`;
                option.href = match.url;
                option.tabIndex = -1;
                option.setAttribute('role', 'option');
                option.setAttribute('aria-selected', 'false');
                option.textContent = [match.caseCode, match.client, match.deceased].filter(Boolean).join(' · ');
                list.append(option);
            });
            status.textContent = matches.length ? '' : 'No matching cases with a balance.';
            input.setAttribute('aria-expanded', String(matches.length > 0));
        } catch {
            if (controller.signal.aborted) return;
            status.textContent = 'Could not load cases. Try searching again.';
        }
        popup.hidden = false;
    }

    function queueSearch(delay) {
        dismiss();
        const term = input.value.trim();
        if ([...term].length < 2) {
            if (delay === 0) {
                status.textContent = 'Enter at least two characters.';
                popup.hidden = false;
            }
            return;
        }
        timer = setTimeout(() => search(term), delay);
    }

    input.addEventListener('input', event => {
        if (!event.isComposing) queueSearch(300);
    });
    input.addEventListener('compositionend', () => queueSearch(300));
    form.addEventListener('submit', event => {
        event.preventDefault();
        input.focus();
        queueSearch(0);
    });
    input.addEventListener('keydown', event => {
        if (event.isComposing) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            dismiss();
            return;
        }
        const options = list.children;
        if (event.key === 'Enter' && activeIndex >= 0) {
            event.preventDefault();
            options[activeIndex].click();
            return;
        }
        if (!options.length || !['ArrowDown', 'ArrowUp'].includes(event.key)) return;

        event.preventDefault();
        options[activeIndex]?.setAttribute('aria-selected', 'false');
        if (event.key === 'ArrowDown') {
            activeIndex = (activeIndex + 1) % options.length;
        } else {
            activeIndex = activeIndex <= 0 ? options.length - 1 : activeIndex - 1;
        }
        const active = options[activeIndex];
        active.setAttribute('aria-selected', 'true');
        input.setAttribute('aria-activedescendant', active.id);
        active.scrollIntoView({ block: 'nearest' });
    });
    list.addEventListener('mousedown', event => {
        if (event.target.closest('[role="option"]')) event.preventDefault();
    });
    form.addEventListener('focusout', event => {
        if (!form.contains(event.relatedTarget)) dismiss();
    });
    document.addEventListener('pointerdown', event => {
        if (!form.contains(event.target)) dismiss();
    });
}
