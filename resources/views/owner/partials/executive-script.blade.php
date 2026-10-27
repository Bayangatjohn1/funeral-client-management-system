<script>
(() => {
    const root = document.querySelector('[data-owner-executive]');
    if (!root) return;
    const form = root.querySelector('[data-owner-overview-filter]');
    const submitFilters = () => {
        ['date_from','date_to'].forEach(name => { form.elements[name].disabled = form.elements.range.value !== 'CUSTOM'; });
        form.requestSubmit();
    };
    const dates = document.getElementById('ownerExecutiveDates');
    const details = document.getElementById('ownerExecutiveDetails');
    const results = details.querySelector('[data-details-results]');
    const destination = details.querySelector('[data-summary-destination]');
    let trigger = null, request = null, currentKind = '', pointFrom = '', pointTo = '', hideAmounts = false;
    try { hideAmounts = sessionStorage.getItem('owner-dashboard-hide-amounts') === 'true'; } catch (_) {}
    const syncAmounts = () => {
        const amountToggle = root.querySelector('[data-hide-amounts]');
        amountToggle.title = hideAmounts ? 'Show monetary amounts on this dashboard' : 'Hide monetary amounts on this dashboard';
        amountToggle.querySelector('i').className = hideAmounts ? 'bi bi-eye' : 'bi bi-eye-slash';
        document.querySelectorAll('.oe-amount').forEach(element => {
            if (!element.dataset.originalAmount) element.dataset.originalAmount = element.textContent;
            element.textContent = hideAmounts ? '••••' : element.dataset.originalAmount;
        });
        root.querySelectorAll('.oe-money-bar').forEach(bar => {
            if (!bar.dataset.originalLabel) bar.dataset.originalLabel = bar.getAttribute('aria-label');
            bar.setAttribute('aria-label', hideAmounts ? (bar.dataset.periodLabel || 'Period') + ': amount hidden; view summary' : bar.dataset.originalLabel);
        });
    };
    const open = (dialog, button) => {
        trigger = button;
        dialog.showModal();
        document.body.classList.add('owner-executive-dialog-open');
    };
    [dates, details].forEach(dialog => {
        dialog.addEventListener('close', () => {
            document.body.classList.remove('owner-executive-dialog-open');
            if (dialog === details) { request?.abort(); request = null; }
            if (dialog === dates) {
                form.elements.range.value = root.dataset.range;
                const draft = dates.querySelector('form');
                draft.reset(); draft.elements.to.min = draft.elements.from.value; dates.querySelector('.oe-date-error').hidden = true;
            }
            trigger?.focus();
        });
        dialog.addEventListener('click', event => {
            if (event.target !== dialog) return;
            const r = dialog.getBoundingClientRect();
            if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) dialog.close();
        });
    });
    dates.querySelectorAll('[data-close-dates]').forEach(button => button.addEventListener('click', () => dates.close()));
    details.querySelectorAll('[data-close-details]').forEach(button => button.addEventListener('click', () => details.close()));
    form.querySelectorAll('select').forEach(select => select.addEventListener('change', () => {
        if (select.name === 'range' && select.value === 'CUSTOM') {
            open(dates, select); dates.querySelector('input').focus();
        } else submitFilters();
    }));
    const custom = dates.querySelector('form');
    const dateError = dates.querySelector('.oe-date-error');
    custom.addEventListener('invalid', event => {
        dateError.hidden = false;
        dateError.textContent = event.target.validity.rangeUnderflow ? 'End date must be on or after the start date.' : 'Enter both a valid start date and end date.';
    },true);
    custom.addEventListener('input', () => { dateError.hidden = true; });
    custom.elements.from.addEventListener('change', () => { custom.elements.to.min = custom.elements.from.value; });
    custom.addEventListener('submit', event => {
        event.preventDefault();
        if (!custom.reportValidity()) return;
        form.elements.range.value = 'CUSTOM';
        form.elements.date_from.value = custom.elements.from.value;
        form.elements.date_to.value = custom.elements.to.value;
        submitFilters();
    });
    root.querySelector('[data-hide-amounts]').addEventListener('click', event => {
        hideAmounts = !hideAmounts;
        event.currentTarget.setAttribute('aria-pressed', String(hideAmounts));
        event.currentTarget.querySelector('span').textContent = hideAmounts ? 'Show amounts' : 'Hide amounts';
        try { sessionStorage.setItem('owner-dashboard-hide-amounts', String(hideAmounts)); } catch (_) {}
        syncAmounts();
    });
    root.querySelector('[data-hide-amounts]').setAttribute('aria-pressed', String(hideAmounts));
    root.querySelector('[data-hide-amounts] span').textContent = hideAmounts ? 'Show amounts' : 'Hide amounts';
    syncAmounts();
    root.querySelectorAll('[data-trend]').forEach(button => button.addEventListener('click', () => {
        root.querySelectorAll('[data-trend]').forEach(other => other.setAttribute('aria-pressed', String(other === button)));
        root.querySelectorAll('[data-trend-panel]').forEach(panel => { panel.hidden = panel.dataset.trendPanel !== button.dataset.trend; });
    }));
    const load = async () => {
        request?.abort(); const controller = new AbortController(); request = controller;
        const url = new URL(root.dataset.detailsUrl, location.origin);
        ['branch','range','from','to'].forEach((key,i)=> { if(root.dataset[key]) url.searchParams.set(['branch_id','range','date_from','date_to'][i],root.dataset[key]); });
        url.searchParams.set('kind',currentKind); url.searchParams.set('summary','1');
        url.searchParams.set('dashboard_range',root.dataset.range); url.searchParams.set('dashboard_from',root.dataset.from); url.searchParams.set('dashboard_to',root.dataset.to);
        if(pointFrom) { url.searchParams.set('range','CUSTOM'); url.searchParams.set('date_from',pointFrom); url.searchParams.set('date_to',pointTo); }
        results.textContent = 'Loading summary…'; destination.hidden=true; results.setAttribute('aria-busy','true');
        try {
            const response = await fetch(url,{headers:{Accept:'application/json'},signal:controller.signal});
            if(!response.ok || response.redirected) throw new Error('Unable to load');
            const data = await response.json(); if(request!==controller || !details.open) return;
            results.innerHTML=data.html;
            destination.href=results.querySelector('[data-records-url]').dataset.recordsUrl;
            destination.textContent=currentKind==='collections'?'View Collection Report':'View Case Monitoring Report'; destination.hidden=false;
            syncAmounts();
        } catch(error) {
            if(error.name!=='AbortError' && request===controller) {
                results.textContent='Unable to load this summary. Please retry.';
                const retry=document.createElement('button'); retry.type='button'; retry.className='oe-button'; retry.textContent='Retry'; retry.addEventListener('click',load); results.appendChild(retry);
            }
        } finally { if(request===controller) results.removeAttribute('aria-busy'); }
    };
    root.querySelectorAll('[data-drilldown]').forEach(button=>button.addEventListener('click',event=>{
        if(button.getAttribute('aria-disabled')==='true') return;
        currentKind=button.dataset.drilldown; pointFrom=button.dataset.pointFrom || ''; pointTo=button.dataset.pointTo || '';
        details.querySelector('h2').textContent=button.dataset.title;
        details.querySelector('[data-summary-icon]').className='bi bi-'+({cases:'file-earmark-text',service:'cash-stack',collections:'wallet2',outstanding:'hourglass-split',risks:'exclamation-triangle'}[currentKind])+' oe-title-icon';
        details.querySelector('[data-details-context]').textContent=root.dataset.scope+'\n'+(['outstanding','risks'].includes(currentKind)?'All dates · Current balances':(pointFrom?pointFrom+' – '+pointTo:root.dataset.period)+' · '+(currentKind==='collections'?'Payment date':'Recording date'));
        open(details,button); details.querySelector('[aria-label="Close summary"]').focus(); load();
    }));
    const loadComparison = async () => {
        if(!root.dataset.comparisonUrl) return;
        const statuses=root.querySelectorAll('[data-comparison-status]');
        try {
            const response=await fetch(root.dataset.comparisonUrl,{headers:{'X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});
            if(!response.ok || response.redirected) throw new Error('Comparison unavailable');
            const previousPage=new DOMParser().parseFromString(await response.text(),'text/html');
            if(!previousPage.querySelector('[data-owner-executive]')) throw new Error('Comparison unavailable');
            root.querySelectorAll('[data-trend-panel]').forEach(panel=>{
                const measure=panel.dataset.trendPanel;
                const currentBars=[...panel.querySelectorAll('.oe-bar-item')];
                const previousBars=JSON.parse(previousPage.querySelector('[data-trend-panel="'+measure+'"]').dataset.chartSeries);
                if(!currentBars.length) return;
                const peak=Math.max(...currentBars.map(b=>Number(b.dataset.pointValue)),...previousBars.map(b=>Number(b[measure])),1);
                const base=Math.pow(10,Math.floor(Math.log10(peak/4)));
                let step=[1,2,5,10].find(n=>n>=peak/4/base)*base;
                if(measure==='cases') step=Math.max(1,Math.ceil(step));
                const scale=step*4;
                panel.querySelectorAll('.oe-y-axis > span').forEach((tick,index)=>{tick.textContent=(measure==='collections'?'₱':'')+(step*(4-index)).toLocaleString('en-PH',{maximumFractionDigits:2});delete tick.dataset.originalAmount;});
                currentBars.forEach((bar,index)=>{
                    const previous=previousBars[index];
                    const currentValue=Number(bar.dataset.pointValue);
                    bar.querySelector('.oe-bar-fill').style.height=currentValue/scale*100+'%';
                    const previousFill=bar.querySelector('.oe-bar-previous');
                    if(!previous) { previousFill.hidden=true; return; }
                    const previousValue=Number(previous[measure]);
                    previousFill.hidden=false;
                    previousFill.style.height=previousValue/scale*100+'%';
                    previousFill.dataset.pointFrom=previous.from; previousFill.dataset.pointTo=previous.to;
                    previousFill.dataset.periodLabel='Previous period · '+previous.label;
                    previousFill.dataset.title=(measure==='collections'?'Collections Received':'Cases Recorded')+' · Previous period · '+previous.label;
                    previousFill.disabled=previousValue<=0;
                    const formatted=(measure==='collections'?'₱':'')+previousValue.toLocaleString('en-PH',{minimumFractionDigits:measure==='collections'?2:0});
                    const tooltip=previousFill.querySelector('.oe-bar-tooltip'); tooltip.replaceChildren();
                    const date=document.createElement('span');date.textContent='Previous period · '+previous.label;
                    const amount=document.createElement('strong'); amount.className=measure==='collections'?'oe-amount':'';amount.textContent=formatted;
                    tooltip.append(date,amount);
                    const label='Previous period · '+previous.label+': '+formatted+'; view summary';
                    previousFill.dataset.originalLabel=label; previousFill.setAttribute('aria-label',label);
                });
            });
            statuses.forEach(status=>{status.textContent='Select a bar for details.';});
            syncAmounts();
        } catch(_) { statuses.forEach(status=>{status.textContent='Previous-period bars unavailable. Period totals are shown above.';}); }
    };
    loadComparison();
    const branchChart = root.querySelector('[data-branch-chart]');
    const rankingSelect = root.querySelector('[data-branch-measure]');
    const rankBranches = () => {
        const metric = rankingSelect.value;
        const isRate = metric === 'rate';
        const label = rankingSelect.options[rankingSelect.selectedIndex].text;
        const score = row => row.dataset[metric] === '' ? -1 : Number(row.dataset[metric]);
        const rows = [...branchChart.querySelectorAll('[data-branch-row]')].sort((a,b) => score(b)-score(a) || a.dataset.code.localeCompare(b.dataset.code));
        const highest = Math.max(0,...rows.map(row=>score(row)));
        const formatMoney = value => '₱'+Number(value).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
        const percent = value => value.toLocaleString('en-PH',{maximumFractionDigits:1})+'%';
        const activeBranches = rows.filter(row=>Number(row.dataset.cases)>0).length;
        branchChart.classList.toggle('is-single-result',activeBranches <= 1);
        root.querySelector('[data-ranking-description]').textContent = isRate ? 'How much of each branch’s service value has been collected?' : metric==='cases' ? 'Which branch recorded the most cases?' : 'Which branch generated the most service value?';
        root.querySelector('[data-ranking-scope]').textContent = root.dataset.branch ? 'Selected branch - '+root.dataset.period : activeBranches+' of '+rows.length+' branches recorded cases - '+root.dataset.period;
        root.querySelector('[data-ranking-footnote]').textContent = isRate
            ? 'Collection rate = collected / total service value for these cases. Payments are counted through today. Dark: collected; light: remaining.'
            : 'Highest to lowest. Bars are relative to the leading branch, not a target or collection percentage. Branches without cases are unranked.';
        rows.forEach(row => {
            const value = score(row);
            const hasCases = Number(row.dataset.cases) > 0;
            const hasValue = hasCases && value >= 0;
            const rank = rows.filter(other=>Number(other.dataset.cases)>0 && score(other)>value).length+1;
            const isTop = !root.dataset.branch && value > 0 && rank === 1;
            row.querySelector('[data-branch-rank]').textContent = root.dataset.branch || !hasValue ? '—' : 'Rank '+rank;
            row.classList.toggle('is-top', isTop);
            row.classList.toggle('is-zero', value <= 0 && !isRate);
            row.querySelector('[data-branch-metric-label]').textContent = label;
            const displayed = row.querySelector('[data-branch-value]');
            const text = !hasCases ? '-' : !hasValue ? 'N/A' : isRate ? percent(value) : metric==='cases' ? value.toLocaleString('en-PH') : formatMoney(value);
            displayed.classList.toggle('oe-amount',metric==='service'); displayed.dataset.originalAmount = text; displayed.textContent=text;
            const share = isRate ? Math.max(0,Math.min(100,value)) : highest > 0 ? value/highest*100 : 0;
            const track = row.querySelector('.oe-horizontal-track');
            track.classList.toggle('is-stacked', isRate && hasValue);
            track.hidden = !hasValue;
            track.style.visibility = hasValue ? 'visible' : 'hidden';
            row.querySelector('[data-branch-fill]').style.width = share+'%';
            const remaining = row.querySelector('[data-branch-remaining]');
            remaining.hidden = !isRate || !hasValue;
            remaining.style.width = (100-share)+'%';
            row.querySelector('[data-branch-share]').textContent = !hasCases ? 'No cases recorded in this period'
                : isRate ? hasValue ? 'Collected vs remaining' : 'No service value to calculate a rate'
                : metric === 'cases' ? 'Cases recorded in this period' : 'Service value after discounts';
            const context = row.querySelector('[data-branch-context]');
            context.replaceChildren();
            const addContext = (caption, amount, money) => {
                const item = document.createElement('span'); item.className='oe-ranking-detail';
                const name = document.createElement('small'); name.textContent=caption;
                const number = document.createElement('span'); number.className=money?'oe-amount':''; number.textContent=money?formatMoney(amount):Number(amount).toLocaleString('en-PH');
                item.append(name,number); context.append(item);
            };
            context.hidden = !hasCases;
            if (hasCases) {
                if (isRate) {
                    addContext('Total Service Value',row.dataset.service,true);
                    addContext('Collected',row.dataset.collected,true);
                    addContext('Remaining Balance',row.dataset.remaining,true);
                } else if(metric==='service') {
                    addContext('Cases',row.dataset.cases,false);
                    addContext('Collected',row.dataset.collected,true);
                    addContext('Remaining Balance',row.dataset.remaining,true);
                } else {
                    addContext('Total Service Value',row.dataset.service,true);
                }
            }
            branchChart.appendChild(row);
        });
        syncAmounts();
    };
    rankingSelect.addEventListener('change',rankBranches); rankBranches();
})();
</script>
