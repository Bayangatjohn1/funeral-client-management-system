const { test } = require('node:test');
const assert = require('node:assert/strict');

let promotionScheduleMessage;
let promotionFieldErrors;
let promotionFieldMessage;
test.before(async () => {
    ({ promotionScheduleMessage, promotionFieldErrors, promotionFieldMessage } = await import('../resources/js/promotion-form.js'));
});

const dateLabel = value => ({ '2026-12-01': 'Dec 1, 2026', '2026-12-31': 'Dec 31, 2026' })[value];

test('promotion schedule feedback covers both dates, start only, end only, and no dates', () => {
    assert.equal(
        promotionScheduleMessage('2026-12-01', '2026-12-31', dateLabel),
        'This promotion will be active from Dec 1, 2026 through Dec 31, 2026.',
    );
    assert.equal(
        promotionScheduleMessage('2026-12-01', '', dateLabel),
        'This promotion will start on Dec 1, 2026 and remain active until manually turned off.',
    );
    assert.equal(
        promotionScheduleMessage('', '2026-12-31', dateLabel),
        'This promotion will apply immediately when enabled and end on Dec 31, 2026.',
    );
    assert.equal(
        promotionScheduleMessage('', '', dateLabel),
        'This promotion will apply immediately when enabled and remain active until manually turned off.',
    );
});

test('promotion field feedback mirrors name, discount, package-price, and schedule validation', () => {
    assert.deepEqual(promotionFieldErrors({}), {
        name: 'Promotion Name is required.',
        discount_type: 'Discount Type is required.',
        discount_value: 'Discount Value is required.',
    });
    assert.equal(promotionFieldErrors({ name: 'Bad 😀 name' }).name,
        'Promotion Name contains unsupported characters. Use valid letters, numbers, spaces, and punctuation.');
    assert.equal(promotionFieldErrors({ name: 'A'.repeat(121) }).name,
        'Promotion Name must not exceed 120 characters.');
    assert.equal(promotionFieldErrors({ name: 'Winter Offer', discountType: 'PERCENT', discountValue: '100.01' }).discount_value,
        'Percentage discount cannot exceed 100%.');
    assert.equal(promotionFieldErrors({ name: 'Winter Offer', discountType: 'AMOUNT', discountValue: '500.01', assignedPackagePrice: '500' }).discount_value,
        "Fixed discount cannot exceed the assigned package's base price.");
    assert.equal(promotionFieldErrors({ name: 'Winter Offer', discountType: 'AMOUNT', discountValue: '500.00', assignedPackagePrice: '500' }).discount_value,
        undefined);
    assert.equal(promotionFieldErrors({ name: 'Winter Offer', discountType: 'AMOUNT', discountValue: '0.01', assignedPackagePrice: '0' }).discount_value,
        "Fixed discount cannot exceed the assigned package's base price.");
    assert.equal(promotionFieldErrors({ name: 'Winter Offer', discountType: 'AMOUNT', discountValue: '1.001', stepMismatch: true }).discount_value,
        'Discount Value can have up to two decimal places.');
    assert.equal(promotionFieldErrors({ name: 'Winter Offer', discountType: 'AMOUNT', discountValue: '1', startDate: '2026-12-31', endDate: '2026-12-01' }).ends_at,
        'End Date must be on or after Start Date.');
});

test('promotion fields stay quiet on initial open, show errors after interaction or submit, and preserve server errors', () => {
    assert.equal(promotionFieldMessage({ clientMessage: 'Promotion Name is required.' }), '');
    assert.equal(promotionFieldMessage({ clientMessage: 'Promotion Name is required.', touched: true }), 'Promotion Name is required.');
    assert.equal(promotionFieldMessage({ clientMessage: 'Promotion Name is required.', submitAttempted: true }), 'Promotion Name is required.');
    assert.equal(promotionFieldMessage({ clientMessage: '', serverMessage: 'This field is invalid.' }), 'This field is invalid.');
    assert.equal(promotionFieldMessage({ clientMessage: '', serverMessage: 'This field is invalid.', touched: true }), '');
});
