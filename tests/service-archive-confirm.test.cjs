const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');

for(const view of ['grid','table'])test(`Service ${view} archive confirms once and preserves the submitted form`,()=>{
    const listeners={};
    const node=()=>({textContent:'',style:{},disabled:false,addEventListener(type,fn){this[type]=fn;},replaceChildren(){},append(){},before(){}});
    const nodes=Object.fromEntries(['.service-confirm-dialog__icon i','h2','[data-confirm-submit]','[data-confirm-copy]','[data-confirm-affected]','[data-confirm-cancel]','footer'].map(s=>[s,node()]));
    let opened=0,submitted=0,prevented=0;
    const dialog={dataset:{},querySelector:s=>nodes[s],addEventListener(type,fn){listeners[type]=fn;},close(){listeners.close();}};
    const submitter={getAttribute:()=> 'Archive Package',textContent:'Archive'};
    class Form {
        constructor(requiresConfirmation = true){this.dataset={};this.requiresConfirmation=requiresConfirmation;}
        hasAttribute(name){return name==='data-service-status-confirm' && this.requiresConfirmation;}
        closest(selector){if(selector==='[data-service-hub]')return {};if(selector==='tr'&&view==='table')return {querySelector:()=>({textContent:'Table package'})};if(selector==='article'&&view==='grid')return {querySelector:()=>({textContent:'Grid package'})};return null;}
        querySelector(selector){return selector==='input[name="_method"]'?{}:submitter;}
        setAttribute(){}
        requestSubmit(button){assert.equal(button,submitter);const event={target:this,submitter:button,preventDefault(){this.prevented=true;}};listeners.submit(event);assert.equal(event.prevented,undefined);submitted++;}
    }
    const form=new Form();
    const document={querySelector:()=>dialog,createElement:node,addEventListener:(type,fn)=>listeners[type]=fn};
    vm.runInNewContext(fs.readFileSync('resources/js/service-confirm.js','utf8'),{document,HTMLFormElement:Form,window:{recordDialogMotion:{open(){opened++;},close(){}}}});
    listeners.submit({target:new Form(false),preventDefault(){prevented++;}});
    assert.equal(prevented,0);assert.equal(opened,0);
    listeners.submit({target:form,submitter,preventDefault(){prevented++;}});
    assert.equal(prevented,1);assert.equal(opened,1);
    assert.equal(dialog.dataset.confirmTone,'warning');
    assert.match(nodes['[data-confirm-copy]'].textContent,new RegExp(view==='grid'?'Grid package':'Table package'));
    nodes['[data-confirm-submit]'].click({currentTarget:nodes['[data-confirm-submit]']});
    assert.equal(submitted,1);
});
