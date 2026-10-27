const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function fixture() {
    const handlers = {}, label = {}, status = {}, error = {};
    let clock = 0, tick;
    const email = { value: 'staff@example.com', addEventListener: (type, fn) => { handlers.input = fn; } };
    const button = { dataset: { throttleEmail: email.value, throttleSeconds: '60' },
        querySelector: () => label, form: { addEventListener: (type, fn) => { handlers.submit = fn; } } };
    vm.runInNewContext(fs.readFileSync('resources/js/login-throttle.js', 'utf8'), {
        document: { readyState: 'complete', querySelector: selector => selector === '[data-login-submit]' ? button : error,
            getElementById: id => id === 'email' ? email : status },
        performance: { now: () => clock }, Math, Number, setInterval: fn => { tick = fn; return 1; }, clearInterval: () => {},
    });
    return { email, button, label, status, handlers, advance: ms => { clock += ms; tick(); } };
}

test('button and keyboard submission are blocked until the countdown ends', () => {
    const f = fixture();
    assert.equal(f.button.disabled, true);
    assert.equal(f.label.textContent, 'Try again in 60s');
    let prevented = false;
    f.handlers.submit({ preventDefault: () => { prevented = true; } });
    assert.equal(prevented, true);
    f.advance(59000);
    assert.equal(f.button.disabled, true);
    f.advance(1000);
    assert.equal(f.button.disabled, false);
    assert.equal(f.label.textContent, 'Sign in');
});

test('the UI limit follows the throttled email rather than blocking every account', () => {
    const f = fixture();
    f.email.value = 'other@example.com'; f.handlers.input();
    assert.equal(f.button.disabled, false);
    f.email.value = 'STAFF@EXAMPLE.COM'; f.handlers.input();
    assert.equal(f.button.disabled, true);
});
