@extends('layouts.panel')

@section('page_title', 'Edit Case Record')
@section('page_desc', 'Update case details — admin full access.')

@section('content')
@php
    $requestDateRecorded = $funeral_case->service_requested_at ?? $funeral_case->created_at ?? now();
@endphp

<style>
/* ── Page wrapper ─────────────────────────────────────────── */
.ace-wrap {
    width: 100%;
    max-width: 860px;
    margin: 0 auto;
    padding: 0 24px 40px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    box-sizing: border-box;
}

/* ── Back button ──────────────────────────────────────────── */
.ace-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    width: fit-content;
    padding: 7px 14px;
    border: 1px solid var(--border);
    border-radius: 10px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--ink-muted);
    background: var(--card);
    text-decoration: none;
    transition: background .15s, border-color .15s, color .15s;
}
.ace-back:hover {
    background: var(--surface-muted, #F3F0E8);
    border-color: var(--accent, #3E4A3D);
    color: var(--accent, #3E4A3D);
}

/* ── Hero ─────────────────────────────────────────────────── */
.ace-hero { background: var(--card); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; }
.ace-hero__top {
    display: flex; align-items: center; justify-content: space-between;
    gap: 16px; padding: 18px 22px 16px; border-bottom: 1px solid var(--border);
}
.ace-hero__left { display: flex; flex-direction: column; gap: 3px; }
.ace-hero__tag  { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: var(--ink-muted); display: flex; align-items: center; gap: 5px; }
.ace-hero__name { font-size: 17px; font-weight: 800; color: var(--ink); letter-spacing: -.3px; line-height: 1.2; }
.ace-hero__sub  { font-size: 12px; color: var(--ink-muted); margin-top: 1px; }
.ace-code-pill  {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 14px; border-radius: 999px;
    background: var(--brand-soft, #e8f0e8); color: var(--brand, #3E4A3D);
    font-size: 13px; font-weight: 800; font-family: monospace;
    border: 1px solid color-mix(in srgb, var(--brand, #3E4A3D) 20%, transparent);
    white-space: nowrap;
}

/* ── Meta strip ───────────────────────────────────────────── */
.ace-meta { display: flex; flex-wrap: wrap; }
.ace-meta__item {
    display: flex; flex-direction: column; gap: 2px;
    padding: 10px 20px; border-right: 1px solid var(--border); min-width: 110px;
}
.ace-meta__item:last-child { border-right: none; }
.ace-meta__label { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .09em; color: var(--ink-muted); }
.ace-meta__value { font-size: 12px; font-weight: 600; color: var(--ink); }

/* ── Card ─────────────────────────────────────────────────── */
.ace-card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; }
.ace-card__head {
    display: flex; align-items: center; gap: 10px;
    padding: 14px 20px; border-bottom: 1px solid var(--border);
    background: var(--surface-muted, #FAFAF7);
}
.ace-card__icon {
    width: 28px; height: 28px; border-radius: 8px;
    background: var(--brand-soft, #e8f0e8); color: var(--brand, #3E4A3D);
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; flex-shrink: 0;
}
.ace-card__title { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .09em; color: var(--ink-muted); }
.ace-card__body  { padding: 18px 20px; }

/* ── Grid & fields ────────────────────────────────────────── */
.ace-grid   { display: grid; gap: 14px; }
.ace-grid-2 { grid-template-columns: 1fr 1fr; }
.ace-grid-3 { grid-template-columns: 1fr 1fr 1fr; }
.ace-full   { grid-column: 1 / -1; }
.ace-field  { display: flex; flex-direction: column; gap: 4px; }
.ace-label  {
    font-size: 10px; font-weight: 800;
    text-transform: uppercase; letter-spacing: .08em;
    color: var(--ink-muted); display: flex; align-items: center; gap: 4px;
}
.ace-label-note { font-size: 9px; font-weight: 500; text-transform: none; letter-spacing: 0; color: var(--ink-muted); opacity: .75; }
.ace-req  { color: #e11d48; }
.ace-hint { font-size: 10px; color: var(--ink-muted); display: flex; align-items: center; gap: 4px; margin-top: 2px; }
.ace-hint-warn { color: #c2680a; }
.ace-err  { font-size: 11px; color: #b91c1c; display: flex; align-items: center; gap: 4px; margin-top: 2px; }
.ace-readonly {
    display: flex; align-items: center; gap: 8px;
    min-height: 40px; padding: 8px 12px;
    border: 1px solid var(--border); border-radius: 10px;
    background: var(--surface-muted, #f5f5f2);
    font-size: 13px; font-weight: 600; color: var(--ink-muted);
    cursor: not-allowed;
}

/* ── Financial summary ────────────────────────────────────── */
.ace-fin {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    padding: 16px 20px;
    background: var(--surface-muted, #FAFAF7);
    border-top: 1px solid var(--border);
}
.ace-fin-card {
    background: var(--card); border: 1px solid var(--border);
    border-radius: 12px; padding: 12px 14px; text-align: center;
}
.ace-fin-label { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .09em; color: var(--ink-muted); margin-bottom: 4px; }
.ace-fin-value { font-size: 16px; font-weight: 800; color: var(--ink); font-variant-numeric: tabular-nums; letter-spacing: -.5px; }
.ace-fin-sub   { font-size: 9px; color: var(--ink-muted); margin-top: 2px; }
.ace-fin-card.is-discount .ace-fin-label,
.ace-fin-card.is-discount .ace-fin-value { color: #b45309; }
.ace-fin-card.is-total .ace-fin-label,
.ace-fin-card.is-total .ace-fin-value { color: #2d6a2d; }

/* ── Error banner ─────────────────────────────────────────── */
.ace-banner {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 12px 16px; background: #fef2f2;
    border: 1px solid #fecaca; border-radius: 12px;
    font-size: 12px; font-weight: 600; color: #7f1d1d;
}
.ace-banner i { color: #dc2626; margin-top: 1px; flex-shrink: 0; }

/* ── Footer ───────────────────────────────────────────────── */
.ace-footer { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.ace-save-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 10px 22px; border-radius: 10px;
    background: var(--accent, #3E4A3D); border: 1px solid var(--accent, #3E4A3D);
    color: #fff; font-size: 13px; font-weight: 700; cursor: pointer;
    transition: background .15s, border-color .15s;
}
.ace-save-btn:hover { background: #2f3a2e; border-color: #2f3a2e; }
.ace-cancel-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 10px 18px; border-radius: 10px;
    background: var(--card); border: 1px solid var(--border);
    color: var(--ink-muted); font-size: 13px; font-weight: 600;
    text-decoration: none; cursor: pointer;
    transition: background .15s, border-color .15s, color .15s;
}
.ace-cancel-btn:hover { background: var(--surface-muted, #F3F0E8); border-color: var(--ink-muted); color: var(--ink); }

/* ── Dark mode ────────────────────────────────────────────── */
html[data-theme='dark'] .ace-card__head { background: rgba(255,255,255,.03); }
html[data-theme='dark'] .ace-readonly   { background: rgba(255,255,255,.04); }
html[data-theme='dark'] .ace-fin        { background: rgba(255,255,255,.02); }
html[data-theme='dark'] .ace-fin-card   { background: rgba(255,255,255,.04); }
html[data-theme='dark'] .ace-banner     { background: #2a0a0a; border-color: #7f1d1d; color: #fca5a5; }

/* ── Responsive ───────────────────────────────────────────── */
@media (max-width: 640px) {
    .ace-wrap   { padding: 0 14px 32px; gap: 16px; }
    .ace-grid-2 { grid-template-columns: 1fr; }
    .ace-grid-3 { grid-template-columns: 1fr 1fr; }
    .ace-fin    { grid-template-columns: 1fr; }
    .ace-meta__item { min-width: 0; flex: 1; }
}
</style>

<form id="adminCaseEditForm" method="POST" action="{{ route('admin.cases.update', $funeral_case) }}">
@csrf
@method('PUT')
<input type="hidden" name="return_to" value="{{ $returnTo }}">

<div class="ace-wrap">

    {{-- Back ──────────────────────────────────────────────────── --}}
    <a href="{{ $returnTo }}" class="ace-back">
        <i class="bi bi-arrow-left"></i> Back
    </a>

    {{-- Error banner ───────────────────────────────────────────── --}}
    @if($errors->any())
        <div class="ace-banner">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        </div>
    @endif

    {{-- Hero ───────────────────────────────────────────────────── --}}
    <div class="ace-hero">
        <div class="ace-hero__top">
            <div class="ace-hero__left">
                <span class="ace-hero__tag"><i class="bi bi-shield-lock"></i> Admin Edit — Case Record</span>
                <div class="ace-hero__name">{{ $funeral_case->client?->full_name ?? 'Client N/A' }}</div>
                <div class="ace-hero__sub">Deceased: {{ $funeral_case->deceased?->full_name ?? 'N/A' }}</div>
            </div>
            <span class="ace-code-pill">
                <i class="bi bi-hash" style="font-size:11px;"></i>
                {{ $funeral_case->case_code }}
            </span>
        </div>
        <div class="ace-meta">
            <div class="ace-meta__item">
                <span class="ace-meta__label">Branch</span>
                <span class="ace-meta__value">
                    {{ $funeral_case->branch?->branch_code ?? '—' }}
                    @if($funeral_case->branch?->branch_name) — {{ $funeral_case->branch->branch_name }} @endif
                </span>
            </div>
            <div class="ace-meta__item">
                <span class="ace-meta__label">Encoded By</span>
                <span class="ace-meta__value">{{ $funeral_case->encodedBy?->name ?? '—' }}</span>
            </div>
            <div class="ace-meta__item">
                <span class="ace-meta__label">Date Created</span>
                <span class="ace-meta__value">{{ $funeral_case->created_at?->format('M d, Y') ?? '—' }}</span>
            </div>
            <div class="ace-meta__item">
                <span class="ace-meta__label">Payment</span>
                <span class="ace-meta__value">
                    <span class="{{ match($funeral_case->payment_status) { 'PAID' => 'status-pill-success', 'PARTIAL' => 'status-pill-warning', default => 'status-pill-danger' } }}">
                        {{ $funeral_case->payment_status ?? 'UNPAID' }}
                    </span>
                </span>
            </div>
            <div class="ace-meta__item">
                <span class="ace-meta__label">Case Status</span>
                <span class="ace-meta__value">
                    <span class="{{ match($funeral_case->case_status) { 'COMPLETED' => 'status-pill-success', 'ACTIVE' => 'status-pill-warning', default => 'status-pill-neutral' } }}">
                        {{ $funeral_case->case_status }}
                    </span>
                </span>
            </div>
        </div>
    </div>

    {{-- Section 1 — Client ──────────────────────────────────────── --}}
    <div class="ace-card">
        <div class="ace-card__head">
            <span class="ace-card__icon"><i class="bi bi-person"></i></span>
            <span class="ace-card__title">Client / Family Representative</span>
        </div>
        <div class="ace-card__body">
            <div class="ace-grid ace-grid-2">

                <div class="ace-field ace-full">
                    <label class="ace-label" for="ace_client_name">Full Name</label>
                    <input type="text" id="ace_client_name" name="client_full_name"
                        value="{{ old('client_full_name', $funeral_case->client?->full_name) }}"
                        class="form-input w-full" maxlength="255" placeholder="Full name of the client / family representative">
                    @error('client_full_name')<div class="ace-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ace-field">
                    <label class="ace-label" for="ace_contact">Contact Number</label>
                    <input type="text" id="ace_contact" name="client_contact"
                        value="{{ old('client_contact', $funeral_case->client?->contact_number) }}"
                        class="form-input w-full" maxlength="50" placeholder="e.g. 09XX XXX XXXX">
                    @error('client_contact')<div class="ace-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ace-field">
                    <label class="ace-label" for="ace_rel">Relationship to Deceased</label>
                    <input type="text" id="ace_rel" name="client_relationship"
                        value="{{ old('client_relationship', $funeral_case->client?->relationship_to_deceased) }}"
                        class="form-input w-full" maxlength="100" placeholder="e.g. Spouse, Parent, Child">
                    @error('client_relationship')<div class="ace-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ace-field ace-full">
                    <label class="ace-label" for="ace_client_address">Address</label>
                    <input type="text" id="ace_client_address" name="client_address"
                        value="{{ old('client_address', $funeral_case->client?->address) }}"
                        class="form-input w-full" maxlength="500" placeholder="Current home address">
                    @error('client_address')<div class="ace-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

            </div>
        </div>
    </div>

    {{-- Section 2 — Deceased ───────────────────────────────────── --}}
    <div class="ace-card">
        <div class="ace-card__head">
            <span class="ace-card__icon"><i class="bi bi-flower1"></i></span>
            <span class="ace-card__title">Deceased Information</span>
        </div>
        <div class="ace-card__body">
            <div class="ace-grid ace-grid-2">

                <div class="ace-field ace-full">
                    <label class="ace-label" for="ace_dec_name">
                        Full Name <span class="ace-req">*</span>
                    </label>
                    <input type="text" id="ace_dec_name" name="deceased_full_name"
                        value="{{ old('deceased_full_name', $funeral_case->deceased?->full_name) }}"
                        class="form-input w-full" maxlength="255" placeholder="Full name of the deceased" required>
                    @error('deceased_full_name')<div class="ace-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ace-field">
                    <label class="ace-label" for="ace_dob">Date of Birth</label>
                    <input type="date" id="ace_dob" name="date_of_birth"
                        value="{{ old('date_of_birth', $funeral_case->deceased?->born?->format('Y-m-d')) }}"
                        class="form-input w-full">
                    @error('date_of_birth')<div class="ace-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ace-field">
                    <label class="ace-label" for="ace_dod">Date of Death</label>
                    <input type="date" id="ace_dod" name="date_of_death"
                        value="{{ old('date_of_death', $funeral_case->deceased?->died?->format('Y-m-d')) }}"
                        class="form-input w-full">
                    @error('date_of_death')<div class="ace-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ace-field">
                    <label class="ace-label" for="ace_age">
                        Age <span class="ace-label-note">— auto-fills from dates above</span>
                    </label>
                    <input type="number" id="ace_age" name="age"
                        value="{{ old('age', $funeral_case->deceased?->age) }}"
                        class="form-input w-full" min="0" max="150" placeholder="Years">
                    @error('age')<div class="ace-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ace-field">
                    <label class="ace-label" for="ace_wake_days">
                        Wake Days <span class="ace-label-note">— auto-calculated</span>
                    </label>
                    <input type="number" id="ace_wake_days" name="wake_days"
                        value="{{ old('wake_days', $funeral_case->deceased?->wake_days) }}"
                        class="form-input w-full" min="0" max="365" placeholder="Days" readonly>
                    <span class="ace-hint"><i class="bi bi-info-circle"></i> Calculated from Wake Start to Funeral Service date.</span>
                </div>

                <div class="ace-field">
                    <label class="ace-label" for="ace_cemetery">Cemetery / Place of Interment</label>
                    <input type="text" id="ace_cemetery" name="place_of_cemetery"
                        value="{{ old('place_of_cemetery', $funeral_case->deceased?->place_of_cemetery) }}"
                        class="form-input w-full" maxlength="255" placeholder="Cemetery name or location">
                    @error('place_of_cemetery')<div class="ace-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ace-field ace-full">
                    <label class="ace-label" for="ace_dec_address">Deceased Home Address</label>
                    <input type="text" id="ace_dec_address" name="deceased_address"
                        value="{{ old('deceased_address', $funeral_case->deceased?->address) }}"
                        class="form-input w-full" maxlength="500" placeholder="Last known address of the deceased">
                    @error('deceased_address')<div class="ace-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

            </div>
        </div>
    </div>

    {{-- Section 3 — Service & Package ─────────────────────────── --}}
    <div class="ace-card">
        <div class="ace-card__head">
            <span class="ace-card__icon"><i class="bi bi-box-seam"></i></span>
            <span class="ace-card__title">Service &amp; Package</span>
        </div>
        <div class="ace-card__body">
            <div class="ace-grid ace-grid-2">

                <div class="ace-field ace-full">
                    <label class="ace-label" for="ace_package">
                        Service Package <span class="ace-req">*</span>
                    </label>
                    <select id="ace_package" name="package_id" class="form-select w-full" required>
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
                                {{ $pkg->name }} — ₱{{ number_format($pkg->price, 2) }}{{ $promoNow ? ' · Promo Active' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('package_id')<div class="ace-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ace-field">
                    <label class="ace-label" for="ace_wake_loc">Wake / Service Location</label>
                    <input type="text" id="ace_wake_loc" name="wake_location"
                        value="{{ old('wake_location', $funeral_case->wake_location) }}"
                        class="form-input w-full" maxlength="255" placeholder="Funeral home, chapel, or address">
                    @error('wake_location')<div class="ace-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ace-field">
                    <label class="ace-label">Date Recorded</label>
                    <div class="ace-readonly">
                        <i class="bi bi-calendar-check" style="font-size:12px;opacity:.5;"></i>
                        {{ $requestDateRecorded->format('F d, Y') }}
                    </div>
                    <span class="ace-hint"><i class="bi bi-lock"></i> System-generated, cannot be changed.</span>
                </div>

                <div class="ace-field">
                    <label class="ace-label" for="ace_wake_start_date">Wake Start Date</label>
                    <input type="date" id="ace_wake_start_date" name="wake_start_date"
                        value="{{ old('wake_start_date', $funeral_case->wake_start_date?->format('Y-m-d')) }}"
                        class="form-input w-full">
                    @error('wake_start_date')<div class="ace-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ace-field">
                    <label class="ace-label" for="ace_wake_start_time">Wake Start Time</label>
                    <input type="time" id="ace_wake_start_time" name="wake_start_time"
                        value="{{ old('wake_start_time', $funeral_case->wake_start_time ? substr($funeral_case->wake_start_time, 0, 5) : '') }}"
                        class="form-input w-full">
                    @error('wake_start_time')<div class="ace-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ace-field">
                    <label class="ace-label" for="ace_svc_date">Funeral Service Date</label>
                    <input type="date" id="ace_svc_date" name="funeral_service_at"
                        value="{{ old('funeral_service_at', $funeral_case->funeral_service_at?->format('Y-m-d')) }}"
                        class="form-input w-full">
                    @error('funeral_service_at')<div class="ace-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ace-field">
                    <label class="ace-label" for="ace_svc_time">Funeral Service Time</label>
                    <input type="time" id="ace_svc_time" name="funeral_service_time"
                        value="{{ old('funeral_service_time', $funeral_case->funeral_service_time ? substr($funeral_case->funeral_service_time, 0, 5) : '') }}"
                        class="form-input w-full">
                    @error('funeral_service_time')<div class="ace-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ace-field">
                    <label class="ace-label" for="ace_interment_date">Interment Date</label>
                    <input type="date" id="ace_interment_date" name="interment_at"
                        value="{{ old('interment_at', $funeral_case->interment_at?->format('Y-m-d')) }}"
                        class="form-input w-full">
                    @error('interment_at')<div class="ace-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ace-field">
                    <label class="ace-label" for="ace_interment_time">Interment Time</label>
                    <input type="time" id="ace_interment_time" name="interment_time"
                        value="{{ old('interment_time', $funeral_case->interment_time ? substr($funeral_case->interment_time, 0, 5) : '') }}"
                        class="form-input w-full">
                    @error('interment_time')<div class="ace-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ace-field ace-full">
                    <label class="ace-label" for="ace_addl_svc">Additional Services</label>
                    <textarea id="ace_addl_svc" name="additional_services"
                        class="form-input w-full" rows="2" maxlength="1000"
                        placeholder="Flowers, catering, transport, or other services..."
                        style="resize:vertical; min-height:52px;"
                    >{{ old('additional_services', $funeral_case->additional_services) }}</textarea>
                    @error('additional_services')<div class="ace-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

            </div>
        </div>

        {{-- Financial preview ──────────────────────────────── --}}
        <div class="ace-fin">
            <div class="ace-fin-card">
                <div class="ace-fin-label">Package Price</div>
                <div class="ace-fin-value" id="ace_subtotal">0.00</div>
                <div class="ace-fin-sub">Base price</div>
            </div>
            <div class="ace-fin-card" id="ace_discount_card">
                <div class="ace-fin-label">Discount</div>
                <div class="ace-fin-value" id="ace_discount">0.00</div>
                <div class="ace-fin-sub" id="ace_discount_src">None</div>
            </div>
            <div class="ace-fin-card is-total">
                <div class="ace-fin-label">Estimated Total</div>
                <div class="ace-fin-value" id="ace_total">0.00</div>
                <div class="ace-fin-sub">After discount</div>
            </div>
        </div>
    </div>

    {{-- Section 4 — Case Management ────────────────────────────── --}}
    <div class="ace-card">
        <div class="ace-card__head">
            <span class="ace-card__icon"><i class="bi bi-sliders2"></i></span>
            <span class="ace-card__title">Case Management</span>
        </div>
        <div class="ace-card__body">
            <div class="ace-grid ace-grid-2">

                <div class="ace-field">
                    <label class="ace-label" for="ace_status">
                        Case Status <span class="ace-req">*</span>
                    </label>
                    <select id="ace_status" name="case_status" class="form-select w-full" required style="font-weight:700;">
                        <option value="ACTIVE" {{ old('case_status', $funeral_case->case_status) === 'ACTIVE' ? 'selected' : '' }}>
                            Active (Ongoing)
                        </option>
                        <option value="COMPLETED"
                            {{ old('case_status', $funeral_case->case_status) === 'COMPLETED' ? 'selected' : '' }}
                            {{ !$intermentPassed ? 'disabled' : '' }}
                        >
                            Completed{{ !$intermentPassed ? ' — available after interment date' : '' }}
                        </option>
                    </select>
                    @error('case_status')<div class="ace-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                    @if(!$intermentPassed)
                        <span class="ace-hint ace-hint-warn">
                            <i class="bi bi-info-circle"></i>
                            Auto-completes after interment date
                            @if($funeral_case->interment_at)({{ $funeral_case->interment_at->format('M d, Y') }})@endif.
                        </span>
                    @endif
                </div>

            </div>
        </div>
    </div>

    {{-- Section 5 — Admin Note ──────────────────────────────────── --}}
    <div class="ace-card">
        <div class="ace-card__head">
            <span class="ace-card__icon"><i class="bi bi-shield-check"></i></span>
            <span class="ace-card__title">Admin Note</span>
        </div>
        <div class="ace-card__body">
            <div class="ace-field">
                <label class="ace-label" for="ace_admin_note">
                    Reason for Edit <span class="ace-label-note">— optional but recommended</span>
                </label>
                <textarea id="ace_admin_note" name="admin_note"
                    class="form-input w-full" rows="3" maxlength="1000"
                    placeholder="e.g. Corrected schedule details, updated package upon family request..."
                    style="resize:vertical; min-height:68px;"
                >{{ old('admin_note') }}</textarea>
                @error('admin_note')<div class="ace-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                <span class="ace-hint"><i class="bi bi-info-circle"></i> This note will be saved in the audit log for this case.</span>
            </div>
        </div>
    </div>

    {{-- Footer ─────────────────────────────────────────────────── --}}
    <div class="ace-footer">
        <button type="submit" class="ace-save-btn">
            <i class="bi bi-save2"></i> Save Changes
        </button>
        <a href="{{ $returnTo }}" class="ace-cancel-btn">
            <i class="bi bi-x"></i> Cancel
        </a>
    </div>

</div>
</form>

<script>
(function () {
    const form = document.getElementById('adminCaseEditForm');
    if (!form) return;

    const packageSel       = form.querySelector('[name="package_id"]');
    const ageInput         = document.getElementById('ace_age');
    const dobInput         = document.getElementById('ace_dob');
    const dodInput         = document.getElementById('ace_dod');
    const wakeStartInput   = document.getElementById('ace_wake_start_date');
    const funeralDateInput = document.getElementById('ace_svc_date');
    const wakeDaysInput    = document.getElementById('ace_wake_days');
    const seniorPct        = {{ (float) config('funeral.senior_discount_percent', 20) }};

    const subtotalEl  = document.getElementById('ace_subtotal');
    const discountEl  = document.getElementById('ace_discount');
    const discSrcEl   = document.getElementById('ace_discount_src');
    const discCard    = document.getElementById('ace_discount_card');
    const totalEl     = document.getElementById('ace_total');

    const fmt = n => '₱ ' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

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

        if (seniorAmt > 0 && seniorAmt >= promoAmt) return { amount: seniorAmt, source: `Senior (${seniorPct}%)` };
        if (promoAmt > 0) return { amount: promoAmt, source: promoLabel };
        return { amount: 0, source: 'None' };
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
        if (discCard)   discCard.classList.toggle('is-discount', discount > 0);
    }

    function calcWakeDays() {
        if (!wakeDaysInput || !wakeStartInput?.value || !funeralDateInput?.value) return;
        const diff = Math.floor(
            (new Date(`${funeralDateInput.value}T00:00:00`) - new Date(`${wakeStartInput.value}T00:00:00`)) / 86400000
        );
        wakeDaysInput.value = diff >= 0 ? diff : '';
    }

    packageSel?.addEventListener('change', render);
    ageInput?.addEventListener('input',    render);
    dobInput?.addEventListener('change',   () => { autoFillAge(); render(); });
    dodInput?.addEventListener('change',   () => { autoFillAge(); render(); });
    wakeStartInput?.addEventListener('change',   calcWakeDays);
    funeralDateInput?.addEventListener('change', calcWakeDays);

    render();
    calcWakeDays();
})();
</script>
@endsection
