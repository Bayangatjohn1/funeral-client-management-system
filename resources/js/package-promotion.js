const MAX_VALUE = 9999999999.99;
const dateLabel = value => new Intl.DateTimeFormat('en-US', {
    timeZone: 'Asia/Manila', year: 'numeric', month: 'long', day: 'numeric',
}).format(new Date(`${value}T00:00:00+08:00`));

export function promotionSchedule(start, end) {
    if (start && end) return `This promotion will be active from ${dateLabel(start)} through ${dateLabel(end)}.`;
    if (start) return `This promotion will start on ${dateLabel(start)} and remain active until manually turned off.`;
    if (end) return `This promotion will apply immediately when enabled and end on ${dateLabel(end)}.`;
    return 'This promotion will apply immediately when enabled and remain active until manually turned off.';
}

export function promotionStatus(enabled, start, end, now = new Date()) {
    if (!enabled) return 'No Promotion';
    const parts = new Intl.DateTimeFormat('en-US', { timeZone: 'Asia/Manila', year: 'numeric', month: '2-digit', day: '2-digit' }).formatToParts(now);
    const part = type => parts.find(p => p.type === type).value;
    const today = `${part('year')}-${part('month')}-${part('day')}`;
    if (start && today < start) return 'Scheduled';
    if (end && today > end) return 'Expired';
    return 'Active';
}

export function promotionErrors({ enabled, label, type, value, price, start, end }) {
    if (!enabled) return {};
    const errors = {};
    if (!label.trim()) errors.promo_label = 'Enter a label for the enabled promotion.';
    if (!['AMOUNT', 'PERCENT'].includes(type)) errors.promo_value_type = 'Choose a discount type for the enabled promotion.';
    if (!value || !Number.isFinite(Number(value)) || Number(value) <= 0) errors.promo_value = 'Enter a promo value greater than zero.';
    else if (Number(value) > MAX_VALUE) errors.promo_value = 'Promo Value cannot exceed 9,999,999,999.99.';
    else if (!/^\d+(?:\.\d{1,2})?$/.test(value)) errors.promo_value = 'Promo Value can have up to two decimal places.';
    else if (type === 'PERCENT' && Number(value) > 100) errors.promo_value = 'Promo percentage cannot exceed 100%.';
    else if (type === 'AMOUNT' && price !== '' && Number.isFinite(Number(price)) && Number(value) > Number(price)) errors.promo_value = 'Fixed promo amount cannot exceed the package base price.';
    if (start && end && end < start) errors.promo_ends_at = 'Promo end date must be on or after the start date.';
    return errors;
}

const form = typeof document !== 'undefined' ? document.querySelector('#packageForm:has([data-promo-toggle])') : null;
if (form) {
    const toggle = form.querySelector('[data-promo-toggle]');
    const fields = ['promo_label', 'promo_value_type', 'promo_value', 'promo_starts_at', 'promo_ends_at'];
    const input = name => form.elements.namedItem(name);
    const statusElement = form.querySelector('[data-promo-effective-status]');
    const wasTurnedOff = statusElement?.textContent.trim() === 'Turned Off';
    let promoToggleChanged = false;
    toggle?.addEventListener('change', () => { promoToggleChanged = true; });
    const update = () => {
        const enabled = !!toggle.checked;
        const state = { enabled, label: input('promo_label').value, type: input('promo_value_type').value,
            value: input('promo_value').value, price: input('price').value,
            start: input('promo_starts_at').value, end: input('promo_ends_at').value };
        const errors = promotionErrors(state);
        fields.forEach(name => {
            const field = input(name);
            field.disabled = !enabled;
            field.required = enabled && ['promo_label', 'promo_value_type', 'promo_value'].includes(name);
            field.setCustomValidity(errors[name] || '');
            const error = [...form.querySelectorAll('[data-service-field-error]')].find(node => node.dataset.serviceFieldError === name);
            if (error) error.textContent = errors[name] || '';
            if (!errors[name]) field.removeAttribute('aria-invalid');
        });
        input('promo_value').min = '0.01';
        input('promo_value').max = state.type === 'PERCENT' ? '100' : (state.type === 'AMOUNT' && state.price !== '' ? String(Math.min(MAX_VALUE, Math.max(0, Number(state.price)))) : String(MAX_VALUE));
        const schedule = form.querySelector('[data-promo-schedule]');
        schedule.textContent = promotionSchedule(state.start, state.end);
        const status = statusElement;
        const configured = !!state.label.trim() && ['AMOUNT', 'PERCENT'].includes(state.type) && Number(state.value) > 0;
        const evaluatedStatus = promotionStatus(true, state.start, state.end);
        status.textContent = !configured ? 'No Promotion'
            : evaluatedStatus === 'Expired' ? 'Expired'
            : (!enabled || (wasTurnedOff && !promoToggleChanged)) ? 'Turned Off'
            : promotionStatus(enabled, state.start, state.end);
        status.dataset.status = status.textContent;
    };
    form.addEventListener('input', update);
    form.addEventListener('change', update);
    update();
    // A long-open form also updates its displayed date-based status after midnight.
    const timer = setInterval(() => { if (form.isConnected) update(); else clearInterval(timer); }, 60000);
}
