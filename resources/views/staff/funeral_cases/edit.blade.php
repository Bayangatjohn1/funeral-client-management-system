@extends('layouts.panel')

@section('page_title','Edit Case')
@section('page_desc', 'Update case record details.')

@section('content')
<form
    id="caseEditForm"
    method="POST"
    action="{{ route('funeral-cases.update', $funeral_case) }}"
>
@csrf
@method('PUT')

<style>
/* ── Edit-Form scoped styles (ef-*) ──────────────────────────────── */
#caseEditForm { display:flex; flex-direction:column; font-size:13px; color:var(--ink); }

.ef-header {
    display:flex; align-items:center; justify-content:space-between;
    padding:13px 20px; gap:10px; flex-wrap:wrap;
    background:var(--surface-panel); border-bottom:1px solid var(--border);
}
.ef-title { font-size:14px; font-weight:700; color:var(--ink); letter-spacing:-.2px; }
.ef-code-pill {
    display:inline-flex; align-items:center; gap:5px;
    padding:3px 11px; border-radius:999px;
    background:var(--brand-soft);
    color:var(--brand);
    font-size:11px; font-weight:700;
    border:1px solid color-mix(in srgb,var(--brand) 22%,transparent);
    letter-spacing:.03em; white-space:nowrap;
}

.ef-ro-strip {
    display:flex; flex-wrap:wrap;
    border-bottom:1px solid var(--border);
    background:var(--surface-panel);
}
.ef-ro-item {
    display:flex; flex-direction:column; gap:2px;
    padding:8px 18px; border-right:1px solid var(--border);
}
.ef-ro-item:last-child { border-right:none; }
.ef-ro-label { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--ink-muted); }
.ef-ro-value { font-size:12px; font-weight:600; color:var(--ink); }

.ef-alert {
    padding:9px 20px; font-size:12px; font-weight:600;
    background:#fef2f2; border-bottom:1px solid #fecaca; color:#991b1b;
    display:flex; align-items:center; gap:6px;
}

.ef-section { padding:14px 20px; border-bottom:1px solid var(--border); }
.ef-section-head { display:flex; align-items:center; gap:7px; margin-bottom:11px; }
.ef-section-icon {
    width:22px; height:22px; border-radius:6px;
    background:var(--brand-soft); color:var(--brand);
    display:flex; align-items:center; justify-content:center;
    font-size:11px; flex-shrink:0;
}
.ef-section-title { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:var(--ink-muted); }

.ef-grid   { display:grid; gap:10px; }
.ef-grid-2 { grid-template-columns:1fr 1fr; }
.ef-full   { grid-column:span 2; }

