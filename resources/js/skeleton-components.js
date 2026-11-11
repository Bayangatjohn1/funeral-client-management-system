const visible = node => node.getClientRects().length && !node.closest('.panel-skeleton, [hidden]') && getComputedStyle(node).visibility !== 'hidden';
export function skeletonShape(kind = 'line', width = '75%') {
    const node = document.createElement('span');
    node.className = `panel-skeleton-bar panel-skeleton-${kind}`;
    node.dataset.skeletonShape = kind; node.style.width = width;
    return node;
}
function component(kind) {
    const node = document.createElement('div'); node.className = `panel-skeleton-component is-${kind}`;
    node.dataset.skeletonComponent = kind; return node;
}
function metrics(count) {
    const grid = component('metrics');
    for (let i = 0; i < count; i++) {
        const card = component('metric'); card.append(skeletonShape('square','32px'),skeletonShape('line','64%'),skeletonShape('value','42%'),skeletonShape('line','76%')); grid.append(card);
    }
    return grid;
}
function tableTemplate(columns, actions = 3) {
    const table = component('table');
    for (let row = 0; row < 6; row++) {
        const line = component(row ? 'data-row' : 'heading-row');
        line.style.gridTemplateColumns = `repeat(${columns.length},minmax(85px,1fr))`;
        columns.forEach((kind, index) => {
            const cell = component('cell');
            if (!row) cell.append(skeletonShape('line','62%'));
            else if (kind === 'badge') cell.append(skeletonShape('pill','64px'));
            else if (kind === 'actions') { cell.classList.add('is-action-set'); for (let i=0;i<actions;i++) cell.append(skeletonShape('square','16px')); }
            else {
                if (kind === 'avatar') cell.append(skeletonShape('circle','32px'));
                const copy = component('copy'); copy.append(skeletonShape('line', index % 2 ? '84%' : '66%'), skeletonShape('line', index % 2 ? '56%' : '42%')); cell.append(copy);
            }
            line.append(cell);
        });
        table.append(line);
    }
    return table;
}
export function renderSkeletonTemplate(surface, profile, { header = true } = {}) {
    const layout = component('page'); layout.setAttribute('aria-hidden','true');
    if (header) { const heading=component('header'); heading.append(skeletonShape('title','32%'),skeletonShape('line','56%')); layout.append(heading); }
    if (profile.tabs) { const tabs=component('tabs'); for(let i=0;i<3;i++) tabs.append(skeletonShape('pill','80px')); layout.append(tabs); }
    if (profile.sectionIntro) {
        const intro=component('section-intro');
        const copy=component('copy'); copy.append(skeletonShape('title','38%'),skeletonShape('line','72%'));
        intro.append(copy,skeletonShape('field','132px')); layout.append(intro);
    }
    const toolbar = profile.kind !== 'detail' && profile.kind !== 'form' && profile.toolbar !== false ? component('toolbar') : null;
    if (toolbar) toolbar.append(skeletonShape('field','58%'),skeletonShape('field','18%'),skeletonShape('field','18%'));
    const metricGrid = profile.metrics ? metrics(profile.metrics) : null;
    if (profile.metricsFirst) {
        if (metricGrid) layout.append(metricGrid);
        if (profile.notice) { const notice=component('notice'); notice.append(skeletonShape('square','28px'),skeletonShape('line','78%')); layout.append(notice); }
        if (toolbar) layout.append(toolbar);
    } else {
        if (toolbar) layout.append(toolbar);
        if (metricGrid) layout.append(metricGrid);
    }
    if (profile.kind === 'schedule') {
        const calendar = component(profile.view === 'week' ? 'calendar-week' : 'calendar-agenda');
        for (let row=0;row<6;row++) {
            const slot=component('calendar-slot');
            slot.style.gridTemplateColumns = profile.view === 'week' ? '60px repeat(7,minmax(65px,1fr))' : '70px minmax(0,1fr)';
            const cells=profile.view === 'week' ? 8 : 2;
            for(let col=0;col<cells;col++) { const cell=component('calendar-cell'); cell.append(skeletonShape(col === 0 || row === 0 ? 'line' : 'field',col === 0 ? '72%' : '86%')); slot.append(cell); }
            calendar.append(slot);
        }
        layout.append(calendar);
    }
    else if (profile.kind === 'table') layout.append(tableTemplate(profile.columns,profile.actions));
    else if (profile.kind === 'list') {
        const list=component('list');
        for(let i=0;i<5;i++) {
            const row=component('list-row');
            const widths = ['68%','86%','72%','64%','58%'].slice(0,profile.listColumns || 5);
            row.style.gridTemplateColumns = widths.length === 3 ? '1.15fr 1fr 96px' : '1fr 2fr 1.5fr 80px 32px';
            for(const [index,width] of widths.entries()) {
                if (index === widths.length - 2 && widths.length > 3) row.append(skeletonShape('pill','64px'));
                else if (index === widths.length - 1 && widths.length > 3) row.append(skeletonShape('square','18px'));
                else { const copy=component('copy'); copy.append(skeletonShape('line',width),skeletonShape('line','48%')); row.append(copy); }
            }
            list.append(row);
        }
        layout.append(list);
    }
    else if (profile.kind === 'catalog' || profile.kind === 'directory') {
        const grid=component('item-grid');
        for(let i=0;i<6;i++) {
            const item=component('item'); item.append(skeletonShape(profile.avatar ? 'circle' : 'square','40px'),skeletonShape('pill','64px'),skeletonShape('title',i%2?'68%':'82%'));
            if (profile.catalog === 'promotions') {
                const details=component('details');
                for(let field=0;field<4;field++) { const copy=component('copy');copy.append(skeletonShape('line','56%'),skeletonShape('line','82%'));details.append(copy); }
                const actions=component('toolbar'); for(let action=0;action<3;action++)actions.append(skeletonShape('square','18px'));
                item.append(details,actions);
            } else item.append(skeletonShape('line','88%'),skeletonShape('line','62%'),skeletonShape('line','74%'));
            grid.append(item);
        }
        layout.append(grid);
    } else if (profile.kind === 'dashboard' || profile.kind === 'analytics') {
        const panels=component('panels');
        for(let i=0;i<2;i++) {
            const panel=component('chart-panel'); panel.append(skeletonShape('title','40%'));
            if(profile.sections === 'access') panel.append(metrics(i ? 5 : 4));
            else { const chart=component('chart'); [50,78,60,90,65,80].forEach(height=>{ const column=skeletonShape('chart-bar','12%'); column.style.height=`${height}%`; chart.append(column); }); panel.append(chart); }
            panels.append(panel);
        }
        layout.append(panels);
    } else {
        const fields=component(profile.kind === 'form' ? 'fields' : 'details');
        for(let i=0;i<6;i++) { const field=component('field-group'); field.append(skeletonShape('line','35%'),skeletonShape(profile.kind === 'form' ? 'field' : 'line', i%2?'72%':'90%')); fields.append(field); }
        layout.append(fields);
    }
    surface.append(layout);
}

