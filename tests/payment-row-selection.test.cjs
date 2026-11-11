const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source=fs.readFileSync('resources/views/staff/payments/history.blade.php','utf8');
const start=source.indexOf('    const setHistoryRowActive =');
const end=source.indexOf('    const setTransactionsModalOpen =',start);

test('Payment row and eye share active/expanded state and both clear on close',()=>{
    class HTMLElement {}
    const row=new HTMLElement(),eye=new HTMLElement(),states=new Set();
    row.attrs={};eye.attrs={};
    row.closest=()=>row;eye.closest=()=>row;
    row.setAttribute=(k,v)=>row.attrs[k]=v;eye.setAttribute=(k,v)=>eye.attrs[k]=v;
    row.classList={toggle(k,v){v?states.add(k):states.delete(k);}};
    row.querySelectorAll=()=>[eye];
    const context={HTMLElement,row,eye};
    vm.createContext(context);
    vm.runInContext(source.slice(start,end)+'\nthis.setActive=setHistoryRowActive;',context);
    for(const trigger of [row,eye]) {
        context.setActive(trigger,true);
        assert.equal(states.has('is-active'),true);
        assert.equal(row.attrs['aria-expanded'],'true');assert.equal(eye.attrs['aria-expanded'],'true');
        context.setActive(trigger,false);
        assert.equal(states.size,0);
        assert.equal(row.attrs['aria-expanded'],'false');assert.equal(eye.attrs['aria-expanded'],'false');
    }
    assert.doesNotThrow(()=>context.setActive(null,false));
    const unrelated=new HTMLElement();unrelated.closest=()=>null;
    assert.doesNotThrow(()=>context.setActive(unrelated,true));
});
