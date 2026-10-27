<style>
.branch-case-drawer { box-sizing:border-box; position:fixed; inset:0 0 0 auto; margin:0; width:min(480px,100vw); max-width:100vw; height:100dvh; max-height:100dvh; padding:0; border:0; border-left:1px solid var(--border); background:var(--card,var(--surface)); color:var(--ink); font:400 14px/1.6 var(--font-body); }
.branch-case-drawer[open] { display:flex; flex-direction:column; }
.branch-case-drawer::backdrop { background:var(--modal-scrim,rgba(15,23,42,.72)); }
.branch-case-drawer header { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:20px 24px; border-bottom:1px solid var(--border); }
.branch-case-drawer h2 { margin:0; font:600 18px/1.4 var(--font-body); }
.branch-case-drawer header button { width:36px; height:36px; border:0; border-radius:6px; background:transparent; color:var(--ink); cursor:pointer; }
.branch-case-drawer header button:hover { background:var(--surface-muted); }
.branch-case-drawer__body { padding:24px; overflow-y:auto; flex:1; }
.branch-case-drawer__purpose { color:var(--ink-muted); margin:0 0 20px; }
.branch-case-drawer footer { display:flex; justify-content:flex-end; padding:18px 24px; border-top:1px solid var(--border); }
.branch-case-drawer footer a { padding:10px 16px; border-radius:8px; background:#374151; color:white; text-decoration:none; }
.branch-case-drawer :is(a,button):focus-visible { outline:2px solid #64748b; outline-offset:3px; }
.branch-summary-fields { display:grid; grid-template-columns:1fr 1fr; gap:20px 16px; margin:0; }
.branch-summary-fields dt { font-size:12px; color:var(--ink-muted); margin-bottom:4px; }
.branch-summary-fields dd { margin:0; overflow-wrap:anywhere; }
.branch-summary-copy { display:grid; gap:20px; }
.branch-summary-copy :is(strong,span,small) { display:block; }
.branch-summary-copy strong { font-weight:500; }
.branch-summary-copy small { color:var(--ink-muted); }
.branch-summary-copy .branch-record-card__meta { display:grid; gap:16px; margin-top:20px; }
.branch-summary-copy .branch-schedule-stack { display:grid; gap:16px; }
</style>
<dialog class="branch-case-drawer" id="branchCaseSummary" aria-labelledby="branchCaseSummaryTitle">
<header><h2 id="branchCaseSummaryTitle">Case summary</h2><button type="button" data-branch-summary-close aria-label="Close summary" autofocus><i class="bi bi-x-lg" aria-hidden="true"></i></button></header>
<div class="branch-case-drawer__body"><p class="branch-case-drawer__purpose" data-branch-summary-purpose></p><div data-branch-summary-body></div></div>
<footer><a data-branch-summary-destination href="#">View Case Details</a></footer>
</dialog>
<script>
(() => {
    const drawer = document.getElementById('branchCaseSummary');
    let trigger = null;
    let previousOverflow = '';
    const scroller = document.querySelector('.page-content');
    document.querySelectorAll('.branch-admin-view .branch-record-card').forEach(card => {
        card.style.cursor = 'pointer';
        card.addEventListener('click', event => { if (!event.target.closest('a,button,input,select,textarea')) card.querySelector('[data-branch-case-summary]')?.click(); });
    });
    document.querySelectorAll('[data-branch-case-summary]').forEach(link => link.addEventListener('click', event => {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault(); trigger = link;
        const panel = link.closest('.branch-panel');
        const section = panel?.querySelector('h3')?.textContent || 'Case summary';
        drawer.querySelector('h2').textContent = section;
        const balance = !!link.querySelector('template');
        drawer.querySelector('[data-branch-summary-purpose]').textContent = balance ? 'Payment position for this case. Amounts reflect payments recorded to date.' : section === 'Currently in Wake' ? 'Current wake progress and expected end for this case.' : section === 'Scheduled Today' ? "Today's event, time, and location for this case." : 'Upcoming events within the next seven days for this case.';
        const body = drawer.querySelector('[data-branch-summary-body]');
        body.replaceChildren();
        if (balance) body.append(link.querySelector('template').content.cloneNode(true));
        else {
            const card = link.closest('.branch-record-card');
            const copy = document.createElement('div'); copy.className = 'branch-summary-copy';
            [...card.children].filter(el => el !== link).forEach(el => copy.append(el.cloneNode(true)));
            body.append(copy);
        }
        const destination = drawer.querySelector('[data-branch-summary-destination]');
        destination.href = link.href; destination.textContent = balance ? 'View Payment Record' : 'View Case Details';
        if (scroller) { previousOverflow = scroller.style.overflow; scroller.style.overflow = 'hidden'; }
        drawer.showModal();
    }));
    drawer.querySelector('[data-branch-summary-close]').addEventListener('click', () => drawer.close());
    drawer.addEventListener('click', event => { const r=drawer.getBoundingClientRect(); if(event.target===drawer && (event.clientX<r.left||event.clientX>r.right||event.clientY<r.top||event.clientY>r.bottom)) drawer.close(); });
    drawer.addEventListener('close', () => { if(scroller) scroller.style.overflow=previousOverflow; trigger?.focus(); });
})();
</script>
