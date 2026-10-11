const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function fixture() {
    const handlers = {}, intervals = [], requests = [], redirects = [];
    const elements = { '[data-idle-stay]': { addEventListener: (type, fn) => { handlers.stay = fn; } }, '[data-idle-error]': {}, '[data-idle-countdown]': {} };
    const dialog = { open: false, showModal() { this.open = true; }, close() { this.open = false; }, querySelector: selector => elements[selector], addEventListener: (type, fn) => { handlers[type] = fn; } };
    let clock = 1_000_000;
    let reply = { expires_at: 1060, server_now: 1000, session_key: 'session' };
    let status = 200;
    const config = { dataset: { state: JSON.stringify(reply), statusUrl: '/session/status', keepAliveUrl: '/session/keep-alive', loginUrl: '/login' } };
    const context = { Date: { now: () => clock }, JSON, Math, console,
        document: { readyState: 'complete', hidden: false, getElementById: id => id === 'idle-session-config' ? config : dialog,
            querySelector: () => ({ content: 'csrf' }), addEventListener: (type, fn) => { handlers[type] = fn; } },
        window: { location: { replace: url => redirects.push(url), reload: () => redirects.push('reload') }, addEventListener: (type, fn) => { handlers[type] = fn; } },
        localStorage: { setItem: () => {} }, setInterval: (fn, delay) => { intervals.push({ fn, delay }); },
        fetch: async (url, options) => { requests.push({ url, options }); return { status, ok: status === 200, json: async () => reply }; },
    };
    vm.runInNewContext(fs.readFileSync('resources/js/idle-session.js', 'utf8'), context);
    return { handlers, intervals, requests, redirects, dialog, elements, setReply: next => { reply = next; }, setStatus: next => { status = next; }, advance: ms => { clock += ms; } };
}

test('warning opens at sixty seconds; explicit keep-alive closes it', async () => {
    const f = fixture();
    f.intervals.find(i => i.delay === 1000).fn();
    assert.equal(f.dialog.open, true);
    assert.equal(f.elements['[data-idle-countdown]'].textContent, '60 seconds');
    f.setReply({ expires_at: 1900, server_now: 1000, session_key: 'session' });
    await f.handlers.stay();
    assert.equal(f.requests[0].options.method, 'POST');
    assert.equal(f.dialog.open, false);
});

test('background polling only checks status and cannot extend activity', async () => {
    const f = fixture();
    await f.intervals.find(i => i.delay === 30000).fn();
    assert.equal(f.requests[0].url, '/session/status');
    assert.equal(f.requests[0].options.method, 'GET');
    assert.equal(f.requests.filter(r => r.options.method === 'POST').length, 0);
});

test('expired response returns to login; cross-tab expiry does the same', async () => {
    const f = fixture();
    f.setStatus(401);
    await f.intervals.find(i => i.delay === 30000).fn();
    assert.deepEqual(f.redirects, ['/login']);
    const other = fixture();
    other.handlers.storage({ key: 'idle-session:session', newValue: JSON.stringify({ expired: true }) });
    assert.deepEqual(other.redirects, ['/login']);
});
