// Shared behavior for the existing Laravel service forms; server rules remain authoritative.
const serviceFrame = document.body.classList.contains('service-catalog-form-frame')
    || document.querySelector('form.cc-form,form.aoc-form,form.fbc-form,form#packageForm,form[data-quick-catalog-photo]');
if (serviceFrame) {
    const form = document.querySelector('form.cc-form,form.aoc-form,form.fbc-form,form#packageForm,form[data-quick-catalog-photo]');
    if (form) {
        let saving = false;
        // Register the initial state before the user can edit. Package forms are
        // full-page wizard forms and were not included in form-discard.js's
        // static selector, so without this their unsaved state could be missed.
        window.registerDiscardForm?.(form);
        if (form.id !== 'packageForm' || typeof window.requestServiceFormClose !== 'function') {
            window.requestServiceFormClose = () => {
                if (saving && form.isConnected && form.getAttribute('aria-busy') === 'true') return Promise.resolve(false);
                return window.canDiscardForm ? window.canDiscardForm(form) : Promise.resolve(true);
            };
        }
        // Names retain intentional mixed case and acronyms; units and descriptions are not title-cased.
        const nameInputs = form.querySelectorAll('input[name="name"], input[name="package_name"], input[name="type_or_material"]');
        const correctName = input => {
            const corrected = input.value.trim().replace(/\s+/g, ' ').replace(/\p{L}+/gu, word =>
                word === word.toLocaleLowerCase() ? word.charAt(0).toLocaleUpperCase() + word.slice(1) : word);
            if (corrected !== input.value) { input.value = corrected; input.dispatchEvent(new Event('input', { bubbles: true })); }
        };
        nameInputs.forEach(input => {
            input.setAttribute('autocapitalize', 'words');
            input.setAttribute('autocorrect', 'off');
            input.addEventListener('blur', () => correctName(input));
        });
        form.addEventListener('submit', () => nameInputs.forEach(correctName), true);
        form.querySelector('input[type="checkbox"][name="is_available"]')?.setAttribute('role', 'switch');
        form.querySelectorAll('textarea').forEach(input => { input.spellcheck = true; input.setAttribute('autocapitalize', 'sentences'); });
        // Bind directly to each cancel link. A document-level delegated handler
        // can be bypassed by the panel/modal navigation listeners that also
        // observe these links, allowing the browser's default navigation to
        // close a dirty form before its discard confirmation is shown.
        const bindCancelLinks = () => form.querySelectorAll('[data-service-form-cancel]').forEach(cancel => {
            if (cancel.dataset.discardGuardBound === 'true') return;
            cancel.dataset.discardGuardBound = 'true';
            cancel.addEventListener('click', async event => {
                if (event.defaultPrevented) return;
                event.preventDefault();
                event.stopImmediatePropagation();
                if (window.parent !== window && window.parent.cancelServiceCatalogModal) {
                    await window.parent.cancelServiceCatalogModal();
                    return;
                }
                if (await window.requestServiceFormClose()) window.location.assign(cancel.href);
            }, true);
        });
        bindCancelLinks();
        new MutationObserver(bindCancelLinks).observe(form, { childList: true, subtree: true });
        const errorHost = input => input.closest('.cc-money,.aoc-money,.pkg-money,.pkg-unit-input')?.parentElement || input.parentElement;
        form.addEventListener('invalid', event => {
            event.preventDefault();
            const firstInvalid = [...form.querySelectorAll('input:invalid,select:invalid,textarea:invalid')].find(field => field.getClientRects().length);
            firstInvalid?.focus();
            const input = event.target;
            input.setAttribute('aria-invalid', 'true');
            let error = [...errorHost(input).querySelectorAll('[data-service-field-error]')].find(error => error.dataset.serviceFieldError === input.name);
            if (!error) {
                error = document.createElement('p'); error.className = 'form-error'; error.dataset.serviceFieldError = input.name;
                error.id = `service-error-${input.id || input.name.replace(/\W/g, '-')}`;
                error.setAttribute('role', 'alert'); errorHost(input).append(error);
            }
            const label = form.querySelector(`label[for="${input.id}"]`)?.textContent.replace(/\*|\(Optional\)/g, '').trim() || 'This field';
            const validity = input.validity;
            let message = input.validationMessage;
            if (!validity.customError) {
                if (validity.valueMissing) message = `${label} is required.`;
                else if (validity.rangeOverflow) message = `${label} cannot exceed ${input.max}.`;
                else if (validity.rangeUnderflow) message = `${label} must be at least ${input.min}.`;
                else if (validity.stepMismatch) message = input.step === '0.01' ? `${label} can have up to two decimal places.` : `${label} must be a whole number.`;
                else if (validity.badInput) message = `${label} must be a number.`;
            }
            error.textContent = message;
            input.setAttribute('aria-describedby', [...new Set([...(input.getAttribute('aria-describedby') || '').split(' ').filter(Boolean), error.id])].join(' '));
        }, true);
        form.addEventListener('input', event => {
            const input = event.target;
            if (input.validity?.valid) {
                input.removeAttribute('aria-invalid');
                const error = [...errorHost(input).querySelectorAll('[data-service-field-error]')].find(error => error.dataset.serviceFieldError === input.name);
                if (error) error.textContent = '';
            }
        });
        new MutationObserver(() => { saving = form.getAttribute('aria-busy') === 'true'; }).observe(form, { attributes: true, attributeFilter: ['aria-busy'] });
        // Associate existing server errors without changing their text or validation rules.
        form.querySelectorAll('.form-error').forEach((error, index) => {
            if (!error.textContent.trim() || getComputedStyle(error).display === 'none') return;
            const field = error.dataset.validationFor;
            const name = field?.replace(/\.([^.]*)/g, '[$1]');
            const input = (name ? form.elements.namedItem(name) : null) || error.parentElement.querySelector('input:not([type="hidden"]),select,textarea');
            if (!input) return;
            error.id ||= `service-server-error-${index}`; error.setAttribute('role', 'alert');
            input.setAttribute('aria-invalid', 'true'); input.setAttribute('aria-describedby', error.id);
        });
    }
}
