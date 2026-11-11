const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const source=fs.readFileSync('resources/js/form-discard.js','utf8');
const setup=()=>{
 const window={};
 const context=vm.createContext({window,document:{body:{},querySelectorAll:()=>[],addEventListener(){}},MutationObserver:class{observe(){}},FormData:class{constructor(form){this.form=form}entries(){return this.form.values[Symbol.iterator]()}},getComputedStyle:()=>({display:'block'})});
 vm.runInContext(source,context);
 const original=[['name','Saved package'],['included_services[hearse][hearse_catalog_id]','1'],['price','50000']];
 const form={values:original.map(x=>[...x]),resets:0,querySelectorAll:()=>[],reset(){this.resets++;this.values=original.map(x=>[...x])}};
 window.registerDiscardForm(form);
 return {window,form};
};
test('Keep Editing preserves all dirty values and never resets the original form',async()=>{
 const {window,form}=setup();
 form.values=[['name','Unsaved name'],['included_services[hearse][hearse_catalog_id]','2'],['price','invalid']];
 const before=JSON.stringify(form.values);let prompts=0;
 window.confirmFormDiscard=async()=>{prompts++;return false};
 assert.equal(await window.canDiscardForm(form),false);
 assert.equal(JSON.stringify(form.values),before);assert.equal(form.resets,0);assert.equal(prompts,1);
});
test('Discard resets temporary form without submitting or saving',async()=>{
 const {window,form}=setup();form.values[0][1]='Unsaved name';
 window.confirmFormDiscard=async()=>true;
 assert.equal(await window.canDiscardForm(form),true);assert.equal(form.resets,1);
 assert.equal(form.values[0][1],'Saved package');
});
test('Pristine forms close without a confirmation',async()=>{
 const {window,form}=setup();window.confirmFormDiscard=async()=>{throw Error('must not prompt')};
 assert.equal(await window.canDiscardForm(form),true);assert.equal(form.resets,0);
});
