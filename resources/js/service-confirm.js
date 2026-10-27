const serviceConfirmation = document.querySelector('[data-service-confirm]');
if (serviceConfirmation) {
    let pending;
    document.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.closest('[data-service-hub]') || !form.querySelector('input[name="_method"]')) return;
        event.preventDefault();
        pending = form;
        const action = form.querySelector('button')?.textContent.trim() || 'Update status';
        const name = form.closest('tr')?.querySelector('.catalog-primary-cell')?.textContent.trim() || 'this catalog item';
        serviceConfirmation.querySelector('h2').textContent = `${action}?`;
        serviceConfirmation.querySelector('[data-confirm-copy]').textContent = `Confirm ${action.toLowerCase()} for ${name}. Existing historical records will be retained.`;
        serviceConfirmation.showModal();
    });
    serviceConfirmation.querySelector('[data-confirm-cancel]').addEventListener('click', () => serviceConfirmation.close());
    serviceConfirmation.querySelector('[data-confirm-submit]').addEventListener('click', event => {
        if (!pending) return;
        event.currentTarget.disabled = true;
        HTMLFormElement.prototype.submit.call(pending);
    });
    serviceConfirmation.addEventListener('close', () => { pending = null; serviceConfirmation.querySelector('[data-confirm-submit]').disabled = false; });
}
