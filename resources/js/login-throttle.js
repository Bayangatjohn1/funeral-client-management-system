function initLoginThrottle() {
    const button = document.querySelector('[data-login-submit]');
    const email = document.getElementById('email');
    if (!button || !email) return;
    const seconds = Number(button.dataset.throttleSeconds || 0);
    if (seconds <= 0) return;
    const started = performance.now();
    const blockedEmail = button.dataset.throttleEmail.trim().toLowerCase();
    const label = button.querySelector('[data-login-submit-label]');
    const status = document.getElementById('login-throttle-status');
    const throttleError = document.querySelector('[data-login-throttle-error]');
    const remaining = () => Math.max(0, Math.ceil(seconds - (performance.now() - started) / 1000));
    function update() {
        const wait = remaining();
        const blocked = wait > 0 && email.value.trim().toLowerCase() === blockedEmail;
        button.disabled = blocked;
        label.textContent = blocked ? `Try again in ${wait}s` : 'Sign in';
        status.hidden = !blocked;
        if (throttleError) throttleError.hidden = !blocked;
        if (wait === 0) {
            status.hidden = false;
            status.textContent = 'You can try signing in again.';
        }
    }
    email.addEventListener('input', update);
    button.form.addEventListener('submit', event => {
        update();
        if (button.disabled) event.preventDefault();
    });
    const interval = setInterval(() => { update(); if (remaining() === 0) clearInterval(interval); }, 250);
    update();
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initLoginThrottle);
else initLoginThrottle();
