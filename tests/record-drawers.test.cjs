const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function fixture() {
    const listeners = {}, dialogListeners = {}, requests = [], classes = new Set();
    const body = { attrs:{}, scrollTop:100, setAttribute(k,v){this.attrs[k]=v;}, removeAttribute(k){delete this.attrs[k];}, replaceChildren(node){this.node=node;} };
    const full = {}, close = {focus(){}};
    const dialog = { dataset:{}, open:false, querySelector:s=>s.includes('body')?body:s.includes('full')?full:close,
        addEventListener:(type,fn)=>dialogListeners[type]=fn, getBoundingClientRect:()=>({left:400,right:960,top:0,bottom:800}) };
    const document = { getElementById:()=>dialog, querySelector:()=>null, body:{classList:{add:c=>classes.add(c),remove:c=>classes.delete(c)}},
        addEventListener:(type,fn)=>listeners[type]=fn, dispatchEvent(){}, importNode:node=>node };
    const window = {location:{origin:'https://app.test'},recordDialogMotion:{open(){dialog.open=true;},close(){dialog.open=false;dialogListeners.close();}}};
    const fetch = (url,options)=>new Promise((resolve,reject)=>requests.push({url,options,resolve,reject}));
    const DOMParser = class { parseFromString(text){return {querySelector:()=>text==='missing'?null:{text}};} };
    vm.runInNewContext(fs.readFileSync('resources/js/record-drawers.js','utf8').replace('export function','function')+'\ninstallRecordDrawers();',
        {document,window,fetch,DOMParser,URL,AbortController,setTimeout,clearTimeout,CustomEvent:class{}});
    function click(href,mods={}) {
        const row = {classes:new Set(),attrs:{},classList:{add(c){row.classes.add(c);},remove(c){row.classes.delete(c);}},setAttribute(k,v){this.attrs[k]=v;},removeAttribute(k){delete this.attrs[k];}};
        const link = {href,attrs:{},closest:()=>row,setAttribute(k,v){this.attrs[k]=v;},isConnected:true,focus(){this.focused=true;}};
        const event = {target:{closest:()=>link},button:0,preventDefault(){this.prevented=true;},stopPropagation(){this.stopped=true;},...mods};
        listeners.click(event);return {link,row,event};
    }
    return {body,full,dialog,dialogListeners,requests,classes,click};
}
const tick = () => new Promise(resolve=>setImmediate(resolve));
const success = (request,text) => request.resolve({ok:true,text:async()=>text});

test('row/eye uses original full-details link, loads summary only, and returns focus on close',async()=>{
    const f=fixture();const {link,row,event}=f.click('https://app.test/funeral-cases/1?return_to=%2Fcases');
    assert.equal(event.prevented,true);assert.equal(event.stopped,true);
    assert.equal(f.dialog.open,true);assert.equal(f.full.href,link.href);
    assert.equal(row.classes.has('is-viewing-record'),true);
    assert.equal(row.attrs['aria-current'],'true');assert.equal(link.attrs['aria-expanded'],'true');
    assert.equal(f.requests[0].url.searchParams.get('drawer_summary'),'1');
    assert.equal(f.requests[0].url.searchParams.get('return_to'),'/cases');
    assert.equal(f.body.scrollTop,0);assert.equal(f.body.attrs['aria-busy'],'true');
    assert.equal(f.body.innerHTML,undefined);
    success(f.requests[0],'first summary');await tick();
    assert.equal(f.body.node.text,'first summary');assert.equal(f.body.attrs['aria-busy'],undefined);
    let prevented=false;f.dialogListeners.cancel({preventDefault(){prevented=true;}});
    assert.equal(prevented,true);assert.equal(link.focused,true);assert.equal(f.classes.size,0);
    assert.equal(row.classes.has('is-viewing-record'),false);
    assert.equal(row.attrs['aria-current'],undefined);assert.equal(link.attrs['aria-expanded'],'false');
});

