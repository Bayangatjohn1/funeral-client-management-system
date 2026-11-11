import '../css/payment-proof.css';

const states = new WeakMap();
const csrf = () => document.querySelector('meta[name=csrf-token]')?.content;

async function api(url, method = 'GET', body) {
    const response = await fetch(url, {
        method, body: body ? JSON.stringify(body) : undefined,
        headers: {Accept:'application/json', 'Content-Type':'application/json', 'X-CSRF-TOKEN':csrf(), 'X-Requested-With':'XMLHttpRequest'},
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Unable to connect. Try again.');
    return data;
}

function setup(widget) {
    if (states.has(widget)) return states.get(widget);
    const input = widget.querySelector('[data-proof-session-id]');
    const formId = widget.querySelector('[data-proof-form-id]');
    const error = widget.querySelector('[data-proof-error]');
    const status = widget.querySelector('[data-proof-state]');
    const qr = widget.querySelector('[data-proof-qr]');
    const received = widget.querySelector('[data-proof-received]');
    const progress = widget.querySelector('[data-proof-progress]');
    const file = widget.querySelector('[data-proof-file]');
    const target = widget.dataset.proofTarget ? document.getElementById(widget.dataset.proofTarget) : null;
    const limit = Number(widget.dataset.proofLimit || 5);
    const catalogPhoto = widget.dataset.context === 'catalog';
    const review = target ? null : widget.closest('form')?.querySelector('[data-proof-review]');
    const reviewRow = review?.closest('[data-proof-review-wrap]');
    let sessionId = input.value, previousSession, expires, pollTimer, clockTimer, busy = false, ready = false, checking = false, transferring = false;
    const url = (suffix = '') => `${widget.dataset.base}/${sessionId}${suffix}`;
    const changed = () => input.dispatchEvent(new Event('input', {bubbles:true}));
    const fail = message => { error.hidden = false; error.textContent = message; };
    const stop = () => { clearTimeout(pollTimer); clearInterval(clockTimer); };
    const setBusy = value => {
        busy = value;
        widget.querySelectorAll('button').forEach(button => button.disabled = value);
    };
    const validate = required => {
        if (!required) return true;
        if (busy || checking) { fail('Wait for the payment proof upload to finish.'); return false; }
        if (!ready || !input.value) { fail('Attach a receipt or transaction confirmation before continuing.'); return false; }
        return true;
    };
    const cancel = async () => {
        stop();
        if (sessionId) {
            const current = await api(url());
            if (current.state !== 'saved') await api(url(), 'DELETE');
        }
        input.value = ''; sessionId = ''; ready = false;
        if (target) { target.value = ''; target.dispatchEvent(new Event('change', {bubbles:true})); }
        if (review) { review.hidden = true; review.removeAttribute('href'); }
        if (reviewRow) reviewRow.hidden = true;
        qr.hidden = true; received.hidden = true; status.textContent = ''; file.value = ''; changed();
        widget.querySelector('[data-proof-file-label]').textContent = 'Choose File';
        if (previousSession) {
            sessionId = previousSession; previousSession = null; input.value = sessionId;
            await refresh();
        }
    };
    const refresh = async () => {
        if (!sessionId) return;
        const data = await api(url());
        if (data.state === 'saved') {
            stop(); ready = true; input.value = sessionId; qr.hidden = true; received.hidden = false;
            widget.querySelector('[data-proof-name]').textContent = data.name;
            widget.querySelector('[data-proof-preview]').href = data.payment_url;
            widget.querySelector('[data-proof-preview]').textContent = 'View Saved Payment';
            delete widget.querySelector('[data-proof-preview]').dataset.proofMime;
            widget.querySelector('[data-proof-image]').hidden = true;
            status.textContent = 'This payment was already saved. Open it to confirm the result before recording another payment.';
            // Preserve the identity for the server idempotency check on a retry.
        } else if (data.state === 'received') {
            if (target) {
                transferring = true;
                try {
                    const receivedSession = sessionId;
                    const response = await fetch(data.preview);
                    if (!response.ok) throw new Error('Could not load the attachment. Try uploading it again.');
                    const blob = await response.blob();
                    if (receivedSession !== sessionId) return;
                    const transfer = new DataTransfer();
                    transfer.items.add(new File([blob], data.name, {type:data.mime}));
                    target.files = transfer.files;
                    target.dispatchEvent(new Event('change', {bubbles:true}));
                } finally {
                    transferring = false;
                }
            }
            if (previousSession) {
                const replaced = previousSession;
                previousSession = null;
                api(`${widget.dataset.base}/${replaced}`, 'DELETE').catch(() => {});
            }
            stop(); ready = true; input.value = sessionId; qr.hidden = true; received.hidden = false;
            widget.querySelector('[data-proof-name]').textContent = data.name;
            widget.querySelector('[data-proof-file-label]').textContent = 'Replace File';
            widget.querySelector('[data-proof-preview]').href = data.preview;
            widget.querySelector('[data-proof-preview]').dataset.proofMime = data.mime;
            widget.querySelector('[data-proof-preview]').textContent = catalogPhoto ? 'View Photo' : 'View Proof';
            if (catalogPhoto) widget.querySelector('[data-proof-preview]').dataset.proofTitle = 'Catalog Photo';
            if (review) { review.href = data.preview; review.dataset.proofMime = data.mime; review.hidden = false; }
            if (reviewRow) reviewRow.hidden = false;
            const image = widget.querySelector('[data-proof-image]');
            image.hidden = !data.mime.startsWith('image/');
            if (!image.hidden) image.src = data.preview;
            status.textContent = catalogPhoto ? 'Photo ready. Save the form to apply it.' : (target ? 'File received. It will be attached when you save the case.' : 'Proof received. Check the reference, amount and date before saving.');
            error.hidden = true; changed();
        } else if (data.state !== 'waiting') {
            stop(); ready = false; input.value = ''; qr.hidden = true; received.hidden = true;
            if (previousSession) {
                sessionId = previousSession; previousSession = null; input.value = sessionId;
                await refresh();
            }
            fail('The upload expired or was cancelled. Choose a file or generate a new QR.');
        }
    };
    const poll = async () => {
        if (!widget.isConnected) { stop(); return; }
        if (!widget.getClientRects().length) {
            try { await cancel(); } catch { stop(); }
            return;
        }
        try { if (!document.hidden) await refresh(); }
        catch (e) { fail(e.message); }
        if (!ready && sessionId && qr.hidden === false) pollTimer = setTimeout(poll, 3000);
    };
    const create = async phone => {
        if (phone && ['localhost', '127.0.0.1', '[::1]'].includes(new URL(widget.dataset.base).hostname)) {
            throw new Error('Open the system using its LAN or hosted address before using phone upload. This address is local to this computer.');
        }
        stop();
        if (sessionId && ready) previousSession = sessionId;
        else if (sessionId) await api(url(), 'DELETE');
        const data = await api(widget.dataset.base, 'POST', {form_id:formId.value, context:widget.dataset.context});
        sessionId = data.id; expires = Date.parse(data.expires_at);
        ready = false; error.hidden = true;
        if (phone) {
            widget.querySelector('[data-proof-qr-image]').src = data.qr;
            qr.hidden = false;
            const countdown = () => {
                const seconds = Math.max(0, Math.ceil((expires - Date.now()) / 1000));
                widget.querySelector('[data-proof-countdown]').textContent = `Expires in ${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
                if (!seconds) { stop(); refresh().catch(e => fail(e.message)); }
            };
            countdown(); clockTimer = setInterval(countdown, 1000);
            pollTimer = setTimeout(poll, 1000);
            status.textContent = 'Waiting for your phone upload…';
        }
    };
    const run = async task => {
        if (busy) return;
        setBusy(true); error.hidden = true;
        try { await task(); } catch (e) {
            const message = e.message;
            if (previousSession) {
                sessionId = previousSession; previousSession = null; input.value = sessionId;
                await refresh().catch(() => {});
            }
            fail(message);
        }
        finally { setBusy(false); }
    };
    widget.querySelector('[data-proof-phone]').onclick = () => run(() => create(true));
    widget.querySelector('[data-proof-choose]').onclick = () => file.click();
    widget.querySelector('[data-proof-cancel]').onclick = () => run(cancel);
    widget.querySelector('[data-proof-remove]').onclick = () => run(cancel);
    const dropZone = widget.querySelector('[data-proof-drop]');
    dropZone?.append(received);
    dropZone?.addEventListener('dragover', event => {
        if (!event.dataTransfer.types.includes('Files')) return;
        event.preventDefault();
        event.dataTransfer.dropEffect = busy || transferring ? 'none' : 'copy';
        dropZone.classList.toggle('is-drop-target', !busy && !transferring);
    });
    dropZone?.addEventListener('dragleave', event => {
        if (!dropZone.contains(event.relatedTarget)) dropZone.classList.remove('is-drop-target');
    });
    dropZone?.addEventListener('drop', event => {
        event.preventDefault();
        dropZone.classList.remove('is-drop-target');
        if (busy || transferring) return;
        const files = event.dataTransfer.files;
        if (files.length !== 1) {
            fail('Attach one photo at a time.');
            return;
        }
        file.files = files;
        file.dispatchEvent(new Event('change'));
    });
    file.onchange = () => run(async () => {
        const selected = file.files[0];
        if (!selected) return;
        if (!file.accept.split(',').includes(selected.type)) throw new Error('Choose a supported file type for this attachment.');
        if (selected.size > limit * 1024 * 1024) throw new Error(`The file must be ${limit} MB or smaller.`);
        await create(false);
        const body = new FormData(); body.append('proof', selected);
        progress.hidden = false; progress.value = 0;
        status.textContent = catalogPhoto ? 'Uploading photo…' : 'Uploading proof…';
        try {
            await new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', url('/file')); xhr.timeout = 120000;
                xhr.setRequestHeader('X-CSRF-TOKEN', csrf()); xhr.setRequestHeader('Accept', 'application/json');
                xhr.upload.onprogress = event => { if (event.lengthComputable) progress.value = event.loaded / event.total * 100; };
                xhr.onload = () => {
                    let data = {}; try { data = JSON.parse(xhr.responseText); } catch { /* The server may return an HTML error. */ }
                    if (xhr.status >= 200 && xhr.status < 300) resolve();
                    else reject(new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Upload failed. Select the file again to retry.'));
                };
                xhr.onerror = xhr.ontimeout = () => reject(new Error('Connection interrupted. Select the file again to retry.'));
                xhr.send(body);
            });
            await refresh();
        } finally { progress.hidden = true; }
    });
    const state = {validate, isTransferring: () => busy || transferring || (catalogPhoto && !!sessionId && !ready && !qr.hidden), restore: async () => {
        if (!input.value || (ready && input.value === sessionId)) return;
        sessionId = input.value; checking = true;
        try { await refresh(); } catch { input.value = ''; ready = false; fail('The saved attachment is unavailable. Attach the proof again.'); }
        finally { checking = false; }
    }};
    states.set(widget, state);
    if (error.textContent.trim()) error.hidden = false;
    state.restore();
    // Intake autosave can restore hidden values after this widget mounts.
    widget.formRestoreTimer = setInterval(() => {
        if (!widget.isConnected) {
            clearInterval(widget.formRestoreTimer); stop();
            if (sessionId && !ready) api(url(), 'DELETE').catch(() => {});
            return;
        }
        if (!busy && !previousSession && input.value && input.value !== sessionId) state.restore();
    }, 1000);
    return state;
}

window.validatePaymentProof = (form, required = true) => {
    const widget = form.querySelector('[data-payment-proof][data-proof-target=""]');
    if (!widget) return !required;
    const valid = setup(widget).validate(required);
    if (!valid) {
        widget.scrollIntoView({block:'nearest'});
        widget.querySelector('[data-proof-choose]').focus({preventScroll:true});
    }
    return valid;
};

document.addEventListener('submit', event => {
    if (event.target.querySelector('[data-catalog-photo]')) {
        const uploading = [...event.target.querySelectorAll('[data-payment-proof]')].find(widget => setup(widget).isTransferring());
        if (uploading) {
            event.preventDefault(); event.stopImmediatePropagation();
            uploading.querySelector('[data-proof-state]').textContent = 'Wait for the photo upload to finish before saving.';
            uploading.scrollIntoView({block:'nearest'});
        }
    }
}, true);

document.addEventListener('submit', event => {
    if (event.target.matches('#intakeWizardForm')) {
        const uploading = [...event.target.querySelectorAll('[data-proof-target]:not([data-proof-target=""])')]
            .find(widget => widget.getClientRects().length && setup(widget).isTransferring());
        if (uploading) {
            event.preventDefault();
            uploading.querySelector('[data-proof-state]').textContent = 'Wait for the file to finish loading before saving.';
            uploading.scrollIntoView({block:'nearest'});
        }
    }
    if (event.target.matches('[data-existing-payment-proof]') && !window.validatePaymentProof(event.target)) {
        event.preventDefault();
    }
});

document.addEventListener('click', event => {
    const widget = event.target.closest('[data-payment-proof]');
    if (widget) setup(widget);
}, true);
function mount() { document.querySelectorAll('[data-payment-proof]').forEach(setup); }
document.addEventListener('DOMContentLoaded', mount);
new MutationObserver(mount).observe(document.documentElement, {childList:true, subtree:true});
mount();
