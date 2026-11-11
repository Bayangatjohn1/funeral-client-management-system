const serviceConfirmation = document.querySelector('[data-service-confirm]');
if (serviceConfirmation) {
    let pending;
    let pendingSubmitter;
    const confirmedForms = new WeakSet();
    document.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-service-status-confirm') || !form.closest('[data-service-hub]')) return;
        if (confirmedForms.has(form)) {
            confirmedForms.delete(form);
            return;
        }
        event.preventDefault();
        pending = form;
        pendingSubmitter = event.submitter || form.querySelector('button[type="submit"]');
        const button = pendingSubmitter;
        const action = button?.getAttribute('aria-label') || button?.textContent.trim() || 'Update status';
        const normalizedAction = action.toLowerCase();
        const isPositive = normalizedAction.includes('restore') || (normalizedAction.includes('mark available') && !normalizedAction.includes('unavailable'));
        const icon = serviceConfirmation.querySelector('.service-confirm-dialog__icon i');
        const blocked = Boolean(form.dataset.archiveBlock);
        const feedbackKind = blocked ? 'error' : (isPositive ? 'success' : 'warning');
        serviceConfirmation.dataset.feedbackKind = feedbackKind;
        serviceConfirmation.dataset.confirmTone = feedbackKind;
        if (icon) icon.className = `bi bi-${blocked ? 'exclamation-circle' : (isPositive ? 'check-circle' : 'exclamation-triangle')}`;
        const name = form.closest('tr')?.querySelector('.catalog-primary-cell')?.textContent.trim()
            || form.closest('article')?.querySelector('h3')?.textContent.trim()
            || 'this catalog item';
        serviceConfirmation.querySelector('h2').textContent = form.dataset.confirmTitle || `${action}?`;
        serviceConfirmation.querySelector('[data-confirm-submit]').textContent = form.dataset.confirmSubmitLabel || (normalizedAction.includes('unavailable') ? 'Mark unavailable' : normalizedAction.includes('available') ? 'Mark available' : normalizedAction.includes('restore') ? 'Restore' : 'Archive');
        serviceConfirmation.querySelector('[data-confirm-copy]').textContent = form.dataset.confirmCopy || `Confirm ${action.toLowerCase()} for ${name}. Existing historical records will be retained. Package Active/Archived status is unchanged.`;
        let usage = serviceConfirmation.querySelector('[data-confirm-affected]');
        if (!usage) { usage = document.createElement('div'); usage.dataset.confirmAffected = ''; serviceConfirmation.querySelector('footer').before(usage); }
        usage.replaceChildren();
        let packages = [];
        try { packages = JSON.parse(form.dataset.affectedPackages || '[]'); } catch (_) {}
        if (!action.toLowerCase().includes('restore') && !action.toLowerCase().includes('mark available') && packages.length > 0) {
            const heading = document.createElement('p'); heading.textContent = `Affected active packages (${packages.length})`;
            const list = document.createElement('ul');
            packages.forEach(name => { const item = document.createElement('li'); item.textContent = `${name} — ${form.dataset.dependencyReason || 'References this catalog item.'}`; list.append(item); });
            usage.append(heading, list);
        }
        const blockedMessage = form.dataset.archiveBlock || '';
        serviceConfirmation.querySelector('[data-confirm-submit]').style.display = blockedMessage ? 'none' : '';
        serviceConfirmation.querySelector('[data-confirm-submit]').disabled = Boolean(blockedMessage);
        serviceConfirmation.querySelector('[data-confirm-cancel]').textContent = blockedMessage ? 'Close' : 'Cancel';
        if (blockedMessage) {
            serviceConfirmation.querySelector('[data-confirm-copy]').textContent = blockedMessage;
            usage.replaceChildren();
            const hint = document.createElement('p');
            hint.textContent = 'Replace or remove this inclusion in package settings before archiving.';
            const list = document.createElement('ul');
            let links = [];
            try { links = JSON.parse(form.dataset.packageLinks || '[]'); } catch (_) {}
            links.forEach(pkg => {
                const item = document.createElement('li');
                const link = document.createElement('a');
                link.textContent = `Edit ${pkg.name}`;
                link.href = pkg.url;
                item.append(link); list.append(item);
            });
            usage.append(hint, list);
        }
        window.recordDialogMotion.open(serviceConfirmation);
    });
    serviceConfirmation.querySelector('[data-confirm-cancel]').addEventListener('click', () => window.recordDialogMotion.close(serviceConfirmation));
    serviceConfirmation.querySelector('[data-confirm-submit]').addEventListener('click', event => {
        if (!pending || pending.dataset.archiveBlock) return;
        const form = pending;
        const submitter = pendingSubmitter;
        const submit = event.currentTarget;
        const verb = submit.textContent.trim();
        submit.disabled = true;
        submit.textContent = verb === 'Archive' ? 'Archiving...'
            : verb === 'Restore' ? 'Restoring...'
                : verb === 'Mark unavailable' ? 'Marking unavailable...'
                    : verb === 'Mark available' ? 'Marking available...'
                        : 'Updating...';
        form.setAttribute('aria-busy', 'true');
        confirmedForms.add(form);
        serviceConfirmation.close('confirm');
        form.requestSubmit(submitter || undefined);
    });
    serviceConfirmation.addEventListener('close', () => {
        const submit = serviceConfirmation.querySelector('[data-confirm-submit]');
        pending = pendingSubmitter = null;
        submit.disabled = false;
    });
}
