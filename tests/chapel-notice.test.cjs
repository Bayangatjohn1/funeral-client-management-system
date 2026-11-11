const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('resources/views/staff/intake/_form.blade.php', 'utf8');
const start = source.indexOf('    let restoredChapelPricing = null;');
const end = source.indexOf('    const serviceCharges =', start);

function renderNotice({ coverage = '', packageId = '', selected = '', rates = [], chapel = true, savedPricing = null, branch = '1' } = {}) {
    const elements = new Map();
    const element = id => {
        if (!elements.has(id)) elements.set(id, {
            textContent: '', hidden: false, coverageHidden: false,
            dataset: {},
            querySelector: element,
            append(child) { child.parentElement = this; },
            before() {},
            toggleAttribute(name, value) { if (name === 'data-coverage-hidden') this.coverageHidden = value; },
            classList: { remove() {} },
        });
        return elements.get(id);
    };
    const allowance = { add_on_branch_id: 1, add_on_id: 1, add_on_category: 'Chapel Viewing', included_quantity: 2 };
    const packages = [
        { value: 'with', dataset: { name: 'First Class' }, rows: [allowance] },
        { value: 'without', dataset: { name: 'Standard' }, rows: [] },
    ];
    const choices = ['included', 'separate'].map(value => ({ value, checked: value === coverage }));
    const anchor = { after() {} };
    const context = {
        document: { getElementById: element },
        branch: { value: branch }, viewingLocation: {}, chapelAddress: {},
        chapelCoverage: () => choices.find(choice => choice.checked)?.value || '', chapelCoverageChoices: choices,
        pkg: () => packages.find(item => item.value === packageId), pkgRadios: packages, customPkgRadio: {},
        packageModeChooser: element('packageModes'), customPackageArea: element('custom'),
        regularPackageArea: { ...element('regular'), querySelector: () => element('changeType') },
        regularPackageChoice: { options: packages.map(item => ({ value: item.value })), closest: () => anchor },
        chapelChargePanel: element('panel'), chapelChargeControls: element('controls'), chapelSaveDraft: element('draft'),
        chapelRate: { value: selected, options: rates.map(rate => ({ value: String(rate.id), dataset: { branch: rate.branch_id || '1' } })) },
        chapelRateReadout: element('rate'), chapelAddressReadout: element('address'), chapelChargePreview: element('preview'),
        chapelPreviewPanel: Object.assign(element('chargePreviewPanel'), { previousElementSibling: anchor }),
        chapelScheduleBox: element('chapel-schedule-context'), chapelPackageFeedback: element('feedback'),
        wakeStart: new Date(2026, 10, 20), wakeStartPicker: null, wakeStartTime: { value: '08:00' },
        wakeEnd: new Date(2026, 10, 20), wakeEndPicker: null, wakeEndTime: { value: '17:00' },
        getDateValue: field => field, setTimeout() {}, clearTimeout() {},
        globalAddOns: rates, chapelCategory: 'Chapel Viewing',
        structuredInclusionsFor: radio => radio?.rows || [],
        isChapelViewing: () => chapel, inclusiveWakeDays: () => 5, wakeDurationLabel: days => days + ' wake days',
        fmt: value => Number(value).toFixed(2), savedPricing,
    };
    vm.createContext(context);
    vm.runInContext(source.slice(start, end) + '\nrestoredChapelPricing = savedPricing; syncChapelPackageChoices(); this.result = chapelViewingCharge(); this.review = restoredServiceMessage;', context);
    return { ...context, title: element('chapel_notice_title').textContent, notice: element('chapel_charge_notice') };
}

const mainChapel = { id: 1, name: 'Main Chapel', price: 2000, chapel_address: 'Quezon City' };

test('included coverage filters packages and charges only excess days', () => {
    const result = renderNotice({
        coverage: 'included', packageId: 'with', rates: [mainChapel],
        savedPricing: { id: '1', rate: 1500, days: 3 },
    });
    assert.deepEqual(result.regularPackageChoice.options.map(option => option.disabled), [false, true]);
    assert.match(result.regularPackageChoice.options[0].textContent, /Main Chapel · 2 days included/);
    assert.equal(result.document.getElementById('regular_package_heading').textContent, 'Packages with Included Chapel');
    assert.match(result.review, /Saved: ₱1500\.00\/day · 3 included day/);
    assert.match(result.review, /Current: ₱2000\.00\/day · 2 included day/);
    assert.equal(result.result.amount, 6000);
    assert.equal(result.chapelPreviewPanel.hidden, false);
});

test('unselected coverage and separate coverage without a chapel defer package selection', () => {
    assert.equal(renderNotice().packageModeChooser.coverageHidden, true);
    const result = renderNotice({ coverage: 'separate', rates: [mainChapel] });
    assert.equal(result.chapelChargeControls.hidden, false);
    assert.equal(result.packageModeChooser.coverageHidden, true);
    assert.equal(result.result.blocked, true);
});

test('separate coverage excludes packages with branch coverage and charges all wake days', () => {
    const result = renderNotice({ coverage: 'separate', packageId: 'without', selected: '1', rates: [mainChapel] });
    assert.deepEqual(result.regularPackageChoice.options.map(option => option.disabled), [true, false]);
    assert.equal(result.result.amount, 10000);
    const otherBranch = renderNotice({
        coverage: 'separate', packageId: 'with', selected: '2', branch: '2',
        rates: [mainChapel, { ...mainChapel, id: 2, branch_id: '2', price: 0 }],
    });
    assert.equal(otherBranch.regularPackageChoice.options[0].disabled, false);
    assert.equal(otherBranch.result.configured, true);
    assert.equal(otherBranch.result.amount, 0);
});

