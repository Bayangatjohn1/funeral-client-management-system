const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('resources/js/app.js', 'utf8');
const code = source.slice(source.indexOf('function initRowActionMenus()'), source.indexOf('function initTableToolbarBehavior()'));

function fixture() {
    const listeners = {}, windowListeners = {}, tasks = [], styles = new Map(), classes = new Set();
    let open = false;
    const trigger = { attributes: {}, getBoundingClientRect: () => ({ top:100, bottom:140, right:500 }), setAttribute(k,v) { this.attributes[k]=v; }, closest: () => menu };
    const dropdown = {
        style: { setProperty: (k,v) => styles.set(k,v), removeProperty: k => styles.delete(k) },
        setAttribute(k,v) { this[k]=v; },
        showPopover() { open=true; }, hidePopover() { open=false; }, matches: () => open,
        getBoundingClientRect: () => ({ height:160, width:200 }),
    };
    const menu = { classList: { contains: k => classes.has(k), add: k => classes.add(k), remove: k => classes.delete(k) }, querySelector: selector => selector === '[data-row-menu-trigger]' ? trigger : dropdown };
    const document = { documentElement: { dataset:{} }, querySelectorAll: () => [menu], addEventListener: (type,fn) => (listeners[type] ??= []).push(fn) };
    const window = { innerWidth:800, innerHeight:600, setTimeout: fn => tasks.push(fn), addEventListener: (type,fn) => windowListeners[type]=fn };
    const context = vm.createContext({ document, window });
    vm.runInContext(code+'\ninitRowActionMenus();initRowActionMenus();',context);
    const click = target => listeners.click.forEach(fn => fn({ target }));
    const openMenu = () => click({ closest: s => s === '[data-row-menu-trigger]' ? trigger : null });
    return { listeners,windowListeners,tasks,styles,classes,trigger,dropdown,click,openMenu,isPopoverOpen:()=>open };
}

test('menus escape clipping in place and reset on external scroll/resize', () => {
    const f=fixture();
    assert.equal(f.listeners.click.length,1);
    f.openMenu();
    assert.equal(f.isPopoverOpen(),true);
    assert.equal(f.dropdown.popover,'manual');
    // The inset shorthand resets the legacy last-row bottom anchor.
    assert.equal(f.styles.get('inset'),'auto');
    assert.equal(f.styles.get('top'),'146px');
    f.listeners.scroll[0]({ target:{ closest:()=>f.dropdown } });
    assert.equal(f.isPopoverOpen(),true);
    f.listeners.scroll[0]({ target:{ closest:()=>null } });
    assert.equal(f.isPopoverOpen(),false);
    assert.equal(f.trigger.attributes['aria-expanded'],'false');
    assert.equal(f.styles.size,0);
    f.openMenu();f.windowListeners.resize();
    assert.equal(f.isPopoverOpen(),false);
    f.openMenu();f.listeners['panel-ui:reset'][0]();
    assert.equal(f.isPopoverOpen(),false);
});

test('submit action stays visible through click activation and closes afterwards', () => {
    const f=fixture();f.openMenu();
    const item={};
    f.click({ closest: s => s === '[data-row-menu-item]' ? item : null });
    assert.equal(f.isPopoverOpen(),true);
    assert.equal(f.tasks.length,1);
    f.tasks.shift()();
    assert.equal(f.isPopoverOpen(),false);
    f.openMenu();
    assert.equal(f.isPopoverOpen(),true);
    f.listeners.keydown[0]({key:'Escape'});
    assert.equal(f.isPopoverOpen(),false);
});
