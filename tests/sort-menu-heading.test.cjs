const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = readFileSync(path.join(__dirname, '../resources/js/app.js'), 'utf8');
const start = source.indexOf('        const optionLabel = (option) =>', source.indexOf('function initFilterSelectAffordances()'));
const end = start + source.slice(start).search(/\r?\n\r?\n        const setOpen =/);
const renderer = source.slice(start, end) + '\nsyncMenu();';

function element(tag) {
    return {
        tag, children: [], attributes: {}, dataset: {}, listeners: {}, classList: { add() {} },
        set innerHTML(value) { this.children = []; },
        appendChild(child) { this.children.push(child); return child; },
        append(...children) { this.children.push(...children); },
        setAttribute(name, value) { this.attributes[name] = value; },
        addEventListener(type, callback) { this.listeners[type] = callback; },
    };
}

for (const wrapper of ['case-compact-sort-filter', 'pm-sort-control', 'worklist-sort-control']) {
    test(`${wrapper} shares one Sort by heading and preserves option values/events`, () => {
        const menu = element('div');
        const select = {
            value: 'latest',
            options: [
                { value: 'latest', textContent: 'Newest first', selected: true, dataset: {} },
                { value: 'name_asc', textContent: 'Name A–Z', selected: false, dataset: {} },
            ],
            hasAttribute: () => false,
            dispatchEvent(event) { events.push(event.type); },
        };
        const events = [];
        const openStates = [];
        const context = {
            menu, select,
            wrap: {
                matches: selector => selector.split(',').some(part => part.trim() === '.' + wrapper),
                closest: selector => selector === '.directory-page' && wrapper === 'worklist-sort-control' ? {} : null,
            },
            document: { createElement: element },
            filterOptionIcon: () => '',
            setOpen: state => openStates.push(state),
            Event: class { constructor(type) { this.type = type; } },
            HTMLDetailsElement: class {}, HTMLElement: class {},
            window: { queueMicrotask: callback => callback() },
        };
        vm.runInNewContext(renderer, context);
        assert.equal(menu.children[0].textContent, 'Sort by');
        assert.deepEqual(menu.children.slice(1).map(item => item.dataset.value), ['latest', 'name_asc']);
        assert.equal(menu.children[1].attributes['aria-selected'], 'true');
        menu.children[2].listeners.click({ preventDefault() {}, stopPropagation() {} });
        assert.equal(select.value, 'name_asc');
        assert.deepEqual(events, ['input', 'change']);
        assert.deepEqual(openStates, [false]);
    });
}
