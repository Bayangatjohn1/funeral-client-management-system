<script>
(() => {
    window.reminderPartialNavigationReady = true;
    let pending;
    let searchTimer;
    const media = matchMedia('(max-width:767px)');
    const syncView = () => {
        const calendar = document.querySelector('.schedule-calendar');
        if (!calendar) return;
        const view = calendar.dataset.upcomingView === 'auto' ? (media.matches ? 'cases' : 'calendar') : calendar.dataset.upcomingView;
        calendar.querySelectorAll('[data-upcoming-mode]').forEach(link => {
            const active = link.dataset.upcomingMode === view;
            link.classList.toggle('is-active', active);
            if (active) link.setAttribute('aria-current', 'page'); else link.removeAttribute('aria-current');
        });
    };
    async function navigate(url, push = true) {
        pending?.abort();
        const controller = new AbortController(); pending = controller;
        const current = document.querySelector('.rp-wrap');
        current.querySelectorAll('.reminder-update-status').forEach(status => status.remove());
        current.setAttribute('aria-busy', 'true');
        const status = document.createElement('p');
        status.className = 'reminder-update-status'; status.setAttribute('role', 'status');
        try {
            const response = await fetch(url, {signal:controller.signal, headers:{'X-Requested-With':'XMLHttpRequest'}});
            if (!response.ok) throw new Error('Unable to load schedules.');
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const next = page.querySelector('.rp-wrap');
            if (!next) { location.assign(response.url); return; }
            next.querySelectorAll('script').forEach(script => script.remove());
            document.querySelectorAll('body > dialog.calendar-details').forEach(dialog => dialog.remove());
            document.documentElement.classList.remove('schedule-modal-open');
            const search = document.activeElement?.id === 'schedule-search' ? document.activeElement : null;
            const caret = search?.selectionStart;
            current.replaceWith(next);
            if (search) { const field = next.querySelector('#schedule-search'); field?.focus({preventScroll:true}); field?.setSelectionRange(caret, caret); }
            const filters = page.querySelector('#rpFilterForm');
            if (filters) document.querySelector('#rpFilterForm')?.replaceWith(filters);
            if (push) history.pushState({}, '', response.url);
            syncView();
        } catch (error) {
            if (error.name === 'AbortError') return;
            status.textContent = 'Could not update. Please try again.'; current.prepend(status);
        } finally {
            if (pending === controller) {
                document.querySelector('.rp-wrap')?.removeAttribute('aria-busy');
            } else status.remove();
        }
    }
    document.addEventListener('click', event => {
        const clear = event.target.closest('.schedule-search-x');
        if (clear) { clearTimeout(searchTimer); const input = clear.form.querySelector('#schedule-search'); input.value = ''; input.focus(); clear.hidden = true; clear.form.requestSubmit(); return; }
        const trigger = event.target.closest('[data-calendar-event]');
        if (trigger) {
            const match = trigger.dataset.calendarEvent.match(/^(?:event|wake|case)-(\d+)(?:-(.+))?$/);
            const dialog = match && document.getElementById('calendar-case-'+match[1]);
            if (dialog) {
                dialog.querySelectorAll('[data-case-event]').forEach(row => row.classList.toggle('is-picked', row.dataset.caseEvent === match[2]));
                document.body.append(dialog); dialog.showModal();
                document.documentElement.classList.add('schedule-modal-open');
                dialog.addEventListener('close', () => document.documentElement.classList.remove('schedule-modal-open'), {once:true});
            }
        }
        const close = event.target.closest('[data-calendar-close]');
        if (close) close.closest('dialog').close();
        const link = event.target.closest('.rp-tab,.schedule-section-tab,.calendar-controls a,.calendar-mobile-days a,.upcoming-view-switch a,.schedule-search a,.rp-filter-reset');
        if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || !link.hasAttribute('href')) return;
        const url = new URL(link.href);
        if (url.origin !== location.origin || url.pathname !== location.pathname) return;
        event.preventDefault(); event.stopImmediatePropagation(); navigate(url.href);
    }, true);
    document.addEventListener('submit', event => {
        if (!event.target.matches('.schedule-search,.calendar-date-jump,#rpFilterForm')) return;
        event.preventDefault(); event.stopImmediatePropagation();
        clearTimeout(searchTimer);
        const url = new URL(event.target.action); url.search = new URLSearchParams(new FormData(event.target)).toString();
        navigate(url.href);
    }, true);
    document.addEventListener('input', event => {
        if (event.target.id !== 'schedule-search') return;
        pending?.abort(); clearTimeout(searchTimer);
        event.target.form.querySelector('.schedule-search-x').hidden = event.target.value === '';
        searchTimer = setTimeout(() => event.target.form?.requestSubmit(), 300);
    });
    document.addEventListener('change', event => {
        if (!event.target.matches('#rpFilterForm select,#rpFilterForm input[type=date]')) return;
        event.stopImmediatePropagation(); event.target.form.requestSubmit();
    }, true);
    window.addEventListener('popstate', () => navigate(location.href, false));
    media.addEventListener('change', syncView);
    syncView();
})();
</script>
<style>
.reminder-update-status{margin:0 0 8px;font-size:13px;color:var(--staff-muted,#1b3e2e)}

</style>