test('a separately selected chapel is checked beside the picker before a package is chosen', () => {
    const separate = renderNotice({ coverage: 'separate', selected: '1', rates: [mainChapel] });
    assert.equal(separate.chapelPreviewPanel.hidden, true);
    assert.equal(separate.chapelScheduleBox.parentElement, separate.chapelChargeControls);
    assert.equal(separate.chapelScheduleBox.hidden, false);
    assert.equal(separate.chapelScheduleBox.dataset.state, 'checking');

    const included = renderNotice({ coverage: 'included', packageId: 'with', rates: [mainChapel] });
    assert.equal(included.chapelScheduleBox.parentElement, included.chapelPreviewPanel);
    assert.equal(included.chapelScheduleBox.dataset.state, 'checking');
});

test('an unavailable included chapel keeps the draft action separate from Continue', () => {
    const result = renderNotice({ coverage: 'included', packageId: 'with' });
    assert.equal(result.result.blocked, true);
    assert.equal(result.chapelSaveDraft.hidden, false);
    assert.match(result.title, /No usable included chapel/);

    const draftHandler = source.split('\n').find(line => line.includes("chapelSaveDraft.addEventListener('click'"));
    let click;
    let submitted;
    const draft = { id: 'saveIntakeDraft' };
    vm.runInNewContext(draftHandler, {
        chapelSaveDraft: { addEventListener: (_, handler) => { click = handler; } },
        document: { getElementById: () => draft }, f: { requestSubmit: button => { submitted = button; } },
    });
    click();
    assert.equal(submitted, draft);
});

test('a recorded overlap exposes the case and scheduled end until the new schedule is checked', async () => {
    const elements = new Map();
    const element = selector => {
        if (!elements.has(selector)) elements.set(selector, {
            hidden: false, textContent: '', children: [],
            querySelector: element,
            get childElementCount() { return this.children.length; },
            replaceChildren() { this.children = []; },
            append(child) { this.children.push(child); },
        });
        return elements.get(selector);
    };
    const box = { hidden: true, dataset: { url: '/chapel-advisory' }, querySelector: element };
    const wakeStartTime = { value: '08:00' };
    let check;
    let events = [{ case_code: 'FC0012', end: '2026-11-20 10:00' }];
    let availableChapelIds = [];
    let coverage = 'separate';
    const context = {
        document: { getElementById: () => box, createElement: () => ({ dataset: {}, append(...children) { this.children = children; } }) },
        wakeStart: new Date(2026, 10, 20), wakeStartPicker: null, wakeStartTime,
        wakeEnd: new Date(2026, 10, 20), wakeEndPicker: null, wakeEndTime: { value: '17:00' },
        getDateValue: field => field, isChapelViewing: () => true,
        chapelCoverage: () => coverage, chapelRate: { options: [{ value: '1' }, { value: '2', disabled: true }] },
        pkgRadios: [{ value: '9', chapelId: 3 }, { value: '10', chapelId: 1 }],
        regularPackageChoice: { options: [{ value: '9', textContent: 'Premium · East Chapel' }, { value: '10', textContent: 'Main Chapel Package' }] },
        chapelAllowanceFor: radio => ({ add_on_id: radio.chapelId }),
        formatScheduleDateTime: (date, time) => date + ' at ' + time,
        setTimeout: callback => { check = callback; }, clearTimeout() {},
        fetch: async () => ({ ok: true, json: async () => ({ events, available_chapel_ids: availableChapelIds }) }),
        location: { origin: 'http://localhost' }, URL, URLSearchParams, AbortController,
    };
    const schedule = vm.runInNewContext(source.slice(start, source.indexOf('    const chapelAllowanceFor', start)) + '\ncheckChapelSchedule;', context);
    schedule(mainChapel);
    assert.equal(box.dataset.state, 'checking');
    await check();
    assert.equal(box.dataset.state, 'conflict');
    assert.equal(element('[data-chapel-overlaps]').hidden, false);
    const [reference, wakeEnd] = element('[data-chapel-events]').children[0].children;
    assert.equal(reference.textContent, 'Case FC0012');
    assert.match(wakeEnd.textContent, /Scheduled Wake End.*2026-11-20 at 10:00/);
    assert.equal(element('[data-alternative-chapels]').hidden, true);

    context.chapelRate.options.push({ value: '3', textContent: 'East Chapel · ₱2,500 / day' });
    availableChapelIds = [3];
    element('[data-chapel-check-retry]').onclick();
    await check();
    assert.equal(element('[data-alternative-chapels]').hidden, false);
    assert.equal(element('[data-alternative-chapel-options]').children[0].textContent, 'East Chapel · ₱2,500 / day');

    coverage = 'included';
    element('[data-chapel-check-retry]').onclick();
    await check();
    assert.equal(element('[data-alternative-packages]').hidden, false);
    assert.deepEqual(element('[data-alternative-package-options]').children.map(option => option.textContent), ['Premium · East Chapel']);

    wakeStartTime.value = '10:00';
    events = [];
    schedule(mainChapel);
    assert.equal(box.dataset.state, 'checking');
    assert.equal(element('[data-chapel-overlaps]').hidden, true);
    await check();
    assert.equal(box.dataset.state, 'available');
});

test('included asset statuses reflect active and availability independently', () => {
    const start = source.indexOf('    const includedAssetStatus =');
    const end = source.indexOf('    const detailRowsForPackage', start);
    const status = vm.runInNewContext(source.slice(start, end) + '\nincludedAssetStatus;');
    assert.equal(status({ active: true, available: true }), 'Included');
    assert.equal(status({ active: true, available: false }), 'Unavailable');
    assert.equal(status({ active: false, available: false }), 'Archived');
    assert.equal(status({ active: false, available: true }), 'Archived');
    assert.equal(status(null), 'Not configured');
});