.ef-label {
    display:block; margin-bottom:3px;
    font-size:10px; font-weight:700;
    text-transform:uppercase; letter-spacing:.06em; color:var(--ink-muted);
}
.ef-label-note { font-size:9px; font-weight:400; text-transform:none; letter-spacing:0; color:var(--ink-muted); opacity:.75; }
.ef-req { color:#e11d48; margin-left:1px; }
.ef-err { font-size:11px; color:#b91c1c; margin-top:3px; display:flex; align-items:center; gap:3px; }

.ef-preview {
    display:grid; grid-template-columns:repeat(3,1fr); gap:8px;
    padding:11px 20px; border-bottom:1px solid var(--border);
    background:var(--surface-panel);
}
.ef-stat {
    border:1px solid var(--border); border-radius:9px;
    padding:9px 8px 8px; text-align:center; background:var(--card);
}
.ef-stat-lbl { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--ink-muted); margin-bottom:3px; }
.ef-stat-val { font-size:14px; font-weight:800; color:var(--ink); font-variant-numeric:tabular-nums; }
.ef-stat-sub { font-size:9px; color:var(--ink-muted); margin-top:1px; }
.ef-stat.s-green .ef-stat-lbl,
.ef-stat.s-green .ef-stat-val { color:#15803d; }
.ef-stat.s-amber .ef-stat-lbl,
.ef-stat.s-amber .ef-stat-val { color:#b45309; }

.ef-footer {
    padding:12px 20px; display:flex; align-items:center; gap:8px; flex-wrap:wrap;
    background:var(--surface-panel); border-top:1px solid var(--border);
}

@media (max-width:560px) {
    .ef-grid-2 { grid-template-columns:1fr; }
    .ef-full   { grid-column:span 1; }
}
html[data-theme='dark'] .ef-stat { background:rgba(255,255,255,.04); }
</style>

{{-- ── Header ── --}}
<div class="ef-header">
    <span class="ef-title">Edit Case Record</span>
    <span class="ef-code-pill">
        <i class="bi bi-hash" style="font-size:9px;"></i>
        {{ $funeral_case->case_code }}
    </span>
</div>

{{-- ── Read-only strip ── --}}
<div class="ef-ro-strip">
    <div class="ef-ro-item">
        <span class="ef-ro-label">Branch</span>
        <span class="ef-ro-value">
            {{ $funeral_case->branch?->branch_code ?? '—' }}
            @if($funeral_case->branch?->branch_name)
                &mdash; {{ $funeral_case->branch->branch_name }}
            @endif
        </span>
    </div>
    <div class="ef-ro-item">
        <span class="ef-ro-label">Encoded By</span>
        <span class="ef-ro-value">{{ $funeral_case->encodedBy?->name ?? '—' }}</span>
    </div>
    <div class="ef-ro-item">
        <span class="ef-ro-label">Created</span>
        <span class="ef-ro-value">{{ $funeral_case->created_at?->format('M d, Y') ?? '—' }}</span>
    </div>
    <div class="ef-ro-item">
        <span class="ef-ro-label">Payment</span>
        <span class="ef-ro-value">
            <span class="{{ match($funeral_case->payment_status) { 'PAID' => 'status-pill-success', 'PARTIAL' => 'status-pill-warning', default => 'status-pill-danger' } }}">
                {{ $funeral_case->payment_status ?? 'UNPAID' }}
            </span>
        </span>
    </div>
</div>

@if($errors->has('case'))
    <div class="ef-alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        {{ $errors->first('case') }}
    </div>
@endif

{{-- ══════════════════════════════════════════════
     SECTION 1 — Client / Family Representative
═══════════════════════════════════════════════ --}}
<div class="ef-section">
    <div class="ef-section-head">
        <span class="ef-section-icon"><i class="bi bi-person"></i></span>
        <span class="ef-section-title">Client / Family Representative</span>
    </div>

    {{-- Hidden IDs — required by the controller; client/deceased cannot be re-linked from this form. --}}
    <input type="hidden" name="client_id" value="{{ $funeral_case->client_id }}">
    <input type="hidden" name="deceased_id" value="{{ $funeral_case->deceased_id }}">

    @php $clientParts = $funeral_case->client?->splitName() ?? []; @endphp
    <div class="ef-grid ef-grid-2">
        <div class="ef-full">
            <label class="ef-label">Name <span class="ef-label-note">(read-only — edit via Client profile)</span></label>
            <div class="grid grid-cols-2 gap-2">
                <input type="text" value="{{ $clientParts['first_name'] ?? '' }}" class="form-input w-full" readonly disabled placeholder="First name">
                <input type="text" value="{{ $clientParts['last_name'] ?? '' }}" class="form-input w-full" readonly disabled placeholder="Last name">
                <input type="text" value="{{ $clientParts['middle_name'] ?? '' }}" class="form-input w-full" readonly disabled placeholder="Middle name">
                <input type="text" value="{{ $clientParts['suffix'] ?? '' }}" class="form-input w-full" readonly disabled placeholder="Suffix">
            </div>
        </div>

        <div>
            <label class="ef-label" for="ef_client_contact">Contact Number</label>
            <input
                type="text"
                id="ef_client_contact"
                name="client_contact"
                value="{{ old('client_contact', $funeral_case->client?->contact_number) }}"
                class="form-input w-full"
                maxlength="50"
                placeholder="e.g. 09XX XXX XXXX"
            >
            @error('client_contact')
                <div class="ef-err">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="ef-label" for="ef_client_rel">Relationship to Deceased</label>
            <input
                type="text"
                id="ef_client_rel"
                name="client_relationship"
                value="{{ old('client_relationship', $funeral_case->client?->relationship_to_deceased) }}"
                class="form-input w-full"
                maxlength="100"
                placeholder="e.g. Spouse, Parent, Child"
            >
        </div>

        <div class="ef-full">
            <label class="ef-label" for="ef_client_address">Address</label>
            <input
                type="text"
                id="ef_client_address"
                name="client_address"
                value="{{ old('client_address', $funeral_case->client?->address) }}"
                class="form-input w-full"
                maxlength="500"
                placeholder="Current home address"
            >
            @error('client_address')
                <div class="ef-err">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     SECTION 2 — Deceased Information
═══════════════════════════════════════════════ --}}
<div class="ef-section">
    <div class="ef-section-head">
        <span class="ef-section-icon"><i class="bi bi-flower1"></i></span>
        <span class="ef-section-title">Deceased Information</span>
    </div>

    <div class="ef-grid ef-grid-2">
        @php $deceasedParts = $funeral_case->deceased?->splitName() ?? []; @endphp
        <div class="ef-full">
            <label class="ef-label">Name <span class="ef-label-note">(read-only — edit via Deceased profile)</span></label>
            <div class="grid grid-cols-2 gap-2">
                <input type="text" value="{{ $deceasedParts['first_name'] ?? '' }}" class="form-input w-full" readonly disabled placeholder="First name">
                <input type="text" value="{{ $deceasedParts['last_name'] ?? '' }}" class="form-input w-full" readonly disabled placeholder="Last name">
                <input type="text" value="{{ $deceasedParts['middle_name'] ?? '' }}" class="form-input w-full" readonly disabled placeholder="Middle name">
                <input type="text" value="{{ $deceasedParts['suffix'] ?? '' }}" class="form-input w-full" readonly disabled placeholder="Suffix">
            </div>
        </div>

        <div>
            <label class="ef-label" for="ef_dob">Date of Birth</label>
            <input
                type="date"
                id="ef_dob"
                name="date_of_birth"
                value="{{ old('date_of_birth', $funeral_case->deceased?->born?->format('Y-m-d')) }}"
                class="form-input w-full"
            >
            @error('date_of_birth')
                <div class="ef-err">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="ef-label" for="ef_dod">Date of Death</label>
            <input
                type="date"
                id="ef_dod"
                name="date_of_death"
                value="{{ old('date_of_death', $funeral_case->deceased?->died?->format('Y-m-d') ?? $funeral_case->deceased?->date_of_death?->format('Y-m-d')) }}"
                class="form-input w-full"
            >
            @error('date_of_death')
                <div class="ef-err">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="ef-label" for="ef_age">
                Age
                <span class="ef-label-note">(auto-fills from dates above)</span>
            </label>
            <input
                type="number"
                id="ef_age"
                name="age"
                value="{{ old('age', $funeral_case->deceased?->age) }}"
                class="form-input w-full"
                min="0" max="150"
                placeholder="Years"
            >
            @error('age')
                <div class="ef-err">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="ef-label" for="ef_wake_days">Wake Days</label>
            <input
                type="number"
                id="ef_wake_days"
                name="wake_days"
                value="{{ old('wake_days', $funeral_case->deceased?->wake_days) }}"
                class="form-input w-full"
                min="0" max="365"
                placeholder="Number of days"
            >
            @error('wake_days')
                <div class="ef-err">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="ef-label" for="ef_interment">Interment Date &amp; Time</label>
            <input
                type="datetime-local"
                id="ef_interment"
                name="interment_at"
                value="{{ old('interment_at',
                    $funeral_case->deceased?->interment_at?->format('Y-m-d\TH:i')
                    ?? $funeral_case->deceased?->interment?->format('Y-m-d\T00:00')
                ) }}"
                class="form-input w-full"
            >
            @error('interment_at')
                <div class="ef-err">{{ $message }}</div>
            @enderror
        </div>

        <div class="ef-full">
            <label class="ef-label" for="ef_cemetery">Cemetery / Place of Interment</label>
            <input
                type="text"
                id="ef_cemetery"
                name="place_of_cemetery"
                value="{{ old('place_of_cemetery', $funeral_case->deceased?->place_of_cemetery) }}"
                class="form-input w-full"
                maxlength="255"
                placeholder="Cemetery name or interment location"
            >
            @error('place_of_cemetery')
                <div class="ef-err">{{ $message }}</div>
            @enderror
        </div>

        <div class="ef-full">
            <label class="ef-label" for="ef_dec_address">Deceased Address</label>
            <input
                type="text"
                id="ef_dec_address"
                name="deceased_address"
                value="{{ old('deceased_address', $funeral_case->deceased?->address) }}"
                class="form-input w-full"
                maxlength="500"
                placeholder="Last known address of deceased"
            >
            @error('deceased_address')
                <div class="ef-err">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     SECTION 3 — Service & Package
