import test from 'node:test';
import assert from 'node:assert/strict';
import { ADDON_PRICE_PREVIEW_PROMPT, formatAddOnPricePreview, initAddOnPricePreviews } from '../resources/js/addon-price-preview.js';

test('previews each supported add-on billing unit without changing its value', () => {
    const examples = [
        [500, 'item', '₱500.00 per item'],
        [2000, 'set', '₱2,000.00 per set'],
        [1500, 'day', '₱1,500.00 per day'],
        [800, 'trip', '₱800.00 per trip'],
        [2500, 'service', '₱2,500.00 per service'],
    ];

    for (const [price, unit, expected] of examples) {
        assert.equal(formatAddOnPricePreview(price, unit, unit), expected);
    }
});

test('shows the prompt when either the price or billing unit is missing', () => {
    assert.equal(formatAddOnPricePreview('', 'item'), ADDON_PRICE_PREVIEW_PROMPT);
    assert.equal(formatAddOnPricePreview('500', ''), ADDON_PRICE_PREVIEW_PROMPT);
    assert.equal(formatAddOnPricePreview('not-a-price', 'item'), ADDON_PRICE_PREVIEW_PROMPT);
});

test('keeps a valid zero unit price visible', () => {
    assert.equal(formatAddOnPricePreview('0', 'service'), '₱0.00 per service');
});

test('initializes saved values and refreshes the preview when either field changes', () => {
    const handlers = { price: {}, unit: {}, category: {} };
    const price = {
        value: '500',
        addEventListener: (name, handler) => { handlers.price[name] = handler; },
    };
    const unit = {
        value: 'item',
        selectedOptions: [{ textContent: 'Item' }],
        options: ['item', 'set', 'day'].map(value => ({ value, textContent: value, disabled: false })),
        addEventListener: (name, handler) => { handlers.unit[name] = handler; },
        dispatchEvent(event) {
            this.selectedOptions = this.options.filter(option => option.value === this.value);
            handlers.unit[event.type]();
        },
    };
    const category = {
        value: 'General',
        selectedOptions: [{ dataset: { defaultUnit: 'item' } }],
        addEventListener: (name, handler) => { handlers.category[name] = handler; },
    };
    const fields = { '[data-addon-unit-price]': price, '[data-addon-billing-unit]': unit, '#addon-category': category };
    const form = { querySelector: selector => fields[selector] };
    const preview = {
        dataset: {},
        textContent: '',
        closest: () => form,
    };

    initAddOnPricePreviews({ querySelectorAll: () => [preview] });
    assert.equal(preview.textContent, '₱500.00 per item');

    price.value = '2000';
    handlers.price.input();
    assert.equal(preview.textContent, '₱2,000.00 per item');

    unit.value = 'set';
    unit.selectedOptions = [{ textContent: 'Set' }];
    handlers.unit.change();
    assert.equal(preview.textContent, '₱2,000.00 per set');

    category.value = 'Chapel Viewing';
    category.selectedOptions = [{ dataset: { defaultUnit: 'day' } }];
    handlers.category.change();
    assert.equal(unit.value, 'day');
    assert.deepEqual(unit.options.filter(option => !option.disabled).map(option => option.value), ['day']);
    assert.equal(preview.textContent, '₱2,000.00 per day');

    category.value = 'Flowers';
    category.selectedOptions = [{ dataset: { defaultUnit: 'set' } }];
    handlers.category.change();
    assert.equal(unit.value, 'set');
    assert.equal(unit.options.every(option => !option.disabled), true);
});
