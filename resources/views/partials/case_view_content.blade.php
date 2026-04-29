@php
    $pkg           = $funeral_case->package ?? null;
    $pkgInclusionItems = $funeral_case->custom_package_inclusions
        ? \App\Models\Package::parseLegacyItems($funeral_case->custom_package_inclusions)
        : ($pkg?->inclusionNames() ?? []);
    $pkgFreebieItems = $funeral_case->custom_package_freebies
        ? \App\Models\Package::parseLegacyItems($funeral_case->custom_package_freebies)
        : ($pkg?->freebieNames() ?? []);
    $pkgPrice      = $funeral_case->custom_package_price      ?: ($pkg?->price       ?? null);
    $pkgCoffin     = $funeral_case->coffin_type               ?: ($pkg?->coffin_type ?? null);
    $isOtherBranch = ($funeral_case->entry_source ?? 'MAIN') === 'OTHER_BRANCH';
    $balanceDue    = (float) $funeral_case->balance_amount > 0;

    // Smart date formatters — skip the time portion when it is midnight
    $fmtDate = fn($dt) => $dt ? $dt->format('M d, Y') : '—';
    $fmtDt   = fn($dt) => $dt
        ? ($dt->format('H:i') === '00:00' ? $dt->format('M d, Y') : $dt->format('M d, Y · H:i'))
        : '—';
@endphp