═══════════════════════════════════════════════ --}}
<div class="ef-section">
    <div class="ef-section-head">
        <span class="ef-section-icon"><i class="bi bi-box-seam"></i></span>
        <span class="ef-section-title">Service &amp; Package</span>
    </div>

    <div class="ef-grid ef-grid-2">
        <div class="ef-full">
            <label class="ef-label" for="ef_package">
                Service Package <span class="ef-req">*</span>
            </label>
            <select id="ef_package" name="package_id" class="form-select w-full" required>
                @foreach($packages as $pkg)
                    @php
                        $promoNow = $pkg->promo_is_active
                            && (!$pkg->promo_starts_at || $pkg->promo_starts_at->lte(now()))
                            && (!$pkg->promo_ends_at   || $pkg->promo_ends_at->gte(now()));
                    @endphp
                    <option
                        value="{{ $pkg->id }}"
                        data-price="{{ $pkg->price }}"
                        data-promo-now="{{ $promoNow ? '1' : '0' }}"
                        data-promo-type="{{ $pkg->promo_value_type }}"
                        data-promo-value="{{ $pkg->promo_value }}"
                        data-promo-label="{{ $pkg->promo_label }}"
                        {{ (string) old('package_id', $funeral_case->package_id) === (string) $pkg->id ? 'selected' : '' }}
                    >
                        {{ $pkg->name }} — {{ number_format($pkg->price, 2) }}{{ $promoNow ? ' · Promo Active' : '' }}
                    </option>
                @endforeach
            </select>
            @error('package_id')
                <div class="ef-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="ef-label" for="ef_wake_loc">Wake / Service Location</label>
            <input
                type="text"
                id="ef_wake_loc"
                name="wake_location"
                value="{{ old('wake_location', $funeral_case->wake_location) }}"
                class="form-input w-full"
                maxlength="255"
                placeholder="Funeral home, chapel, or address"
            >
            @error('wake_location')
                <div class="ef-err">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="ef-label" for="ef_svc_date">Funeral Service Date</label>
            <input
                type="date"
                id="ef_svc_date"
                name="funeral_service_at"
                value="{{ old('funeral_service_at', $funeral_case->funeral_service_at?->format('Y-m-d')) }}"
                class="form-input w-full"
            >
            @error('funeral_service_at')
                <div class="ef-err">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="ef-label" for="ef_req_date">Service Request Date</label>
            <input
                type="date"
                id="ef_req_date"
                name="service_requested_at"
                value="{{ old('service_requested_at', $funeral_case->service_requested_at?->format('Y-m-d')) }}"
                class="form-input w-full"
            >
            @error('service_requested_at')
                <div class="ef-err">{{ $message }}</div>
            @enderror
        </div>

        <div class="ef-full">
            <label class="ef-label" for="ef_addl_svc">Additional Services</label>
            <textarea
                id="ef_addl_svc"
                name="additional_services"
                class="form-input w-full"
                rows="2"
                maxlength="1000"
                placeholder="List any additional services requested (flowers, catering, transport, etc.)..."
                style="resize:vertical; min-height:52px;"
            >{{ old('additional_services', $funeral_case->additional_services) }}</textarea>
            @error('additional_services')
                <div class="ef-err">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

