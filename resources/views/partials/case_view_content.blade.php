@php
    $displaySnapshot = app(\App\Support\CaseSnapshotDisplayService::class)->data($funeral_case);
    $pkgInclusionItems = $displaySnapshot['inclusions'];
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
    $discountDisplay = $displaySnapshot['discount'];
    $pkgCoffin = $includedCasket['name'] ?? $funeral_case->coffin_type;
    $isOtherBranch = ($funeral_case->entry_source ?? 'MAIN') === 'OTHER_BRANCH';
    $balanceDue    = (float) $funeral_case->balance_amount > 0;
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
  .cv-fin-grid     { display:grid; grid-template-columns:1fr 1fr; gap:.7rem; padding:0 1rem 1rem; }
  .cv-fin-item     { padding:.75rem .85rem; border:1px solid var(--border); border-radius:.7rem; background:#DCE6D6; }
  .cv-fin-item:nth-child(odd)  { padding-right:.85rem; border-right:1px solid var(--border); }
  .cv-fin-item:nth-child(even) { padding-left:.85rem; }
  .cv-fin-item:last-child,
  .cv-fin-item:nth-last-child(2):nth-child(odd) { border-bottom:0; }
  /* package inclusions/freebies */
  .cv-pkg-pair     { display:grid; grid-template-columns:1fr; gap:.7rem; padding:0 1rem 1rem; }
  @media(min-width:520px) { .cv-pkg-pair { grid-template-columns:1fr 1fr; } }
  .cv-pkg-box      { border:1px solid var(--border); border-radius:.7rem; padding:.85rem .95rem; background:#DCE6D6; }
  .cv-pkg-box-head { display:flex; align-items:center; gap:.4rem; font-size:.68rem; font-weight:650; text-transform:uppercase; letter-spacing:.07em; color:var(--ink-muted); margin-bottom:.45rem; }
  .cv-addon-list   { display:flex; flex-direction:column; gap:.7rem; padding:0 1rem 1rem; }
  .cv-addon-row    { border:1px solid var(--border); border-radius:.7rem; padding:.85rem .95rem; background:#DCE6D6; display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; }
  /* payment transactions */
  .cv-txn-list     { display:flex; flex-direction:column; gap:.7rem; padding:0 1rem 1rem; }
  .cv-txn          { border:1px solid var(--border); border-radius:.7rem; padding:.9rem 1rem; background:#DCE6D6; box-shadow:none; }
  .cv-txn-head     { display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.8rem; }
  .cv-txn-receipt  { font-family:ui-monospace,'Cascadia Code',monospace; font-size:.82rem; font-weight:750; color:var(--ink); }
  .cv-txn-grid     { display:grid; grid-template-columns:1fr 1fr; gap:.65rem .75rem; }
  @media(min-width:480px) { .cv-txn-grid { grid-template-columns:repeat(3,1fr); } }
  .cv-txn-lbl      { font-size:.68rem; font-weight:650; text-transform:uppercase; letter-spacing:.06em; color:var(--ink-muted); margin-bottom:.12rem; }
  .cv-txn-val      { font-size:.82rem; font-weight:600; color:var(--ink); }
  /* tarpaulin photo */
  .cv-service-grid > .cv-field-tarpaulin { grid-column:2; grid-row:1 / span 3; align-self:stretch; }
  .cv-field-tarpaulin { padding:0; overflow:hidden; display:flex; flex-direction:column; min-height:14.75rem; }
  .cv-subsection-head { display:flex; align-items:center; gap:.5rem; padding:.8rem .9rem; border-bottom:1px solid var(--border); background:#C7D5BE; }
  .cv-tarp-grid    { display:grid; grid-template-columns:1fr; gap:.9rem; padding:1rem; align-items:stretch; flex:1; }
  @media(min-width:640px) { .cv-tarp-grid { grid-template-columns:minmax(12rem,14.5rem) minmax(0,1fr); } }
  .cv-tarp-preview { min-height:10rem; max-height:none; border:1px dashed var(--border); border-radius:.75rem; background:#E1E7D9; display:flex; align-items:center; justify-content:center; overflow:hidden; }
  .cv-tarp-preview img { width:100%; height:100%; max-height:260px; object-fit:cover; display:block; }
  .cv-tarp-empty   { text-align:center; color:var(--ink-muted); font-size:13px; font-weight:600; padding:18px; }
  .cv-tarp-side    { display:flex; flex-direction:column; justify-content:flex-start; gap:.9rem; min-width:0; padding-top:.15rem; }
  .cv-tarp-meta    { display:none; grid-template-columns:1fr; gap:8px; padding:.75rem; border:1px solid var(--border); border-radius:.7rem; background:#E1E7D9; }
  .cv-tarp-meta.open { display:grid; }
  @media(min-width:460px) { .cv-tarp-meta { grid-template-columns:repeat(3,1fr); } }
  .cv-tarp-actions { display:flex; flex-wrap:wrap; gap:.65rem; align-items:center; }
  .cv-tarp-actions .btn-outline,
  .cv-tarp-actions .btn-secondary { white-space:nowrap; }
  .cv-field-tarpaulin .cv-tarp-actions .btn-outline,
  .cv-field-tarpaulin .cv-tarp-actions .btn-secondary { min-height:2.15rem; padding:.45rem .7rem; font-size:.8rem; }
  .cv-tarp-note { font-size:11.5px; color:var(--ink-muted); line-height:1.45; max-width:28rem; }
  .cv-tarp-form    { display:none; border:1px solid var(--border); border-radius:.75rem; padding:.85rem; background:#DCE6D6; }
  .cv-tarp-form.open { display:block; }
  .cv-tarp-file    { width:100%; border:1px solid var(--border); border-radius:.65rem; background:#E1E7D9; color:var(--ink); padding:.65rem; font-size:.82rem; cursor:pointer; }
  @media(max-width:700px) { .cv-service-grid > .cv-field-tarpaulin, .cv-service-grid > .cv-field-package { grid-column:auto; grid-row:auto; } .cv-field-tarpaulin { min-height:0; } .cv-field-tarpaulin .cv-tarp-grid { grid-template-columns:1fr; } }
  .cv-tarp-modal   { position:fixed; inset:0; z-index:70; display:none; align-items:center; justify-content:center; padding:18px; background:rgba(15,23,42,.72); }
  .cv-tarp-modal.open { display:flex; }
  .cv-tarp-dialog  { width:min(100%,920px); max-height:92vh; background:#D3DEC9; border:1px solid var(--border); border-radius:.75rem; overflow:hidden; box-shadow:none; }
  .cv-tarp-modal-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 14px; border-bottom:1px solid var(--border); }
  .cv-tarp-modal-body { padding:.9rem; background:#DCE6D6; }
  .cv-tarp-modal-body img { width:100%; max-height:74vh; object-fit:contain; border-radius:8px; background:#111827; }
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
  <div class="cv-card cv-section-case">
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
          <span class="cv-meta-value">{{ $fmtDate($funeral_case->service_requested_at ?? $funeral_case->created_at) }}</span>
        </div>
        <div class="cv-meta-item">
          <span class="cv-meta-label">Encoded By</span>
          <span class="cv-meta-value">{{ $funeral_case->encodedBy?->name ?? '—' }}</span>
        </div>
      </div>
    </div>
  </div>

  {{-- ── Client & Deceased (side by side) ── --}}
  <div class="cv-two-col cv-section-people">

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
      <div class="cv-fields cv-fields-2">
        <div class="cv-field cv-field-full">
          <div class="cv-field-label">Full Name</div>
          <div class="cv-field-value">{{ $funeral_case->deceased?->full_name ?? '—' }}</div>
        </div>
        <div class="cv-field">
          <div class="cv-field-label">Date of Birth</div>
          <div class="cv-field-value">{{ $fmtDate($funeral_case->deceased?->born) }}</div>
        </div>
        <div class="cv-field">
          <div class="cv-field-label">Date of Death</div>
          <div class="cv-field-value">{{ $fmtDate($funeral_case->deceased?->died ?? $funeral_case->deceased?->date_of_death) }}</div>
        </div>
        <div class="cv-field">
          <div class="cv-field-label">Age</div>
          <div class="cv-field-value">{{ $funeral_case->deceased?->age ?? '—' }}</div>
        </div>
        <div class="cv-field">
          <div class="cv-field-label">Cemetery</div>
          <div class="cv-field-value">{{ $funeral_case->deceased?->place_of_cemetery ?? '—' }}</div>
        </div>
        @if($funeral_case->deceased?->coffin_size || $funeral_case->deceased?->coffin_length_cm)
        <div class="cv-field">
          <div class="cv-field-label">Coffin</div>
          <div class="cv-field-value">
            {{ $funeral_case->deceased?->coffin_size ?? '' }}{{ ($funeral_case->deceased?->coffin_size && $funeral_case->deceased?->coffin_length_cm) ? ' · ' : '' }}{{ $funeral_case->deceased?->coffin_length_cm ? number_format((float) $funeral_case->deceased->coffin_length_cm, 2) . ' cm' : '' }}
          </div>
        </div>
        @endif
        @if($funeral_case->deceased?->address)
        <div class="cv-field cv-field-full">
          <div class="cv-field-label">Address</div>
          <div class="cv-field-value">{{ $funeral_case->deceased->address }}</div>
        </div>
        @endif
      </div>
    </div>

  </div>

  {{-- ── Package & Service ── --}}
  <div class="cv-card cv-section-service">
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
      @if($pkgPrice)
      <div class="cv-field">
        <div class="cv-field-label">Package Price</div>
        <div class="cv-field-value" style="font-variant-numeric:tabular-nums;">₱ {{ number_format((float) $pkgPrice, 2) }}</div>
      </div>
      @endif
      @if($funeral_case->custom_package_name)
      <div class="cv-field">
        <div class="cv-field-label">Custom Name</div>
        <div class="cv-field-value">{{ $funeral_case->custom_package_name }}</div>
      </div>
      @endif
      @if($funeral_case->service_type)
      <div class="cv-field cv-field-package">
        <div class="cv-field-label">Service Type</div>
        <div class="cv-field-value">{{ $funeral_case->service_type }}</div>
      </div>
      @endif
      <div class="cv-field cv-field-tarpaulin">
        <div class="cv-subsection-head">
          <div class="cv-card-icon"><i class="bi bi-camera"></i></div>
          <span class="cv-card-title">Tarpaulin Photo</span>
        </div>

        <div class="cv-tarp-grid">
          <div class="cv-tarp-preview">
            @if($tarpaulinAttachment && $tarpaulinUrl)
              <img src="{{ $tarpaulinUrl }}" alt="Tarpaulin photo reference for {{ $funeral_case->deceased?->full_name ?? $funeral_case->case_code }}">
            @else
              <div class="cv-tarp-empty">No Tarpaulin Photo Uploaded</div>
            @endif
          </div>

          <div class="cv-tarp-side">
            <div class="cv-tarp-actions no-print">
              <button type="button" class="btn-outline" data-tarpaulin-details-toggle aria-expanded="false">
                <i class="bi bi-info-circle mr-1"></i>Details
              </button>
              @if($tarpaulinAttachment && $tarpaulinUrl)
                <button type="button" class="btn-outline" data-tarpaulin-open>
                  <i class="bi bi-eye mr-1"></i>View
                </button>
                @if($canReplaceTarpaulin)
                  <button type="button" class="btn-outline" data-tarpaulin-toggle="replace">
                    <i class="bi bi-arrow-repeat mr-1"></i>Replace
                  </button>
                @endif
                @if($canDeleteTarpaulin)
                  <form method="POST" action="{{ route('funeral-cases.tarpaulin.destroy', $funeral_case) }}" onsubmit="return confirm('Delete this tarpaulin photo?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-outline" style="color:#9E4B3F;border-color:#9E4B3F;">
                      <i class="bi bi-trash mr-1"></i>Delete
                    </button>
                  </form>
                @endif
              @elseif($canUploadTarpaulin)
                <button type="button" class="btn-secondary" data-tarpaulin-toggle="upload">
                  <i class="bi bi-upload mr-1"></i>Upload Photo
                </button>
              @endif
            </div>

            <div class="cv-tarp-meta" data-tarpaulin-details>
              <div>
                <div class="cv-field-label">Status</div>
                <div class="cv-field-value">
                  @if($tarpaulinAttachment)
                    <span class="status-pill-success">Uploaded</span>
                  @else
                    <span class="status-pill-warning">Not Uploaded</span>
                  @endif
                </div>
              </div>
              <div>
                <div class="cv-field-label">Uploaded By</div>
                <div class="cv-field-value">{{ $tarpaulinAttachment?->uploader?->name ?? 'Not set' }}</div>
              </div>
              <div>
                <div class="cv-field-label">Upload Date</div>
                <div class="cv-field-value">{{ $tarpaulinAttachment?->created_at ? $tarpaulinAttachment->created_at->format('M d, Y') : 'Not set' }}</div>
              </div>
            </div>

            @if(! $tarpaulinAttachment && $canUploadTarpaulin)
              <form method="POST" action="{{ route('funeral-cases.tarpaulin.store', $funeral_case) }}" enctype="multipart/form-data" class="cv-tarp-form no-print" data-tarpaulin-form="upload">
                @csrf
                <div class="cv-field-label" style="margin-bottom:6px;">Upload Photo</div>
                <input type="file" name="photo" class="cv-tarp-file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required>
                <div class="cv-tarp-actions" style="margin-top:10px;">
                  <button type="submit" class="btn-secondary">Save Photo</button>
                  <button type="button" class="btn-outline" data-tarpaulin-cancel>Cancel</button>
                </div>
              </form>
            @endif

            @if($tarpaulinAttachment && $canReplaceTarpaulin)
              <form method="POST" action="{{ route('funeral-cases.tarpaulin.update', $funeral_case) }}" enctype="multipart/form-data" class="cv-tarp-form no-print" data-tarpaulin-form="replace">
                @csrf
                @method('PUT')
                <div class="cv-field-label" style="margin-bottom:6px;">Replace Photo</div>
                <input type="file" name="photo" class="cv-tarp-file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required>
                <div class="cv-tarp-actions" style="margin-top:10px;">
                  <button type="submit" class="btn-secondary">Replace Photo</button>
                  <button type="button" class="btn-outline" data-tarpaulin-cancel>Cancel</button>
                </div>
              </form>
            @endif

            <div class="cv-field-value cv-tarp-note">
              Accepted files: JPG, JPEG, PNG. Maximum size: 5MB.
            </div>
          </div>
        </div>
      </div>
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
          <div style="font-size:11px;color:var(--ink-muted);font-weight:600;margin-top:2px;">
            Status: {{ $discountDisplay['status'] ?? 'Applied' }}
            @if($discountDisplay['starts_at'] ?? null)
              &middot; From {{ \Carbon\Carbon::parse($discountDisplay['starts_at'])->format('M d, Y') }}
            @endif
            @if($discountDisplay['ends_at'] ?? null)
              to {{ \Carbon\Carbon::parse($discountDisplay['ends_at'])->format('M d, Y') }}
            @endif
          </div>
        </div>
      </div>
      @endif
      @if($funeral_case->wake_location)
      <div class="cv-field cv-field-package">
        <div class="cv-field-label">Wake Location</div>
        <div class="cv-field-value">{{ $funeral_case->wake_location }}</div>
      </div>
      @endif
      <div class="cv-field cv-field-package">
        <div class="cv-field-label">Wake Start Date &amp; Time</div>
        <div class="cv-field-value">{{ $fmtSchedule($funeral_case->wake_start_date, $funeral_case->wake_start_time) }}</div>
      </div>
      @if($funeral_case->funeral_service_at)
      <div class="cv-field cv-field-package">
        <div class="cv-field-label">Funeral Service Date &amp; Time</div>
        <div class="cv-field-value">{{ $fmtSchedule($funeral_case->funeral_service_at, $funeral_case->funeral_service_time) }}</div>
      </div>
      @endif
      <div class="cv-field cv-field-package">
        <div class="cv-field-label">Interment Date &amp; Time</div>
        <div class="cv-field-value">{{ $fmtSchedule($displayIntermentAt, $funeral_case->interment_time ?? $displayIntermentAt?->format('H:i:s')) }}</div>
      </div>
      <div class="cv-field cv-field-full">
        <div class="cv-field-label">Wake Duration</div>
        <div class="cv-field-value">{{ $displayWakeDays !== null ? $displayWakeDuration : $fmtDate(null) }}</div>
      </div>
    </div>

    @if($pkgInclusionItems || $pkgFreebieItems)
    <div class="cv-pkg-pair">
      <div class="cv-pkg-box">
        <div class="cv-pkg-box-head"><i class="bi bi-check2-circle" style="color:#6F8A6D"></i>Inclusions</div>
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

  @if($tarpaulinAttachment && $tarpaulinUrl)
  <div class="cv-tarp-modal no-print" data-tarpaulin-modal aria-hidden="true">
    <div class="cv-tarp-dialog" role="dialog" aria-modal="true" aria-label="Tarpaulin photo preview">
      <div class="cv-tarp-modal-head">
        <div>
          <div class="cv-card-title">Tarpaulin Photo</div>
          <div class="cv-field-value" style="font-size:12px;">{{ $tarpaulinAttachment->file_name }}</div>
        </div>
        <button type="button" class="btn-outline" data-tarpaulin-close aria-label="Close tarpaulin photo preview">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <div class="cv-tarp-modal-body">
        <img src="{{ $tarpaulinUrl }}" alt="Full tarpaulin photo reference">
      </div>
    </div>
  </div>
  @endif

  {{-- Generated Documents --}}
  <div class="cv-card cv-section-documents">
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
            <a href="{{ route('funeral-cases.documents.download', [$funeral_case, $funeralContract]) }}" class="btn-outline">
              <i class="bi bi-download mr-1"></i>Download
            </a>
            <a href="{{ route('funeral-cases.documents.print', [$funeral_case, $funeralContract]) }}" target="_blank" rel="noopener" class="btn-outline">
              <i class="bi bi-printer mr-1"></i>Print
            </a>
            @if($canGenerateFuneralContract)
              <a href="{{ route('funeral-cases.documents.contract.preview', $funeral_case) }}" class="btn-secondary">
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
              <a href="{{ route('funeral-cases.documents.contract.preview', $funeral_case) }}" class="btn-secondary">
                <i class="bi bi-file-earmark-pdf mr-1"></i>Prepare Contract
              </a>
            </div>
          @endif
        </div>
      @endif
    </div>
  </div>

  {{-- ── Payment Summary ── --}}
  <div class="cv-card cv-section-payment">
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
      @if($funeral_case->paid_at)
      <div class="cv-fin-item">
        <div class="cv-field-label">Last Payment</div>
        <div class="cv-field-value">{{ $fmtDt($funeral_case->paid_at) }}</div>
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

    @if($funeral_case->payments->isNotEmpty())
      <div class="cv-txn-list">
        @foreach($funeral_case->payments as $pmt)
          <div class="cv-txn">
            <div class="cv-txn-head">
              <span class="cv-txn-receipt">{{ $pmt->display_payment_record_no ?? '—' }}</span>
              <x-status-badge :status="$pmt->payment_status_after_payment ?? '—'" />
            </div>
            <div class="cv-txn-grid">
              <div>
                <div class="cv-txn-lbl">Payment Amount</div>
                <div class="cv-txn-val" style="font-size:13px;font-weight:800;font-variant-numeric:tabular-nums;">₱ {{ number_format((float) $pmt->amount, 2) }}</div>
              </div>
              <div>
                <div class="cv-txn-lbl">Balance After</div>
                <div class="cv-txn-val" style="font-variant-numeric:tabular-nums;">₱ {{ number_format((float) ($pmt->balance_after_payment ?? 0), 2) }}</div>
              </div>
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
          </div>
        @endforeach
      </div>
    @else
      <div class="cv-empty-note">No payment transactions recorded yet.</div>
    @endif
  </div>

  {{-- ── Record Source (OTHER_BRANCH cases) ── --}}
  @if($isOtherBranch || $funeral_case->reporter_name)
  <div class="cv-card cv-section-source">
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
    const forms = document.querySelectorAll('[data-tarpaulin-form]');
    const showForm = (name) => {
      forms.forEach(form => form.classList.toggle('open', form.dataset.tarpaulinForm === name));
    };

    document.querySelectorAll('[data-tarpaulin-toggle]').forEach(button => {
      button.addEventListener('click', () => showForm(button.dataset.tarpaulinToggle));
    });

    document.querySelectorAll('[data-tarpaulin-cancel]').forEach(button => {
      button.addEventListener('click', () => {
        const form = button.closest('[data-tarpaulin-form]');
        form?.classList.remove('open');
        form?.reset();
      });
    });

    const details = document.querySelector('[data-tarpaulin-details]');
    const detailsToggle = document.querySelector('[data-tarpaulin-details-toggle]');
    detailsToggle?.addEventListener('click', () => {
      const isOpen = details?.classList.toggle('open') ?? false;
      detailsToggle.setAttribute('aria-expanded', String(isOpen));
    });

    const modal = document.querySelector('[data-tarpaulin-modal]');
    const openButton = document.querySelector('[data-tarpaulin-open]');
    const closeButtons = document.querySelectorAll('[data-tarpaulin-close]');

    const openModal = () => {
      if (!modal) return;
      modal.classList.add('open');
      modal.setAttribute('aria-hidden', 'false');
    };
    const closeModal = () => {
      if (!modal) return;
      modal.classList.remove('open');
      modal.setAttribute('aria-hidden', 'true');
    };

    openButton?.addEventListener('click', openModal);
    closeButtons.forEach(button => button.addEventListener('click', closeModal));
    modal?.addEventListener('click', event => {
      if (event.target === modal) closeModal();
    });
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape') closeModal();
    });
  })();
</script>
</div>{{-- #caseViewContent --}}

