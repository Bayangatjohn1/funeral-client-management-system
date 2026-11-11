import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';

const workflow = readFileSync(new URL('../resources/js/payment-workflow.js', import.meta.url), 'utf8')
    .replace('export function', 'function');

test('a hidden dashboard defers the toast and visiting Payment Monitoring acknowledges the count', async () => {
    const element = () => Object.assign(new EventTarget(), {
        dataset: {},
        setAttribute() {},
        append(...children) { this.children = [...(this.children || []), ...children]; },
    });
    const sidebar = element();
    Object.assign(sidebar.dataset, { user: '6', current: 'false', latestPendingId: '20' });
    sidebar.textContent = '1';
    const bell = element();
    const badge = element();
    bell.dataset.reminders = '2';
    bell.querySelector = () => badge;
    const list = element();
    list.replaceChildren = (...children) => { list.children = children; };
    const unread = element();
    const host = element();
    host.dataset.workflowNoticesUrl = '/payment-corrections/notices';
    host.querySelector = selector => selector === '[data-workflow-unread]' ? unread : list;
    host.closest = () => ({ querySelector: () => bell });
    const document = Object.assign(new EventTarget(), {
        documentElement: { dataset: {} },
        hidden: false,
        querySelector: selector => ({
            '[data-sidebar-correction-count]': sidebar,
            '[data-workflow-notices-url]': host,
            '[data-notification-toggle]': bell,
        })[selector],
        querySelectorAll: () => [],
        createElement: element,
    });
    const storage = new Map();
    const localStorage = { getItem: key => storage.get(key), setItem: (key, value) => storage.set(key, value) };
    const toasts = [];
    const window = Object.assign(new EventTarget(), { localStorage, showPanelToast: (...args) => toasts.push(args) });
    const destination = '/admin/payments?tab=corrections&open_correction=20';
    let pendingId = 20;
    const payload = () => ({
        pending: 1, latest_pending_id: pendingId, unread: 1,
        items: [{ id: 3, key: 'workflow:3', read: false, event: 'overdue', tone: 'danger', title: 'Payment correction needs follow-up', at: 'Nov 09, 2026 8:00 AM', created_at: '2026-11-09T00:00:00Z', url: destination }],
    });
    let finishFirstFetch;
    const firstFetch = new Promise(resolve => { finishFirstFetch = resolve; });
    let first = true;
    const fetch = async () => {
        if (first) { first = false; await firstFetch; }
        return { ok: true, json: async () => payload() };
    };
    const settle = () => new Promise(resolve => setImmediate(resolve));
    const refresh = async () => { document.dispatchEvent(new Event('visibilitychange')); await settle(); };
    runInNewContext(workflow + '\ninstallPaymentWorkflow();', { document, window, localStorage, fetch, AbortController, Event, setInterval() {} });
    document.hidden = true;
    finishFirstFetch();
    await settle();
    assert.equal(badge.textContent, 3);
    assert.equal(list.children[0].dataset.notificationKey, 'workflow:3');
    assert.match(list.children[0].className, /tone-danger/);
    assert.equal(list.children[0].children[0].href, destination);
    assert.equal(toasts.length, 0);

    document.hidden = false;
    await refresh();
    assert.deepEqual(toasts, [['Payment correction needs follow-up', 'warning', destination]]);
    await refresh();
    assert.equal(toasts.length, 1);
    assert.equal(sidebar.dataset.attention, 'true');

    sidebar.dataset.current = 'true';
    await refresh();
    assert.equal(sidebar.dataset.attention, 'false');
    assert.equal(sidebar.textContent, 1);
    sidebar.dataset.current = 'false';
    await refresh();
    assert.equal(sidebar.dataset.attention, 'false');
    pendingId++;
    await refresh();
    assert.equal(sidebar.dataset.attention, 'true');
});