{{-- ── Financial Preview (read-only, auto-computed) ── --}}
<div class="ef-preview">
    <div class="ef-stat">
        <div class="ef-stat-lbl">Package Price</div>
        <div class="ef-stat-val" id="ef_subtotal">0.00</div>
    </div>
    <div class="ef-stat" id="ef_discount_card">
        <div class="ef-stat-lbl">Discount</div>
        <div class="ef-stat-val" id="ef_discount">0.00</div>
        <div class="ef-stat-sub" id="ef_discount_src">None</div>
    </div>
    <div class="ef-stat s-green">
        <div class="ef-stat-lbl">Estimated Total</div>
        <div class="ef-stat-val" id="ef_total">0.00</div>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     SECTION 4 — Case Management
═══════════════════════════════════════════════ --}}
<div class="ef-section" style="border-bottom:none;">
    <div class="ef-section-head">
        <span class="ef-section-icon"><i class="bi bi-sliders2"></i></span>
        <span class="ef-section-title">Case Management</span>
    </div>

    <div class="ef-grid ef-grid-2">
        <div>
            <label class="ef-label" for="ef_status">
                Case Status <span class="ef-req">*</span>
            </label>
            <select
                id="ef_status"
                name="case_status"
                class="form-select w-full"
                required
                style="font-weight:700;"
            >
                <option value="DRAFT"     {{ old('case_status', $funeral_case->case_status) === 'DRAFT'     ? 'selected' : '' }}>Draft</option>
                <option value="ACTIVE"    {{ old('case_status', $funeral_case->case_status) === 'ACTIVE'    ? 'selected' : '' }}>Active</option>
                <option value="COMPLETED" {{ old('case_status', $funeral_case->case_status) === 'COMPLETED' ? 'selected' : '' }}>Completed</option>
            </select>
            @error('case_status')
                <div class="ef-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
            @enderror
        </div>

        <div class="ef-full">
            <label class="ef-label" for="ef_notes">Notes / Remarks</label>
            <textarea
                id="ef_notes"
                name="notes"
                class="form-input w-full"
                rows="2"
                maxlength="1000"
                placeholder="Optional notes or remarks about this update..."
                style="resize:vertical; min-height:52px;"
            >{{ old('notes', ($funeral_case->verification_note !== 'Auto-verified main-branch case update.') ? $funeral_case->verification_note : '') }}</textarea>
            @error('notes')
                <div class="ef-err">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

