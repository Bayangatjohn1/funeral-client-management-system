<script>
(() => {
    let pending;
    async function updateActivity(url, push = true) {
        pending?.abort();
        const controller = new AbortController(); pending = controller;
        const panel = document.querySelector('[data-activity-panel="cases"]');
        panel.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(url, {signal:controller.signal, headers:{'X-Requested-With':'XMLHttpRequest'}});
            if (!response.ok) throw new Error('Activity request failed');
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const next = page.querySelector('[data-activity-panel="cases"]');
            if (!next) throw new Error('Activity panel missing');
            panel.innerHTML = next.innerHTML;
            if (push) history.pushState({activityPage:true}, '', response.url);
            window.dispatchEvent(new Event('staff-dashboard-reflow'));
        } catch (error) {
            if (error.name !== 'AbortError') {
                panel.querySelector('.activity-page-error')?.remove();
                const message = document.createElement('p'); message.className = 'activity-page-error';
                message.setAttribute('role', 'alert'); message.textContent = 'Could not update recent cases. Please try again.';
                panel.append(message);
            }
        } finally {
            if (pending === controller) panel.removeAttribute('aria-busy');
        }
    }
    document.addEventListener('click', event => {
        const link = event.target.closest('.dashboard-activity-pagination a');
        if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault(); event.stopImmediatePropagation();
        updateActivity(link.href);
    }, true);
    window.addEventListener('popstate', () => updateActivity(location.href, false));
})();
</script>