// Measure presentation geometry only; no names/values/HTML are copied into the
// skeleton. Retaining underlying content preserves cancellation/error recovery.
export function renderMeasuredSkeleton(target, surface) {
    const origin = target.getBoundingClientRect();
    const layer = component('projection'); layer.setAttribute('aria-hidden','true');
    let count = 0;
    const mask = (node, {card = false} = {}) => {
        const rect = node.getBoundingClientRect();
        const style = getComputedStyle(node);
        const cover = component('mask');
        Object.assign(cover.style,{position:'absolute',left:`${rect.left-origin.left-target.clientLeft}px`,top:`${rect.top-origin.top-target.clientTop}px`,width:`${rect.width}px`,height:`${rect.height}px`,borderRadius:card ? style.borderRadius : '0'});
        if (card) {
            if (style.backgroundColor !== 'rgba(0, 0, 0, 0)' && style.backgroundColor !== 'transparent') cover.style.backgroundColor = style.backgroundColor;
            cover.style.border = `${style.borderTopWidth} ${style.borderTopStyle} ${style.borderTopColor}`;
        }
        layer.append(cover);
    };
    const add = (rect, kind, radius = null) => {
        if (!rect.width || !rect.height || rect.bottom < origin.top || rect.top > origin.bottom || count++ > 350) return;
        const shape=skeletonShape(kind,`${rect.width}px`);
        Object.assign(shape.style,{position:'absolute',left:`${rect.left-origin.left-target.clientLeft}px`,top:`${rect.top-origin.top-target.clientTop}px`,height:`${rect.height}px`});
        if(radius) shape.style.borderRadius=radius;
        layer.append(shape);
    };
    const text = node => {
        if (!visible(node)) return;
        const walker=document.createTreeWalker(node,NodeFilter.SHOW_TEXT);
        let part;
        while((part=walker.nextNode())) {
            if(!part.textContent.trim() || part.parentElement.closest('button,i,svg,[aria-hidden="true"],.status-badge,span[class*="status"]:not([data-case-status]),[class*="avatar"],.case-record-icon-action')) continue;
            const range=document.createRange(); range.selectNodeContents(part);
            for(const box of range.getClientRects()) add({left:box.left,top:box.top+box.height*.2,width:box.width,height:Math.max(8,box.height*.6),bottom:box.bottom},'line');
        }
    };
    const table=[...target.querySelectorAll('table')].find(visible);
    if(table) {
        // Headers are stable context during result refreshes. Only cover data.
        for(const body of table.tBodies) if(visible(body)) mask(body);
        for(const cell of table.querySelectorAll('tbody :is(th,td)')) {
            if(!visible(cell)) continue;
            text(cell);
            for(const special of cell.querySelectorAll('.status-badge,.badge,.service-status,[class*="avatar"],[class*="status-dot"],img,.case-record-icon-action > i,.service-catalog-actions .row-hover-action > i')) {
                if(!visible(special)) continue;
                const kind=special.matches('[class*="avatar"],[class*="status-dot"]')?'circle':special.matches('.status-badge,.badge,.service-status')?'pill':'square';
                add(special.getBoundingClientRect(),kind,getComputedStyle(special).borderRadius);
            }
        }
    }
    const containers=target.querySelectorAll('[data-skeleton-empty],[data-skeleton-item],.service-package-card,.directory-item-card,.branch-reference-card,.branch-card,.branch-kpi-card,.user-directory-card,.user-directory-item,.user-directory-stat,.staff-summary-card,.oe-kpi,.sa-metric,.admin-kpi-card,.ba-kpi-card,.pm-case-item,.ops-list-row');
    for(const item of containers) {
        if(!visible(item) || item.closest('table')) continue;
        mask(item,{card:true});
        text(item);
        if (item.matches('[data-skeleton-empty]')) {
            const icon = item.querySelector('.directory-empty-icon,.service-hub-empty > i');
            if (icon && visible(icon)) add(icon.getBoundingClientRect(),'circle');
            for (const action of item.querySelectorAll('button,a')) if(visible(action)) add(action.getBoundingClientRect(),'field',getComputedStyle(action).borderRadius);
            continue;
        }
        for(const icon of item.querySelectorAll('img,svg,i.bi,[class*="avatar"],.status-badge,.badge,span[class*="status"]')) {
            if(!visible(icon)) continue;
            add(icon.getBoundingClientRect(),icon.matches('.status-badge,.badge,span[class*="status"]')?'pill':icon.matches('[class*="avatar"],img')?'circle':'square',getComputedStyle(icon).borderRadius);
        }
    }
    for(const heading of target.querySelectorAll('h1,h2,h3,p[data-skeleton-description]')) {
        if(visible(heading) && !heading.closest('table') && ![...containers].some(item=>item.contains(heading))) { mask(heading); text(heading); }
    }
    for(const chart of target.querySelectorAll('canvas,.oe-chart,.sa-chart,.chart-container')) if(visible(chart)) { mask(chart); add(chart.getBoundingClientRect(),'chart-surface','8px'); }
    if (!layer.children.length) return false;
    surface.append(layer); return true;
}