{{-- ── Footer Buttons ── --}}
<div class="ef-footer">
    <button
        type="submit"
        class="btn"
        style="background:var(--brand-mid,#1e3a5f);border-color:var(--brand-mid,#1e3a5f);color:#fff;padding-left:18px;padding-right:18px;font-weight:700;"
    >
        <i class="bi bi-save2"></i>
        Save Changes
    </button>
    <button
        type="button"
        class="btn-outline"
        onclick="(document.getElementById('caseEditClose') || { click:()=>window.history.back() }).click()"
    >
        Cancel
    </button>
</div>

</form>

<script>
(function () {
    const form = document.getElementById('caseEditForm');
    if (!form) return;

    const packageSel = form.querySelector('[name="package_id"]');
    const ageInput   = form.querySelector('[name="age"]');
    const dobInput   = form.querySelector('[name="date_of_birth"]');
    const dodInput   = form.querySelector('[name="date_of_death"]');
    const seniorPct  = {{ (float) config('funeral.senior_discount_percent', 20) }};

    const subtotalEl = document.getElementById('ef_subtotal');
    const discountEl = document.getElementById('ef_discount');
    const discSrcEl  = document.getElementById('ef_discount_src');
    const discCard   = document.getElementById('ef_discount_card');
    const totalEl    = document.getElementById('ef_total');

    const fmt = n => n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function getAge() {
        const raw = parseInt(ageInput?.value, 10);
        if (!isNaN(raw) && raw >= 0) return raw;
        if (dobInput?.value && dodInput?.value) {
            const ms  = new Date(dodInput.value) - new Date(dobInput.value);
            const yrs = Math.floor(ms / (365.25 * 86400000));
            return yrs >= 0 ? yrs : 0;
        }
        return 0;
    }

    function autoFillAge() {
        if (!dobInput?.value || !dodInput?.value) return;
        const ms  = new Date(dodInput.value) - new Date(dobInput.value);
        const yrs = Math.floor(ms / (365.25 * 86400000));
        if (!isNaN(yrs) && yrs >= 0 && ageInput) ageInput.value = yrs;
    }

    function resolveDiscount(subtotal) {
        const age       = getAge();
        const seniorAmt = age >= 60 ? subtotal * seniorPct / 100 : 0;

        const opt = packageSel?.options[packageSel.selectedIndex];
        let promoAmt = 0, promoLabel = '';
        if (opt?.dataset.promoNow === '1') {
            const type = (opt.dataset.promoType || '').toUpperCase();
            const val  = parseFloat(opt.dataset.promoValue) || 0;
            promoAmt   = type === 'PERCENT'
                ? subtotal * Math.min(Math.max(val, 0), 100) / 100
                : Math.min(val, subtotal);
            if (promoAmt > 0) promoLabel = opt.dataset.promoLabel || 'Promo';
        }

        if (seniorAmt > 0 && seniorAmt >= promoAmt) {
            return { amount: seniorAmt, source: `Senior (${seniorPct}%)` };
        }
        if (promoAmt > 0) return { amount: promoAmt, source: promoLabel };
        return { amount: 0, source: '—' };
    }

    function render() {
        const opt      = packageSel?.options[packageSel.selectedIndex];
        const subtotal = parseFloat(opt?.dataset.price || 0) || 0;
        const { amount, source } = resolveDiscount(subtotal);
        const discount = Math.min(amount, subtotal);
        const total    = Math.max(subtotal - discount, 0);

        if (subtotalEl) subtotalEl.textContent = fmt(subtotal);
        if (discountEl) discountEl.textContent = fmt(discount);
        if (discSrcEl)  discSrcEl.textContent  = source;
        if (totalEl)    totalEl.textContent    = fmt(total);

        if (discCard) {
            discCard.classList.remove('s-amber', 's-green');
            if (discount > 0) discCard.classList.add('s-amber');
        }
    }

    packageSel?.addEventListener('change', render);
    ageInput?.addEventListener('input',   render);
    dobInput?.addEventListener('change',  () => { autoFillAge(); render(); });
    dodInput?.addEventListener('change',  () => { autoFillAge(); render(); });

    render();
})();
</script>
@endsection
