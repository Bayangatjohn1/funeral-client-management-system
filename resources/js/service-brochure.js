import '../css/service-brochure.css';

const form = document.querySelector('[data-brochure-form]');
const pages = document.querySelector('[data-brochure-pages]');
const source = document.querySelector('[data-brochure-source]');
const printButton = document.querySelector('[data-print-brochure]');
const state = document.querySelector('[data-preview-state]');
let ready = false, dirty = false;

document.querySelector('[data-close-preview]')?.addEventListener('click', event => {
    const returnUrl=event.currentTarget.dataset.returnUrl;
    window.close();
    setTimeout(()=>{if(!window.closed)location.assign(returnUrl);},100);
});
const updateState = () => {
    printButton.disabled = !ready || dirty || !pages?.children.length;
    state.textContent = dirty ? 'Update Preview to apply your selection.' : (ready ? `${document.body.dataset.brochureCount} item(s) · ${pages?.children.length || 0} page(s)` : 'Preparing pages…');
};
printButton?.addEventListener('click', () => { if (ready && !dirty) window.print(); });
const scope = form?.querySelector('[data-print-scope]');
const picker = form?.querySelector('[data-print-picker]');
const search = form?.querySelector('[data-picker-search]');
const clear = form?.querySelector('[data-picker-clear]');
const matches = form?.querySelector('[data-picker-matches]');
const choices = [...(form?.querySelectorAll('[data-picker-item]') || [])];
const counter = form?.querySelector('[data-selected-count]');
let activeMatch = -1;
const hideMatches = () => { matches.hidden = true; search.setAttribute('aria-expanded','false'); activeMatch = -1; };
const mark = (value, needle) => {
    const fragment = document.createDocumentFragment(); let offset=0, index;
    while (needle && (index=value.toLocaleLowerCase().indexOf(needle,offset))!==-1) {
        fragment.append(value.slice(offset,index));
        const marked=document.createElement('mark'); marked.textContent=value.slice(index,index+needle.length); fragment.append(marked); offset=index+needle.length;
    }
    fragment.append(value.slice(offset)); return fragment;
};
const applySearch = () => {
    const needle = search.value.trim().toLocaleLowerCase();
    choices.forEach(choice=>choice.hidden=!choice.dataset.name.toLocaleLowerCase().includes(needle));
    clear.hidden=!needle; hideMatches();
    form.querySelector('[data-picker-empty]').hidden=choices.some(choice=>!choice.hidden);
};
const count = () => { counter.textContent=`${choices.filter(choice=>choice.querySelector('input').checked).length} selected`; };
form?.addEventListener('change', event => {
    if (event.target === scope) picker.hidden=scope.value!=='selected';
    dirty=true; count(); updateState();
});
form?.addEventListener('submit', event => {
    if (scope.value==='selected' && !choices.some(choice=>choice.querySelector('input').checked)) {
        event.preventDefault(); state.textContent='Select at least one item before updating the preview.'; picker.hidden=false;
        choices[0]?.querySelector('input').focus();
    }
});
search?.setAttribute('aria-expanded','false');
search?.addEventListener('input',()=>{
    const needle=search.value.trim().toLocaleLowerCase(); clear.hidden=!needle; activeMatch=-1; matches.replaceChildren();
    if(!needle){hideMatches();return;}
    choices.filter(choice=>choice.dataset.name.toLocaleLowerCase().includes(needle)).slice(0,5).forEach(choice=>{
        const button=document.createElement('button');button.type='button';button.append(mark(choice.dataset.name,needle));
        button.onclick=()=>{search.value=choice.dataset.name;applySearch();choice.querySelector('input').focus();};matches.append(button);
    });
    matches.hidden=!matches.children.length;search.setAttribute('aria-expanded',String(!matches.hidden));
});
search?.addEventListener('keydown', event=>{
    const options=[...matches.children];
    if(event.key==='Escape'){hideMatches();return;}
    if(['ArrowDown','ArrowUp'].includes(event.key)&&options.length){event.preventDefault();activeMatch=(activeMatch+(event.key==='ArrowDown'?1:-1)+options.length)%options.length;options[activeMatch].focus();}
    if(event.key==='Enter'){event.preventDefault();applySearch();}
});
matches?.addEventListener('keydown', event=>{
    const options=[...matches.children];
    if(['ArrowDown','ArrowUp'].includes(event.key)){event.preventDefault();activeMatch=(activeMatch+(event.key==='ArrowDown'?1:-1)+options.length)%options.length;options[activeMatch]?.focus();}
    if(event.key==='Escape'){hideMatches();search.focus();}
});
form?.querySelector('[data-picker-search-button]').addEventListener('click',applySearch);
clear?.addEventListener('click',()=>{search.value='';applySearch();search.focus();});
document.addEventListener('pointerdown',event=>{if(!event.target.closest('.menu-search'))hideMatches();});
form?.querySelector('[data-select-matches]').addEventListener('click',()=>{choices.filter(choice=>!choice.hidden).forEach(choice=>choice.querySelector('input').checked=true);dirty=true;count();updateState();});
form?.querySelector('[data-clear-selection]').addEventListener('click',()=>{choices.forEach(choice=>choice.querySelector('input').checked=false);dirty=true;count();updateState();});

