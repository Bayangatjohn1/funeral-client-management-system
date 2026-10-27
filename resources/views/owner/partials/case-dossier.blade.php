@php
    $dossierPaidPercent = (float)$funeral_case->total_amount > 0 ? round(min(100,max(0,(float)$funeral_case->total_paid/(float)$funeral_case->total_amount*100)),1) : null;
@endphp
<template id="ownerContractPreviewTemplate">
@php
    $ownerContract = $funeral_case->funeralContract;
    // A read-only preview requires case visibility, not permission to issue a financial document.
    $ownerCanDraft = auth()->user()?->isOwner() && auth()->user()->can('view', $funeral_case);
    $ownerContractMissing = $ownerCanDraft && !$ownerContract ? app(\App\Services\CaseDocument\FuneralContractValidationService::class)->missingFields($funeral_case) : [];
    $ownerContractHtml = null;
    if (($ownerContract && $ownerContract->contract_snapshot) || (!$ownerContract && $ownerCanDraft && !$ownerContractMissing)) {
        $contractSnapshot = $ownerContract ? $ownerContract->contract_snapshot : app(\App\Services\CaseDocument\FuneralContractSnapshotService::class)->previewData($funeral_case);
        $contractView = app(\App\Services\CaseDocument\FuneralContractMapper::class)->map($contractSnapshot);
        $layoutService = app(\App\Services\CaseDocument\FuneralContractLayout::class);
        $previewLayout = $ownerContract ? $layoutService->forIssuedContract($contractSnapshot) : $layoutService->forNewContract($contractView,$contractSnapshot);
        $ownerContractHtml = view($layoutService->viewName($previewLayout), ['funeral_case'=>$funeral_case,'generatedAt'=>$ownerContract?->generated_at ?? now(),'contractNumber'=>$ownerContract?->contract_number ?? data_get($contractView,'contract_number'),'contractSnapshot'=>$contractSnapshot,'contractView'=>$contractView])->render();
        $ownerContractHtml = str_replace('</head>','<style>body{margin:0;padding:24px;background:#fff;} .sheet{box-sizing:border-box;width:760px;max-width:none;margin:auto;padding:32px;background:#fff;} @media(min-width:900px){body{zoom:1.15;}}</style></head>',$ownerContractHtml);
    }
@endphp
<div class="od-contract-preview">
    <div class="od-contract-preview-head"><i class="bi bi-file-earmark-text" aria-hidden="true"></i><div><h3>Funeral Contract</h3><p>{{ $ownerContract ? 'Issued document · saved contract' : 'Draft preview · not yet prepared or issued' }}</p></div></div>
    @if($ownerContract || ($ownerCanDraft && !$ownerContractMissing))
    @if($ownerContractHtml)
    <iframe sandbox="" title="Funeral Contract {{ $ownerContract?'issued document':'draft' }} preview" srcdoc="{{ $ownerContractHtml }}"></iframe>
    @else
    <iframe loading="lazy" title="Funeral Contract issued document preview" src="{{ route('funeral-cases.documents.preview',[$funeral_case,$ownerContract]) }}"></iframe>
    @endif
    @elseif($ownerContractMissing)
    <div class="od-contract-empty"><strong>Complete these case details to show the draft preview:</strong><ul>@foreach($ownerContractMissing as $field)<li>{{ $field }}</li>@endforeach</ul></div>
    @else
    <div class="od-contract-empty">No issued contract is available. A permitted user can prepare the Funeral Contract.</div>
    @endif
