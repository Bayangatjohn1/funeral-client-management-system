const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const source=fs.readFileSync('resources/views/staff/intake/_form.blade.php','utf8');
const start=source.indexOf('    const hearseUpgradeAmount = () => {');
const end=source.indexOf('    const includedAddOnAdjustments =',start);
const fn=source.slice(start,end)+'\nhearseUpgradeAmount();';
test('intake preview matches positive-difference and custom all-in rules',()=>{
 const price=(base,chosen,custom=false,value='2')=>vm.runInNewContext(fn,{isCustomSelected:()=>custom,hearseCatalogSelect:{value,selectedOptions:[{dataset:{referenceValue:chosen}}]},structuredInclusionsFor:()=>[{service_type:'hearse',hearse:{id:1,reference_value:base}}],num:v=>Number(v)||0});
 assert.equal(price(5000,'8000'),3000);
 assert.equal(price(5000,'3000'),0);
 assert.equal(price(5000,'8000',true),0);
 assert.equal(price(null,'8000'),0);
 assert.equal(price(5000,''),0);
 assert.equal(price(5000,'8000',false,''),0);
});