function paginate() {
    if(!pages||!source)return;
    pages.replaceChildren();
    let current, body;
    const newPage = () => {
        current=document.querySelector('[data-page-template]').content.firstElementChild.cloneNode(true);
        if(pages.children.length)current.classList.add('is-continuation');
        pages.append(current);body=current.querySelector('[data-page-content]');
    };
    const fits = () => [...body.children].every(child=>child.getBoundingClientRect().bottom<=body.getBoundingClientRect().bottom+.5);
    const fragment = (original,continued) => {
        const item=original.cloneNode(false),heading=original.querySelector('[data-item-heading]').cloneNode(true);
        if(continued){const note=document.createElement('span');note.className='menu-continuation';note.textContent='continued';heading.querySelector('h3').append(note);}
        const content=document.createElement('div');content.className='menu-item-body';content.dataset.itemBody='';item.append(heading,content);body.append(item);return{item,content};
    };
    const split = original => {
        let continued=false;
        let part=fragment(original,false);
        for(const originalGroup of original.querySelector('[data-item-body]').children){
            const emptyGroup=()=>{const group=originalGroup.cloneNode(true);group.querySelectorAll('[data-print-row]').forEach(row=>row.remove());return group;};
            let group=emptyGroup();part.content.append(group);
            const container=()=>group.querySelector('dl')||group;
            const advance=()=>{
                if(!group.querySelector('[data-print-row]'))group.remove();
                continued ||= !!part.content.querySelector('[data-print-row]');
                if(!part.content.children.length)part.item.remove();
                if(body.children.length)newPage();
                part=fragment(original,continued);group=emptyGroup();part.content.append(group);
            };
            for(const originalRow of originalGroup.querySelectorAll('[data-print-row]')){
                let row=originalRow.cloneNode(true);container().append(row);
                if(fits())continue;
                row.remove();advance();container().append(row);
                if(fits())continue;
                // Split unusually long legacy descriptions without truncating data.
                row.remove();
                const textNode=originalRow.querySelector('dd')||originalRow;
                let tokens=textNode.textContent.match(/\S+\s*/gu)||[''];
                while(tokens.length){
                    let low=1,high=tokens.length,best=0;
                    while(low<=high){const mid=Math.floor((low+high)/2);row=originalRow.cloneNode(true);(row.querySelector('dd')||row).textContent=tokens.slice(0,mid).join('');container().append(row);const ok=fits();row.remove();if(ok){best=mid;low=mid+1;}else high=mid-1;}
                    if(!best){
                        if(tokens[0].length>1){tokens=[...tokens[0].match(/.{1,40}/gu),...tokens.slice(1)];continue;}
                        throw new Error('A line cannot fit on an A4 page.');
                    }
                    row=originalRow.cloneNode(true);(row.querySelector('dd')||row).textContent=tokens.splice(0,best).join('');container().append(row);
                    if(tokens.length)advance();
                }
            }
        }
    };
    newPage();
    for(const original of source.children){
        // Package sheets are independent; long content continues at readable size.
        if(document.body.dataset.brochureTab==='packages' && body.children.length)newPage();
        const item=original.cloneNode(true);body.append(item);
        if(fits())continue;
        item.remove();
        if(body.children.length)newPage();
        body.append(item);
        if(fits())continue;
        item.remove();split(original);
    }
    [...pages.children].filter(page=>!page.querySelector('[data-page-content]').children.length).forEach(page=>page.remove());
    [...pages.children].forEach((page,index)=>page.querySelector('[data-page-number]').textContent=`Page ${index+1} of ${pages.children.length}`);
    pages.setAttribute('aria-busy','false');
}

async function prepare() {
    try {
        await document.fonts.ready;
        await Promise.all([...document.querySelectorAll('img')].map(image => image.decode().catch(() => {
            const gallery = image.closest('.menu-photos');
            if (!gallery) {
                image.hidden = true;
                return;
            }
            image.closest('figure').remove();
            if (!gallery.children.length) gallery.remove();
        })));
        paginate();ready=true;updateState();
    } catch { state.textContent='The preview could not be arranged. Refresh the page and try again.';printButton.disabled=true; }
}
prepare();
