@if(request()->boolean('drawer_summary'))
    @include('partials.case_record_summary')
@else
@php
    $displaySnapshot = app(\App\Support\CaseSnapshotDisplayService::class)->data($funeral_case);
    $pkgFreebieItems = $displaySnapshot['freebies'];
    $pkgPrice = $displaySnapshot['package_price'];
    $displayPackageName = $displaySnapshot['package_name'];
    $caseAddOns = collect($displaySnapshot['add_ons']);
    $serviceChargeItems = collect($displaySnapshot['service_charges']);
    $additionalDisplayItems = collect($displaySnapshot['additional_items']);
    $serviceChargeTotal = round((float) $serviceChargeItems->sum(fn ($charge) => (float) ($charge['amount'] ?? 0)), 2);
    $addOnsTotal = (float) $displaySnapshot['add_ons_total'];
    $additionalDisplayTotal = (float) $displaySnapshot['additional_total'];
    $includedCasket = $displaySnapshot['included_casket'];
    $selectedCasket = $displaySnapshot['selected_casket'];
    $selectedHearseName = $funeral_case->hearse_name_snapshot
        ?: data_get($funeral_case->pricing_snapshot, 'selected_hearse.name');
    $discountDisplay = $displaySnapshot['discount'];
    $isCustomPackage = (bool) ($funeral_case->pricing_snapshot['custom_package'] ?? false)
        || (bool) $funeral_case->custom_package_name;
    $pkgCoffin = $includedCasket['name'] ?? ($isCustomPackage ? null : $funeral_case->coffin_type);
    $isOtherBranch = ($funeral_case->entry_source ?? 'MAIN') === 'OTHER_BRANCH';
    $balanceDue    = (float) $funeral_case->balance_amount > 0;
    $casePayments = $funeral_case->payments
        ->sortByDesc(fn ($payment) => [($payment->paid_at ?? $payment->paid_date)?->timestamp ?? 0, $payment->id])
        ->values();
    $lastValidPayment = $casePayments->first(fn ($payment) => !$payment->isVoided());
    $canViewProof = auth()->user()?->isStaff() || auth()->user()?->isAdmin();
    $deceasedPhotoAttachment = $funeral_case->deceasedPhotoAttachment;
    $seniorProofAttachment = $funeral_case->seniorProofAttachment;
    $deceasedPhotoPath = $deceasedPhotoAttachment?->file_path ?: $funeral_case->deceased?->photo_path;
    $seniorProofPath = $seniorProofAttachment?->file_path ?: $funeral_case->deceased?->senior_proof_path;
    $seniorProofIsImage = $seniorProofPath && in_array(strtolower(pathinfo((string) $seniorProofPath, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);
    $canManageCaseMedia = auth()->user()?->can('manageCaseMedia', $funeral_case) ?? false;
    $chapelId = $funeral_case->chapel_catalog_id;
    $includedChapel = $chapelId ? collect(data_get($funeral_case->pricing_snapshot, 'included_add_ons', []))->firstWhere('id', $chapelId) : null;
    $separateChapel = $chapelId ? collect(data_get($funeral_case->pricing_snapshot, 'add_ons', []))->firstWhere('id', $chapelId) : null;
    $chapelDays = $includedChapel['requested_quantity'] ?? $separateChapel['quantity'] ?? null;
    $coveredChapelDays = $chapelDays === null ? null : min($chapelDays, $includedChapel['included_quantity'] ?? 0);
    $displayIntermentAt = $funeral_case->interment_at
        ?? $funeral_case->deceased?->interment_at
        ?? $funeral_case->serviceDetail?->internment_date
        ?? $funeral_case->deceased?->interment;
    $displayWakeDays = $funeral_case->deceased?->wake_days;
    $displayWakeDuration = $displaySnapshot['wake_duration'];
    $tarpaulinAttachment = $funeral_case->tarpaulinAttachment;
    $tarpaulinUrl = $tarpaulinAttachment?->publicUrl();
    $canUploadTarpaulin = auth()->user()?->can('uploadTarpaulin', $funeral_case) ?? false;
    $canReplaceTarpaulin = auth()->user()?->can('replaceTarpaulin', $funeral_case) ?? false;
    $canDeleteTarpaulin = auth()->user()?->can('deleteTarpaulin', $funeral_case) ?? false;
    $funeralContract = $funeral_case->funeralContract;
    $canGenerateFuneralContract = auth()->user()?->can('generateFuneralContract', $funeral_case) ?? false;

    // Smart date formatters — skip the time portion when it is midnight
    $fmtDate = fn($dt) => $dt ? $dt->format('M d, Y') : '—';
    $fmtDt   = fn($dt) => $dt
        ? ($dt->format('H:i') === '00:00' ? $dt->format('M d, Y') : $dt->format('M d, Y · H:i'))
        : '—';
    $fmtTime = fn($time) => $time ? \Carbon\Carbon::parse($time)->format('h:i A') : 'Time not set';
    $fmtSchedule = fn($date, $time) => ($date ? $date->format('M d, Y') : 'Not set') . ' at ' . $fmtTime($time);
@endphp

<div id="caseViewContent">
<style>
  /* ── case-view partial (cv-*) ── */
  .cv-shell        { display:flex; flex-direction:column; gap:14px; }
  .cv-deceased-layout { display:grid; grid-template-columns:minmax(270px, 300px) minmax(0,1fr); align-items:start; gap:22px; padding:18px; }
  .cv-deceased-photo-panel { position:relative; align-self:start; display:flex; flex-direction:column; gap:10px; align-items:stretch; padding:12px; border-radius:14px; background:#e8f0e9; }
  .cv-deceased-photo-frame { display:flex; align-items:center; justify-content:center; height:220px; min-height:0; overflow:hidden; border-radius:10px; background:#dce7de; }
  .cv-deceased-photo-frame img { display:block; width:100%; height:220px; object-fit:cover; cursor:zoom-in; }
  .cv-deceased-photo-placeholder { display:flex; width:100%; height:220px; min-height:0; flex-direction:column; align-items:center; justify-content:center; gap:8px; color:#687b70; text-align:center; }
  .cv-deceased-photo-placeholder i { font-size:34px; }
  .cv-deceased-photo-actions { position:relative; display:grid; grid-template-columns:1fr 1fr; gap:8px; }
  .cv-deceased-photo-actions button { display:flex; min-width:0; min-height:40px; align-items:center; justify-content:center; gap:8px; padding:8px 7px; border:0; border-radius:9px; background:#d3e4d9; color:#174b38; font:inherit; font-size:11px; font-weight:650; line-height:1.2; white-space:nowrap; cursor:pointer; transition:background .16s ease, transform .16s ease; }
  .cv-deceased-photo-actions button i, .cv-deceased-proof-action i { flex:0 0 auto; line-height:1; }
  .cv-deceased-photo-actions button:hover { background:#c2d9cb; transform:translateY(-1px); }
  .cv-deceased-photo-actions button:focus-visible { outline:2px solid #28724f; outline-offset:2px; }
  .cv-deceased-details { min-width:0; }
  .cv-deceased-info-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); column-gap:24px; }
  .cv-deceased-info-row { position:relative; min-width:0; padding:11px 4px 12px; border-bottom:1px solid var(--border,#c8d8cc); }
  .cv-deceased-info-row--full { grid-column:1/-1; }
  .cv-deceased-info-row .cv-field-label { margin-bottom:4px; }
  .cv-deceased-status-proof { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:20px; }
  .cv-deceased-status-proof__cell { min-width:0; }
  .cv-deceased-status-proof__cell:last-child { position:relative; }
  .cv-deceased-status-proof__cell:only-child { grid-column:1/-1; }
  .cv-deceased-proof { position:relative; display:flex; align-items:center; flex-wrap:wrap; gap:8px 12px; }
  .cv-deceased-proof .cv-case-attachment-link { white-space:nowrap; }
  .cv-senior-proof-thumb { display:inline-flex; width:48px; height:48px; align-items:center; justify-content:center; overflow:hidden; padding:0; border:0; border-radius:7px; background:#e4eee7; color:#174b38; cursor:zoom-in; }
  .cv-senior-proof-thumb img { display:block; width:100%; height:100%; object-fit:cover; }
  .cv-senior-proof-thumb i { font-size:20px; }
  .cv-proof-preview-link { padding:0; border:0; background:transparent; color:inherit; font:inherit; text-decoration:underline; text-underline-offset:3px; cursor:pointer; }
  .cv-deceased-proof-action { display:inline-flex; align-items:center; justify-content:center; gap:7px; padding:6px 10px; border:0; border-radius:8px; background:#e4eee7; color:#174b38; font:inherit; font-size:12px; font-weight:650; line-height:1.2; cursor:pointer; }
  .cv-deceased-proof-action:hover { background:#d2e4d9; }
  .cv-deceased-proof-popover { position:fixed; z-index:100010; top:0; right:auto; bottom:auto; left:0; width:min(320px,calc(100vw - 24px)); padding:14px; border-radius:12px; background:#fff; box-shadow:0 12px 32px rgba(20,45,32,.18); color:var(--ink); }
  .cv-deceased-proof-popover--photo, .cv-deceased-proof-popover--senior { transform:none; }
  .cv-deceased-proof-popover[hidden], .cv-case-media-modal[hidden], .cv-deceased-photo-preview[hidden] { display:none !important; }
  .cv-deceased-proof-popover p { margin:5px 0 0; color:var(--ink-muted); font-size:12px; }
  .cv-case-media-modal, .cv-deceased-photo-preview { position:fixed; inset:0; z-index:99999; display:flex; align-items:center; justify-content:center; padding:20px; background:rgba(18,31,25,.58); }
  .cv-case-media-dialog { width:min(560px,100%); max-height:min(88vh,820px); overflow:auto; border-radius:16px; background:var(--surface,#f7faf8); box-shadow:0 14px 36px rgba(20,35,27,.18); }
  body.cv-case-media-open { overflow:hidden; }
  .cv-case-media-dialog-head, .cv-case-media-dialog-foot { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 18px; border-bottom:1px solid var(--border,#c8d8cc); }
  .cv-case-media-dialog-foot { justify-content:flex-end; border-top:1px solid var(--border,#c8d8cc); border-bottom:0; }
  .cv-case-media-dialog-body { display:flex; flex-direction:column; gap:12px; padding:18px; }
  .cv-case-media-dialog-close { display:grid; width:40px; height:40px; flex:0 0 40px; place-items:center; padding:0; border:0; border-radius:9px; background:transparent; color:var(--ink); font-size:16px; cursor:pointer; }
  .cv-case-media-dialog-close:hover { background:#e8f0e9; }
  .cv-case-media-dialog [data-case-media-submit]:disabled { opacity:.5; cursor:not-allowed; }
  .cv-deceased-photo-preview__dialog { position:relative; max-width:min(92vw,1000px); max-height:90vh; padding:12px; border-radius:14px; background:#f7faf8; }
  .cv-deceased-photo-preview__dialog img { display:block; max-width:calc(92vw - 24px); max-height:78vh; object-fit:contain; border-radius:8px; }
  .cv-deceased-photo-preview__dialog iframe { display:block; width:min(88vw,1000px); height:78vh; border:0; border-radius:8px; background:#fff; }
  .cv-deceased-photo-preview__close { position:absolute; top:18px; right:18px; width:38px; height:38px; border:0; border-radius:50%; background:rgba(17,35,27,.8); color:#fff; cursor:pointer; }
  @media(max-width:760px) { .cv-deceased-layout { grid-template-columns:250px minmax(0,1fr); gap:16px; padding:14px; } }
  @media(max-width:760px) { .cv-deceased-photo-actions button { white-space:normal; } }
  @media(max-width:600px) { .cv-deceased-layout { grid-template-columns:1fr; } .cv-deceased-photo-panel { display:grid; grid-template-columns:minmax(115px, 38%) minmax(0,1fr); align-items:center; gap:10px; } .cv-deceased-photo-frame, .cv-deceased-photo-placeholder { height:150px; grid-row:span 2; } .cv-deceased-photo-frame img { height:150px; } .cv-deceased-photo-actions { grid-template-columns:1fr; } .cv-deceased-info-grid { grid-template-columns:1fr; } .cv-deceased-info-row--full { grid-column:auto; } .cv-deceased-status-proof { grid-template-columns:1fr; gap:12px; } }
  .cv-section-case { order:1; }
  .cv-section-people { order:2; }
  .cv-section-service { order:3; }
  .cv-section-tarpaulin { order:4; }
  .cv-section-payment { order:5; }
  .cv-section-transactions { order:6; }
  .cv-section-documents { order:7; }
  .cv-section-source { order:8; }
  .cv-card         { background:#D3DEC9; border:1px solid var(--border); border-radius:.75rem; overflow:hidden; box-shadow:none; }
  .cv-card-head    { display:flex; align-items:center; gap:.55rem; padding:.85rem 1rem; background:#C7D5BE; border-bottom:1px solid var(--border); }
  .cv-card-icon    { width:1.4rem; height:1.4rem; display:flex; align-items:center; justify-content:center; color:var(--brand); font-size:.86rem; flex-shrink:0; }
  .cv-card-title   { font-size:.72rem; font-weight:650; text-transform:uppercase; letter-spacing:.07em; color:var(--ink-muted); }
  /* fields */
  .cv-fields       { display:grid; gap:.7rem; padding:1rem; }
  .cv-service-grid { align-items:start; }
  .cv-fields-2     { grid-template-columns:1fr 1fr; }
  .cv-field        { padding:.8rem .9rem; border:1px solid var(--border); border-radius:.7rem; background:#DCE6D6; min-width:0; }
  .cv-field:last-child { border-bottom:1px solid var(--border); }
  .cv-fields-2 .cv-field:nth-child(odd):not(.cv-field-full) { border-right:1px solid var(--border); }
  .cv-fields-2 .cv-field-full { grid-column:span 2; }
  .cv-field-label  { font-size:.68rem; font-weight:650; text-transform:uppercase; letter-spacing:.06em; color:var(--ink-muted); margin-bottom:.18rem; }
  .cv-field-value  { font-size:.88rem; font-weight:600; color:var(--ink); line-height:1.42; }
  .cv-field-value em { color:var(--ink-muted); font-style:italic; font-size:12px; }
  /* hero header */
  .cv-hero-accent  { height:3px; background:var(--brand); }
  .cv-hero-body    { padding:1.1rem 1.15rem; }
  .cv-hero-top     { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1rem; }
  .cv-hero-titleblock { min-width:0; display:flex; flex-direction:column; gap:.35rem; }
  .cv-hero-kicker  { font-size:.68rem; font-weight:650; text-transform:uppercase; letter-spacing:.07em; color:var(--ink-muted); }
  .cv-hero-case    { font-size:1.42rem; font-weight:750; color:var(--ink); letter-spacing:0; line-height:1.08; }
  .cv-hero-badges  { display:flex; flex-wrap:wrap; gap:.45rem; padding-top:0; }
  .cv-hero-right   { display:grid; gap:.7rem; min-width:0; }
  .cv-hero-actions { display:flex; align-items:center; justify-content:flex-end; gap:.55rem; flex-wrap:wrap; }
  .cv-modify-btn       { display:inline-flex; align-items:center; justify-content:center; gap:.45rem; min-height:2.2rem; padding:0 .8rem; font-size:.82rem; font-weight:700; color:var(--ink); background:#E1E7D9; border:1px solid var(--border); border-radius:.65rem; text-decoration:none; transition:background-color .16s ease,border-color .16s ease,color .16s ease; white-space:nowrap; }
  .cv-modify-btn:hover { background:#C7D5BE; border-color:#8EA083; color:var(--ink); }
  .cv-interment-badge  { display:inline-flex; align-items:center; gap:.4rem; min-height:2.15rem; padding:0 .75rem; font-size:.78rem; font-weight:650; color:var(--ink-muted); background:#E1E7D9; border:1px solid var(--border); border-radius:.65rem; white-space:nowrap; }
  .cv-interment-badge i { font-size:11px; }
  .cv-interment-badge [class*="status-pill"] { font-size:10px; padding:1px 7px; }
  @media print { .cv-modify-btn { display:none !important; } }
  .cv-hero-meta    { display:grid; grid-template-columns:repeat(auto-fit,minmax(12rem,1fr)); gap:.7rem; padding-top:.9rem; border-top:1px solid var(--border); }
  .cv-meta-item    { display:flex; flex-direction:column; gap:.15rem; padding:.8rem .9rem; border:1px solid var(--border); border-radius:.7rem; background:#DCE6D6; }
  .cv-meta-label   { font-size:.68rem; font-weight:650; text-transform:uppercase; letter-spacing:.06em; color:var(--ink-muted); }
  .cv-meta-value   { font-size:.88rem; font-weight:650; color:var(--ink); }
  /* two-col layout for client/deceased side-by-side */
  .cv-two-col      { display:grid; grid-template-columns:1fr; gap:14px; align-items:stretch; }
  @media(min-width:600px) { .cv-two-col { grid-template-columns:1fr 1fr; } }
  /* payment stat row */
  .cv-stat-row     { display:grid; grid-template-columns:repeat(3,1fr); gap:.75rem; padding:1rem; }
  .cv-stat         { border:1px solid var(--border); border-radius:.7rem; padding:.85rem .9rem; text-align:left; background:#DCE6D6; box-shadow:none; }
  .cv-stat-lbl     { font-size:.68rem; font-weight:650; text-transform:uppercase; letter-spacing:.07em; color:var(--ink-muted); margin-bottom:.28rem; }
  .cv-stat-val     { font-size:1rem; font-weight:750; color:var(--ink); font-variant-numeric:tabular-nums; }
  .cv-stat.s-paid .cv-stat-lbl { color:#6F8A6D; }
  .cv-stat.s-paid .cv-stat-val { color:#6F8A6D; }
  .cv-stat.s-due .cv-stat-lbl  { color:#9E4B3F; }
  .cv-stat.s-due .cv-stat-val  { color:#9E4B3F; }
  .cv-stat.s-ok .cv-stat-lbl   { color:#6F8A6D; }
  .cv-stat.s-ok .cv-stat-val   { color:#6F8A6D; }
  .cv-payment-total-row { display:grid; }
  /* financial breakdown */
  .cv-fin-grid     { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; padding:.85rem 1rem 1rem; }
  .cv-fin-item     { min-width:0; padding:.85rem 1rem; border:0; border-radius:.75rem; background:transparent; }
  /* package inclusions/freebies */
  .cv-pkg-pair     { display:grid; grid-template-columns:1fr; gap:.7rem; padding:0 1rem 1rem; }
  @media(min-width:520px) { .cv-pkg-pair { grid-template-columns:1fr 1fr; } }
  .cv-pkg-box      { border:1px solid var(--border); border-radius:.7rem; padding:.85rem .95rem; background:transparent; }
  .cv-pkg-box-head { display:flex; align-items:center; gap:.4rem; font-size:.68rem; font-weight:650; text-transform:uppercase; letter-spacing:.07em; color:var(--ink-muted); margin-bottom:.45rem; }
  .cv-addon-list   { display:flex; flex-direction:column; gap:.7rem; padding:0 1rem 1rem; }
  .cv-addon-row    { border:1px solid var(--border); border-radius:.7rem; padding:.85rem .95rem; background:transparent; display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; }
  .cv-pkg-pair > .cv-addon-list { min-width:0; padding:0; gap:.45rem; }
  /* payment transactions */
  .cv-txn-list     { display:flex; flex-direction:column; gap:.7rem; padding:.85rem 1rem 1rem; }
  .cv-txn          { min-width:0; border:1px solid var(--border); border-radius:.7rem; padding:.9rem 1rem; background:var(--surface-muted); box-shadow:none; }
  .cv-txn-head     { display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.7rem; }
  .cv-txn-receipt  { font-family:ui-monospace,'Cascadia Code',monospace; font-size:.82rem; font-weight:750; color:var(--ink); }
  .cv-txn-grid     { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:.65rem .9rem; align-items:start; }
  .cv-txn-grid > div { min-width:0; }
  .cv-txn-lbl      { font-size:.68rem; font-weight:650; text-transform:uppercase; letter-spacing:.06em; color:var(--ink-muted); margin-bottom:.12rem; }
  .cv-txn-val      { font-size:.82rem; font-weight:600; color:var(--ink); }
  .cv-txn-links:not(:empty) { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:8px 14px; margin-top:12px; font-size:.82rem; }
  .cv-txn-links a,.cv-case-attachment-link { text-decoration:underline; text-underline-offset:3px; }
  .cv-txn-related { display:flex; flex-wrap:wrap; gap:5px 12px; min-width:0; color:var(--ink-muted); font-size:12px; }
  .cv-txn-actions { display:flex; flex:0 0 auto; align-items:center; justify-content:flex-end; gap:8px; margin-inline-start:auto; }
  .cv-txn-action { display:inline-flex; min-height:36px; align-items:center; justify-content:center; gap:7px; padding:7px 11px; border:0; border-radius:8px; background:var(--card); color:var(--ink); font:inherit; font-size:12px; font-weight:650; line-height:1.2; text-decoration:none !important; white-space:nowrap; cursor:pointer; transition:background-color .16s ease,color .16s ease; }
  .cv-txn-action:hover { background:var(--records-hover, var(--surface-muted)); color:var(--ink); }
  .cv-txn-action:focus-visible { outline:2px solid var(--brand); outline-offset:2px; }
  .cv-correction-drawer[hidden] { display:none !important; }
  .cv-correction-drawer { position:fixed; inset:0; z-index:100000; display:flex; justify-content:flex-end; background:rgba(15,23,42,.42); opacity:0; transition:opacity .2s ease; }
  .cv-correction-drawer.is-open { opacity:1; }
  .cv-correction-drawer__panel { display:flex; width:min(500px,100vw); height:100%; min-height:0; flex-direction:column; background:var(--card); color:var(--ink); box-shadow:-8px 0 24px rgba(15,23,42,.12); transform:translateX(24px); transition:transform .22s cubic-bezier(.2,.75,.25,1); }
  .cv-correction-drawer.is-open .cv-correction-drawer__panel { transform:translateX(0); }
  .cv-correction-drawer__head { display:flex; flex:0 0 auto; align-items:flex-start; justify-content:space-between; gap:16px; padding:20px 22px 16px; border-bottom:1px solid var(--border); }
  .cv-correction-drawer__head small { display:block; margin-bottom:4px; color:var(--ink-muted); font-size:12px; }
  .cv-correction-drawer__head h2 { margin:0; color:var(--ink); font-size:20px; line-height:1.25; }
  .cv-correction-drawer__close { display:grid; width:38px; height:38px; flex:0 0 38px; place-items:center; border:0; border-radius:9px; background:var(--surface-muted); color:var(--ink); cursor:pointer; transition:background-color .16s ease; }
  .cv-correction-drawer__close:hover { background:var(--records-hover, var(--surface-muted)); }
  .cv-correction-drawer__close:focus-visible { outline:2px solid var(--brand); outline-offset:2px; }
  .cv-correction-drawer__body { min-height:0; flex:1 1 auto; overflow:auto; padding:18px 22px 24px; }
  .cv-correction-drawer__payment { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; margin-bottom:18px; padding:14px; border-radius:10px; background:var(--surface-muted); }
  .cv-correction-drawer__payment span { display:block; margin-bottom:3px; color:var(--ink-muted); font-size:11px; }
  .cv-correction-drawer__payment strong { display:block; overflow-wrap:anywhere; color:var(--ink); font-size:13px; }
  .cv-correction-drawer__counts { display:flex; flex-wrap:wrap; gap:7px; margin-bottom:14px; }
  .cv-correction-drawer__count,.cv-correction-drawer__status { display:inline-flex; align-items:center; padding:5px 9px; border-radius:999px; background:var(--surface-muted); color:var(--ink-muted); font-size:11px; font-weight:700; }
  .cv-correction-drawer__count[data-status="PENDING"],.cv-correction-drawer__status[data-status="PENDING"] { background:color-mix(in srgb, var(--warning) 15%, var(--card)); color:var(--warning); }
  .cv-correction-drawer__count[data-status="APPROVED"],.cv-correction-drawer__status[data-status="APPROVED"] { background:color-mix(in srgb, var(--success) 15%, var(--card)); color:var(--success); }
  .cv-correction-drawer__count[data-status="REJECTED"],.cv-correction-drawer__status[data-status="REJECTED"] { background:color-mix(in srgb, var(--danger) 13%, var(--card)); color:var(--danger); }
  .cv-correction-drawer__list { display:grid; gap:10px; margin:0; padding:0; list-style:none; }
  .cv-correction-drawer__item { min-width:0; padding:13px 14px; border-radius:10px; background:color-mix(in srgb, var(--surface-muted) 72%, transparent); }
  .cv-correction-drawer__item-head { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
  .cv-correction-drawer__item-head strong { color:var(--ink); font-size:13px; }
  .cv-correction-drawer__meta { display:grid; gap:6px; margin-top:9px; color:var(--ink-muted); font-size:12px; }
  .cv-correction-drawer__meta p { margin:0; overflow-wrap:anywhere; }
  .cv-correction-drawer__link { display:inline-flex; align-items:center; gap:6px; margin-top:11px; color:var(--brand); font-size:12px; font-weight:700; text-decoration:none; }
  .cv-correction-drawer__link:hover { text-decoration:underline; text-underline-offset:3px; }
  body.cv-correction-drawer-open { overflow:hidden; }
  body.cv-correction-drawer-open .page-content { overflow:hidden !important; }
  @media(max-width:1000px) { .cv-txn-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } }
  @media(max-width:600px) { .cv-txn-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .cv-txn-links:not(:empty) { align-items:stretch; } .cv-txn-related { flex:1 0 100%; } .cv-txn-actions { width:100%; justify-content:flex-start; margin-inline-start:0; } .cv-txn-action { flex:1 1 auto; } .cv-correction-drawer__head { padding:16px; } .cv-correction-drawer__body { padding:16px; } }
  @media(prefers-reduced-motion:reduce) { .cv-correction-drawer,.cv-correction-drawer__panel { transition:none; } }
  .cv-deceased-photo { width:92px; height:92px; margin-top:6px; border-radius:8px; object-fit:cover; }
  /* Tarpaulin preview lives inside the schedule card. */
  .cv-field-tarpaulin { min-width:0; align-self:start; padding:0; border:0; background:transparent; }
  .cv-schedule-fields { grid-column:1 / -1; border:1px solid var(--border); border-radius:.7rem; background:transparent; overflow:hidden; }
  .cv-schedule-fields__head { padding:.72rem .85rem; border-bottom:1px solid var(--border); background:transparent; }
  .cv-schedule-fields__body { display:grid; grid-template-columns:minmax(0,2fr) minmax(230px,.82fr); gap:22px; align-items:start; padding:.85rem 1rem 1rem; }
  .cv-schedule-fields__grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px 20px; padding:0; }
  .cv-schedule-fields__grid > .cv-field-package:not([id^="schedule-event-"]) { grid-column:auto; grid-row:4; }
  #schedule-event-viewing-type { grid-column:1; grid-row:1; }
  #schedule-event-wake-location { grid-column:2; grid-row:1; }
  #schedule-event-wake-duration { grid-column:3; grid-row:1; }
  #schedule-event-wake-start { grid-column:1; grid-row:2; }
  #schedule-event-wake-end { grid-column:2; grid-row:2; }
  #schedule-event-funeral-ceremony { grid-column:1; grid-row:3; }
  #schedule-event-interment { grid-column:2; grid-row:3; }
  .cv-schedule-fields__grid > .cv-field-full { grid-column:1 / -1; }
  .cv-subsection-head { display:flex; align-items:center; gap:.5rem; padding:.8rem .9rem; border-bottom:1px solid var(--border); background:#C7D5BE; }
  .cv-tarp-grid    { display:block; padding:0; }
  .cv-tarp-media-panel { position:relative; display:flex; min-width:0; flex-direction:column; align-self:start; gap:10px; padding:0; }
  .cv-tarp-preview { width:100%; height:148px; min-height:148px; max-height:148px; padding:0; border:0; border-radius:12px; background:#E1E7D9; display:flex; align-items:center; justify-content:center; overflow:hidden; cursor:zoom-in; }
  .cv-tarp-preview img { width:100%; height:100%; max-height:148px; object-fit:cover; display:block; }
  .cv-tarp-preview--empty { border:1px dashed var(--border); cursor:default; }
  .cv-tarp-empty   { text-align:center; color:var(--ink-muted); font-size:13px; font-weight:600; padding:18px; }
  .cv-tarp-side    { display:none; }
  .cv-tarp-actions { position:relative; display:grid; grid-template-columns:1fr 1fr; gap:8px; align-items:stretch; }
  .cv-tarp-actions form { min-width:0; margin:0; }
  .cv-tarp-actions form button { width:100%; }
  .cv-field-tarpaulin .cv-tarp-actions button { display:flex; min-width:0; min-height:40px; align-items:center; justify-content:center; gap:8px; padding:8px 7px; border:0; border-radius:9px; background:#d3e4d9; color:#174b38; font:inherit; font-size:11px; font-weight:650; line-height:1.2; white-space:normal; cursor:pointer; transition:background .16s ease, transform .16s ease; }
  .cv-field-tarpaulin .cv-tarp-actions button:hover { background:#c2d9cb; transform:translateY(-1px); }
  .cv-field-tarpaulin .cv-tarp-actions button:focus-visible { outline:2px solid #28724f; outline-offset:2px; }
  .cv-field-tarpaulin .cv-tarp-actions .cv-tarp-action--danger { background:#f8e7e5; color:#9E4B3F; }
  .cv-field-tarpaulin .cv-tarp-actions .cv-tarp-action--danger:hover { background:#f1d5d1; }
  .cv-tarp-meta { width:min(320px,calc(100vw - 24px)); }
  .cv-tarp-meta__row { display:grid; grid-template-columns:110px minmax(0,1fr); gap:8px; padding:7px 0; border-bottom:1px solid #e3ebe5; font-size:12px; }
  .cv-tarp-meta__row:last-child { border-bottom:0; }
  .cv-tarp-meta__label { color:var(--ink-muted); }
  .cv-tarp-meta__value { min-width:0; color:var(--ink); font-weight:600; overflow-wrap:anywhere; }
  .cv-tarp-note { font-size:11.5px; color:var(--ink-muted); line-height:1.45; }
  .cv-tarp-form    { display:none; border:1px solid var(--border); border-radius:.75rem; padding:.85rem; background:#DCE6D6; }
  .cv-tarp-form.open { display:block; }
  @media(max-width:900px) { .cv-schedule-fields__body { grid-template-columns:minmax(0,1.6fr) minmax(205px,.8fr); gap:16px; } }
  @media(max-width:700px) { .cv-schedule-fields__body { grid-template-columns:1fr; } .cv-schedule-fields__grid { grid-template-columns:1fr 1fr; } .cv-schedule-fields__grid > .cv-field-package, .cv-schedule-fields__grid > .cv-field-full { grid-column:auto; grid-row:auto; } .cv-tarp-preview { height:168px; min-height:168px; max-height:168px; } .cv-tarp-preview img { max-height:168px; } }
  @media(max-width:460px) { .cv-schedule-fields__grid { grid-template-columns:1fr; } .cv-schedule-fields__grid > .cv-field-package, .cv-schedule-fields__grid > .cv-field-full { grid-column:auto; grid-row:auto; } }
  .cv-tarp-modal-preview { width:100%; max-height:74vh; object-fit:contain; border-radius:8px; background:#111827; }
  .cv-tarp-modal[data-mode="upload"] .cv-case-media-dialog { width:min(560px,100%); }
  .cv-tarp-modal[data-mode="upload"] .cv-tarp-modal-preview,
  .cv-tarp-modal[data-mode="photo"] [data-tarpaulin-details-body],
  .cv-tarp-modal[data-mode="photo"] [data-tarpaulin-submit] { display:none !important; }
  .cv-tarp-modal[data-mode="upload"] [data-tarpaulin-details-body] { display:grid; gap:12px; }
  .cv-tarp-modal[data-mode="photo"] .cv-case-media-dialog { width:min(920px,100%); }
  .cv-tarp-modal[data-mode="photo"] [data-tarpaulin-modal-foot] button { display:inline-flex; }
  .cv-tarp-modal [data-tarpaulin-modal-body] { overflow:auto; }
  /* generated documents */
  .cv-doc-wrap     { padding:1rem; display:flex; flex-direction:column; gap:.8rem; }
  .cv-doc-row      { border:1px solid var(--border); border-radius:.75rem; background:#DCE6D6; padding:.9rem 1rem; display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
  .cv-doc-main     { display:flex; align-items:flex-start; gap:10px; min-width:220px; flex:1; }
  .cv-doc-icon     { width:2.15rem; height:2.15rem; color:var(--brand); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .cv-doc-name     { font-size:.9rem; font-weight:700; color:var(--ink); line-height:1.3; }
  .cv-doc-meta     { font-size:.76rem; color:var(--ink-muted); font-weight:600; margin-top:.18rem; }
  .cv-doc-actions  { display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
  .cv-shell .btn-outline,
  .cv-shell .btn-secondary {
    min-height:2.45rem;
    border-radius:.7rem;
    box-shadow:none !important;
    font-size:.82rem;
    font-weight:700;
    cursor:pointer;
    transition:background-color .16s ease, border-color .16s ease, color .16s ease;
  }
  .cv-shell .btn-outline {
    background:#E1E7D9;
    border-color:var(--border);
    color:var(--ink);
  }
  .cv-shell .btn-outline:hover {
    background:#C7D5BE;
    border-color:#8EA083;
    color:var(--ink);
  }
  .cv-shell .btn-secondary {
    background:var(--accent);
    border-color:var(--accent);
    color:#fff;
  }
  .cv-shell .btn-secondary:hover {
    background:#2F3A2E;
    border-color:#2F3A2E;
    color:#fff;
  }
  .cv-doc-alert    { border:1px solid #f0b8a8; background:#fff5f2; color:#7f2f22; border-radius:10px; padding:11px 13px; font-size:12px; }
  .cv-doc-alert-title { font-weight:800; margin-bottom:5px; }
  .cv-doc-alert ul { margin:0; padding-left:18px; }
  .cv-doc-alert li { margin:2px 0; }
  .cv-empty-note   { padding:1.1rem; text-align:center; font-size:.85rem; color:var(--ink-muted); font-style:italic; }
  @media(max-width:700px) {
    .cv-fields-2,
    .cv-stat-row,
    .cv-fin-grid { grid-template-columns:1fr; }
    .cv-fields-2 .cv-field-full { grid-column:auto; }
    .cv-hero-top,
    .cv-hero-right,
    .cv-hero-actions,
    .cv-hero-badges,
    .cv-interment-badge,
    .cv-modify-btn { width:100%; }
    .cv-hero-top { grid-template-columns:1fr; }
    .cv-interment-badge,
    .cv-modify-btn { justify-content:center; }
    .cv-addon-row,
    .cv-txn-head { flex-direction:column; align-items:flex-start; }
  }
  html[data-theme='dark'] .cv-stat,
  html[data-theme='dark'] .cv-pkg-box { background:rgba(255,255,255,.04); }
</style>

<div class="cv-shell">

  {{-- ── Case Header ── --}}
  <div class="cv-card cv-section-case" id="case-overview">
    <div class="cv-hero-accent"></div>
    <div class="cv-hero-body">
      <div class="cv-hero-top">
        <div class="cv-hero-titleblock">
          <div class="cv-hero-kicker">Case Full Information</div>
          <div class="cv-hero-case">{{ $funeral_case->case_code }}</div>
        </div>
        <div class="cv-hero-right">
          <div class="cv-hero-actions">
          <div class="cv-hero-badges">
            @if($isOtherBranch)
              <span class="status-pill-warning">Other Branch</span>
            @endif
            <span class="cv-interment-badge">
              <i class="bi bi-calendar-event"></i>
              Interment: {{ $displayIntermentAt ? $fmtDt($displayIntermentAt) : '—' }}
              <span class="{{ in_array($funeral_case->case_status, ['DRAFT','ACTIVE']) ? 'status-pill-warning' : 'status-pill-success' }}" style="margin-left:4px;">
                {{ $funeral_case->case_status }}
              </span>
            </span>
          </div>
          @if(!$isOtherBranch && auth()->user()?->can('update', $funeral_case))
            <a href="{{ route('funeral-cases.edit', ['funeral_case' => $funeral_case, 'return_to' => request()->fullUrl()]) }}"
               class="cv-modify-btn no-print">
              <i class="bi bi-pencil-square"></i>
              Edit Case
            </a>
          @endif
          </div>
        </div>
      </div>
      <div class="cv-hero-meta">
        <div class="cv-meta-item">
          <span class="cv-meta-label">Branch</span>
          <span class="cv-meta-value">{{ $funeral_case->branch?->branch_code ?? '—' }}{{ $funeral_case->branch?->branch_name ? ' — ' . $funeral_case->branch->branch_name : '' }}</span>
        </div>
        <div class="cv-meta-item">
          <span class="cv-meta-label">Date Created</span>
          <span class="cv-meta-value">{{ $funeral_case->created_at?->format('M d, Y g:i A') ?? '—' }}</span>
        </div>
        <div class="cv-meta-item">
          <span class="cv-meta-label">Service Requested</span>
          <span class="cv-meta-value">{{ $fmtDate($funeral_case->service_requested_at) }}</span>
        </div>
        <div class="cv-meta-item">
          <span class="cv-meta-label">Encoded By</span>
          <span class="cv-meta-value">{{ $funeral_case->encodedBy?->name ?? '—' }}</span>
        </div>
      </div>
    </div>
  </div>

  {{-- ── Client & Deceased (side by side) ── --}}
  <div class="cv-two-col cv-section-people" id="case-people">

    <div class="cv-card">
      <div class="cv-card-head">
        <div class="cv-card-icon"><i class="bi bi-person"></i></div>
        <span class="cv-card-title">Client</span>
      </div>
      <div class="cv-fields">
        <div class="cv-field">
          <div class="cv-field-label">Full Name</div>
          <div class="cv-field-value">{{ $funeral_case->client?->full_name ?? '—' }}</div>
        </div>
        @if($relationship = $funeral_case->clientRelationship())
          <div class="cv-field">
            <div class="cv-field-label">Relationship to Deceased</div>
            <div class="cv-field-value">{{ $relationship }}</div>
          </div>
        @endif
        <div class="cv-field">
          <div class="cv-field-label">Contact Number</div>
          <div class="cv-field-value">{{ $funeral_case->client?->contact_number ?? '—' }}</div>
        </div>
        <div class="cv-field">
          <div class="cv-field-label">Address</div>
          <div class="cv-field-value">{{ $funeral_case->client?->address ?: '—' }}</div>
        </div>
      </div>
    </div>

    <div class="cv-card">
      <div class="cv-card-head">
        <div class="cv-card-icon"><i class="bi bi-file-earmark-person"></i></div>
        <span class="cv-card-title">Deceased</span>
      </div>
      <div class="cv-deceased-layout">
        <aside class="cv-deceased-photo-panel" aria-label="Deceased photo">
          <div class="cv-deceased-photo-frame">
            @if($canViewProof && $deceasedPhotoPath)
              <button type="button" data-deceased-photo-preview-open aria-label="Preview deceased photo" style="display:block;width:100%;padding:0;border:0;background:transparent;">
                <img src="{{ route('funeral-cases.saved-attachment', [$funeral_case, 'deceased-photo']) }}" loading="lazy" alt="Deceased photo">
              </button>
            @else
              <div class="cv-deceased-photo-placeholder">
                <i class="bi bi-person-bounding-box" aria-hidden="true"></i>
                <span>{{ $deceasedPhotoPath ? 'Photo preview unavailable' : 'No deceased photo uploaded' }}</span>
              </div>
            @endif
          </div>
          <div class="cv-deceased-photo-actions no-print">
            @if($canManageCaseMedia)
              <button type="button" data-case-media-open="deceased-photo"><i class="bi bi-upload" aria-hidden="true"></i> {{ $deceasedPhotoPath ? 'Re-upload Photo' : 'Upload Photo' }}</button>
            @endif
            <button type="button" data-deceased-photo-details-toggle aria-expanded="false"><i class="bi bi-info-circle" aria-hidden="true"></i> Photo Details</button>
            <div class="cv-deceased-proof-popover cv-deceased-proof-popover--photo no-print" data-deceased-photo-details hidden role="dialog" aria-label="Deceased photo details">
              <strong>Photo details</strong>
              <p>Status: {{ $deceasedPhotoPath ? 'Uploaded' : 'Not uploaded' }}</p>
              @if($deceasedPhotoPath)
                <p>Uploaded by: {{ $deceasedPhotoAttachment?->uploader?->name ?? 'Not recorded' }}</p>
                <p>Uploaded on: {{ $deceasedPhotoAttachment?->created_at?->format('M d, Y · h:i A') ?? 'Not recorded' }}</p>
                @if($deceasedPhotoAttachment?->file_name)<p>File name: {{ $deceasedPhotoAttachment->file_name }}</p>@endif
              @endif
              @if($deceasedPhotoPath && !$deceasedPhotoAttachment)
                <p>Attached during intake. Uploader and upload date were not recorded.</p>
              @endif
            </div>
          </div>
        </aside>

        <div class="cv-deceased-details">
          <div class="cv-deceased-info-grid">
            <div class="cv-deceased-info-row"><div class="cv-field-label">Full Name</div><div class="cv-field-value">{{ $funeral_case->deceased?->full_name ?? '—' }}</div></div>
            @if($funeral_case->deceased?->address)
              <div class="cv-deceased-info-row"><div class="cv-field-label">Address</div><div class="cv-field-value">{{ $funeral_case->deceased->address }}</div></div>
            @endif
            <div class="cv-deceased-info-row"><div class="cv-field-label">Date of Birth</div><div class="cv-field-value">{{ $fmtDate($funeral_case->deceased?->born) }}</div></div>
            <div class="cv-deceased-info-row"><div class="cv-field-label">Date of Death</div><div class="cv-field-value">{{ $fmtDate($funeral_case->deceased?->died ?? $funeral_case->deceased?->date_of_death) }}</div></div>
            <div class="cv-deceased-info-row"><div class="cv-field-label">Age</div><div class="cv-field-value">{{ $funeral_case->deceased?->age ?? '—' }}</div></div>
            <div class="cv-deceased-info-row"><div class="cv-field-label">Cemetery</div><div class="cv-field-value">{{ $funeral_case->deceased?->place_of_cemetery ?? '—' }}</div></div>
            @if($funeral_case->deceased?->coffin_size || $funeral_case->deceased?->coffin_length_cm)
              <div class="cv-deceased-info-row"><div class="cv-field-label">Coffin</div><div class="cv-field-value">{{ $funeral_case->deceased?->coffin_size ?? '' }}{{ ($funeral_case->deceased?->coffin_size && $funeral_case->deceased?->coffin_length_cm) ? ' · ' : '' }}{{ $funeral_case->deceased?->coffin_length_cm ? number_format((float) $funeral_case->deceased->coffin_length_cm, 2) . ' cm' : '' }}</div></div>
            @endif
            @if($funeral_case->deceased?->senior_citizen_status || $funeral_case->deceased?->pwd_status || ($canViewProof && ($seniorProofAttachment || $seniorProofPath)))
              <div class="cv-deceased-info-row cv-deceased-info-row--full cv-deceased-status-proof">
                @if($funeral_case->deceased?->senior_citizen_status || $funeral_case->deceased?->pwd_status)
                  <div class="cv-deceased-status-proof__cell">
                    <div class="cv-field-label">Recorded Eligibility</div>
                    <div class="cv-field-value">{{ implode(' · ', array_filter([$funeral_case->deceased->senior_citizen_status ? 'Senior citizen' : null, $funeral_case->deceased->pwd_status ? 'Person with disability (PWD)' : null])) }}</div>
                    @if($funeral_case->deceased?->senior_citizen_status && $funeral_case->deceased?->senior_citizen_id_number)
                      <div class="cv-field-value">Senior ID No. {{ $funeral_case->deceased->senior_citizen_id_number }}</div>
                    @endif
                  </div>
                @endif
                @if($canViewProof && ($funeral_case->deceased?->senior_citizen_status || $seniorProofAttachment || $seniorProofPath))
                  <div class="cv-deceased-status-proof__cell">
                    <div class="cv-field-label">Senior ID / Certificate Proof</div>
                    <div class="cv-deceased-proof">
                      @if($seniorProofPath)
                        <button type="button" class="cv-senior-proof-thumb" data-senior-proof-preview-open aria-label="Preview senior ID or certificate proof">
                          @if($seniorProofIsImage)<img src="{{ route('funeral-cases.senior-proof', $funeral_case) }}" loading="lazy" alt="Senior ID or certificate proof">@else<i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>@endif
                        </button>
                        <button type="button" class="cv-case-attachment-link cv-proof-preview-link" data-senior-proof-preview-open>View Proof</button>
                      @endif
                      @if($canManageCaseMedia)<button class="cv-deceased-proof-action no-print" type="button" data-case-media-open="senior-proof"><i class="bi bi-upload" aria-hidden="true"></i> {{ $seniorProofPath ? 'Re-upload Proof' : 'Upload Proof' }}</button>@endif
                      <button class="cv-deceased-proof-action no-print" type="button" data-deceased-senior-details-toggle aria-expanded="false"><i class="bi bi-info-circle" aria-hidden="true"></i> Proof Details</button>
                      <div class="cv-deceased-proof-popover cv-deceased-proof-popover--senior no-print" data-deceased-senior-details hidden role="dialog" aria-label="Senior proof details">
                        <strong>Proof details</strong>
                        <p>Status: {{ $seniorProofPath ? 'Uploaded' : 'Not uploaded' }}</p>
                        @if($seniorProofPath)
                          <p>Uploaded by: {{ $seniorProofAttachment?->uploader?->name ?? 'Not recorded' }}</p>
                          <p>Uploaded on: {{ $seniorProofAttachment?->created_at?->format('M d, Y · h:i A') ?? 'Not recorded' }}</p>
                          @if($seniorProofAttachment?->file_name)<p>File name: {{ $seniorProofAttachment->file_name }}</p>@endif
                          @if(!$seniorProofAttachment)<p>Attached during intake. Uploader and upload date were not recorded.</p>@endif
                        @endif
                      </div>
                    </div>
                  </div>
                @endif
              </div>
            @endif
          </div>
        </div>
      </div>

      @if($canManageCaseMedia)
        <div class="cv-case-media-modal no-print" data-case-media-modal="deceased-photo" hidden>
          <section class="cv-case-media-dialog" role="dialog" aria-modal="true" aria-labelledby="deceased-photo-upload-title">
            <header class="cv-case-media-dialog-head"><div><strong id="deceased-photo-upload-title">{{ $deceasedPhotoPath ? 'Re-upload Deceased Photo' : 'Upload Deceased Photo' }}</strong><div class="cv-field-value" style="font-size:12px;font-weight:400;">Attach a photo or upload it using your phone.</div></div><button type="button" class="cv-case-media-dialog-close" data-case-media-close aria-label="Close"><i class="bi bi-x-lg" aria-hidden="true"></i></button></header>
            <form method="POST" action="{{ route('funeral-cases.media.save', [$funeral_case, 'deceased-photo']) }}" enctype="multipart/form-data" id="caseDeceasedPhotoForm">
              @csrf
              <div class="cv-case-media-dialog-body">
                <x-payment-proof-upload context="deceased" target="case-deceased-photo-target" label="Deceased Photo" accept="image/jpeg,image/png,image/webp" :limit="3" :restore-old="false" />
                <input id="case-deceased-photo-target" type="file" name="deceased_photo_file" accept="image/jpeg,image/png,image/webp" hidden>
                @error('deceased_photo_file')<span class="cv-case-media__error" role="alert">{{ $message }}</span>@enderror
              </div>
              <footer class="cv-case-media-dialog-foot"><button type="button" class="btn-secondary" data-case-media-close>Cancel</button><button type="submit" class="btn-primary" data-case-media-submit disabled>Save Photo</button></footer>
            </form>
          </section>
        </div>

        @if($funeral_case->deceased?->senior_citizen_status || $seniorProofPath)
          <div class="cv-case-media-modal no-print" data-case-media-modal="senior-proof" hidden>
            <section class="cv-case-media-dialog" role="dialog" aria-modal="true" aria-labelledby="senior-proof-upload-title">
              <header class="cv-case-media-dialog-head"><div><strong id="senior-proof-upload-title">{{ $seniorProofPath ? 'Re-upload Senior Proof' : 'Upload Senior Proof' }}</strong><div class="cv-field-value" style="font-size:12px;font-weight:400;">Attach a file or upload it using your phone.</div></div><button type="button" class="cv-case-media-dialog-close" data-case-media-close aria-label="Close"><i class="bi bi-x-lg" aria-hidden="true"></i></button></header>
              <form method="POST" action="{{ route('funeral-cases.media.save', [$funeral_case, 'senior-proof']) }}" enctype="multipart/form-data" id="caseSeniorProofForm">
                @csrf
                <div class="cv-case-media-dialog-body">
                  <x-payment-proof-upload context="senior" target="case-senior-proof-target" label="Senior ID / Certificate Proof" accept="image/jpeg,image/png,image/webp,application/pdf" :limit="5" :restore-old="false" />
                  <input id="case-senior-proof-target" type="file" name="senior_proof_file" accept="image/jpeg,image/png,image/webp,application/pdf" hidden>
                  @error('senior_proof_file')<span class="cv-case-media__error" role="alert">{{ $message }}</span>@enderror
                </div>
                <footer class="cv-case-media-dialog-foot"><button type="button" class="btn-secondary" data-case-media-close>Cancel</button><button type="submit" class="btn-primary" data-case-media-submit disabled>Save Proof</button></footer>
              </form>
            </section>
          </div>
        @endif
      @endif

      @if($canViewProof && $deceasedPhotoPath)
        <div class="cv-deceased-photo-preview no-print" data-deceased-photo-preview hidden>
          <div class="cv-deceased-photo-preview__dialog" role="dialog" aria-modal="true" aria-label="Deceased photo preview">
            <button type="button" class="cv-deceased-photo-preview__close" data-deceased-photo-preview-close aria-label="Close photo preview"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            <img src="{{ route('funeral-cases.saved-attachment', [$funeral_case, 'deceased-photo']) }}" alt="Deceased photo preview">
          </div>
        </div>
      @endif
      @if($canViewProof && $seniorProofPath)
        <div class="cv-deceased-photo-preview no-print" data-senior-proof-preview hidden>
          <div class="cv-deceased-photo-preview__dialog" role="dialog" aria-modal="true" aria-label="Senior ID or certificate proof preview">
            <button type="button" class="cv-deceased-photo-preview__close" data-senior-proof-preview-close aria-label="Close proof preview"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            @if($seniorProofIsImage)
              <img src="{{ route('funeral-cases.senior-proof', $funeral_case) }}" alt="Senior ID or certificate proof preview">
            @else
              <iframe src="{{ route('funeral-cases.senior-proof', $funeral_case) }}" title="Senior ID or certificate proof preview"></iframe>
            @endif
          </div>
        </div>
      @endif
    </div>

  </div>

  {{-- ── Package & Service ── --}}
  <div class="cv-card cv-section-service" id="case-service">
    <div class="cv-card-head">
      <div class="cv-card-icon"><i class="bi bi-box-seam"></i></div>
      <span class="cv-card-title">Package &amp; Service Schedule</span>
    </div>
    <div class="cv-fields cv-fields-2 cv-service-grid">
      <div class="cv-field cv-field-package {{ !$pkgCoffin && !$pkgPrice ? 'cv-field-full' : '' }}">
        <div class="cv-field-label">Package</div>
        <div class="cv-field-value">{{ $displayPackageName }}</div>
      </div>
      @if($pkgCoffin)
      <div class="cv-field">
        <div class="cv-field-label">Included Casket / Coffin</div>
        <div class="cv-field-value">{{ $pkgCoffin }}</div>
      </div>
      @endif
      @if($selectedCasket && (($selectedCasket['name'] ?? null) || (float) ($selectedCasket['reference_value'] ?? 0) > 0))
      <div class="cv-field">
        <div class="cv-field-label">Selected Casket / Coffin</div>
        <div class="cv-field-value">
          {{ $selectedCasket['name'] ?? 'Selected casket' }}
          @if($selectedCasket['material'] ?? null)
            <em> - {{ $selectedCasket['material'] }}</em>
          @endif
        </div>
      </div>
      @endif
      @if($selectedHearseName)
      <div class="cv-field">
        <div class="cv-field-label">Assigned Hearse</div>
        <div class="cv-field-value">{{ $selectedHearseName }}</div>
      </div>
      @endif
      @if($pkgPrice)
      <div class="cv-field">
        <div class="cv-field-label">Package Price</div>
        <div class="cv-field-value" style="font-variant-numeric:tabular-nums;">₱ {{ number_format((float) $pkgPrice, 2) }}</div>
      </div>
      @endif
      @if($funeral_case->service_type)
      <div class="cv-field cv-field-package">
        <div class="cv-field-label">Service Type</div>
        <div class="cv-field-value">{{ $funeral_case->service_type }}</div>
      </div>
      @endif
      @if(($discountDisplay['amount'] ?? 0) > 0 || ($discountDisplay['source'] ?? 'NONE') !== 'NONE')
      <div class="cv-field cv-field-full">
        <div class="cv-field-label">{{ ($discountDisplay['source'] ?? '') === 'PROMO' ? 'Saved Promo / Discount' : 'Saved Discount' }}</div>
        <div class="cv-field-value">
          {{ $discountDisplay['label'] ?? 'Discount' }}
          @if(($discountDisplay['type'] ?? null) === 'PERCENT')
            <em> - {{ number_format((float) ($discountDisplay['value'] ?? 0), 2) }}% off</em>
          @elseif((float) ($discountDisplay['value'] ?? 0) > 0)
            <em> - &#8369; {{ number_format((float) ($discountDisplay['value'] ?? 0), 2) }} off</em>
          @endif
          @if(($discountDisplay['starts_at'] ?? null) || ($discountDisplay['ends_at'] ?? null))
          <div style="font-size:11px;color:var(--ink-muted);font-weight:600;margin-top:2px;">
            @if($discountDisplay['starts_at'] ?? null)
              &middot; From {{ \Carbon\Carbon::parse($discountDisplay['starts_at'])->format('M d, Y') }}
            @endif
            @if($discountDisplay['ends_at'] ?? null)
              to {{ \Carbon\Carbon::parse($discountDisplay['ends_at'])->format('M d, Y') }}
            @endif
          </div>
          @endif
        </div>
      </div>
      @endif
      <section class="cv-schedule-fields" id="wake-schedule" aria-labelledby="wake-schedule-title">
      <div class="cv-subsection-head cv-schedule-fields__head">
        <div class="cv-card-icon"><i class="bi bi-calendar2-week"></i></div>
        <span class="cv-card-title" id="wake-schedule-title">Service Schedule</span>
      </div>
      <div class="cv-schedule-fields__body">
      <div class="cv-schedule-fields__grid">
      @if($funeral_case->viewing_type)
      <div class="cv-field cv-field-package" id="schedule-event-viewing-type">
        <div class="cv-field-label">Viewing Type</div>
        <div class="cv-field-value">{{ match ($funeral_case->viewing_type) { 'home' => 'Home Viewing', 'chapel' => 'Chapel Viewing', default => \Illuminate\Support\Str::headline($funeral_case->viewing_type) } }}</div>
      </div>
      @endif
      @if($funeral_case->wake_location)
      <div class="cv-field cv-field-package" id="schedule-event-wake-location">
        <div class="cv-field-label">{{ $funeral_case->viewing_type === 'chapel' ? 'Chapel' : 'Wake Location' }}</div>
        <div class="cv-field-value">{{ $funeral_case->wake_location }}</div>
        @if($funeral_case->viewing_type === 'chapel' && $funeral_case->chapel_address)
          <div class="cv-field-value">{{ $funeral_case->chapel_address }}</div>
        @endif
      </div>
      @endif
      <div class="cv-field cv-field-package" id="schedule-event-wake-duration">
        <div class="cv-field-label">Wake Duration</div>
        <div class="cv-field-value">{{ $displayWakeDays !== null ? $displayWakeDuration : $fmtDate(null) }}</div>
      </div>
      @if($funeral_case->viewing_type === 'chapel' && $chapelDays !== null)
      <div class="cv-field cv-field-package">
        <div class="cv-field-label">Chapel Coverage</div>
        <div class="cv-field-value">{{ $includedChapel ? 'Included in package' : 'Separate daily rate' }}</div>
        <div class="cv-field-value">{{ $coveredChapelDays }} covered · {{ max(0, $chapelDays - $coveredChapelDays) }} billable day(s)</div>
      </div>
      @endif
      @if($funeral_case->transport_option || $funeral_case->transport_notes)
      <div class="cv-field cv-field-package">
        <div class="cv-field-label">Transport</div>
        <div class="cv-field-value">{{ \Illuminate\Support\Str::headline($funeral_case->transport_option ?? '') }}</div>
        @if($funeral_case->transport_notes)<div class="cv-field-value">{{ $funeral_case->transport_notes }}</div>@endif
      </div>
      @endif
      @if($funeral_case->embalming_required || $funeral_case->embalming_status || $funeral_case->embalming_at || $funeral_case->embalming_notes)
      <div class="cv-field cv-field-package">
        <div class="cv-field-label">Embalming</div>
        <div class="cv-field-value">{{ \Illuminate\Support\Str::headline($funeral_case->embalming_status ?? ($funeral_case->embalming_required ? 'Required' : '')) }}</div>
        @if($funeral_case->embalming_at)<div class="cv-field-value">{{ $fmtDt($funeral_case->embalming_at) }}</div>@endif
        @if($funeral_case->embalming_notes)<div class="cv-field-value">{{ $funeral_case->embalming_notes }}</div>@endif
      </div>
      @endif
      <div class="cv-field cv-field-package" id="schedule-event-wake-start">
        <div class="cv-field-label">Wake Start Date &amp; Time</div>
        <div class="cv-field-value">{{ $fmtSchedule($funeral_case->wake_start_date, $funeral_case->wake_start_time) }}</div>
      </div>
      <div class="cv-field cv-field-package" id="schedule-event-wake-end">
        <div class="cv-field-label">Wake End Date &amp; Time</div>
        <div class="cv-field-value">{{ $fmtSchedule($funeral_case->wake_end_date, $funeral_case->wake_end_time) }}</div>
      </div>
      @if($funeral_case->funeral_service_at)
      <div class="cv-field cv-field-package" id="schedule-event-funeral-ceremony">
        <div class="cv-field-label">Funeral Ceremony Date &amp; Time</div>
        <div class="cv-field-value">
          {{ $fmtSchedule($funeral_case->funeral_service_at, $funeral_case->funeral_service_time) }}
        @if($ceremonyLocation = $funeral_case->ceremonyLocation())
          <span class="cv-ceremony-location"> · <i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $ceremonyLocation }}</span>
        @endif
        </div>
      </div>
      @endif
      <div class="cv-field cv-field-package" id="schedule-event-interment">
        <div class="cv-field-label">Interment Date &amp; Time</div>
        <div class="cv-field-value">{{ $fmtSchedule($displayIntermentAt, $funeral_case->interment_time ?? $displayIntermentAt?->format('H:i:s')) }}</div>
      </div>
      </div>
      <div class="cv-field cv-field-tarpaulin">
        <div class="cv-tarp-grid">
          <div class="cv-tarp-media-panel">
            <div class="cv-field-label">Tarpaulin Photo</div>
            @if($tarpaulinAttachment && $tarpaulinUrl)
              <button type="button" class="cv-tarp-preview" data-tarpaulin-open aria-label="Preview tarpaulin photo">
                <img src="{{ $tarpaulinUrl }}" alt="Tarpaulin photo reference for {{ $funeral_case->deceased?->full_name ?? $funeral_case->case_code }}">
              </button>
            @else
              <div class="cv-tarp-preview cv-tarp-preview--empty"><div class="cv-tarp-empty">No Tarpaulin Photo Uploaded</div></div>
            @endif
            <div class="cv-tarp-actions cv-deceased-photo-actions no-print">
              @if($tarpaulinAttachment && $canReplaceTarpaulin)
                <button type="button" data-tarpaulin-toggle="replace"><i class="bi bi-upload" aria-hidden="true"></i> Re-upload Photo</button>
              @elseif(! $tarpaulinAttachment && $canUploadTarpaulin)
                <button type="button" data-tarpaulin-toggle="upload"><i class="bi bi-upload" aria-hidden="true"></i> Upload Photo</button>
              @endif
              <button type="button" data-tarpaulin-details-toggle aria-expanded="false"><i class="bi bi-info-circle" aria-hidden="true"></i> Photo Details</button>
              @if($canDeleteTarpaulin && $tarpaulinAttachment)
                <form method="POST" action="{{ route('funeral-cases.tarpaulin.destroy', $funeral_case) }}" onsubmit="return confirm('Delete this tarpaulin photo?');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="cv-deceased-proof-action"><i class="bi bi-trash" aria-hidden="true"></i> Delete Photo</button>
                </form>
              @endif
              <div class="cv-deceased-proof-popover cv-tarp-meta no-print" data-tarpaulin-details hidden role="dialog" aria-label="Tarpaulin photo details">
                <strong>Photo details</strong>
                <div class="cv-tarp-meta__row"><span class="cv-tarp-meta__label">Status</span><span class="cv-tarp-meta__value">{{ $tarpaulinAttachment ? 'Uploaded' : 'Not uploaded' }}</span></div>
                <div class="cv-tarp-meta__row"><span class="cv-tarp-meta__label">Uploaded by</span><span class="cv-tarp-meta__value">{{ $tarpaulinAttachment?->uploader?->name ?? 'Not recorded' }}</span></div>
                <div class="cv-tarp-meta__row"><span class="cv-tarp-meta__label">Upload date</span><span class="cv-tarp-meta__value">{{ $tarpaulinAttachment?->created_at?->format('M d, Y g:i A') ?? 'Not recorded' }}</span></div>
              </div>
            </div>
          </div>
          <div class="cv-tarp-side">
            @if(! $tarpaulinAttachment && $canUploadTarpaulin)
              <form method="POST" action="{{ route('funeral-cases.tarpaulin.store', $funeral_case) }}" enctype="multipart/form-data" class="cv-tarp-form no-print" data-tarpaulin-form="upload">
                @csrf
                <input id="tarpaulin-photo-upload-target" type="file" name="photo" accept="image/jpeg,image/png" hidden>
                <x-payment-proof-upload context="tarpaulin" target="tarpaulin-photo-upload-target" label="Tarpaulin Photo" accept="image/jpeg,image/png" :limit="5" :restore-old="false" />
              </form>
            @endif
            @if($tarpaulinAttachment && $canReplaceTarpaulin)
              <form method="POST" action="{{ route('funeral-cases.tarpaulin.update', $funeral_case) }}" enctype="multipart/form-data" class="cv-tarp-form no-print" data-tarpaulin-form="replace">
                @csrf
                @method('PUT')
                <input id="tarpaulin-photo-replace-target" type="file" name="photo" accept="image/jpeg,image/png" hidden>
                <x-payment-proof-upload context="tarpaulin" target="tarpaulin-photo-replace-target" label="Tarpaulin Photo" accept="image/jpeg,image/png" :limit="5" :restore-old="false" />
              </form>
            @endif
            <div class="cv-field-value cv-tarp-note">Accepted files: JPG, JPEG, PNG. Maximum size: 5MB.</div>
          </div>
        </div>
      </div>
      </div>
      </section>
    </div>

    <div class="cv-pkg-pair">
    @if($displaySnapshot['inclusions'] || $pkgFreebieItems)
      @foreach(['Basic Funeral Inclusions' => $displaySnapshot['basic_inclusions'], 'Other Inclusions' => $displaySnapshot['other_inclusions']] as $heading => $pkgInclusionItems)
      <div class="cv-pkg-box">
        <div class="cv-pkg-box-head"><i class="bi bi-check2-circle" style="color:#6F8A6D"></i>{{ $heading }}</div>
        @if($pkgInclusionItems)
          <ul style="margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:3px;">
            @foreach($pkgInclusionItems as $item)
              <li style="display:flex;align-items:flex-start;gap:5px;font-size:12px;color:var(--ink);">
                <i class="bi bi-dot" style="color:#6F8A6D;font-size:15px;line-height:1.2;flex-shrink:0;"></i>{{ $item }}
              </li>
            @endforeach
          </ul>
        @else
          <p style="font-size:12px;color:var(--ink-muted);font-style:italic;margin:0;">Not configured.</p>
        @endif
      </div>
      @endforeach
      <div class="cv-pkg-box">
        <div class="cv-pkg-box-head"><i class="bi bi-gift" style="color:#B87956"></i>Freebies</div>
        @if($pkgFreebieItems)
          <ul style="margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:3px;">
            @foreach($pkgFreebieItems as $item)
              <li style="display:flex;align-items:flex-start;gap:5px;font-size:12px;color:var(--ink);">
                <i class="bi bi-dot" style="color:#B87956;font-size:15px;line-height:1.2;flex-shrink:0;"></i>{{ $item }}
              </li>
            @endforeach
          </ul>
        @else
          <p style="font-size:12px;color:var(--ink-muted);font-style:italic;margin:0;">Not configured.</p>
        @endif
      </div>
    @endif

    <div class="cv-addon-list">
      @if($caseAddOns->isNotEmpty())
        @foreach($caseAddOns as $addOn)
          <div class="cv-addon-row">
            <div>
              <div class="cv-field-label">Selected Add-on</div>
              <div class="cv-field-value" style="margin-top:1px;">{{ $addOn['name'] }}</div>
              @if($addOn['description'])
                <div style="font-size:11px;color:var(--ink-muted);font-weight:500;margin-top:2px;">{{ $addOn['description'] }}</div>
              @endif
            </div>
            <div style="text-align:right;flex-shrink:0;">
              <div class="cv-field-label">Qty {{ $addOn['quantity'] }}{{ $addOn['unit'] ? ' ' . $addOn['unit'] : '' }}</div>
              <div class="cv-field-value" style="margin-top:1px;font-variant-numeric:tabular-nums;">&#8369; {{ number_format((float) $addOn['line_total'], 2) }}</div>
              <div style="font-size:11px;color:var(--ink-muted);font-weight:600;">&#8369; {{ number_format((float) $addOn['unit_price'], 2) }} each</div>
            </div>
          </div>
        @endforeach
      @else
        <div class="cv-addon-row">
          <div>
            <div class="cv-field-label">Selected Add-ons</div>
            <div class="cv-field-value"><em>No optional add-ons selected.</em></div>
          </div>
        </div>
      @endif
    </div>
    </div>

    @if($serviceChargeItems->isNotEmpty())
    <div style="padding:0 16px 12px;">
      <div style="border:1px solid var(--border);border-radius:.7rem;padding:.85rem .95rem;background:#DCE6D6;">
        <div class="cv-field-label" style="margin-bottom:6px;">Package Adjustments &amp; Extra Charges</div>
        <div style="display:flex;flex-direction:column;gap:7px;">
          @foreach($serviceChargeItems as $charge)
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
              <div>
                <div class="cv-field-value" style="font-size:12px;">{{ $charge['label'] ?? \Illuminate\Support\Str::headline((string) ($charge['type'] ?? 'Charge')) }}</div>
                @if(isset($charge['excess'], $charge['rate']))
                  <div style="font-size:11px;color:var(--ink-muted);font-weight:600;">{{ $charge['excess'] }} extra @if(in_array(($charge['type'] ?? ''), ['body_retrieval','hearse'], true))km @else day(s) @endif x &#8369; {{ number_format((float) $charge['rate'], 2) }}</div>
                @endif
              </div>
              <div class="cv-field-value" style="font-variant-numeric:tabular-nums;white-space:nowrap;">&#8369; {{ number_format((float) ($charge['amount'] ?? 0), 2) }}</div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
    @endif

    @if($additionalDisplayItems->isNotEmpty())
    <div style="padding:0 16px 12px;">
      <div style="border:1px solid var(--border);border-radius:.7rem;padding:.85rem .95rem;background:#DCE6D6;display:flex;flex-direction:column;gap:7px;">
        <div class="cv-field-label">Itemized Additional Services</div>
        @foreach($additionalDisplayItems as $item)
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
          <div class="cv-field-value" style="margin-top:1px;">{{ $item['description'] ?: 'No description provided.' }}</div>
          <div class="cv-field-value" style="margin-top:1px;font-variant-numeric:tabular-nums;white-space:nowrap;">&#8369; {{ number_format((float) $item['amount'], 2) }}</div>
        </div>
        @endforeach
      </div>
    </div>
    @endif
  </div>

  @if(($tarpaulinAttachment && $tarpaulinUrl) || $canUploadTarpaulin || $canReplaceTarpaulin)
  <div class="cv-case-media-modal cv-tarp-modal no-print" data-tarpaulin-modal aria-hidden="true" hidden>
    <section class="cv-case-media-dialog" role="dialog" aria-modal="true" aria-label="Tarpaulin photo" aria-labelledby="tarpaulin-modal-title">
      <header class="cv-case-media-dialog-head">
        <div><strong id="tarpaulin-modal-title" data-tarpaulin-modal-title>Tarpaulin Photo</strong><div class="cv-field-value" style="font-size:12px;font-weight:400;">Attach a photo or upload it using your phone.</div></div>
        <button type="button" class="cv-case-media-dialog-close" data-tarpaulin-close aria-label="Close"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
      </header>
      <div class="cv-case-media-dialog-body" data-tarpaulin-modal-body>
        @if($tarpaulinAttachment && $tarpaulinUrl)
          <img class="cv-tarp-modal-preview" src="{{ $tarpaulinUrl }}" alt="Full tarpaulin photo reference">
        @endif
        <div data-tarpaulin-details-body></div>
      </div>
      <footer class="cv-case-media-dialog-foot" data-tarpaulin-modal-foot>
        <button type="button" class="btn-secondary" data-tarpaulin-close>Cancel</button>
        <button type="button" class="btn-primary" data-tarpaulin-submit disabled>Save Photo</button>
      </footer>
    </section>
  </div>
  @endif

  {{-- Generated Documents --}}
  <div class="cv-card cv-section-documents" id="case-documents">
    <div class="cv-card-head">
      <div class="cv-card-icon"><i class="bi bi-file-earmark-pdf"></i></div>
      <span class="cv-card-title">Generated Documents</span>
    </div>

    <div class="cv-doc-wrap">
      @if($errors->has('contract') || session('contract_missing_fields'))
        <div class="cv-doc-alert no-print">
          <div class="cv-doc-alert-title">{{ $errors->first('contract') ?: 'Cannot generate Funeral Contract.' }}</div>
          @if(session('contract_missing_fields'))
            <ul>
              @foreach(session('contract_missing_fields') as $missingField)
                <li>{{ $missingField }}</li>
              @endforeach
            </ul>
          @endif
        </div>
      @endif

      @if($funeralContract)
        <div class="cv-doc-row">
          <div class="cv-doc-main">
            <div class="cv-doc-icon"><i class="bi bi-file-earmark-text"></i></div>
            <div>
              <div class="cv-doc-name">Funeral Contract</div>
              <div class="cv-doc-meta">
                Reference No.: {{ $funeralContract->contract_number ?? '—' }}<br>
                {{ $funeralContract->file_name }}<br>
                Generated {{ $funeralContract->generated_at?->format('M d, Y h:i A') ?? '—' }}
                @if($funeralContract->generator)
                  by {{ $funeralContract->generator->name }}
                @endif
              </div>
            </div>
          </div>

          <div class="cv-doc-actions no-print">
            <a href="{{ route('funeral-cases.documents.preview', [$funeral_case, $funeralContract]) }}" target="_blank" rel="noopener" class="btn-outline">
              <i class="bi bi-eye mr-1"></i>Review Funeral Contract
            </a>
            @if(!auth()->user()?->isOwner())
            <a href="{{ route('funeral-cases.documents.print', [$funeral_case, $funeralContract]) }}" target="_blank" rel="noopener" class="btn-outline">
              <i class="bi bi-printer mr-1"></i>Print
            </a>
            @endif
            @if($canGenerateFuneralContract)
              <a href="{{ route('funeral-cases.documents.contract.preview', ['funeral_case' => $funeral_case, 'return_to' => $returnUrl ?? null]) }}" class="btn-secondary">
                <i class="bi bi-file-earmark-text mr-1"></i>Review Contract Form
              </a>
            @endif
          </div>
        </div>
      @else
        <div class="cv-doc-row">
          <div class="cv-doc-main">
            <div class="cv-doc-icon"><i class="bi bi-file-earmark-plus"></i></div>
            <div>
              <div class="cv-doc-name">Funeral Contract</div>
              <div class="cv-doc-meta">No generated Funeral Contract is available for this case.</div>
            </div>
          </div>

          @if($canGenerateFuneralContract)
            <div class="cv-doc-actions no-print">
              <a href="{{ route('funeral-cases.documents.contract.preview', ['funeral_case' => $funeral_case, 'return_to' => $returnUrl ?? null]) }}" class="btn-secondary">
                <i class="bi bi-file-earmark-pdf mr-1"></i>Prepare Contract
              </a>
            </div>
          @endif
        </div>
      @endif
    </div>
  </div>

  {{-- ── Payment Summary ── --}}
  <div class="cv-card cv-section-payment" id="case-payments">
    <div class="cv-card-head">
      <div class="cv-card-icon"><i class="bi bi-cash-stack"></i></div>
      <span class="cv-card-title">Payment Summary</span>
    </div>

    <div class="cv-stat-row cv-payment-total-row">
      <div class="cv-stat">
        <div class="cv-stat-lbl">Service Amount</div>
        <div class="cv-stat-val">₱ {{ number_format((float) $funeral_case->total_amount, 2) }}</div>
      </div>
      <div class="cv-stat s-paid">
        <div class="cv-stat-lbl">Total Paid</div>
        <div class="cv-stat-val">₱ {{ number_format((float) $funeral_case->total_paid, 2) }}</div>
      </div>
      <div class="cv-stat {{ $balanceDue ? 's-due' : 's-ok' }}">
        <div class="cv-stat-lbl">Remaining Balance</div>
        <div class="cv-stat-val">₱ {{ number_format((float) $funeral_case->balance_amount, 2) }}</div>
      </div>
    </div>

    <div class="cv-fin-grid">
      <div class="cv-fin-item">
        <div class="cv-field-label">Package Price</div>
        <div class="cv-field-value" style="font-variant-numeric:tabular-nums;">&#8369; {{ number_format((float) ($pkgPrice ?? 0), 2) }}</div>
      </div>
      <div class="cv-fin-item">
        <div class="cv-field-label">Add-ons Total</div>
        <div class="cv-field-value" style="font-variant-numeric:tabular-nums;">&#8369; {{ number_format($addOnsTotal, 2) }}</div>
      </div>
      @if($serviceChargeTotal > 0)
      <div class="cv-fin-item">
        <div class="cv-field-label">Extra Charges</div>
        <div class="cv-field-value" style="font-variant-numeric:tabular-nums;">&#8369; {{ number_format($serviceChargeTotal, 2) }}</div>
      </div>
      @endif
      <div class="cv-fin-item">
        <div class="cv-field-label">Additional Services</div>
        <div class="cv-field-value" style="font-variant-numeric:tabular-nums;">&#8369; {{ number_format($additionalDisplayTotal, 2) }}</div>
      </div>
      <div class="cv-fin-item">
        <div class="cv-field-label">Subtotal</div>
        <div class="cv-field-value" style="font-variant-numeric:tabular-nums;">₱ {{ number_format((float) ($funeral_case->subtotal_amount ?? $funeral_case->total_amount), 2) }}</div>
      </div>
      <div class="cv-fin-item">
        <div class="cv-field-label">Discount</div>
        <div class="cv-field-value" style="font-variant-numeric:tabular-nums;">
          &#8369; {{ number_format((float) ($funeral_case->discount_amount ?? 0), 2) }}
          @if(($discountDisplay['label'] ?? '') && ($discountDisplay['label'] ?? 'No discount') !== 'No discount')
            <em> - {{ $discountDisplay['label'] }}</em>
          @elseif($funeral_case->discount_note)
            <em> - {{ $funeral_case->discount_note }}</em>
          @endif
        </div>
      </div>
      @if((float) ($funeral_case->tax_amount ?? 0) > 0)
      <div class="cv-fin-item">
        <div class="cv-field-label">Tax ({{ $funeral_case->tax_rate }}%)</div>
        <div class="cv-field-value" style="font-variant-numeric:tabular-nums;">₱ {{ number_format((float) $funeral_case->tax_amount, 2) }}</div>
      </div>
      @endif
      @if($lastValidPayment)
      <div class="cv-fin-item">
        <div class="cv-field-label">Last Payment</div>
        <div class="cv-field-value">{{ $lastValidPayment->paid_at?->format('M d, Y g:i A') ?? $fmtDate($lastValidPayment->paid_date) }}</div>
      </div>
      @endif
    </div>
  </div>

  {{-- ── Payment Transactions ── --}}
  <div class="cv-card cv-section-transactions">
    <div class="cv-card-head">
      <div class="cv-card-icon"><i class="bi bi-receipt"></i></div>
      <span class="cv-card-title">Transactions</span>
    </div>

    @if($casePayments->isNotEmpty())
      <div class="cv-txn-list">
        @foreach($casePayments as $pmt)
          <div class="cv-txn">
            <div class="cv-txn-head">
              <span class="cv-txn-receipt">{{ $pmt->display_payment_record_no ?? '—' }}</span>
              <span class="{{ $pmt->isVoided() || $pmt->ledgerCorrection()?->status === 'PENDING' ? 'status-pill-warning' : 'status-pill-success' }}">{{ $pmt->ledgerStatusLabel($pmt->ledgerCorrection()) }}</span>
            </div>
            <div class="cv-txn-grid">
              <div>
                <div class="cv-txn-lbl">Payment Amount</div>
                <div class="cv-txn-val" style="font-size:13px;font-weight:800;font-variant-numeric:tabular-nums;">₱ {{ number_format((float) $pmt->amount, 2) }}</div>
              </div>
              @if(!$pmt->isVoided() && $pmt->balance_after_payment !== null)
              <div>
                <div class="cv-txn-lbl">Balance After</div>
                <div class="cv-txn-val" style="font-variant-numeric:tabular-nums;">₱ {{ number_format((float) $pmt->balance_after_payment, 2) }}</div>
              </div>
              @endif
              <div>
                <div class="cv-txn-lbl">Payment Method</div>
                <div class="cv-txn-val">
                  {{ \App\Support\Payments\PaymentDetails::label($pmt) }}
                  @if(\App\Support\Payments\PaymentDetails::referenceLabel($pmt))
                    <div style="font-size:11px;color:#5F685F;font-weight:600;">{{ \App\Support\Payments\PaymentDetails::referenceLabel($pmt) }}</div>
                  @endif
                </div>
              </div>
              <div>
                <div class="cv-txn-lbl">Payment Date &amp; Time</div>
                <div class="cv-txn-val">{{ $fmtDt($pmt->paid_at) !== '—' ? $fmtDt($pmt->paid_at) : $fmtDate($pmt->paid_date) }}</div>
              </div>
              <div>
                <div class="cv-txn-lbl">Receipt / OR No.</div>
                <div class="cv-txn-val">{{ $pmt->receipt_or_no ?: ($pmt->accounting_reference_no ?? '—') }}</div>
              </div>
              <div>
                <div class="cv-txn-lbl">Encoded By</div>
                <div class="cv-txn-val">{{ $pmt->encodedBy?->name ?? $pmt->recordedBy?->name ?? '—' }}</div>
              </div>
            </div>
            <div class="cv-txn-links">
              <div class="cv-txn-related">
                @include('staff.payments._ledger_correction_links', ['payment' => $pmt, 'showCorrectionLink' => false])
              </div>
              @php
                $visibleCorrectionHistory = $pmt->correctionRequests
                    ->filter(fn ($request) => auth()->user() && $request->isVisibleTo(auth()->user()))
                    ->sortByDesc('id')
                    ->values();
                $correctionHistoryCounts = $visibleCorrectionHistory->countBy('status');
                $correctionDrawerId = 'case-correction-history-'.$pmt->id;
              @endphp
              <div class="cv-txn-actions no-print">
                @if($canViewProof && $pmt->proof_path && $pmt->proof_mime)
                  <a class="cv-txn-action" href="{{ route('payment-proof.payment', $pmt) }}" data-proof-viewer data-proof-mime="{{ $pmt->proof_mime }}"><i class="bi bi-paperclip" aria-hidden="true"></i> View Proof</a>
                @endif
                @if($visibleCorrectionHistory->isNotEmpty())
                  <button class="cv-txn-action" type="button" data-correction-history-open="{{ $correctionDrawerId }}" aria-controls="{{ $correctionDrawerId }}" aria-haspopup="dialog">
                    <i class="bi bi-clock-history" aria-hidden="true"></i> Correction history <span>({{ $visibleCorrectionHistory->count() }})</span>
                  </button>
                @endif
              </div>
            </div>
          </div>
          @if($visibleCorrectionHistory->isNotEmpty())
            <div class="cv-correction-drawer no-print" id="{{ $correctionDrawerId }}" data-correction-history-drawer hidden>
              <aside class="cv-correction-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="{{ $correctionDrawerId }}-title" tabindex="-1">
                <header class="cv-correction-drawer__head">
                  <div>
                    <small>Payment record · {{ $pmt->display_payment_record_no ?? 'Record number unavailable' }}</small>
                    <h2 id="{{ $correctionDrawerId }}-title">Correction history</h2>
                  </div>
                  <button type="button" class="cv-correction-drawer__close" data-correction-history-close aria-label="Close correction history"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                </header>
                <div class="cv-correction-drawer__body">
                  <div class="cv-correction-drawer__payment">
                    <div><span>Payment amount</span><strong>₱ {{ number_format((float) $pmt->amount, 2) }}</strong></div>
                    <div><span>Payment method</span><strong>{{ \App\Support\Payments\PaymentDetails::label($pmt) }}</strong></div>
                    <div><span>Paid at</span><strong>{{ $fmtDt($pmt->paid_at) !== '—' ? $fmtDt($pmt->paid_at) : $fmtDate($pmt->paid_date) }}</strong></div>
                    <div><span>Receipt / OR No.</span><strong>{{ $pmt->receipt_or_no ?: ($pmt->accounting_reference_no ?? 'Not recorded') }}</strong></div>
                  </div>
                  <div class="cv-correction-drawer__counts" aria-label="Correction request counts by status">
                    @foreach(['PENDING', 'APPROVED', 'REJECTED', 'WITHDRAWN'] as $historyStatus)
                      @if($correctionHistoryCounts->has($historyStatus))
                        <span class="cv-correction-drawer__count" data-status="{{ $historyStatus }}">{{ \Illuminate\Support\Str::headline($historyStatus) }} · {{ $correctionHistoryCounts[$historyStatus] }}</span>
                      @endif
                    @endforeach
                  </div>
                  <ol class="cv-correction-drawer__list">
                    @foreach($visibleCorrectionHistory as $correctionRequest)
                      <li class="cv-correction-drawer__item">
                        <div class="cv-correction-drawer__item-head">
                          <strong>Request #{{ $correctionRequest->id }} · {{ $correctionRequest->correction_type === 'VOID_ONLY' ? 'Void payment' : 'Correct payment' }}</strong>
                          <span class="cv-correction-drawer__status" data-status="{{ $correctionRequest->status }}">{{ \Illuminate\Support\Str::headline($correctionRequest->status) }}</span>
                        </div>
                        <div class="cv-correction-drawer__meta">
                          <p>Requested by {{ $correctionRequest->requester?->name ?? 'Unknown user' }} · {{ $correctionRequest->created_at?->format('M d, Y g:i A') ?? 'Date unavailable' }}</p>
                          @if($correctionRequest->reason_category || $correctionRequest->reason)
                            <p>Reason: {{ $correctionRequest->reason_category }}{{ $correctionRequest->reason ? ' · '.$correctionRequest->reason : '' }}</p>
                          @endif
                          @if($correctionRequest->reviewed_at)
                            <p>Reviewed by {{ $correctionRequest->reviewer?->name ?? 'Unknown user' }} · {{ $correctionRequest->reviewed_at->format('M d, Y g:i A') }}</p>
                          @endif
                          @if($correctionRequest->review_reason)
                            <p>Review note: {{ $correctionRequest->review_reason }}</p>
                          @endif
                        </div>
                        <a class="cv-correction-drawer__link" href="{{ route('payments.corrections.show', $correctionRequest) }}" data-payment-workflow>View request details <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                      </li>
                    @endforeach
                  </ol>
                </div>
              </aside>
            </div>
          @endif
        @endforeach
      </div>
    @else
      <div class="cv-empty-note">No payment transactions recorded yet.</div>
    @endif
  </div>

  {{-- ── Record Source (OTHER_BRANCH cases) ── --}}
  @if($isOtherBranch || $funeral_case->reporter_name)
  <div class="cv-card cv-section-source" id="case-source">
    <div class="cv-card-head">
      <div class="cv-card-icon"><i class="bi bi-diagram-3"></i></div>
      <span class="cv-card-title">Record Source</span>
    </div>
    <div class="cv-fields cv-fields-2">
      @if($funeral_case->reportedBranch || $funeral_case->branch)
      <div class="cv-field">
        <div class="cv-field-label">Reported Branch</div>
        <div class="cv-field-value">{{ $funeral_case->reportedBranch?->branch_code ?? $funeral_case->branch?->branch_code ?? '—' }}</div>
      </div>
      @endif
      @if($funeral_case->reported_at)
      <div class="cv-field">
        <div class="cv-field-label">Report Submitted At</div>
        <div class="cv-field-value">{{ $fmtDt($funeral_case->reported_at) }}</div>
      </div>
      @endif
      @if($funeral_case->reporter_name)
      <div class="cv-field">
        <div class="cv-field-label">Reporter</div>
        <div class="cv-field-value">{{ $funeral_case->reporter_name }}</div>
      </div>
      @endif
      @if($funeral_case->reporter_contact)
      <div class="cv-field">
        <div class="cv-field-label">Reporter Contact</div>
        <div class="cv-field-value">{{ $funeral_case->reporter_contact }}</div>
      </div>
      @endif
    </div>
  </div>
  @endif

</div>{{-- .cv-shell --}}
<script>
  (() => {
    const staffCaseVisual = @json((bool) ($staffCaseVisual ?? false));
    {
      const tarpaulinSection = document.querySelector('.cv-field-tarpaulin');
      const packageDetails = document.querySelector('.cv-pkg-pair');
      const serviceGrid = document.querySelector('.cv-service-grid');
      const schedule = document.querySelector('#wake-schedule');
      if (schedule && serviceGrid?.contains(schedule)) {
        if (packageDetails) schedule.before(packageDetails);
        const scheduleGrid = schedule.querySelector('.cv-schedule-fields__grid');
        if (scheduleGrid && tarpaulinSection) {
          let scheduleBody = schedule.querySelector('.cv-schedule-fields__body');
          if (!scheduleBody) {
            scheduleBody = document.createElement('div');
            scheduleBody.className = 'cv-schedule-fields__body';
            schedule.append(scheduleBody);
          }
          if (scheduleGrid.parentElement !== scheduleBody) scheduleBody.append(scheduleGrid);
          scheduleBody.append(tarpaulinSection);
        }
      }
    }
    const forms = [...document.querySelectorAll('[data-tarpaulin-form]')];
    const modal = document.querySelector('[data-tarpaulin-modal]');
    const detailsBody = modal?.querySelector('[data-tarpaulin-details-body]');
    const modalTitle = modal?.querySelector('[data-tarpaulin-modal-title]');
    const modalFooter = modal?.querySelector('[data-tarpaulin-modal-foot]');
    const modalCancel = modalFooter?.querySelector('[data-tarpaulin-close]');
    const modalSubmit = modal?.querySelector('[data-tarpaulin-submit]');
    let returnFocus = null;
    let modalAnchor = null;
    let activeUploadForm = null;
    const modalFocusable = () => [...(modal?.querySelectorAll(
      'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
    ) || [])].filter(element => !element.hidden && element.getClientRects().length > 0);
    if (detailsBody) {
      [...forms, document.querySelector('.cv-tarp-side > .cv-tarp-note')].forEach(node => {
        if (node) detailsBody.appendChild(node);
      });
    }
    forms.forEach(form => form.addEventListener('submit', event => {
      const file = form.querySelector('input[type="file"][id$="-target"]');
      if (file?.files?.length) return;
      event.preventDefault();
      const widget = form.querySelector('[data-payment-proof]');
      const status = widget?.querySelector('[data-proof-state]');
      if (status) status.textContent = 'Choose a photo or upload one using your phone before saving.';
      widget?.querySelector('[data-proof-choose]')?.focus();
    }));
    const showForm = name => {
      activeUploadForm = forms.find(form => form.dataset.tarpaulinForm === name) || null;
      forms.forEach(form => form.classList.toggle('open', form === activeUploadForm));
      if (modalSubmit) {
        const target = activeUploadForm?.querySelector('input[type="file"][id$="-target"]');
        modalSubmit.disabled = !target?.files?.length;
      }
    };
    const closeModal = () => {
      if (!modal || modal.hidden) return;
      modal.hidden = true;
      modal.setAttribute('aria-hidden', 'true');
      modal.removeAttribute('data-mode');
      document.documentElement.classList.remove('cv-tarp-modal-open');
      document.body.classList.remove('cv-tarp-modal-open');
      if (modalAnchor) {
        modalAnchor.replaceWith(modal);
        modalAnchor = null;
      }
      if (detailsBody) showForm(null);
      returnFocus?.focus({preventScroll:true});
    };
    const openModal = (mode, trigger) => {
      if (!modal) return;
      returnFocus = trigger;
      if (modal.parentElement !== document.body) {
        modalAnchor = document.createComment('tarpaulin-modal-position');
        modal.before(modalAnchor);
        document.body.appendChild(modal);
      }
      modal.dataset.mode = mode;
      if (modalTitle) modalTitle.textContent = mode === 'upload'
        ? (trigger?.dataset.tarpaulinToggle === 'replace' ? 'Re-upload Tarpaulin Photo' : 'Upload Tarpaulin Photo')
        : 'Tarpaulin Photo';
      modal.hidden = false;
      modal.setAttribute('aria-hidden', 'false');
      if (modalCancel) modalCancel.textContent = mode === 'photo' ? 'Close' : 'Cancel';
      if (modalSubmit) modalSubmit.hidden = mode !== 'upload';
      document.documentElement.classList.add('cv-tarp-modal-open');
      document.body.classList.add('cv-tarp-modal-open');
      document.body.removeAttribute('data-sidebar-hovered');
      modal.querySelector('[data-tarpaulin-modal-body]')?.scrollTo(0, 0);
      requestAnimationFrame(() => modalFocusable()[0]?.focus());
    };

    document.querySelectorAll('[data-tarpaulin-toggle]').forEach(button => {
      button.addEventListener('click', () => {
        showForm(button.dataset.tarpaulinToggle);
        openModal('upload', button);
      });
    });

    forms.forEach(form => form.querySelector('input[type="file"][id$="-target"]')?.addEventListener('change', () => {
      if (form === activeUploadForm && modalSubmit) modalSubmit.disabled = !form.querySelector('input[type="file"][id$="-target"]')?.files?.length;
    }));
    modalSubmit?.addEventListener('click', () => {
      if (!activeUploadForm) return;
      if (!activeUploadForm.querySelector('input[type="file"][id$="-target"]')?.files?.length) {
        activeUploadForm.querySelector('[data-proof-choose]')?.focus();
        return;
      }
      activeUploadForm.requestSubmit();
    });

    const openButton = document.querySelector('[data-tarpaulin-open]');
    const closeButtons = document.querySelectorAll('[data-tarpaulin-close]');
    openButton?.addEventListener('click', () => openModal('photo', openButton));
    closeButtons.forEach(button => button.addEventListener('click', closeModal));
    modal?.addEventListener('click', event => {
      if (event.target === modal) closeModal();
    });
    document.addEventListener('keydown', event => {
      if (!modal || modal.hidden) return;
      if (event.key === 'Escape') { closeModal(); return; }
      if (event.key !== 'Tab') return;
      const focusable = modalFocusable();
      if (!focusable.length) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });

    const caseRoot = document.getElementById('caseViewContent');
    const correctionHistoryDrawers = [...(caseRoot?.querySelectorAll('[data-correction-history-drawer]') || [])];
    let activeCorrectionDrawer = null;
    let correctionDrawerReturnFocus = null;
    const correctionDrawerAnchors = new WeakMap();
    const moveCorrectionDrawerToBody = drawer => {
      if (!drawer || drawer.parentElement === document.body) return;
      const anchor = document.createComment('case-correction-history-position');
      drawer.before(anchor);
      correctionDrawerAnchors.set(drawer, anchor);
      document.body.appendChild(drawer);
    };
    const restoreCorrectionDrawer = drawer => {
      const anchor = drawer ? correctionDrawerAnchors.get(drawer) : null;
      if (!anchor?.parentNode) return;
      anchor.parentNode.insertBefore(drawer, anchor);
      anchor.remove();
      correctionDrawerAnchors.delete(drawer);
    };
    const closeCorrectionDrawer = drawer => {
      if (!drawer || drawer.hidden) return;
      drawer.classList.remove('is-open');
      window.clearTimeout(drawer._closeTimer);
      drawer._closeTimer = window.setTimeout(() => {
        drawer.hidden = true;
        restoreCorrectionDrawer(drawer);
        if (!activeCorrectionDrawer) document.body.classList.remove('cv-correction-drawer-open');
      }, 220);
      activeCorrectionDrawer = null;
      correctionDrawerReturnFocus?.focus({preventScroll:true});
    };
    caseRoot?.querySelectorAll('[data-correction-history-open]').forEach(button => {
      button.addEventListener('click', () => {
        const drawer = document.getElementById(button.dataset.correctionHistoryOpen);
        if (!drawer) return;
        if (activeCorrectionDrawer) closeCorrectionDrawer(activeCorrectionDrawer);
        window.clearTimeout(drawer._closeTimer);
        correctionDrawerReturnFocus = button;
        activeCorrectionDrawer = drawer;
        moveCorrectionDrawerToBody(drawer);
        drawer.hidden = false;
        document.body.classList.add('cv-correction-drawer-open');
        requestAnimationFrame(() => {
          drawer.classList.add('is-open');
          drawer.querySelector('.cv-correction-drawer__panel')?.focus({preventScroll:true});
        });
      });
    });
    correctionHistoryDrawers.forEach(drawer => {
      drawer.querySelectorAll('[data-correction-history-close]').forEach(button => button.addEventListener('click', () => closeCorrectionDrawer(drawer)));
      drawer.addEventListener('click', event => { if (event.target === drawer) closeCorrectionDrawer(drawer); });
    });
    document.addEventListener('keydown', event => {
      if (!activeCorrectionDrawer || activeCorrectionDrawer.hidden) return;
      if (event.key === 'Escape') {
        event.preventDefault();
        closeCorrectionDrawer(activeCorrectionDrawer);
        return;
      }
      if (event.key !== 'Tab') return;
      const focusable = [...activeCorrectionDrawer.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])')]
        .filter(element => !element.hidden && element.getClientRects().length > 0);
      if (!focusable.length) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });
    const mediaModals = [...(caseRoot?.querySelectorAll('[data-case-media-modal]') || [])];
    const portalAnchors = new WeakMap();
    const moveModalToBody = modalEl => {
      if (!modalEl || modalEl.parentElement === document.body) return;
      const anchor = document.createComment('case-media-modal-position');
      modalEl.before(anchor);
      portalAnchors.set(modalEl, anchor);
      document.body.appendChild(modalEl);
    };
    const restoreModal = modalEl => {
      const anchor = modalEl ? portalAnchors.get(modalEl) : null;
      if (!anchor?.parentNode) return;
      anchor.parentNode.insertBefore(modalEl, anchor);
      anchor.remove();
      portalAnchors.delete(modalEl);
    };
    const closeCaseMedia = modalEl => {
      if (!modalEl) return;
      modalEl.hidden = true;
      restoreModal(modalEl);
      document.body.classList.remove('cv-case-media-open');
      modalEl._returnFocus?.focus({preventScroll:true});
    };
    caseRoot?.querySelectorAll('[data-case-media-open]').forEach(button => {
      button.addEventListener('click', () => {
        const modalEl = caseRoot.querySelector(`[data-case-media-modal="${button.dataset.caseMediaOpen}"]`);
        if (!modalEl) return;
        modalEl._returnFocus = button;
        moveModalToBody(modalEl);
        modalEl.hidden = false;
        document.body.classList.add('cv-case-media-open');
        modalEl.querySelector('[data-proof-choose]')?.focus();
      });
    });
    mediaModals.forEach(modalEl => {
      modalEl.querySelectorAll('[data-case-media-close]').forEach(button => button.addEventListener('click', () => closeCaseMedia(modalEl)));
      modalEl.addEventListener('click', event => { if (event.target === modalEl) closeCaseMedia(modalEl); });
      const form = modalEl.querySelector('form');
      const target = form?.querySelector('input[type="file"][id$="-target"]');
      const save = form?.querySelector('[data-case-media-submit]');
      target?.addEventListener('change', () => { if (save) save.disabled = !target.files?.length; });
      form?.addEventListener('submit', event => {
        if (!target?.files?.length) { event.preventDefault(); form.querySelector('[data-proof-choose]')?.focus(); }
      });
    });
    const photoPreview = caseRoot?.querySelector('[data-deceased-photo-preview]');
    const closePhotoPreview = () => { if (photoPreview) { photoPreview.hidden = true; restoreModal(photoPreview); photoPreview._returnFocus?.focus({preventScroll:true}); } document.body.classList.remove('cv-case-media-open'); };
    caseRoot?.querySelector('[data-deceased-photo-preview-open]')?.addEventListener('click', event => {
      if (!photoPreview) return;
      photoPreview._returnFocus = event.currentTarget;
      moveModalToBody(photoPreview);
      photoPreview.hidden = false;
      document.body.classList.add('cv-case-media-open');
      photoPreview.querySelector('[data-deceased-photo-preview-close]')?.focus();
    });
    caseRoot?.querySelectorAll('[data-deceased-photo-preview-close]').forEach(button => button.addEventListener('click', closePhotoPreview));
    photoPreview?.addEventListener('click', event => { if (event.target === photoPreview) closePhotoPreview(); });
    const seniorProofPreview = caseRoot?.querySelector('[data-senior-proof-preview]');
    const closeSeniorProofPreview = () => { if (seniorProofPreview) { seniorProofPreview.hidden = true; restoreModal(seniorProofPreview); seniorProofPreview._returnFocus?.focus({preventScroll:true}); } document.body.classList.remove('cv-case-media-open'); };
    caseRoot?.querySelectorAll('[data-senior-proof-preview-open]').forEach(button => button.addEventListener('click', event => {
      if (!seniorProofPreview) return;
      seniorProofPreview._returnFocus = event.currentTarget;
      moveModalToBody(seniorProofPreview);
      seniorProofPreview.hidden = false;
      document.body.classList.add('cv-case-media-open');
      seniorProofPreview.querySelector('[data-senior-proof-preview-close]')?.focus();
    }));
    caseRoot?.querySelectorAll('[data-senior-proof-preview-close]').forEach(button => button.addEventListener('click', closeSeniorProofPreview));
    seniorProofPreview?.addEventListener('click', event => { if (event.target === seniorProofPreview) closeSeniorProofPreview(); });
    const positionPopoverBesideButton = ({toggle, popover}) => {
      if (!toggle || !popover || popover.hidden) return;
      const rect = toggle.getBoundingClientRect();
      const width = Math.max(180, Math.min(320, window.innerWidth - 24));
      popover.style.width = `${width}px`;
      const left = Math.max(12, Math.min(rect.right + 8, window.innerWidth - width - 12));
      const top = Math.max(12, Math.min(rect.top, window.innerHeight - popover.offsetHeight - 12));
      popover.style.left = `${left}px`;
      popover.style.top = `${top}px`;
    };
    const bindPopover = (toggleSelector, popoverSelector) => {
      const toggle = caseRoot?.querySelector(toggleSelector);
      const popover = caseRoot?.querySelector(popoverSelector);
      toggle?.addEventListener('click', () => {
        const open = popover?.hidden ?? true;
        if (popover) {
          if (open) {
            popover.hidden = false;
            popover.style.visibility = 'hidden';
            positionPopoverBesideButton({toggle, popover});
            popover.style.visibility = '';
          } else {
            popover.hidden = true;
            popover.style.removeProperty('left');
            popover.style.removeProperty('top');
            popover.style.removeProperty('width');
          }
        }
        toggle.setAttribute('aria-expanded', String(open));
      });
      return {toggle, popover};
    };
    const photoDetails = bindPopover('[data-deceased-photo-details-toggle]', '[data-deceased-photo-details]');
    const seniorDetails = bindPopover('[data-deceased-senior-details-toggle]', '[data-deceased-senior-details]');
    const tarpaulinDetails = bindPopover('[data-tarpaulin-details-toggle]', '[data-tarpaulin-details]');
    const popovers = [photoDetails, seniorDetails, tarpaulinDetails];
    const repositionOpenPopovers = () => popovers.forEach(positionPopoverBesideButton);
    window.addEventListener('resize', repositionOpenPopovers);
    window.addEventListener('scroll', repositionOpenPopovers, true);
    document.addEventListener('click', event => {
      popovers.forEach(({toggle, popover}) => {
        if (popover && !popover.hidden && !popover.contains(event.target) && !toggle?.contains(event.target)) {
          popover.hidden = true; toggle?.setAttribute('aria-expanded', 'false');
        }
      });
    });
    document.addEventListener('keydown', event => {
      if (event.key !== 'Escape') return;
      mediaModals.forEach(closeCaseMedia);
      closePhotoPreview();
      closeSeniorProofPreview();
      popovers.forEach(({toggle, popover}) => { if (popover) popover.hidden = true; toggle?.setAttribute('aria-expanded', 'false'); });
      document.body.classList.remove('cv-case-media-open');
    });
    if (@json($errors->has('deceased_photo_file'))) {
      const modalEl = caseRoot?.querySelector('[data-case-media-modal="deceased-photo"]');
      if (modalEl) { moveModalToBody(modalEl); modalEl.hidden = false; document.body.classList.add('cv-case-media-open'); }
    }
    if (@json($errors->has('senior_proof_file'))) {
      const modalEl = caseRoot?.querySelector('[data-case-media-modal="senior-proof"]');
      if (modalEl) { moveModalToBody(modalEl); modalEl.hidden = false; document.body.classList.add('cv-case-media-open'); }
    }
  })();
</script>
</div>{{-- #caseViewContent --}}


@endif
