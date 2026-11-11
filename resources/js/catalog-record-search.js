const bindings = new Map();
let observer;

function initCatalogRecordSearch() {
    for (const [hub, dispose] of bindings) if (!hub.isConnected) { dispose(); bindings.delete(hub); }
    document.querySelectorAll('[data-service-hub]').forEach(hub => {
        if (bindings.has(hub)) return;
        const form=hub.querySelector('[data-service-filter-form]');
        const input=form?.querySelector('[data-service-search]');
        const panel=form?.querySelector('[data-service-search-matches]');
        const clear=form?.querySelector('[data-service-clear-search]');
        const submit=form?.querySelector('[data-service-search-submit]');
        if (!input || !panel || !submit) return;
        const events=new AbortController(), signal=events.signal;
        const wrap=input.closest('.service-record-search');
        let timer, request, version=0;
        const hide=()=>{
            clearTimeout(timer); request?.abort(); version++;
            panel.hidden=true; panel.replaceChildren(); input.setAttribute('aria-expanded','false');
        };
        const syncClear=()=>{ if(clear) clear.hidden=!input.value; };
        const urlFor=query=>{
            const url=new URL(form.action,location.href);url.search=new URLSearchParams(new FormData(form)).toString();
            if(query)url.searchParams.set('q',query);else url.searchParams.delete('q');
            [...url.searchParams.keys()].filter(key=>key==='page'||key.endsWith('_page')).forEach(key=>url.searchParams.delete(key));
            return url;
        };
        const records=root=>[...root.querySelectorAll('.service-hub-panel.is-active [data-catalog-search-name]')].map(row=>{
            let fields=[];try{fields=JSON.parse(row.dataset.catalogSearchFields||'[]');}catch{}
            return {id:row.dataset.catalogSearchId,name:row.dataset.catalogSearchName,fields:fields.map(String)};
        });
        const underline=(span,value,query)=>{
            const text=String(value),folded=text.toLowerCase();let start=0,index;
            while((index=folded.indexOf(query,start))!==-1){
                span.append(document.createTextNode(text.slice(start,index)));
                const mark=document.createElement('mark');mark.textContent=text.slice(index,index+query.length);span.append(mark);start=index+query.length;
            }
            span.append(document.createTextNode(text.slice(start)));
        };
        const draw=rows=>{
            panel.replaceChildren();const seen=new Set(),query=input.value.trim().toLowerCase();
            if(!query)return;
            rows.filter(row=>row.name&&!seen.has(row.id)&&seen.add(row.id)).slice(0,5).forEach(row=>{
                const detail=row.fields.find(field=>field!==row.name&&field.toLowerCase().includes(query))||row.fields.find(field=>field!==row.name)||'';
                const option=document.createElement('button');option.type='button';option.className='service-search-match';option.setAttribute('role','option');option.setAttribute('aria-selected','false');option.dataset.catalogMatchValue=row.name;
                option.setAttribute('aria-label',row.name+(detail?' · '+detail:''));option.title=option.getAttribute('aria-label');
                const name=document.createElement('span');name.className='service-match-name';underline(name,row.name,query);option.append(name);
                if(detail){const context=document.createElement('span');context.className='service-match-detail';const text=document.createElement('span');underline(text,detail,query);context.append(text);option.append(context);}
                option.addEventListener('click',()=>{input.value=[...row.name].slice(0,input.maxLength>0?input.maxLength:100).join('');hide();syncClear();form.requestSubmit(submit);},{signal});panel.append(option);
            });
            panel.hidden=!panel.children.length;input.setAttribute('aria-expanded',String(!panel.hidden));
        };
        const suggest=()=>{
            hide();syncClear();const query=input.value.trim();if(query.length<2)return;
            const folded=query.toLowerCase();draw(records(hub).filter(row=>row.fields.some(field=>field.toLowerCase().includes(folded))));
            const current=version;
            timer=setTimeout(async()=>{
                request=new AbortController();
                try{
                    const response=await fetch(urlFor(query),{signal:request.signal,credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest',Accept:'text/html'}});
                    if(!response.ok)return;
                    const doc=new DOMParser().parseFromString(await response.text(),'text/html');
                    const next=doc.querySelector('[data-service-hub]');
                    if(current!==version||!hub.isConnected||!next||next.dataset.activeTab!==hub.dataset.activeTab)return;
                    draw(records(next));
                }catch{ /* Preserve loaded matches when a suggestion request fails. */ }
            },300);
        };
        input.addEventListener('input',suggest,{signal});
        input.addEventListener('keydown',event=>{
            if(event.isComposing)return;
            if(event.key==='Enter'){event.preventDefault();input.value=input.value.trim();hide();syncClear();form.requestSubmit(submit);}
            else if(event.key==='Escape'){event.preventDefault();hide();}
            else if(['ArrowDown','ArrowUp'].includes(event.key)&&!panel.hidden){event.preventDefault();const choices=panel.querySelectorAll('button');choices[event.key==='ArrowDown'?0:choices.length-1]?.focus();}
        },{signal});
        panel.addEventListener('keydown',event=>{
            const options=[...panel.querySelectorAll('button')],index=options.indexOf(document.activeElement);
            if(event.key==='Escape'){event.preventDefault();hide();input.focus();}
            else if(event.key==='Enter'){event.preventDefault();options[index]?.click();}
            else if(['ArrowDown','ArrowUp'].includes(event.key)){event.preventDefault();const next=index+(event.key==='ArrowDown'?1:-1);if(next<0)input.focus();else options[Math.min(next,options.length-1)]?.focus();}
        },{signal});
        clear?.addEventListener('click',()=>{input.value='';hide();syncClear();input.focus();form.requestSubmit(submit);},{signal});
        form.addEventListener('submit',event=>{input.value=input.value.trim();hide();syncClear();if(event.submitter===submit)input.focus({preventScroll:true});},{capture:true,signal});
        hub.addEventListener('catalog-search:cancel',hide,{signal});
        document.addEventListener('click',event=>{if(!wrap.contains(event.target))hide();},{signal});
        wrap.addEventListener('focusout',()=>setTimeout(()=>{if(!wrap.matches(':focus-within'))hide();},0),{signal});
        bindings.set(hub,()=>{hide();events.abort();});syncClear();
    });
    if(bindings.size&&!observer){observer=new MutationObserver(()=>{for(const [hub,dispose] of bindings)if(!hub.isConnected){dispose();bindings.delete(hub);}});observer.observe(document.body,{childList:true,subtree:true});}
}

if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initCatalogRecordSearch,{once:true});
else initCatalogRecordSearch();
document.addEventListener('panel-ui:reset',initCatalogRecordSearch);