</div>
</template>
<style>
/* Full Dossier from CaseDetailDrawer.tsx, using existing server-rendered data. */
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-shell { gap:20px; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-card { padding:20px; border-radius:16px; background:color-mix(in srgb,var(--surface-muted) 30%,var(--card)) !important; box-shadow:none; }
html[data-theme='dark'] body.owner-ui .owner-case-details-shell.owner-dossier .cv-card { background:#13261D !important; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-card-head { background:transparent !important; padding:0 0 12px; margin:0 0 16px; gap:8px; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-card-title { font:600 13px/1.5 var(--font-body); text-transform:uppercase; letter-spacing:.045em; color:var(--ink) !important; }
html[data-theme='dark'] body.owner-ui .owner-case-details-shell.owner-dossier .cv-card-title { color:#6EE7B7 !important; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-card-icon { color:var(--brand); font-size:16px; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier :is(.cv-fields,.cv-schedule-fields__grid) { padding:0; gap:16px; grid-template-columns:repeat(3,minmax(0,1fr)); }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-field { background:transparent !important; padding:0; border:0 !important; border-radius:0; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier :is(.cv-field-label,.cv-meta-label,.cv-txn-lbl) { font-size:12px; text-transform:none; letter-spacing:normal; font-weight:500; color:var(--ink-muted) !important; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier :is(.cv-field-value,.cv-meta-value,.cv-txn-val) { font-size:14px; font-weight:400; color:var(--ink) !important; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-hero-accent { display:none; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-hero-body { padding:0; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-hero-meta { padding-top:12px; gap:16px; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-hero-case { display:inline-block; padding:6px 10px; border:1px solid var(--border); border-radius:8px; font-size:16px; background:var(--card); }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-pkg-pair { padding:12px 0 0; gap:16px; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-pkg-box { background:var(--card) !important; border-radius:12px; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-addon-list { padding:16px 0 0; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-service-grid { grid-template-columns:repeat(3,minmax(0,1fr)); }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-stat-row { padding:0 0 16px; gap:12px; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-stat { background:var(--card) !important; border-radius:12px; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-fin-grid { padding:12px 16px; background:var(--card); border:1px solid var(--border); border-radius:12px; }
.od-settlement { margin-left:auto; font-size:12px; color:var(--ink-muted); text-transform:none; }
.od-paid-track { height:8px; margin:0 0 16px; background:var(--surface-muted); border-radius:8px; overflow:hidden; }
.od-paid-fill { height:100%; background:#1B4332; border-radius:8px; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .od-paid-fill { background:#1B4332 !important; }
html[data-theme='dark'] body.owner-ui .owner-case-details-shell.owner-dossier .od-paid-fill { background:#34D399 !important; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-fin-item { background:transparent !important; border:0 !important; border-bottom:1px solid var(--border) !important; padding:8px 0; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-fin-grid .cv-field-label { text-transform:none; letter-spacing:normal; font-size:12px; font-weight:400; }
html[data-theme='dark'] .owner-dossier .od-paid-fill { background:#34D399; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier :is(.cv-fin-item .cv-field-value,.cv-stat-val) { font-family:'JetBrains Mono',monospace; font-variant-numeric:tabular-nums; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-txn-list { padding:0; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-txn { padding:16px; border-radius:12px; background:var(--card) !important; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-tarp-grid { padding:0; gap:20px; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-tarp-preview { min-height:180px; max-height:280px; border-radius:12px; background:var(--card); }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-shell > .cv-card { order:initial; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-section-documents > :not(.cv-card-head) { padding-inline:0; }
@media(max-width:760px) { html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier :is(.cv-fields,.cv-service-grid,.cv-schedule-fields__grid) { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:520px) { html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-card { padding:16px; } html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier :is(.cv-fields,.cv-service-grid,.cv-schedule-fields__grid) { grid-template-columns:minmax(0,1fr); } }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-fields-2 .cv-field-full { grid-column:auto; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .od-date-pair { display:grid; gap:8px; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .od-address { grid-column:span 2; }
@media(max-width:520px) { html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .od-address { grid-column:auto; } }
</style>
<style>
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier :is(.cv-fields,.cv-service-grid,.cv-schedule-fields__grid) { gap:24px; align-items:start; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier :is(.cv-field-value,.cv-meta-value,.cv-txn-val) { line-height:1.6; margin-top:5px; overflow-wrap:anywhere; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-hero-body { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:24px; align-items:start; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier :is(.cv-hero-top,.cv-hero-meta,.cv-hero-right,.cv-hero-actions) { display:contents; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-hero-titleblock { grid-column:span 1; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-hero-badges { grid-column:span 2; align-items:start; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-interment-badge { white-space:normal; min-height:44px; flex-wrap:wrap; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-meta-item { padding:0; background:transparent !important; border:0; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-hero-kicker { font-size:12px; font-weight:500; text-transform:none; color:var(--ink-muted) !important; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-hero-case { width:fit-content; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-field-label { margin-bottom:0; }
.owner-dossier .case-section-nav a { gap:8px; }
html[data-theme] body.owner-ui .owner-dossier .case-section-nav a[aria-current='location'] { background:var(--surface-muted); border-color:var(--border); color:var(--ink); }
.od-contract-preview { margin-top:20px; border:1px solid var(--border); border-radius:12px; overflow:hidden; background:var(--card); }
.od-contract-preview-head { display:flex; gap:12px; align-items:center; padding:16px; border-bottom:1px solid var(--border); }
.od-contract-preview-head h3 { margin:0; font-size:16px; font-weight:600; }
.od-contract-preview-head p { margin:4px 0 0; font-size:13px; }
html[data-theme] body.owner-ui .owner-dossier .od-contract-preview-head :is(h3,i) { color:var(--ink) !important; }
html[data-theme] body.owner-ui .owner-dossier .od-contract-preview-head p { color:var(--ink-muted) !important; }
.od-contract-preview iframe { display:block; width:100%; height:760px; border:0; background:#fff; }
.od-contract-empty { padding:20px; color:var(--ink); font-size:14px; }
@media(max-width:1000px) { html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-hero-body { grid-template-columns:repeat(3,minmax(0,1fr)); } .od-contract-preview iframe { height:640px; } }
@media(max-width:520px) { html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-hero-body { grid-template-columns:minmax(0,1fr); } html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier :is(.cv-hero-titleblock,.cv-hero-badges) { grid-column:auto; } .od-contract-preview iframe { height:480px; } }
</style>
<style>
body.owner-ui .owner-case-back { position:relative; width:44px; height:44px; padding:0 !important; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; }
body.owner-ui .owner-case-back-tooltip { position:absolute; top:calc(100% + 8px); left:0; z-index:30; padding:7px 10px; border-radius:8px; background:var(--surface); color:var(--ink); border:1px solid var(--border); font-size:12px; white-space:nowrap; opacity:0; visibility:hidden; pointer-events:none; }
body.owner-ui .owner-case-back:hover .owner-case-back-tooltip,body.owner-ui .owner-case-back:focus-visible .owner-case-back-tooltip { opacity:1; visibility:visible; }
html[data-theme="light"] body.owner-ui .owner-case-details-shell.owner-dossier #case-overview .od-status-active { color:#166534 !important; }
html[data-theme="dark"] body.owner-ui .owner-case-details-shell.owner-dossier #case-overview .od-status-active { color:#86EFAC !important; }
/* Neutral, fully opaque case text; layout fixes requested for the three sections. */
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier :is(h2,h3,p,span,small,em,strong,a,button,i,li,ul,.cv-tarp-empty,.cv-field-label,.cv-field-value,.cv-meta-label,.cv-meta-value,.cv-txn-lbl,.cv-txn-val,.cv-stat-lbl,.cv-stat-val,.cv-pkg-box-head,.cv-doc-name,.cv-doc-meta,.od-settlement,.cv-hero-kicker) { color:var(--ink) !important; opacity:1; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .cv-hero-body { display:block; }
.od-overview-grid { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:20px; align-items:start; }
.od-overview-field { display:grid; gap:8px; min-width:0; }
.od-overview-value { min-height:32px; display:flex; align-items:center; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .od-overview-grid .cv-meta-item { display:grid; gap:8px; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .od-overview-grid .cv-meta-value { margin:0; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .od-overview-grid .cv-interment-badge { min-height:32px; padding:0; border:0; border-radius:0; background:transparent !important; font-weight:400; font-size:14px; display:block; text-align:left; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier [class*='status-pill'] { color:var(--ink) !important; background:transparent !important; border:0; padding:0; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier #case-deceased .cv-fields { grid-template-columns:repeat(3,minmax(0,1fr)); gap:20px 24px; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .od-photo-empty .cv-tarp-grid { display:flex; gap:16px; align-items:center; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .od-photo-empty .cv-tarp-preview { min-height:88px; max-height:none; flex:1; padding:16px; background:transparent !important; border:1px dashed var(--border); }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .od-photo-empty .cv-tarp-empty { padding:0; background:transparent !important; border:0; font-weight:400; text-align:left; }
html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .od-photo-empty .cv-tarp-side { padding:0; flex-shrink:0; }
@media(max-width:1100px) { .od-overview-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } }
@media(max-width:760px) { html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier #case-deceased .cv-fields { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:520px) { .od-overview-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier #case-deceased .cv-fields { grid-template-columns:minmax(0,1fr); } html[data-theme] body.owner-ui .owner-case-details-shell.owner-dossier .od-photo-empty .cv-tarp-grid { flex-direction:column; align-items:stretch; } }
</style>
<script>
(() => {
    const shell=document.querySelector('.owner-dossier .cv-shell'); if(!shell) return;
    const overview=shell.querySelector('#case-overview');
    const caseLabel=overview?.querySelector('.cv-hero-kicker'); if(caseLabel) caseLabel.textContent='Case Code';
    if(overview) {
        const body=overview.querySelector('.cv-hero-body');
        const code=overview.querySelector('.cv-hero-case');
        const interment=overview.querySelector('.cv-interment-badge');
        const status=interment?.querySelector('[class*="status-pill"]');
        const fields=document.createElement('div'); fields.className='od-overview-grid';
        const addField=(label,node)=>{ if(!node) return; const field=document.createElement('div'); field.className='od-overview-field'; const heading=document.createElement('div'); heading.className='cv-meta-label'; heading.textContent=label; const value=document.createElement('div'); value.className='od-overview-value'; value.appendChild(node); field.append(heading,value); fields.appendChild(field); };
        if(status?.textContent.trim()==='ACTIVE') status.classList.add('od-status-active');
        addField('Case Code',code); addField('Case Status',status);
        if(interment) { interment.querySelectorAll('i').forEach(icon=>icon.remove()); [...interment.childNodes].filter(n=>n.nodeType===Node.TEXT_NODE).forEach(n=>{n.textContent=n.textContent.replace('Interment:','').trim();}); }
        addField('Interment Schedule',interment);
        [...overview.querySelectorAll('.cv-meta-item')].forEach(field=>{field.classList.add('od-overview-field'); fields.appendChild(field);});
        const actions=overview.querySelectorAll('.cv-modify-btn');
        body?.prepend(fields); actions.forEach(action=>fields.appendChild(action));
        const other=overview.querySelector('.cv-hero-badges'); if(other && other.textContent.trim()) body?.appendChild(other);
        overview.querySelector('.cv-hero-top')?.remove(); overview.querySelector('.cv-hero-meta')?.remove();
    }
    const people=shell.querySelector('#case-people');
    const client=people?.querySelector('.cv-card:first-child');
    const deceased=people?.querySelector('.cv-card:nth-child(2)');
    if(client) client.id='case-client';
    if(deceased) {
        deceased.id='case-deceased';

    }
    const service=shell.querySelector('#case-service');
    const schedule=shell.querySelector('#wake-schedule');
    const photo=shell.querySelector('.cv-field-tarpaulin');
    if(schedule) { schedule.classList.remove('cv-schedule-fields'); schedule.classList.add('cv-card'); schedule.querySelector('.cv-schedule-fields__head')?.classList.add('cv-card-head'); }
    if(photo && photo.querySelector('.cv-tarp-empty')) photo.classList.add('od-photo-empty');
    if(photo) { photo.id='case-photo'; photo.classList.remove('cv-field','cv-field-tarpaulin'); photo.classList.add('cv-card'); photo.querySelector('.cv-subsection-head')?.classList.add('cv-card-head'); }
    const payments=shell.querySelector('#case-payments');
    const transactions=shell.querySelector('.cv-section-transactions'); if(transactions) transactions.id='case-transactions';
    const documents=shell.querySelector('#case-documents');
    const contractTemplate=document.getElementById('ownerContractPreviewTemplate');
    if(documents && contractTemplate) documents.appendChild(contractTemplate.content.cloneNode(true));
    const source=shell.querySelector('#case-source');
    const overviewHead=document.createElement('div'); overviewHead.className='cv-card-head';
    overviewHead.innerHTML='<span class="cv-card-icon"><i class="bi bi-file-earmark-check" aria-hidden="true"></i></span><h2 class="cv-card-title">1. Case Overview</h2>';
    overview?.prepend(overviewHead);
    const sections=[overview,client,deceased,service,schedule,photo,payments,transactions,documents,source].filter(Boolean);
    const names=['Case Overview','Client (Family Representative)','Deceased Particulars','Package & Service','Wake & Service Schedule','Tarpaulin Photo','Payment Summary','Transactions (Individual Payment Records)','Funeral Contract & Documents','Record Source'];
    sections.forEach((section,index)=>{
        const title=section.querySelector('.cv-card-title');
        if(title) { const heading=document.createElement('h2'); heading.className='cv-card-title'; if(title.id) heading.id=title.id; heading.textContent=(index+1)+'. '+names[index]; title.replaceWith(heading); }
        shell.appendChild(section);
    });
    const percent=@json($dossierPaidPercent);
    if(payments && percent!==null) {
        const badge=document.createElement('span'); badge.className='od-settlement'; badge.textContent=percent+'% settled'; payments.querySelector('.cv-card-head')?.appendChild(badge);
        const progress=document.createElement('div'); progress.className='od-paid-track'; progress.setAttribute('role','progressbar'); progress.setAttribute('aria-label','Share of service amount paid'); progress.setAttribute('aria-valuemin','0'); progress.setAttribute('aria-valuemax','100'); progress.setAttribute('aria-valuenow',String(percent));
        const fill=document.createElement('div'); fill.className='od-paid-fill'; fill.style.width=percent+'%'; progress.appendChild(fill); payments.querySelector('.cv-stat-row')?.after(progress);
    }
    if(people) people.remove();
    const icons={'#case-overview':'file-earmark-check','#case-client':'person','#case-deceased':'heart','#case-service':'layers','#wake-schedule':'calendar-event','#case-photo':'image','#case-payments':'credit-card','#case-documents':'file-earmark-text','#case-source':'diagram-3'};
    document.querySelectorAll('.owner-dossier .case-section-nav a').forEach(link=>{ const icon=document.createElement('i'); icon.className='bi bi-'+(icons[link.getAttribute('href')] || 'file-earmark'); icon.setAttribute('aria-hidden','true'); link.prepend(icon); });
    document.querySelector('.owner-dossier .case-section-nav')?.addEventListener('click',event=>{ const selected=event.target.closest('a'); if(!selected) return; document.querySelectorAll('.owner-dossier .case-section-nav a').forEach(link=>{ if(link===selected) link.setAttribute('aria-current','location'); else link.removeAttribute('aria-current'); }); });
})();
</script>
