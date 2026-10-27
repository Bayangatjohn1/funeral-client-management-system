const dialog = document.getElementById('password-reset-dialog');
if (dialog) {
    const form = dialog.querySelector('form');
    const password = form.querySelector('[name="password"]');
    const confirmation = form.querySelector('[name="password_confirmation"]');
    const status = form.querySelector('[data-reset-status]');
    const show = form.querySelector('[data-reset-show]');
    const submit = form.querySelector('[data-reset-submit]');
    const review = form.querySelector('[data-reset-review]');
    const back = form.querySelector('[data-reset-back]');
    const authorize = form.querySelector('[data-reset-authorize]');
    const adminPassword = form.querySelector('[name="current_password"]');
    const feedback = form.querySelector('[data-reset-feedback]');
    const details = form.querySelectorAll('[data-reset-details]');
    const setStep = (confirming) => {
        details.forEach(field => { field.hidden = confirming; });
        authorize.hidden = !confirming;
        adminPassword.disabled = !confirming;
        review.hidden = confirming;
        submit.hidden = back.hidden = !confirming;
        adminPassword.value = '';
        if (confirming) adminPassword.focus();
    };
    review.addEventListener('click', () => {
        if (form.reportValidity()) setStep(true);
    });
    back.addEventListener('click', () => { setStep(false); form.querySelector('[name="reason"]').focus(); });
    let issuedPassword = '';
    const random = (size) => {
        const bytes = new Uint32Array(1);
        const ceiling = Math.floor(0x100000000 / size) * size;
        do { crypto.getRandomValues(bytes); } while (bytes[0] >= ceiling);
        return bytes[0] % size;
    };
    const generate = () => {
        const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
        let value;
        do { value = Array.from({ length: 20 }, () => alphabet[random(alphabet.length)]).join(''); }
        while (!/[A-Za-z]/.test(value) || !/[0-9]/.test(value));
        password.value = confirmation.value = value;
        issuedPassword = value;
        status.textContent = 'Generated · Share privately';
    };
    document.querySelector('[data-open-password-reset]').addEventListener('click', () => {
        feedback.hidden = true; feedback.textContent = ''; setStep(false); generate(); dialog.showModal();
    });
    dialog.querySelectorAll('[data-close-password-reset]').forEach(button => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('close', () => {
        form.reset(); password.value = confirmation.value = issuedPassword = '';
        password.type = 'password'; show.setAttribute('aria-label', 'Show temporary password');
        setStep(false);
        status.textContent = ''; submit.disabled = false; submit.textContent = 'Confirm reset';
    });
    form.querySelector('[data-reset-generate]').addEventListener('click', generate);
    show.addEventListener('click', () => {
        password.type = password.type === 'password' ? 'text' : 'password';
        show.setAttribute('aria-label', password.type === 'password' ? 'Show temporary password' : 'Hide temporary password');
    });
    form.querySelector('[data-reset-copy]').addEventListener('click', async () => {
        try { await navigator.clipboard.writeText(password.value); status.textContent = 'Copied. Share privately with the verified user.'; }
        catch { status.textContent = 'Copy unavailable. Show the password and copy it manually.'; }
    });
    // Reopening after validation uses only the in-memory value, never persisted credentials.
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (authorize.hidden) {
            event.preventDefault();
            if (form.reportValidity()) setStep(true);
            return;
        }
        submit.disabled = true; submit.textContent = 'Resetting…';
        password.value = confirmation.value = issuedPassword;
        feedback.hidden = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST', body: new FormData(form), credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (response.redirected || !response.headers.get('content-type')?.includes('application/json')) {
                throw new Error('Your session may have expired. Sign in again before retrying.');
            }
            const result = await response.json();
            if (!response.ok) {
                const errors = result.errors || {};
                const messages = Object.values(errors).flat();
                if (errors.reason || errors.password) setStep(false);
                else adminPassword.value = '';
                feedback.textContent = `Password reset unsuccessful. ${messages.join(' ') || result.message || 'Please try again.'}`;
                feedback.hidden = false;
                if (!authorize.hidden) adminPassword.focus();
                return;
            }
            // Reload only after success so the existing global right-corner flash toast is reused.
            dialog.close();
            window.location.reload();
        } catch (error) {
            adminPassword.value = '';
            feedback.textContent = `Unable to confirm the reset result. ${error.message || 'Check your connection before retrying.'}`;
            feedback.hidden = false;
        } finally {
            submit.disabled = false; submit.textContent = 'Confirm reset';
        }
    });
    if (dialog.dataset.reopen === '1') { generate(); dialog.showModal(); }
    window.addEventListener('pageshow', () => { submit.disabled = false; submit.textContent = 'Confirm reset'; });
}
