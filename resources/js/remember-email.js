const checkbox = document.getElementById('remember_email');
const email = document.getElementById('email');
if (checkbox && email) {
    const key = 'funeral-system:remembered-email';
    try {
        const saved = localStorage.getItem(key);
        if (saved) {
            checkbox.checked = true;
            if (!email.value) email.value = saved;
            email.dispatchEvent(new Event('input', { bubbles: true }));
        }
    } catch { /* Sign in remains available without browser storage. */ }
    checkbox.addEventListener('change', () => {
        try { if (!checkbox.checked) localStorage.removeItem(key); } catch {}
    });
    checkbox.form.addEventListener('submit', (event) => {
        if (event.defaultPrevented) return;
        try {
            if (checkbox.checked) localStorage.setItem(key, email.value.trim());
            else localStorage.removeItem(key);
        } catch {}
    });
}
