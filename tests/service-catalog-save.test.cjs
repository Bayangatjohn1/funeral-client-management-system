const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const source=fs.readFileSync('resources/views/admin/service-management/index.blade.php','utf8');
const start=source.indexOf('    window.handleServiceCatalogSaved = async event => {');
const end=source.indexOf('    window.servicesModalMessageBound = true;',start)+'    window.servicesModalMessageBound = true;'.length;
const handler=source.slice(start,end);
test('the catalog frame accepts a package submission that already set aria-busy', () => {
 const layout = fs.readFileSync('resources/views/layouts/modal-frame.blade.php', 'utf8');
 const start = layout.indexOf("        document.addEventListener('submit'");
 const script = layout.slice(start, layout.indexOf('</script>', start));
 let onSubmit;
 const mode = {};
 class HTMLFormElement {
  dataset = {};
  getAttribute() { return 'true'; }
  setAttribute() {}
  querySelector() { return mode; }
 }
 const document = {
  body: { classList: { contains: () => true } },
  addEventListener: (_type, listener) => { onSubmit = listener; },
 };
 vm.runInNewContext(script, { document, HTMLFormElement });
 const form = new HTMLFormElement();
 const submitter = { disabled: true };
 let blocked = 0;
 const submit = () => onSubmit({ target: form, submitter, preventDefault: () => blocked++ });
 submit();
 assert.equal(blocked, 0);
 assert.equal(mode.value, '1');
 submit();
 assert.equal(blocked, 1);
});
test('save after tab replacement refreshes the current hub once',async()=>{
 let listener,oldRefresh=0,currentRefresh=0,closed=0,notices=0;
 const window={location:{origin:'http://localhost'},addEventListener:(type,fn)=>{listener=fn},setTimeout:()=>{}};
 const document={querySelector:()=>null,createElement:()=>({dataset:{},setAttribute(){},replaceChildren(){}}),body:{appendChild(){notices++}}};
 const context=vm.createContext({window,document,modalFrame:{dataset:{}},closeModal:async()=>{closed++}});
 window.refreshServiceCatalogHub=async()=>{oldRefresh++};
 vm.runInContext(handler,context);
 window.refreshServiceCatalogHub=async()=>{currentRefresh++};
 vm.runInContext(handler,context);
 await listener({origin:'http://localhost',data:{type:'service-catalog:saved',message:'Saved'}});
 await Promise.resolve();
 assert.equal(oldRefresh,0);assert.equal(currentRefresh,1);assert.equal(closed,1);assert.equal(notices,1);
 await listener({origin:'http://other-site',data:{type:'service-catalog:saved'}});
 assert.equal(currentRefresh,1);
});
