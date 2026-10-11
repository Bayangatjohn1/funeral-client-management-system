function initIdleSession() {
    const config = document.getElementById('idle-session-config');
    const dialog = document.getElementById('idle-session-dialog');
    if (!config || !dialog) return;
    let state = JSON.parse(config.dataset.state);
    let offset = state.server_now * 1000 - Date.now();
    let checking = false;
    let leaving = false;
    let lastKeepAlive = 0;
    const button = dialog.querySelector('[data-idle-stay]');
    const error = dialog.querySelector('[data-idle-error]');
    const syncKey = `idle-session:${state.session_key}`;
    const remaining = () => Math.max(0, Math.ceil((state.expires_at * 1000 - Date.now() - offset) / 1000));
    const showError = message => { error.textContent = message; error.hidden = false; };
    function applyState(next, broadcast = true) {
        if (next.session_key !== state.session_key) {
            window.location.reload();
            return;
        }
        state = next;
        offset = state.server_now * 1000 - Date.now();
        if (remaining() > 60 && dialog.open) dialog.close();
        if (broadcast) {
            try { localStorage.setItem(syncKey, JSON.stringify({ ...state, sent_at: Date.now() })); } catch { /* Storage may be disabled. Server polling still synchronizes tabs. */ }
        }
    }
    async function requestState(extend = false) {
        if (checking || leaving) return;
        checking = true;
        if (extend) button.disabled = true;
        try {
            const response = await fetch(extend ? config.dataset.keepAliveUrl : config.dataset.statusUrl, {
                method: extend ? 'POST' : 'GET', credentials: 'same-origin', cache: 'no-store',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Idle-Session': state.session_key },
            });
            if ([401, 419].includes(response.status)) {
                leaving = true;
                try { localStorage.setItem(syncKey, JSON.stringify({ expired: true })); } catch { /* Optional tab notification. */ }
                window.location.replace(config.dataset.loginUrl);
                return;
            }
            if (response.status === 409) { window.location.reload(); return; }
            if (!response.ok) throw new Error('Session check failed');
            applyState(await response.json());
            error.hidden = true;
        } catch {
            showError('Unable to check your session. Check your connection and try again.');
        } finally { checking = false; button.disabled = false; }
    }
    dialog.addEventListener('cancel', event => event.preventDefault());
    button.addEventListener('click', () => requestState(true));
    // Only real interaction sends a keep-alive; timers and background polling do not.
    const activity = event => {
        if (!event.isTrusted || dialog.open || leaving || remaining() <= 0) return;
        if (Date.now() - lastKeepAlive < 30000) return;
        lastKeepAlive = Date.now();
        requestState(true);
    };
    ['pointerdown', 'keydown', 'wheel', 'touchstart'].forEach(type => document.addEventListener(type, activity, { passive: true }));
    window.addEventListener('storage', event => {
        if (event.key !== syncKey || !event.newValue) return;
        try {
            const next = JSON.parse(event.newValue);
            if (next.expired) { leaving = true; window.location.replace(config.dataset.loginUrl); return; }
            // Preserve elapsed transit time when another tab broadcasts a deadline.
            next.server_now += Math.max(0, (Date.now() - next.sent_at) / 1000);
            applyState(next, false);
        } catch { /* Ignore invalid browser storage. */ }
    });
    document.addEventListener('visibilitychange', () => { if (!document.hidden) requestState(); });
    window.addEventListener('pageshow', () => requestState());
    setInterval(() => {
        const seconds = remaining();
        if (seconds <= 60 && !dialog.open && !leaving) dialog.showModal();
        dialog.querySelector('[data-idle-countdown]').textContent = `${seconds} seconds`;
        if (seconds === 0) requestState();
    }, 1000);
    setInterval(() => requestState(), 30000);
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initIdleSession);
else initIdleSession();
