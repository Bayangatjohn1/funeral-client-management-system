const NAME_PATTERN = /^[\p{L}\p{M}\p{N}][\p{L}\p{M}\p{N}\s.,'()/&-]*$/u;
const VALID_TYPES = new Set(['AMOUNT', 'PERCENT']);

export function promotionScheduleMessage(startDate, endDate, formatDate) {
    if (startDate && endDate) {
        return `This promotion will be active from ${formatDate(startDate)} through ${formatDate(endDate)}.`;
    }
    if (startDate) {
        return `This promotion will start on ${formatDate(startDate)} and remain active until manually turned off.`;
    }
    if (endDate) {
        return `This promotion will apply immediately when enabled and end on ${formatDate(endDate)}.`;
    }
    return 'This promotion will apply immediately when enabled and remain active until manually turned off.';
}

export function promotionFieldErrors({
    name = '',
    discountType = '',
    discountValue = '',
    startDate = '',
    endDate = '',
    discountRequired = true,
    valueRequired = true,
    stepMismatch = false,
    assignedPackagePrice = null,
}) {
    const errors = {};
    const cleanedName = name.trim();

    if (!cleanedName) errors.name = 'Promotion Name is required.';
    else if (!NAME_PATTERN.test(cleanedName)) {
        errors.name = 'Promotion Name contains unsupported characters. Use valid letters, numbers, spaces, and punctuation.';
    } else if ([...cleanedName].length > 120) {
        errors.name = 'Promotion Name must not exceed 120 characters.';
    }

    if (discountRequired && !discountType) errors.discount_type = 'Discount Type is required.';
    else if (discountType && !VALID_TYPES.has(discountType)) {
        errors.discount_type = 'Select a valid discount type: Amount or Percentage.';
    }

    if (valueRequired && discountValue === '') errors.discount_value = 'Discount Value is required.';
    else if (discountValue !== '') {
        const amount = Number(discountValue);
        if (!Number.isFinite(amount) || amount <= 0) {
            errors.discount_value = 'Discount Value must be greater than zero.';
        } else if (stepMismatch) {
            errors.discount_value = 'Discount Value can have up to two decimal places.';
        } else if (discountType === 'PERCENT' && amount > 100) {
            errors.discount_value = 'Percentage discount cannot exceed 100%.';
        } else if (amount > 9999999999.99) {
            errors.discount_value = 'Discount Value cannot exceed the maximum allowed amount.';
        } else if (discountType === 'AMOUNT' && assignedPackagePrice !== null && assignedPackagePrice !== ''
            && Number.isFinite(Number(assignedPackagePrice)) && amount > Number(assignedPackagePrice)) {
            errors.discount_value = "Fixed discount cannot exceed the assigned package's base price.";
        }
    }

    if (startDate && endDate && endDate < startDate) {
        errors.ends_at = 'End Date must be on or after Start Date.';
    }

    return errors;
}

export function promotionFieldMessage({ clientMessage = '', serverMessage = '', touched = false, submitAttempted = false }) {
    if (serverMessage && !touched) return serverMessage;
    return touched || submitAttempted ? clientMessage : '';
}

function initializePromotionForm() {
    const form = document.getElementById('promotionForm');
    if (!form) return;

    const name = form.elements.namedItem('name');
    const discountType = form.elements.namedItem('discount_type');
    const discountValue = form.elements.namedItem('discount_value');
    const start = form.elements.namedItem('starts_at');
    const end = form.elements.namedItem('ends_at');
    const suffix = document.querySelector('[data-promotion-suffix]');
    const fieldsByErrorKey = {
        name,
        discount_type: discountType,
        discount_value: discountValue,
        starts_at: start,
        ends_at: end,
    };
    const touchedFields = new WeakSet();
    let submitAttempted = false;

    window.registerDiscardForm?.(form);
    window.requestServiceFormClose = () => window.canDiscardForm?.(form) ?? true;

    document.addEventListener('click', async event => {
        const cancel = event.target.closest('[data-service-form-cancel]');
        if (!cancel || window.parent !== window) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        if (await window.canDiscardForm?.(form)) window.location.assign(cancel.href);
    }, true);

    const updateDiscount = () => {
        if (!discountType || !discountValue) return;
        const percentage = discountType.value === 'PERCENT';
        if (suffix) suffix.textContent = percentage ? '%' : '₱';
        discountValue.placeholder = percentage ? 'e.g., 20' : 'e.g., 5000.00';
        discountValue.max = percentage ? '100' : '9999999999.99';
    };

    const validate = () => {
        const errors = promotionFieldErrors({
            name: name?.value ?? '',
            discountType: discountType?.value ?? '',
            discountValue: discountValue?.value ?? '',
            startDate: start?.value ?? '',
            endDate: end?.value ?? '',
            discountRequired: Boolean(discountType?.required),
            valueRequired: Boolean(discountValue?.required),
            stepMismatch: Boolean(discountValue?.value && discountValue.validity.stepMismatch),
            assignedPackagePrice: form.dataset.assignedPackagePrice,
        });
        Object.entries(fieldsByErrorKey).forEach(([key, field]) => {
            if (!field) return;
            const serverError = form.querySelector(`#${{
                name: 'promotion-name-error',
                discount_type: 'promotion-type-error',
                discount_value: 'promotion-value-error',
                starts_at: 'promotion-start-error',
                ends_at: 'promotion-end-error',
            }[key]}`);
            const clientError = form.querySelector(`[data-promotion-client-error="${key}"]`);
            const serverMessage = serverError?.textContent.trim() ?? '';
            const message = promotionFieldMessage({
                clientMessage: errors[key] ?? '',
                serverMessage,
                touched: touchedFields.has(field),
                submitAttempted,
            });
            field.setCustomValidity(message);
            const errorElement = serverError ?? clientError;
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.hidden = !message;
            }
            if (message) {
                field.setAttribute('aria-invalid', 'true');
                const describedBy = new Set((field.getAttribute('aria-describedby') ?? '').split(/\s+/).filter(Boolean));
                if (errorElement?.id) describedBy.add(errorElement.id);
                field.setAttribute('aria-describedby', [...describedBy].join(' '));
            } else {
                field.removeAttribute('aria-invalid');
            }
        });
    };

    const validateChangedField = event => {
        if (Object.values(fieldsByErrorKey).includes(event.target)) touchedFields.add(event.target);
        validate();
    };
    form.addEventListener('input', validateChangedField);
    form.addEventListener('change', validateChangedField);
    form.addEventListener('submit', event => {
        submitAttempted = true;
        validate();
        if (form.reportValidity()) return;
        event.preventDefault();
        form.querySelector(':invalid')?.focus();
    });
    discountType?.addEventListener('change', updateDiscount);

    updateDiscount();
    validate();
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializePromotionForm, { once: true });
    } else {
        initializePromotionForm();
    }
}