test('a later record wins and closing prevents stale responses from replacing content',async()=>{
    const f=fixture();const first=f.click('https://app.test/funeral-cases/1');const second=f.click('https://app.test/funeral-cases/2');
    assert.equal(first.row.classes.size,0);assert.equal(first.link.attrs['aria-expanded'],'false');
    assert.equal(second.row.classes.has('is-viewing-record'),true);
    assert.equal(f.requests[0].options.signal.aborted,true);
    success(f.requests[1],'second summary');await tick();success(f.requests[0],'first summary');await tick();
    assert.equal(f.body.node.text,'second summary');
    f.click('https://app.test/funeral-cases/3');f.dialogListeners.close();f.dialog.open=false;
    success(f.requests[2],'closed summary');await tick();
    assert.equal(f.body.node,undefined);assert.equal(f.body.attrs['aria-busy'],undefined);
});

test('failed summary offers retry and keeps the full-details destination',async()=>{
    const f=fixture();f.click('https://app.test/funeral-cases/7');
    f.requests[0].resolve({ok:false});await tick();
    assert.match(f.body.innerHTML,/data-case-summary-retry/);assert.equal(f.full.href,'https://app.test/funeral-cases/7');
    f.dialogListeners.click({target:{closest:s=>s.includes('retry')?{}:null}});
    success(f.requests[1],'retried summary');await tick();assert.equal(f.body.node.text,'retried summary');
});

test('modified clicks keep native navigation and backdrop alone closes the drawer',()=>{
    const f=fixture();const {event}=f.click('https://app.test/funeral-cases/1',{ctrlKey:true});
    assert.equal(event.prevented,undefined);assert.equal(f.requests.length,0);
    f.click('https://app.test/funeral-cases/1');f.dialog.closest=()=>null;
    f.dialogListeners.click({target:f.dialog,clientX:600,clientY:200});assert.equal(f.dialog.open,true);
    f.dialogListeners.click({target:f.dialog,clientX:200,clientY:200});assert.equal(f.dialog.open,false);
});

test('drawer motion is horizontal, reduced motion skips animation, reopening cancels pending close',async()=>{
    for(const reduced of [false,true]) {
        const window={},animations=[];let done;
        const panel={animate(frames,options){const animation={frames,options,finished:new Promise(resolve=>done=resolve),cancel(){done();}};animations.push(animation);return animation;}};
        const dialog={open:false,querySelector:()=>panel,matches:()=>true,showModal(){this.open=true;},close(){this.open=false;}};
        vm.runInNewContext(fs.readFileSync('resources/js/record-dialog-motion.js','utf8').replace('export function','function')+'\ninstallRecordDialogMotion();',{window,setTimeout,clearTimeout,matchMedia:()=>({matches:reduced})});
        window.recordDialogMotion.open(dialog);
        if(reduced){assert.equal(animations.length,0);await window.recordDialogMotion.close(dialog);assert.equal(dialog.open,false);}
        else {
            assert.equal(animations[0].frames[0].transform,'translateX(100%)');
            const pending=window.recordDialogMotion.close(dialog);window.recordDialogMotion.open(dialog);
            assert.equal(await pending,false);assert.equal(dialog.open,true);
            const closing=window.recordDialogMotion.close(dialog);done();await closing;assert.equal(dialog.open,false);
        }
    }
});

test('service drawer keeps its scroll body and original content when the footer is absent',()=>{
    const classes=new Set(),header={},footer=null,content=[{id:'identity'},{id:'pricing'},{id:'inclusions'}];
    let ready,body;
    const card={children:[header,...content],classList:{add:c=>classes.add(c)},querySelector(s){return s.includes('record-service')?body:s.includes('head')?header:footer;}};
    header.after=node=>{body=node;card.children=[header,body];};
    const document={body:{matches:()=>true},addEventListener(type,fn){ready=fn;},querySelectorAll:()=>[card],createElement(){return {children:[],append(child){this.children.push(child);card.children=card.children.filter(node=>node!==child);}};}};
    const script=fs.readFileSync('resources/views/partials/service_detail_drawer_body.blade.php','utf8').replace(/<\/?script>/g,'');
    vm.runInNewContext(script,{document});ready();
    assert.equal(classes.has('record-service-drawer-card'),true);
    assert.deepEqual(body.children,content);assert.deepEqual(card.children,[header,body]);
    ready();assert.deepEqual(body.children,content);
});