<div id="caseViewContent">
<style>
  /* ── case-view partial (cv-*) ── */
  .cv-shell        { display:flex; flex-direction:column; gap:12px; }
  .cv-card         { background:var(--card); border:1px solid var(--border); border-radius:12px; overflow:hidden; }
  .cv-card-head    { display:flex; align-items:center; gap:8px; padding:10px 18px; background:var(--surface-panel); border-bottom:1px solid var(--border); }
  .cv-card-icon    { width:24px; height:24px; border-radius:7px; background:var(--brand-soft); display:flex; align-items:center; justify-content:center; color:var(--brand); font-size:11px; flex-shrink:0; }
  .cv-card-title   { font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--ink-muted); }
  /* fields */
  .cv-fields       { display:grid; }
  .cv-fields-2     { grid-template-columns:1fr 1fr; }
  .cv-field        { padding:9px 18px; border-bottom:1px solid var(--border); }
  .cv-field:last-child { border-bottom:0; }
  .cv-fields-2 .cv-field:nth-child(odd):not(.cv-field-full) { border-right:1px solid var(--border); }
  .cv-fields-2 .cv-field-full { grid-column:span 2; }
  .cv-field-label  { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--ink-muted); margin-bottom:1px; }
  .cv-field-value  { font-size:13px; font-weight:500; color:var(--ink); line-height:1.4; }
  .cv-field-value em { color:var(--ink-muted); font-style:italic; font-size:12px; }
  /* hero header */
  .cv-hero-accent  { height:3px; background:linear-gradient(90deg,var(--brand) 0%,var(--brand-mid,#28456e) 100%); }
  .cv-hero-body    { padding:14px 18px 12px; }
  .cv-hero-top     { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; flex-wrap:wrap; margin-bottom:8px; }
  .cv-hero-case    { font-size:19px; font-weight:800; color:var(--ink); letter-spacing:-.4px; line-height:1.1; }
  .cv-hero-badges  { display:flex; flex-wrap:wrap; gap:5px; padding-top:2px; }
  .cv-hero-meta    { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:6px 12px; padding-top:10px; border-top:1px solid var(--border); }
  .cv-meta-item    { display:flex; flex-direction:column; gap:1px; }
  .cv-meta-label   { font-size:9.5px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--ink-muted); }
  .cv-meta-value   { font-size:12px; font-weight:600; color:var(--ink); }
  /* two-col layout for client/deceased side-by-side */
  .cv-two-col      { display:grid; grid-template-columns:1fr; gap:12px; }
  @media(min-width:600px) { .cv-two-col { grid-template-columns:1fr 1fr; } }
  /* payment stat row */
  .cv-stat-row     { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; padding:11px 16px; }
  .cv-stat         { border:1px solid var(--border); border-radius:9px; padding:10px 8px; text-align:center; background:var(--surface-panel); }
  .cv-stat-lbl     { font-size:9.5px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--ink-muted); margin-bottom:3px; }
  .cv-stat-val     { font-size:14px; font-weight:800; color:var(--ink); font-variant-numeric:tabular-nums; }
  .cv-stat.s-paid .cv-stat-lbl { color:#15803d; }
  .cv-stat.s-paid .cv-stat-val { color:#15803d; }
  .cv-stat.s-due .cv-stat-lbl  { color:#b91c1c; }
  .cv-stat.s-due .cv-stat-val  { color:#b91c1c; }
  .cv-stat.s-ok .cv-stat-lbl   { color:#15803d; }
  .cv-stat.s-ok .cv-stat-val   { color:#15803d; }
  /* financial breakdown */
  .cv-fin-grid     { display:grid; grid-template-columns:1fr 1fr; padding:0 16px 12px; }
  .cv-fin-item     { padding:5px 0; border-bottom:1px solid var(--border); }
  .cv-fin-item:nth-child(odd)  { padding-right:14px; border-right:1px solid var(--border); }
  .cv-fin-item:nth-child(even) { padding-left:14px; }
  .cv-fin-item:last-child,
  .cv-fin-item:nth-last-child(2):nth-child(odd) { border-bottom:0; }
  /* package inclusions/freebies */
  .cv-pkg-pair     { display:grid; grid-template-columns:1fr; gap:8px; padding:0 16px 12px; }
  @media(min-width:520px) { .cv-pkg-pair { grid-template-columns:1fr 1fr; } }
  .cv-pkg-box      { border:1px solid var(--border); border-radius:9px; padding:10px 13px; background:var(--surface-panel); }
  .cv-pkg-box-head { display:flex; align-items:center; gap:5px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--ink-muted); margin-bottom:6px; }
  /* payment transactions */
  .cv-txn-list     { display:flex; flex-direction:column; gap:7px; padding:0 16px 12px; }
  .cv-txn          { border:1px solid var(--border); border-radius:9px; padding:10px 14px; background:var(--card); }
  .cv-txn-head     { display:flex; align-items:center; justify-content:space-between; margin-bottom:7px; }
  .cv-txn-receipt  { font-family:ui-monospace,'Cascadia Code',monospace; font-size:11.5px; font-weight:700; color:var(--ink); }
  .cv-txn-grid     { display:grid; grid-template-columns:1fr 1fr; gap:5px 12px; }
  @media(min-width:480px) { .cv-txn-grid { grid-template-columns:repeat(3,1fr); } }
  .cv-txn-lbl      { font-size:9.5px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--ink-muted); margin-bottom:1px; }
  .cv-txn-val      { font-size:12px; font-weight:600; color:var(--ink); }
  .cv-empty-note   { padding:18px; text-align:center; font-size:13px; color:var(--ink-muted); font-style:italic; }
  html[data-theme='dark'] .cv-stat,
  html[data-theme='dark'] .cv-pkg-box { background:rgba(255,255,255,.04); }
</style>

<div class="cv-shell">

  {{-- ── Case Header ── --}}
  <div class="cv-card">
    <div class="cv-hero-accent"></div>
    <div class="cv-hero-body">
      <div class="cv-hero-top">
        <div class="cv-hero-case">{{ $funeral_case->case_code }}</div>
        <div class="cv-hero-badges">
          <span class="{{ in_array($funeral_case->case_status, ['DRAFT','ACTIVE']) ? 'status-pill-warning' : 'status-pill-success' }}">
            {{ $funeral_case->case_status }}
          </span>
          <span class="{{ $funeral_case->payment_status === 'PAID' ? 'status-pill-success' : ($funeral_case->payment_status === 'PARTIAL' ? 'status-pill-warning' : 'status-pill-danger') }}">
            {{ $funeral_case->payment_status }}
          </span>
          @if($isOtherBranch)
            <span class="status-pill-warning">Other Branch</span>
          @endif
          @if(!empty($funeral_case->verification_status))
            <span class="{{ $funeral_case->verification_status === 'VERIFIED' ? 'status-pill-success' : ($funeral_case->verification_status === 'DISPUTED' ? 'status-pill-danger' : 'status-pill-warning') }}">
              {{ $funeral_case->verification_status }}
            </span>
          @endif
        </div>
      </div>
      <div class="cv-hero-meta">
        <div class="cv-meta-item">
          <span class="cv-meta-label">Branch</span>
          <span class="cv-meta-value">{{ $funeral_case->branch?->branch_code ?? '—' }}{{ $funeral_case->branch?->branch_name ? ' — ' . $funeral_case->branch->branch_name : '' }}</span>
        </div>
        <div class="cv-meta-item">
          <span class="cv-meta-label">Service Date</span>
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
  <div class="cv-two-col">

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
          <div class="cv-field-label">Wake Days</div>
          <div class="cv-field-value">{{ $funeral_case->deceased?->wake_days ?? '—' }}</div>
        </div>
        <div class="cv-field">
          <div class="cv-field-label">Interment</div>
          <div class="cv-field-value">{{ $fmtDt($funeral_case->deceased?->interment_at) !== '—' ? $fmtDt($funeral_case->deceased?->interment_at) : $fmtDate($funeral_case->deceased?->interment) }}</div>
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
  <div class="cv-card">
    <div class="cv-card-head">
      <div class="cv-card-icon"><i class="bi bi-box-seam"></i></div>
      <span class="cv-card-title">Package &amp; Service</span>
    </div>
    <div class="cv-fields cv-fields-2">
      <div class="cv-field {{ !$pkgCoffin && !$pkgPrice ? 'cv-field-full' : '' }}">
        <div class="cv-field-label">Package</div>
        <div class="cv-field-value">{{ $funeral_case->service_package ?? $pkg?->name ?? '—' }}</div>
      </div>
      @if($pkgCoffin)
      <div class="cv-field">
        <div class="cv-field-label">Coffin Type</div>
        <div class="cv-field-value">{{ $pkgCoffin }}</div>
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
      <div class="cv-field">
        <div class="cv-field-label">Service Type</div>
        <div class="cv-field-value">{{ $funeral_case->service_type }}</div>
      </div>
      @endif
      @if($funeral_case->wake_location)
      <div class="cv-field">
        <div class="cv-field-label">Wake Location</div>
        <div class="cv-field-value">{{ $funeral_case->wake_location }}</div>
      </div>
      @endif
      @if($funeral_case->funeral_service_at)
      <div class="cv-field">
        <div class="cv-field-label">Funeral Service Date</div>
        <div class="cv-field-value">{{ $fmtDate($funeral_case->funeral_service_at) }}</div>
      </div>
      @endif
    </div>

    @if($pkgInclusionItems || $pkgFreebieItems)
    <div class="cv-pkg-pair">
      <div class="cv-pkg-box">
        <div class="cv-pkg-box-head"><i class="bi bi-check2-circle" style="color:#15803d"></i>Inclusions</div>
        @if($pkgInclusionItems)
          <ul style="margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:3px;">
            @foreach($pkgInclusionItems as $item)
              <li style="display:flex;align-items:flex-start;gap:5px;font-size:12px;color:var(--ink);">
                <i class="bi bi-dot" style="color:#15803d;font-size:15px;line-height:1.2;flex-shrink:0;"></i>{{ $item }}
              </li>
            @endforeach
          </ul>
        @else
          <p style="font-size:12px;color:var(--ink-muted);font-style:italic;margin:0;">Not configured.</p>
        @endif
      </div>
      <div class="cv-pkg-box">
        <div class="cv-pkg-box-head"><i class="bi bi-gift" style="color:#d97706"></i>Freebies</div>
        @if($pkgFreebieItems)
          <ul style="margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:3px;">
            @foreach($pkgFreebieItems as $item)
              <li style="display:flex;align-items:flex-start;gap:5px;font-size:12px;color:var(--ink);">
                <i class="bi bi-dot" style="color:#d97706;font-size:15px;line-height:1.2;flex-shrink:0;"></i>{{ $item }}
              </li>
            @endforeach
          </ul>
        @else
          <p style="font-size:12px;color:var(--ink-muted);font-style:italic;margin:0;">Not configured.</p>
        @endif
      </div>
    </div>
    @endif

    @if($funeral_case->additional_services)
    <div style="padding:0 16px 12px;">
      <div style="border:1px solid var(--border);border-radius:9px;padding:10px 14px;background:var(--surface-panel);display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
        <div>
          <div class="cv-field-label">Additional Services</div>
          <div class="cv-field-value" style="margin-top:1px;">{{ $funeral_case->additional_services }}</div>
        </div>
        @if($funeral_case->additional_service_amount)
        <div style="text-align:right;flex-shrink:0;">
          <div class="cv-field-label">Amount</div>
          <div class="cv-field-value" style="margin-top:1px;font-variant-numeric:tabular-nums;">₱ {{ number_format((float) $funeral_case->additional_service_amount, 2) }}</div>
        </div>
        @endif
      </div>
    </div>
    @endif
  </div>

  {{-- ── Payment Summary ── --}}
  <div class="cv-card">
    <div class="cv-card-head">
      <div class="cv-card-icon"><i class="bi bi-cash-stack"></i></div>
      <span class="cv-card-title">Payment Summary</span>
    </div>

    <div class="cv-stat-row">
      <div class="cv-stat">
        <div class="cv-stat-lbl">Total</div>
        <div class="cv-stat-val">₱ {{ number_format((float) $funeral_case->total_amount, 2) }}</div>
      </div>
      <div class="cv-stat s-paid">
        <div class="cv-stat-lbl">Paid</div>
        <div class="cv-stat-val">₱ {{ number_format((float) $funeral_case->total_paid, 2) }}</div>
      </div>
      <div class="cv-stat {{ $balanceDue ? 's-due' : 's-ok' }}">
        <div class="cv-stat-lbl">Balance</div>
        <div class="cv-stat-val">₱ {{ number_format((float) $funeral_case->balance_amount, 2) }}</div>
      </div>
    </div>

    <div class="cv-fin-grid">
      <div class="cv-fin-item">
        <div class="cv-field-label">Subtotal</div>
        <div class="cv-field-value" style="font-variant-numeric:tabular-nums;">₱ {{ number_format((float) ($funeral_case->subtotal_amount ?? $funeral_case->total_amount), 2) }}</div>
      </div>
      <div class="cv-fin-item">
        <div class="cv-field-label">Discount</div>
        <div class="cv-field-value" style="font-variant-numeric:tabular-nums;">
          ₱ {{ number_format((float) ($funeral_case->discount_amount ?? 0), 2) }}@if($funeral_case->discount_note)<em> — {{ $funeral_case->discount_note }}</em>@endif
        </div>
      </div>
      @if($funeral_case->tax_amount)
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
  <div class="cv-card">
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
                <div class="cv-txn-val">{{ ($pmt->payment_method ?? $pmt->payment_mode) === 'bank_transfer' ? 'Bank Transfer' : 'Cash' }}</div>
              </div>
              <div>
                <div class="cv-txn-lbl">Payment Date &amp; Time</div>
                <div class="cv-txn-val">{{ $fmtDt($pmt->paid_at) !== '—' ? $fmtDt($pmt->paid_at) : $fmtDate($pmt->paid_date) }}</div>
              </div>
              <div>
                <div class="cv-txn-lbl">Accounting Reference No.</div>
                <div class="cv-txn-val">{{ $pmt->accounting_reference_no ?? '—' }}</div>
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
  @if($isOtherBranch || $funeral_case->reportedBranch || $funeral_case->reporter_name)
  <div class="cv-card">
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
        <div class="cv-field-label">Reported At</div>
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
</div>{{-- #caseViewContent --}}
