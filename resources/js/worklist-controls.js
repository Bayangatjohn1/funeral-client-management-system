const states=new Map();
const removePages=url=>{[...url.searchParams.keys()].filter(key=>key==='page'||key.endsWith('_page')).forEach(key=>url.searchParams.delete(key));return url;};
function initWorklists(){
    document.querySelectorAll('[data-worklist-consistent][data-record-results-page]').forEach(root=>{
        const form=root.querySelector('[data-worklist-form]');if(!form||states.has(root))return;
        const state={applied:new URLSearchParams(new FormData(form)),version:0};states.set(root,state);
        const input=form.querySelector('[data-worklist-search]'),panel=form.querySelector('[data-worklist-matches]'),clear=form.querySelector('[data-worklist-search-clear]');
        const details=form.querySelector('[data-worklist-filter]');
        const sort=form.querySelector('[data-worklist-sort]'),sortLabel=form.querySelector('[data-worklist-sort-label]');
        const syncSortLabel=()=>{if(sort&&sortLabel)sortLabel.textContent='Sort: '+(sort.selectedOptions[0]?.textContent.trim()||'');};
        const closeFilter=()=>{if(!details)return;details.querySelectorAll('.filter-select-menu').forEach(menu=>{if(menu.matches(':popover-open'))menu.hidePopover();menu.hidden=true;const wrap=menu.closest('[data-filter-select-wrap]');wrap?.classList.remove('is-open');wrap?.querySelector('select')?.setAttribute('aria-expanded','false');});details.open=false;};
        const hide=()=>{clearTimeout(state.timer);state.request?.abort();state.version++;if(panel){panel.hidden=true;panel.replaceChildren();}input?.setAttribute('aria-expanded','false');};
        const urlFor=(query=input?.value.trim()||'')=>{
            const url=new URL(form.action,location.href);url.search=state.applied.toString();
            if(input){if(query)url.searchParams.set('q',query);else url.searchParams.delete('q');}
            if(form.elements.sort)url.searchParams.set('sort',form.elements.sort.value);
            return removePages(url);
        };
        const navigate=url=>{hide();closeFilter();form.querySelector('[data-worklist-date-panel]:popover-open')?.hidePopover();return window.loadRecordResults?.(root,url);};
        const commit=()=>{if(!input)return;input.value=input.value.trim();if(clear)clear.hidden=!input.value;input.focus({preventScroll:true});navigate(urlFor());};
        const records=scope=>[...scope.querySelectorAll('[data-worklist-name]')].map(row=>{let fields=[];try{fields=JSON.parse(row.dataset.worklistFields||'[]');}catch{}return {id:row.dataset.worklistSearchId,name:row.dataset.worklistName,detail:row.dataset.worklistDetail,fields};});
        const underline=(node,value,query)=>{const text=String(value||''),fold=text.toLowerCase();let start=0,index;while((index=fold.indexOf(query,start))!==-1){node.append(document.createTextNode(text.slice(start,index)));const mark=document.createElement('mark');mark.textContent=text.slice(index,index+query.length);node.append(mark);start=index+query.length;}node.append(document.createTextNode(text.slice(start)));};
        const draw=rows=>{
            if(!panel)return;panel.replaceChildren();const query=input.value.trim().toLowerCase(),seen=new Set();if(!query)return;
            rows.filter(row=>row.name&&!seen.has(row.id)&&seen.add(row.id)).slice(0,5).forEach(row=>{
                const button=document.createElement('button');button.type='button';button.className='worklist-match';button.dataset.worklistMatch=row.name;button.setAttribute('role','option');
                const detail=row.fields.find(value=>value!==row.name&&String(value).toLowerCase().includes(query))||row.detail||'';button.title=row.name+(detail?' · '+detail:'');button.setAttribute('aria-label',button.title);
                for(const value of [row.name,...(detail?[detail]:[])]){const span=document.createElement('span');underline(span,value,query);button.append(span);}
                button.addEventListener('click',()=>{input.value=[...row.name].slice(0,input.maxLength>0?input.maxLength:100).join('');commit();});panel.append(button);
            });panel.hidden=!panel.children.length;input.setAttribute('aria-expanded',String(!panel.hidden));
        };
        input?.addEventListener('input',()=>{
            hide();if(clear)clear.hidden=!input.value;const query=input.value.trim();if(query.length<2)return;
            draw(records(root).filter(row=>row.fields.some(value=>String(value).toLowerCase().includes(query.toLowerCase()))));
            const version=state.version;state.timer=setTimeout(async()=>{
                state.request=new AbortController();try{const response=await fetch(urlFor(query),{signal:state.request.signal,credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest',Accept:'text/html'}});if(!response.ok)return;
                    const doc=new DOMParser().parseFromString(await response.text(),'text/html'),next=doc.querySelector('[data-record-results-page="'+root.dataset.recordResultsPage+'"]');
                    if(version===state.version&&root.isConnected&&next)draw(records(next));
                }catch{/* Keep immediate scoped matches when suggestions are unavailable. */}
            },300);
        });
        input?.addEventListener('keydown',event=>{if(event.isComposing)return;if(event.key==='Enter'){event.preventDefault();commit();}else if(event.key==='Escape'){event.preventDefault();hide();}else if(event.key==='ArrowDown'&&!panel.hidden){event.preventDefault();panel.querySelector('button')?.focus();}});
        panel?.addEventListener('keydown',event=>{const options=[...panel.querySelectorAll('button')],index=options.indexOf(document.activeElement);if(event.key==='Enter'){event.preventDefault();options[index]?.click();}else if(event.key==='Escape'){event.preventDefault();hide();input.focus();}else if(['ArrowDown','ArrowUp'].includes(event.key)){event.preventDefault();const next=index+(event.key==='ArrowDown'?1:-1);if(next<0)input.focus();else options[Math.min(next,options.length-1)]?.focus();}});
        form.querySelector('[data-worklist-search-submit]')?.addEventListener('click',commit);
        clear?.addEventListener('click',()=>{input.value='';clear.hidden=true;commit();});
        sort?.addEventListener('change',()=>{syncSortLabel();navigate(urlFor());});
        form.querySelector('[data-worklist-page-size]')?.addEventListener('change',event=>{const url=urlFor();url.searchParams.set('per_page',event.target.value);navigate(url);});
        form.addEventListener('submit',()=>{hide();closeFilter();form.querySelector('[data-worklist-date-panel]:popover-open')?.hidePopover();});
        form.querySelector('[data-worklist-close]')?.addEventListener('click',closeFilter);
        details?.addEventListener('toggle',()=>{if(!details.open)closeFilter();});
        document.addEventListener('click',event=>{if(!root.isConnected)return;if(panel&&!event.target.closest('.worklist-search-control'))hide();if(details?.open&&!details.contains(event.target)&&!event.target.closest('.filter-select-menu,[data-worklist-date-panel]'))closeFilter();});
        form.addEventListener('keydown',event=>{if(event.key==='Escape'&&!form.querySelector('.is-open')){closeFilter();hide();}});
        const dateSelect=form.querySelector('[data-worklist-date-preset]'),datePanel=form.querySelector('[data-worklist-date-panel]');
        let stagedDatePreset=dateSelect?.value;
        state.refresh=()=>{state.applied=new URLSearchParams(new FormData(form));stagedDatePreset=dateSelect?.value;if(clear)clear.hidden=!input.value;syncSortLabel();hide();};
        if(dateSelect&&datePanel){
            const fromField=form.elements[datePanel.dataset.fromName||'date_from'];
            const toField=form.elements[datePanel.dataset.toName||'date_to'];
            const customValue=datePanel.dataset.customValue||'CUSTOM';
            dateSelect.addEventListener('change',()=>{
                const option=dateSelect.selectedOptions[0];
                if(option.value===customValue){
                    datePanel.querySelector('[data-date-from]').value=fromField.value;datePanel.querySelector('[data-date-to]').value=toField.value;
                    window.openReportDatePicker?.(datePanel,dateSelect);return;
                }
                stagedDatePreset=option.value;
                fromField.value=option.dataset.from||'';toField.value=option.dataset.to||'';
            });
            datePanel.querySelector('[data-date-use]').addEventListener('click',()=>{
                const from=datePanel.querySelector('[data-date-from]'),to=datePanel.querySelector('[data-date-to]');from.required=to.required=!datePanel.hasAttribute('data-optional-bounds');to.min=from.value;
                if(!from.reportValidity()||!to.reportValidity())return;
                stagedDatePreset=customValue;
                fromField.value=from.value;toField.value=to.value;datePanel.hidePopover();dateSelect.focus({preventScroll:true});
            });
            datePanel.querySelector('[data-date-cancel]').addEventListener('click',()=>datePanel.hidePopover());
            datePanel.addEventListener('toggle',()=>{
                if(datePanel.matches(':popover-open'))return;
                datePanel.querySelectorAll('input').forEach(input=>input.required=false);
                if(datePanel.hasAttribute('data-optional-bounds'))dateSelect.value=stagedDatePreset;
            });
        }
    });
}
document.addEventListener('record-results:updated',event=>states.get(event.detail.root)?.refresh(event.detail.url||location.href));
document.addEventListener('panel-ui:reset',initWorklists);
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initWorklists,{once:true});else initWorklists();
