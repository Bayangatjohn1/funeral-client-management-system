import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createSkeletonLifecycle } from '../resources/js/skeleton-lifecycle.js';

function setup(t) {
    t.mock.timers.enable({apis:['setTimeout']});
    const events = [];
    const loader = createSkeletonLifecycle(() => events.push('show'), () => events.push('hide'));
    return {loader, events};
}
test('fast responses never flash placeholders', t => {
    const {loader,events} = setup(t);
    loader.begin()(); t.mock.timers.tick(500);
    assert.deepEqual(events, []);
});
test('navigation can reveal synchronously before the document is left', t => {
    t.mock.timers.enable({apis:['setTimeout']});
    const events = [];
    const loader = createSkeletonLifecycle(() => events.push('show'), () => events.push('hide'), {delay:0});
    const finish = loader.begin();
    assert.deepEqual(events, ['show']);
    finish(); assert.deepEqual(events, ['show','hide']);
});
test('one completed request cannot hide a concurrent request', t => {
    const {loader,events} = setup(t);
    const old = loader.begin(); const latest = loader.begin();
    t.mock.timers.tick(160); old(); old();
    assert.deepEqual(events, ['show']); assert.equal(loader.pending,1);
    latest(); assert.deepEqual(events, ['show','hide']);
});
test('cancellation before reveal cleans the timer', t => {
    const {loader,events} = setup(t);
    const cancel = loader.begin(); t.mock.timers.tick(80); cancel(); t.mock.timers.tick(1000);
    assert.deepEqual(events, []); assert.equal(loader.pending,0);
});
test('a hung navigation recovers content and late callbacks are harmless', t => {
    const {loader,events} = setup(t);
    const finish = loader.begin(); t.mock.timers.tick(160); t.mock.timers.tick(30000);
    assert.deepEqual(events, ['show','hide']); assert.equal(loader.pending,0);
    const next = loader.begin(); finish(); t.mock.timers.tick(160);
    assert.deepEqual(events, ['show','hide','show']); next();
});
test('Back/Forward reset clears all leases and permits future loads', t => {
    const {loader,events} = setup(t);
    const old = loader.begin(); t.mock.timers.tick(160); loader.reset();
    const current = loader.begin(); old(); t.mock.timers.tick(160); current();
    assert.deepEqual(events, ['show','hide','show','hide']);
});
