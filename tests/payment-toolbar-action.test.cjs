const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

test('Record Payment opens once and restores focus after its toolbar trigger is replaced', () => {
    const view = readFileSync(path.join(__dirname, '../resources/views/staff/payments/_record_modal.blade.php'), 'utf8');
    const script = view.match(/<script>([\s\S]*?)<\/script>/)[1]
        .replace('@json($preselectCase->id ?? null)', 'null')
        .replace('@json($autoOpenPayment ?? false)', 'false')
        .replace('@json($errors->any())', 'false');
    const element = () => {
        const classes = new Set(['hidden']);
        return {
            listeners: {}, attributes: {}, focused: 0,
            classList: { add: x => classes.add(x), remove: x => classes.delete(x), contains: x => classes.has(x) },
            addEventListener(type, callback) { this.listeners[type] = callback; },
            setAttribute(name, value) { this.attributes[name] = value; },
            focus() { this.focused++; },
        };
    };
    const ids = Object.fromEntries(['openPaymentForm', 'paymentFormModal', 'paymentFormBackdrop', 'closePaymentFormTop', 'closePaymentFormBottom', 'funeral_case_id'].map(id => [id, element()]));
    const handlers = {};
    const body = element();
    const modal = ids.paymentFormModal;
    modal.parentElement = body;
    let openings = 0;
    const setAttribute = modal.setAttribute;
    modal.setAttribute = function (name, value) {
        if (name === 'aria-hidden' && value === 'false') openings++;
        setAttribute.call(this, name, value);
    };
    const document = {
        body, documentElement: element(),
        getElementById: id => ids[id],
        addEventListener: (type, callback) => (handlers[type] ??= []).push(callback),
    };
    vm.runInNewContext(script, { document, window: { setTimeout: callback => callback() }, requestAnimationFrame: callback => callback() });
    handlers.DOMContentLoaded.forEach(callback => callback());

    for (let cycle = 0; cycle < 3; cycle++) {
        const oldTrigger = ids.openPaymentForm;
        const trigger = element();
        ids.openPaymentForm = trigger;
        handlers.click.forEach(callback => callback({ target: { closest: selector => selector === '#openPaymentForm' ? trigger : null } }));
        assert.equal(openings, cycle + 1);
        assert.equal(modal.attributes['aria-hidden'], 'false');
        assert.equal(modal.classList.contains('hidden'), false);
        ids.closePaymentFormTop.listeners.click();
        assert.equal(modal.attributes['aria-hidden'], 'true');
        assert.equal(trigger.focused, 1);
        assert.equal(oldTrigger.focused, cycle === 0 ? 0 : 1);
    }
    handlers.click.forEach(callback => callback({ target: { closest: () => null } }));
    assert.equal(openings, 3);
});
