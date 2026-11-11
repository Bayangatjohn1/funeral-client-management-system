export const ADDON_PRICE_PREVIEW_PROMPT = 'Enter the unit price and select a billing unit to preview.';

export function formatAddOnPricePreview(priceValue, unitValue, unitLabel = unitValue) {
    const rawPrice = String(priceValue ?? '').trim();
    const unit = String(unitValue ?? '').trim();
    const label = String(unitLabel ?? '').trim().toLowerCase();
    const amount = Number(rawPrice);

    if (!rawPrice || !unit || !label || !Number.isFinite(amount)) return ADDON_PRICE_PREVIEW_PROMPT;

    return `₱${amount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} per ${label}`;
}

export function initAddOnPricePreviews(root = document) {
    root.querySelectorAll('[data-addon-price-preview]').forEach((preview) => {
        if (preview.dataset.ready === 'true') return;
        const form = preview.closest('form');
        const price = form?.querySelector('[data-addon-unit-price]');
        const unit = form?.querySelector('[data-addon-billing-unit]');
        if (!price || !unit) return;
        const category = form.querySelector('#addon-category');

        preview.dataset.ready = 'true';
        const update = () => {
            preview.textContent = formatAddOnPricePreview(
                price.value,
                unit.value,
                unit.selectedOptions?.[0]?.textContent,
            );
        };

        price.addEventListener('input', update);
        unit.addEventListener('change', update);
        category?.addEventListener('change', () => {
            const defaultUnit = category.selectedOptions[0]?.dataset?.defaultUnit;
            if (!defaultUnit) return;
            for (const option of unit.options) {
                option.disabled = category.value === 'Chapel Viewing' && option.value !== 'day';
            }
            unit.value = defaultUnit;
            unit.dispatchEvent(new Event('change', { bubbles: true }));
        });
        update();
    });
}

function installAddOnPricePreviewObserver() {
    initAddOnPricePreviews();
    new MutationObserver((records) => {
        records.forEach((record) => record.addedNodes.forEach((node) => {
            if (!(node instanceof Element)) return;
            if (node.matches('[data-addon-price-preview]') || node.querySelector('[data-addon-price-preview]')) {
                initAddOnPricePreviews(node);
            }
        }));
    }).observe(document.body, { childList: true, subtree: true });
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', installAddOnPricePreviewObserver, { once: true });
    } else {
        installAddOnPricePreviewObserver();
    }
}
