@extends('layouts.panel')

@section('page_title','Edit Case')
@section('page_desc', 'Update case record details.')

@section('content')
@php
    $returnTo = request()->query('return_to', route('funeral-cases.index'));
    $requestDateRecorded = $funeral_case->service_requested_at ?? $funeral_case->created_at ?? now();
    $intermentPassed = $funeral_case->interment_at
        && $funeral_case->interment_at->copy()->startOfDay()->isPast();
@endphp

<style>
/* ── Page wrapper ─────────────────────────────────────────── */
.ec-wrap {
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
.ec-back {
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
.ec-back:hover {
    background: var(--surface-muted, #F3F0E8);
    border-color: var(--accent, #3E4A3D);
    color: var(--accent, #3E4A3D);
}

/* ── Page hero header ─────────────────────────────────────── */
.ec-hero {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
}
.ec-hero__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 18px 22px 16px;
    border-bottom: 1px solid var(--border);
}
.ec-hero__left { display: flex; flex-direction: column; gap: 3px; }
.ec-hero__tag {
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--ink-muted);
    display: flex;
    align-items: center;
    gap: 5px;
}
.ec-hero__title {
    font-size: 17px;
    font-weight: 800;
    color: var(--ink);
    letter-spacing: -0.3px;
    line-height: 1.2;
}
.ec-hero__sub {
    font-size: 12px;
    color: var(--ink-muted);
    margin-top: 1px;
}
.ec-code-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 14px;
    border-radius: 999px;
    background: var(--brand-soft, #e8f0e8);
    color: var(--brand, #3E4A3D);
    font-size: 13px;
    font-weight: 800;
    border: 1px solid color-mix(in srgb, var(--brand, #3E4A3D) 20%, transparent);
    letter-spacing: 0.04em;
    white-space: nowrap;
    font-family: monospace;
}

/* ── Meta strip ───────────────────────────────────────────── */
.ec-meta {
    display: flex;
    flex-wrap: wrap;
}
.ec-meta__item {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 10px 20px;
    border-right: 1px solid var(--border);
    min-width: 120px;
}
.ec-meta__item:last-child { border-right: none; }
.ec-meta__label {
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.09em;
    color: var(--ink-muted);
}
.ec-meta__value {
    font-size: 12px;
    font-weight: 600;
    color: var(--ink);
}

/* ── Card ─────────────────────────────────────────────────── */
.ec-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
}
.ec-card__head {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 20px;
    border-bottom: 1px solid var(--border);
    background: var(--surface-muted, #FAFAF7);
}
.ec-card__icon {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: var(--brand-soft, #e8f0e8);
    color: var(--brand, #3E4A3D);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
}
.ec-card__title {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.09em;
    color: var(--ink-muted);
}
.ec-card__body { padding: 18px 20px; }

/* ── Grid ─────────────────────────────────────────────────── */
.ec-grid   { display: grid; gap: 14px; }
.ec-grid-2 { grid-template-columns: 1fr 1fr; }
.ec-grid-3 { grid-template-columns: 1fr 1fr 1fr; }
.ec-full   { grid-column: 1 / -1; }

/* ── Field ────────────────────────────────────────────────── */
.ec-field { display: flex; flex-direction: column; gap: 4px; }
.ec-label {
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--ink-muted);
    display: flex;
    align-items: center;
    gap: 4px;
}
.ec-label-note {
    font-size: 9px;
    font-weight: 500;
    text-transform: none;
    letter-spacing: 0;
    color: var(--ink-muted);
    opacity: 0.75;
}
.ec-req { color: #e11d48; }
.ec-hint {
    font-size: 10px;
    color: var(--ink-muted);
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 2px;
}
.ec-hint-warn { color: #c2680a; }
.ec-err {
    font-size: 11px;
    color: #b91c1c;
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 2px;
}

/* Readonly display field */
.ec-readonly {
    display: flex;
    align-items: center;
    gap: 8px;
    min-height: 40px;
    padding: 8px 12px;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: var(--surface-muted, #f5f5f2);
    font-size: 13px;
    font-weight: 600;
    color: var(--ink-muted);
    cursor: not-allowed;
}

/* Name grid for readonly name fields */
.ec-name-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

/* ── Financial summary ────────────────────────────────────── */
.ec-fin {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    padding: 16px 20px;
    background: var(--surface-muted, #FAFAF7);
    border-top: 1px solid var(--border);
}
.ec-fin-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 12px 14px;
    text-align: center;
}
.ec-fin-label {
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.09em;
    color: var(--ink-muted);
    margin-bottom: 4px;
}
.ec-fin-value {
    font-size: 16px;
    font-weight: 800;
    color: var(--ink);
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.5px;
}
.ec-fin-sub {
    font-size: 9px;
    color: var(--ink-muted);
    margin-top: 2px;
}
.ec-fin-card.is-discount .ec-fin-label,
.ec-fin-card.is-discount .ec-fin-value { color: #b45309; }
.ec-fin-card.is-total .ec-fin-label,
.ec-fin-card.is-total .ec-fin-value { color: #2d6a2d; }

/* ── Error banner ─────────────────────────────────────────── */
.ec-banner {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 16px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    color: #7f1d1d;
}
.ec-banner i { margin-top: 1px; color: #dc2626; flex-shrink: 0; }

/* ── Footer ───────────────────────────────────────────────── */
.ec-footer {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.ec-save-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 10px 22px;
    border-radius: 10px;
    background: var(--accent, #3E4A3D);
    border: 1px solid var(--accent, #3E4A3D);
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: background .15s, border-color .15s;
}
.ec-save-btn:hover { background: #2f3a2e; border-color: #2f3a2e; }
.ec-cancel-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 18px;
    border-radius: 10px;
    background: var(--card);
    border: 1px solid var(--border);
    color: var(--ink-muted);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: background .15s, border-color .15s, color .15s;
}
.ec-cancel-btn:hover {
    background: var(--surface-muted, #F3F0E8);
    border-color: var(--ink-muted);
    color: var(--ink);
}

/* ── Dark mode ────────────────────────────────────────────── */
html[data-theme='dark'] .ec-card__head { background: rgba(255,255,255,.03); }
html[data-theme='dark'] .ec-hero__top  { background: transparent; }
html[data-theme='dark'] .ec-readonly   { background: rgba(255,255,255,.04); }
html[data-theme='dark'] .ec-fin        { background: rgba(255,255,255,.02); }
html[data-theme='dark'] .ec-fin-card   { background: rgba(255,255,255,.04); }
html[data-theme='dark'] .ec-banner     { background: #2a0a0a; border-color: #7f1d1d; color: #fca5a5; }

/* ── Responsive ───────────────────────────────────────────── */
@media (max-width: 640px) {
    .ec-wrap      { padding: 0 14px 32px; gap: 16px; }
    .ec-grid-2    { grid-template-columns: 1fr; }
    .ec-grid-3    { grid-template-columns: 1fr 1fr; }
    .ec-name-grid { grid-template-columns: 1fr; }
    .ec-fin       { grid-template-columns: 1fr; }
    .ec-meta__item { min-width: 0; flex: 1; }
}
</style>

<form id="caseEditForm" method="POST" action="{{ route('funeral-cases.update', $funeral_case) }}">
@csrf
@method('PUT')
<input type="hidden" name="client_id"           value="{{ $funeral_case->client_id }}">
<input type="hidden" name="deceased_id"         value="{{ $funeral_case->deceased_id }}">
<input type="hidden" name="service_requested_at" value="{{ $requestDateRecorded->format('Y-m-d') }}">
<input type="hidden" name="return_to"           value="{{ $returnTo }}">

<div class="ec-wrap">

    {{-- Back ──────────────────────────────────────────────── --}}
    <a href="{{ $returnTo }}" class="ec-back">
        <i class="bi bi-arrow-left"></i> Back to Cases
    </a>

    {{-- Error banner ──────────────────────────────────────── --}}
    @if($errors->any())
        <div class="ec-banner">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Hero header ───────────────────────────────────────── --}}
    <div class="ec-hero">
        <div class="ec-hero__top">
            <div class="ec-hero__left">
                <span class="ec-hero__tag">
                    <i class="bi bi-pencil-square"></i> Edit Case Record
                </span>
                <div class="ec-hero__title">
                    {{ $funeral_case->client?->full_name ?? 'Client N/A' }}
                </div>
                <div class="ec-hero__sub">
                    Deceased: {{ $funeral_case->deceased?->full_name ?? 'N/A' }}
                </div>
            </div>
            <span class="ec-code-pill">
                <i class="bi bi-hash" style="font-size:11px;"></i>
                {{ $funeral_case->case_code }}
            </span>
        </div>

        <div class="ec-meta">
            <div class="ec-meta__item">
                <span class="ec-meta__label">Branch</span>
                <span class="ec-meta__value">
                    {{ $funeral_case->branch?->branch_code ?? '—' }}
                    @if($funeral_case->branch?->branch_name)
                        — {{ $funeral_case->branch->branch_name }}
                    @endif
                </span>
            </div>
            <div class="ec-meta__item">
                <span class="ec-meta__label">Encoded By</span>
                <span class="ec-meta__value">{{ $funeral_case->encodedBy?->name ?? '—' }}</span>
            </div>
            <div class="ec-meta__item">
                <span class="ec-meta__label">Date Created</span>
                <span class="ec-meta__value">{{ $funeral_case->created_at?->format('M d, Y') ?? '—' }}</span>
            </div>
            <div class="ec-meta__item">
                <span class="ec-meta__label">Payment Status</span>
                <span class="ec-meta__value">
                    <span class="{{ match($funeral_case->payment_status) { 'PAID' => 'status-pill-success', 'PARTIAL' => 'status-pill-warning', default => 'status-pill-danger' } }}">
                        {{ $funeral_case->payment_status ?? 'UNPAID' }}
                    </span>
                </span>
            </div>
            <div class="ec-meta__item">
                <span class="ec-meta__label">Case Status</span>
                <span class="ec-meta__value">
                    <span class="{{ match($funeral_case->case_status) { 'COMPLETED' => 'status-pill-success', 'ACTIVE' => 'status-pill-warning', default => 'status-pill-neutral' } }}">
                        {{ $funeral_case->case_status }}
                    </span>
                </span>
            </div>
        </div>
    </div>

    {{-- Section 1 — Client ─────────────────────────────────── --}}
    <div class="ec-card">
        <div class="ec-card__head">
            <span class="ec-card__icon"><i class="bi bi-person"></i></span>
            <span class="ec-card__title">Client / Family Representative</span>
        </div>
        <div class="ec-card__body">
            <div class="ec-grid ec-grid-2">

                <div class="ec-field ec-full">
                    <label class="ec-label">
                        Full Name
                        <span class="ec-label-note">— read-only, edit via Client profile</span>
                    </label>
                    @php $clientParts = $funeral_case->client?->splitName() ?? []; @endphp
                    <div class="ec-name-grid">
                        <div class="ec-readonly"><i class="bi bi-person-fill" style="font-size:11px;opacity:.4;"></i>{{ $clientParts['first_name'] ?? '—' }}</div>
                        <div class="ec-readonly">{{ $clientParts['last_name'] ?? '—' }}</div>
                        <div class="ec-readonly">{{ $clientParts['middle_name'] ?? '—' }}</div>
                        <div class="ec-readonly">{{ $clientParts['suffix'] ?? '—' }}</div>
                    </div>
                </div>

                <div class="ec-field">
                    <label class="ec-label" for="ec_client_contact">Contact Number</label>
                    <input type="text" id="ec_client_contact" name="client_contact"
                        value="{{ old('client_contact', $funeral_case->client?->contact_number) }}"
                        class="form-input w-full" maxlength="50" placeholder="e.g. 09XX XXX XXXX">
                    @error('client_contact')<div class="ec-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ec-field">
                    <label class="ec-label" for="ec_client_rel">Relationship to Deceased</label>
                    <input type="text" id="ec_client_rel" name="client_relationship"
                        value="{{ old('client_relationship', $funeral_case->client?->relationship_to_deceased) }}"
                        class="form-input w-full" maxlength="100" placeholder="e.g. Spouse, Parent, Child">
                    @error('client_relationship')<div class="ec-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ec-field ec-full">
                    <label class="ec-label" for="ec_client_address">Address</label>
                    <input type="text" id="ec_client_address" name="client_address"
                        value="{{ old('client_address', $funeral_case->client?->address) }}"
                        class="form-input w-full" maxlength="500" placeholder="Current home address">
                    @error('client_address')<div class="ec-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

            </div>
        </div>
    </div>

    {{-- Section 2 — Deceased ──────────────────────────────── --}}
    <div class="ec-card">
        <div class="ec-card__head">
            <span class="ec-card__icon"><i class="bi bi-flower1"></i></span>
            <span class="ec-card__title">Deceased Information</span>
        </div>
        <div class="ec-card__body">
            <div class="ec-grid ec-grid-2">

                <div class="ec-field ec-full">
                    <label class="ec-label">
                        Full Name
                        <span class="ec-label-note">— read-only, edit via Deceased profile</span>
                    </label>
                    @php $deceasedParts = $funeral_case->deceased?->splitName() ?? []; @endphp
                    <div class="ec-name-grid">
                        <div class="ec-readonly"><i class="bi bi-flower1" style="font-size:11px;opacity:.4;"></i>{{ $deceasedParts['first_name'] ?? '—' }}</div>
                        <div class="ec-readonly">{{ $deceasedParts['last_name'] ?? '—' }}</div>
                        <div class="ec-readonly">{{ $deceasedParts['middle_name'] ?? '—' }}</div>
                        <div class="ec-readonly">{{ $deceasedParts['suffix'] ?? '—' }}</div>
                    </div>
                </div>

                <div class="ec-field">
                    <label class="ec-label" for="ec_dob">Date of Birth</label>
                    <input type="date" id="ec_dob" name="date_of_birth"
                        value="{{ old('date_of_birth', $funeral_case->deceased?->born?->format('Y-m-d')) }}"
                        class="form-input w-full">
                    @error('date_of_birth')<div class="ec-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ec-field">
                    <label class="ec-label" for="ec_dod">Date of Death</label>
                    <input type="date" id="ec_dod" name="date_of_death"
                        value="{{ old('date_of_death', $funeral_case->deceased?->died?->format('Y-m-d') ?? $funeral_case->deceased?->date_of_death?->format('Y-m-d')) }}"
                        class="form-input w-full">
                    @error('date_of_death')<div class="ec-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ec-field">
                    <label class="ec-label" for="ec_age">
                        Age <span class="ec-label-note">— auto-fills from dates above</span>
                    </label>
                    <input type="number" id="ec_age" name="age"
                        value="{{ old('age', $funeral_case->deceased?->age) }}"
                        class="form-input w-full" min="0" max="150" placeholder="Years">
                    @error('age')<div class="ec-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ec-field">
                    <label class="ec-label" for="ec_wake_days">
                        Wake Days <span class="ec-label-note">— auto-calculated</span>
                    </label>
                    <input type="number" id="ec_wake_days" name="wake_days"
                        value="{{ old('wake_days', $funeral_case->deceased?->wake_days) }}"
                        class="form-input w-full" min="0" max="365" placeholder="Days" readonly>
                    <span class="ec-hint"><i class="bi bi-info-circle"></i> Calculated from Wake Start to Funeral Service date.</span>
                    @error('wake_days')<div class="ec-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ec-field">
                    <label class="ec-label" for="ec_interment">Interment Date</label>
                    <input type="date" id="ec_interment" name="interment_at"
                        value="{{ old('interment_at', $funeral_case->interment_at?->format('Y-m-d')) }}"
                        class="form-input w-full">
                    @error('interment_at')<div class="ec-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ec-field">
                    <label class="ec-label" for="ec_interment_time">Interment Time</label>
                    <input type="time" id="ec_interment_time" name="interment_time"
                        value="{{ old('interment_time', $funeral_case->interment_time ? substr($funeral_case->interment_time, 0, 5) : '') }}"
                        class="form-input w-full">
                    @error('interment_time')<div class="ec-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ec-field ec-full">
                    <label class="ec-label" for="ec_cemetery">Cemetery / Place of Interment</label>
                    <input type="text" id="ec_cemetery" name="place_of_cemetery"
                        value="{{ old('place_of_cemetery', $funeral_case->deceased?->place_of_cemetery) }}"
                        class="form-input w-full" maxlength="255" placeholder="Cemetery name or interment location">
                    @error('place_of_cemetery')<div class="ec-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ec-field ec-full">
                    <label class="ec-label" for="ec_dec_address">Deceased Home Address</label>
                    <input type="text" id="ec_dec_address" name="deceased_address"
                        value="{{ old('deceased_address', $funeral_case->deceased?->address) }}"
                        class="form-input w-full" maxlength="500" placeholder="Last known address of the deceased">
                    @error('deceased_address')<div class="ec-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

            </div>
        </div>
    </div>

    {{-- Section 3 — Service & Package ────────────────────── --}}
    <div class="ec-card">
        <div class="ec-card__head">
            <span class="ec-card__icon"><i class="bi bi-box-seam"></i></span>
            <span class="ec-card__title">Service &amp; Package</span>
        </div>
        <div class="ec-card__body">
            <div class="ec-grid ec-grid-2">

                <div class="ec-field ec-full">
                    <label class="ec-label" for="ec_package">
                        Service Package <span class="ec-req">*</span>
                    </label>
                    <select id="ec_package" name="package_id" class="form-select w-full" required>
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
                    @error('package_id')<div class="ec-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ec-field">
                    <label class="ec-label" for="ec_wake_loc">Wake / Service Location</label>
                    <input type="text" id="ec_wake_loc" name="wake_location"
                        value="{{ old('wake_location', $funeral_case->wake_location) }}"
                        class="form-input w-full" maxlength="255" placeholder="Funeral home, chapel, or address">
                    @error('wake_location')<div class="ec-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ec-field">
                    <label class="ec-label" for="ec_req_date">Date Recorded</label>
                    <div class="ec-readonly">
                        <i class="bi bi-calendar-check" style="font-size:12px;opacity:.5;"></i>
                        {{ $requestDateRecorded->format('F d, Y') }}
                    </div>
                    <span class="ec-hint"><i class="bi bi-lock"></i> System-generated, cannot be changed.</span>
                </div>

                <div class="ec-field">
                    <label class="ec-label" for="ec_wake_start_date">Wake Start Date</label>
                    <input type="date" id="ec_wake_start_date" name="wake_start_date"
                        value="{{ old('wake_start_date', $funeral_case->wake_start_date?->format('Y-m-d')) }}"
                        class="form-input w-full">
                    @error('wake_start_date')<div class="ec-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ec-field">
                    <label class="ec-label" for="ec_wake_start_time">Wake Start Time</label>
                    <input type="time" id="ec_wake_start_time" name="wake_start_time"
                        value="{{ old('wake_start_time', $funeral_case->wake_start_time ? substr($funeral_case->wake_start_time, 0, 5) : '') }}"
                        class="form-input w-full">
                    @error('wake_start_time')<div class="ec-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ec-field">
                    <label class="ec-label" for="ec_svc_date">Funeral Service Date</label>
                    <input type="date" id="ec_svc_date" name="funeral_service_at"
                        value="{{ old('funeral_service_at', $funeral_case->funeral_service_at?->format('Y-m-d')) }}"
                        class="form-input w-full">
                    @error('funeral_service_at')<div class="ec-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ec-field">
                    <label class="ec-label" for="ec_svc_time">Funeral Service Time</label>
                    <input type="time" id="ec_svc_time" name="funeral_service_time"
                        value="{{ old('funeral_service_time', $funeral_case->funeral_service_time ? substr($funeral_case->funeral_service_time, 0, 5) : '') }}"
                        class="form-input w-full">
                    @error('funeral_service_time')<div class="ec-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

                <div class="ec-field ec-full">
                    <label class="ec-label" for="ec_addl_svc">Additional Services</label>
                    <textarea id="ec_addl_svc" name="additional_services"
                        class="form-input w-full" rows="2" maxlength="1000"
                        placeholder="Flowers, catering, transport, or other services..."
                        style="resize:vertical; min-height:52px;"
                    >{{ old('additional_services', $funeral_case->additional_services) }}</textarea>
                    @error('additional_services')<div class="ec-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                </div>

            </div>
        </div>

        {{-- Financial preview ──────────────────────────────── --}}
        <div class="ec-fin">
            <div class="ec-fin-card">
                <div class="ec-fin-label">Package Price</div>
                <div class="ec-fin-value" id="ec_subtotal">0.00</div>
                <div class="ec-fin-sub">Base price</div>
            </div>
            <div class="ec-fin-card" id="ec_discount_card">
                <div class="ec-fin-label">Discount</div>
                <div class="ec-fin-value" id="ec_discount">0.00</div>
                <div class="ec-fin-sub" id="ec_discount_src">None</div>
            </div>
            <div class="ec-fin-card is-total">
                <div class="ec-fin-label">Estimated Total</div>
                <div class="ec-fin-value" id="ec_total">0.00</div>
                <div class="ec-fin-sub">After discount</div>
            </div>
        </div>
    </div>

    {{-- Section 4 — Case Management ──────────────────────── --}}
    <div class="ec-card">
        <div class="ec-card__head">
            <span class="ec-card__icon"><i class="bi bi-sliders2"></i></span>
            <span class="ec-card__title">Case Management</span>
        </div>
        <div class="ec-card__body">
            <div class="ec-grid ec-grid-2">

                <div class="ec-field">
                    <label class="ec-label">Case Status</label>
                    {{-- Status is system-managed and auto-set to COMPLETED when interment date is reached. --}}
                    <div class="flex items-center gap-2 mt-1">
                        <span class="{{ match($funeral_case->case_status) { 'COMPLETED' => 'status-pill-success', 'ACTIVE' => 'status-pill-warning', default => 'status-pill-neutral' } }}">
                            {{ $funeral_case->case_status }}
                        </span>
                    </div>
                    <span class="ec-hint">
                        <i class="bi bi-info-circle"></i>
                        Automatically set to Completed once the interment date is reached.
                    </span>
                    {{-- Pass current value so the controller doesn't need it from user input --}}
                    <input type="hidden" name="case_status" value="{{ $funeral_case->case_status }}">
                </div>

            </div>
        </div>
    </div>

    {{-- Section 5 — Notes ──────────────────────────────────── --}}
    <div class="ec-card">
        <div class="ec-card__head">
            <span class="ec-card__icon"><i class="bi bi-pencil-square"></i></span>
            <span class="ec-card__title">Notes / Reason for Edit</span>
        </div>
        <div class="ec-card__body">
            <div class="ec-field">
                <label class="ec-label" for="ec_notes">
                    Notes <span class="ec-label-note">— optional but recommended</span>
                </label>
                <textarea id="ec_notes" name="notes"
                    class="form-input w-full" rows="3" maxlength="1000"
                    placeholder="e.g. Corrected schedule details, updated package upon family request..."
                    style="resize:vertical; min-height:68px;"
                >{{ old('notes') }}</textarea>
                @error('notes')<div class="ec-err"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@enderror
                <span class="ec-hint"><i class="bi bi-info-circle"></i> This note will be saved in the audit log for this case.</span>
            </div>
        </div>
    </div>

    {{-- Footer buttons ─────────────────────────────────────── --}}
    <div class="ec-footer">
        <button type="submit" class="ec-save-btn">
            <i class="bi bi-save2"></i> Save Changes
        </button>
        <a href="{{ $returnTo }}" class="ec-cancel-btn">
            <i class="bi bi-x"></i> Cancel
        </a>
    </div>

</div>
</form>

<script>
(function () {
    const form = document.getElementById('caseEditForm');
    if (!form) return;

    const packageSel       = form.querySelector('[name="package_id"]');
    const ageInput         = form.querySelector('[name="age"]');
    const dobInput         = form.querySelector('[name="date_of_birth"]');
    const dodInput         = form.querySelector('[name="date_of_death"]');
    const wakeStartInput   = document.getElementById('ec_wake_start_date');
    const funeralDateInput = document.getElementById('ec_svc_date');
    const wakeDaysInput    = document.getElementById('ec_wake_days');
    const seniorPct        = {{ (float) config('funeral.senior_discount_percent', 20) }};

    const subtotalEl  = document.getElementById('ec_subtotal');
    const discountEl  = document.getElementById('ec_discount');
    const discSrcEl   = document.getElementById('ec_discount_src');
    const discCard    = document.getElementById('ec_discount_card');
    const totalEl     = document.getElementById('ec_total');

    const fmt = n => '₱ ' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

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

        if (discCard) {
            discCard.classList.toggle('is-discount', discount > 0);
        }
    }

    function calcWakeDays() {
        if (!wakeDaysInput || !wakeStartInput?.value || !funeralDateInput?.value) return;
        const diff = Math.floor(
            (new Date(`${funeralDateInput.value}T00:00:00`) - new Date(`${wakeStartInput.value}T00:00:00`)) / 86400000
        );
        wakeDaysInput.value = diff >= 0 ? diff : '';
    }

    packageSel?.addEventListener('change', render);
    ageInput?.addEventListener('input',   render);
    dobInput?.addEventListener('change',  () => { autoFillAge(); render(); });
    dodInput?.addEventListener('change',  () => { autoFillAge(); render(); });
    wakeStartInput?.addEventListener('change', calcWakeDays);
    funeralDateInput?.addEventListener('change', calcWakeDays);

    render();
})();
</script>
@endsection
