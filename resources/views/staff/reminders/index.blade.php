@extends('layouts.panel')

@section('page_title', 'Schedules & Reminders')
@section('page_desc', 'Track wake periods, funeral ceremonies, interments, and payment follow-ups.')

@php
    $remindersRoute = request()->routeIs('admin.*') ? 'admin.reminders.index' : 'staff.reminders.index';
    $selectedBranch = $branchChoices->firstWhere('id', $selectedBranchId ?? null);
    $hasBranchSwitcher = $branchChoices->count() > 1;
    $dueWindow = $filters['due_window'] ?? (request()->filled('date') ? 'custom' : 'any');
    $hasCustomDate = $dueWindow === 'custom';
    $hasActiveReminderFilters = request()->filled('date')
        || request()->filled('case_status')
        || request()->filled('payment_status')
        || ($hasBranchSwitcher && request()->filled('branch_id'))
        || ($dueWindow !== 'any');
@endphp

@section('header_actions')
    @if($hasBranchSwitcher)
    <form method="GET" action="{{ route($remindersRoute) }}" class="rp-header-filters" id="rpFilterForm">
        <input type="hidden" name="tab" value="{{ $activeTab ?? 'all' }}">

        @if($activeTab === 'schedules')
            <input type="hidden" name="calendar_date" value="{{ $calendarDate->toDateString() }}">
            <input type="hidden" name="calendar_view" value="{{ $calendarView }}">
            <input type="hidden" name="schedule_section" value="{{ $scheduleSection }}">
            <input type="hidden" name="schedule_q" value="{{ $scheduleSearch }}">
            <input type="hidden" name="upcoming_view" value="{{ $upcomingView }}">
        @endif

        @if($hasBranchSwitcher)
            <div class="rp-field rp-field--branch">
                <label for="rp_branch"><i class="bi bi-building"></i> Branch</label>
                <div class="rp-select-control">
                    <select id="rp_branch" name="branch_id" class="form-select" onchange="this.form.submit()">
                        @if($allowAllBranches ?? false)
                            <option value="" @selected(!($selectedBranchId ?? null))>All Branches</option>
                        @endif
                        @foreach($branchChoices as $branch)
                            <option value="{{ $branch->id }}" {{ ($selectedBranchId ?? null) === $branch->id ? 'selected' : '' }}>
                                {{ $branch->branch_code }} - {{ $branch->branch_name }}
                            </option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down rp-filter-chevron" data-filter-select-icon aria-hidden="true"></i>
                </div>
            </div>
        @else
            <input type="hidden" name="branch_id" value="{{ $selectedBranchId ?? '' }}">
        @endif

        @if($hasActiveReminderFilters)
            <a href="{{ route($remindersRoute, ['tab' => $activeTab ?? 'all']) }}" class="rp-filter-reset" title="Clear filters" aria-label="Clear filters">
                <i class="bi bi-x-circle"></i> Clear
            </a>
        @endif
    </form>
    @endif
@endsection

@section('content')
<style>
    /* ── Page wrapper ─────────────────────────────────────────── */
    .rp-wrap {
        width: 100%;
        max-width: 1440px;
        margin: 0 auto;
        padding: 0 24px 40px;
        display: flex;
        flex-direction: column;
        gap: 16px;
        box-sizing: border-box;
    }

    .rp-topline {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    /* ── Filter card ──────────────────────────────────────────── */
    .rp-filter-card {
        background: rgba(235, 243, 227, 0.74);
        border: 1px solid #b9cbb1;
        border-radius: 12px;
        padding: 12px 14px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 14px;
        flex-wrap: wrap;
    }
    .rp-filter-card__head {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 10px;
        min-width: auto;
    }
    .rp-filter-card__title {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #7a8076;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .rp-scope-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 34px;
        padding: 7px 12px;
        border: 1px solid #bfd0b8;
        border-radius: 999px;
        background: #edf4e8;
        color: #426043;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }
    .rp-scope-pill i { color: #0f766e; }

    .rp-filter-grid {
        display: flex;
        align-items: flex-end;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
        flex: 1;
    }
    .rp-header-filters {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: flex-end;
        justify-content: flex-end;
        gap: 8px;
        flex-wrap: nowrap;
        padding: 7px 9px;
        border: 1px solid rgba(185, 203, 177, .9);
        border-radius: 12px;
        background: rgba(250, 252, 247, .96);
        box-shadow: 0 3px 10px rgba(45, 68, 48, .06);
    }
    .panel-page-header {
        position: relative;
        z-index: 20 !important;
        overflow: visible !important;
    }
    .panel-page-header__actions { position: relative; z-index: 2; isolation: isolate; }
    .rp-header-filters .rp-field { min-width: 0; width: 150px; }
    .rp-header-filters .rp-field--custom-date { width: 150px; }
    .rp-header-filters .rp-field--status { width: 150px; }
    .rp-header-filters .rp-field--branch { width: 210px; flex: 0 0 210px; }
    .rp-header-filters .rp-filter-reset { min-height: 38px; align-self: flex-end; }
    .rp-filter-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
    }
    .rp-filter-note {
        font-size: 10px;
        color: #a0a9a0;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .rp-filter-reset {
        font-size: 11px;
        font-weight: 700;
        color: #8a9590;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 12px;
        border: 1px solid #d9e3ee;
        border-radius: 9px;
        background: #f8fbff;
        transition: color .15s, border-color .15s, background .15s;
        white-space: nowrap;
    }
    .rp-filter-reset:hover { color: #3E4A3D; border-color: #3E4A3D; background: #F3F0E8; }

    .rp-field {
        display: flex;
        flex-direction: column;
        gap: 4px;
        min-width: 180px;
    }
    .rp-field--date { max-width: 190px; }
    .rp-field--custom-date { max-width: 190px; }
    .rp-field--custom-date.is-hidden { display: none; }
    .rp-field--status { min-width: 190px; max-width: 210px; }
    .rp-field--branch { min-width: 260px; flex: 1; }
    .rp-field label {
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #8a9590;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .rp-field label i { font-size: 11px; color: #aab4ae; }
    .rp-field input,
    .rp-field select {
        width: 100%;
        height: 38px;
        min-height: 38px;
        margin-top: 0 !important;
        border-radius: 10px;
        box-sizing: border-box;
        border-color: #bccab5;
        background-color: #fffef9;
        color: #263129 !important;
        opacity: 1;
    }
    .rp-field select option { background: #fffef9; color: #263129; }
    .panel-shell-body .rp-field:has(select:not([multiple]):not([size]))::after { display: none; }
    .rp-select-control {
        position: relative;
        width: 100%;
        height: 44px;
    }
    .rp-select-control .form-select {
        height: 44px !important;
        min-height: 44px !important;
        padding-right: 38px !important;
        cursor: pointer;
    }
    .rp-filter-chevron {
        position: absolute;
        top: 50%;
        right: 13px;
        z-index: 3;
        color: #657166;
        font-size: 12px;
        line-height: 1;
        pointer-events: none;
        transform: translateY(-50%) rotate(0deg);
        transform-origin: center;
        transition: transform .16s ease, color .16s ease, opacity .16s ease;
    }
    .rp-field.is-open .rp-filter-chevron {
        color: #263129;
        transform: translateY(-50%) rotate(180deg);
    }

    /* ── Tabs card ────────────────────────────────────────────── */
    .rp-tabs-card {
        background: transparent;
        border: 0;
        border-radius: 0;
        padding: 0;
    }
    .rp-tabs-row {
        display: flex;
        flex-wrap: wrap;
        gap: 26px;
        margin-bottom: 14px;
        padding-bottom: 14px;
        border-bottom: 1px solid #c7d5bf;
    }

    /* ── Tab + tooltip ────────────────────────────────────────── */
    .rp-tab {
        position: relative;
        min-height: 48px;
        padding: 9px 3px 12px;
        font-size: 13px;
        font-weight: 750;
        text-transform: none;
        letter-spacing: 0;
        border-radius: 0;
        border: 0;
        border-bottom: 2px solid transparent;
        transition: color .15s, border-color .15s, background-color .15s, box-shadow .15s;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        text-decoration: none;
        white-space: nowrap;
    }
    .rp-tab > i { font-size: 14px; }
    .rp-tab.is-active {
        background: transparent;
        border-bottom-color: #2f5131;
        color: #29382c;
        cursor: default;
        box-shadow: none;
    }
    .rp-tab.is-idle {
        background: transparent;
        color: #5f685f;
    }
    .rp-tab.is-idle:hover {
        border-bottom-color: #91a28e;
        color: #3E4A3D;
        background: transparent;
        cursor: pointer;
    }
    .rp-tab:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(62,74,61,0.18); }

    /* Tooltip */
    .rp-tab[data-tip]::after {
        content: attr(data-tip);
        position: absolute;
        bottom: calc(100% + 9px);
        left: 50%;
        transform: translateX(-50%);
        background: #1e2b1e;
        color: #f0f4f0;
        padding: 7px 11px;
        border-radius: 9px;
        font-size: 10px;
        font-weight: 500;
        line-height: 1.45;
        white-space: normal;
        text-transform: none;
        letter-spacing: 0;
        text-align: center;
        width: max-content;
        max-width: 210px;
        pointer-events: none;
        opacity: 0;
        transition: opacity .18s ease;
        z-index: 30;
        box-shadow: 0 4px 14px rgba(0,0,0,0.18);
    }
    .rp-tab[data-tip]::before {
        content: '';
        position: absolute;
        bottom: calc(100% + 3px);
        left: 50%;
        transform: translateX(-50%);
        border: 5px solid transparent;
        border-top-color: #1e2b1e;
        pointer-events: none;
        opacity: 0;
        transition: opacity .18s ease;
        z-index: 30;
    }
    .rp-tab[data-tip]:hover::after,
    .rp-tab[data-tip]:hover::before { opacity: 1; }

    .rp-tab__count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 22px;
        min-height: 22px;
        padding: 3px 7px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 800;
        line-height: 1;
        box-shadow: 0 1px 3px rgba(39, 72, 43, .18);
    }
    .is-active .rp-tab__count { background: #264f2d; color: #ffffff; }
    .is-idle  .rp-tab__count  { background: #3f6a45; color: #ffffff; }

    /* ── Empty state ──────────────────────────────────────────── */
    .rp-empty {
        padding: 42px 20px;
        background: linear-gradient(135deg, rgba(255,255,249,0.95), rgba(245,250,240,0.95));
        border: 1px dashed #c3d3bc;
        border-radius: 14px;
        text-align: center;
    }
    .rp-empty i { color: #8ea184; }

    /* ── Item list ────────────────────────────────────────────── */
    .rp-list { display: grid; gap: 10px; }

    .rp-item {
        padding: 16px 18px;
        background: #eef3e9;
        border: 1px solid #bdccb7;
        border-radius: 12px;
        box-shadow: 0 2px 7px rgba(44, 65, 46, .045);
        transition: border-color .16s, background .16s, box-shadow .16s;
    }
    .rp-item:hover {
        border-color: #a9bba2;
        background: #f3f6ef;
        box-shadow: 0 4px 12px rgba(44, 65, 46, .07);
    }
    .rp-item.rp-item--focused {
        background: #e4eee0;
        border-color: #9db394;
        box-shadow: inset 3px 0 0 #718a69, 0 3px 10px rgba(44, 65, 46, .08);
    }
    .rp-item.rp-item--focused:hover { background: #e8f0e4; }
    .rp-item__body {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        background: none !important;
    }
    .rp-item__info {
        flex: 1;
        min-width: 0;
        display: grid;
        grid-template-columns: minmax(470px, 1.8fr) minmax(170px, .8fr) minmax(220px, auto);
        align-items: center;
        gap: 16px 24px;
        background: none !important;
        box-shadow: none !important;
        border: 0 !important;
    }
    .rp-item__info--schedule {
        grid-template-columns: minmax(360px, .85fr) minmax(0, 1.65fr);
        gap: 14px 22px;
    }
    .rp-item__actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
    .rp-item__action-copy { max-width: 290px; margin: 0; font-size: 13px; font-weight: 500; color: #354139; line-height: 1.5; }
    .rp-case-identity {
        min-width: 0;
        display: grid;
        grid-template-columns: minmax(90px, .55fr) minmax(170px, 1.2fr) minmax(170px, 1fr);
        align-items: center;
        gap: 16px;
        background: none !important;
    }
    .rp-identity-field { min-width: 0; }
    .rp-identity-label {
        display: block;
        margin-bottom: 3px;
        color: #667269;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .07em;
        line-height: 1.2;
        text-transform: uppercase;
    }
    .rp-identity-value {
        display: block;
        overflow: hidden;
        color: #263129;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.35;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .rp-identity-field--deceased .rp-identity-value { font-size: 15px; font-weight: 800; }
    .rp-item__reason { margin: 1px 0 0; font-size: 13px; font-weight: 500; color: #354139; line-height: 1.5; }
    .rp-payment-status { display: flex; align-items: center; min-width: 0; }
    .rp-details { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 2px; background: none !important; }
    .rp-item__info > .rp-conflict-panel { grid-column: 1 / -1; margin-top: 0; }
    .rp-detail {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 9px;
        border: 1px solid #d6e0d1;
        border-radius: 8px;
        background: #f5f8f1;
        color: #3f4b42;
        font-size: 12px;
        line-height: 1.25;
        white-space: nowrap;
    }
    .rp-detail i { color: #657b64; }
    .rp-detail strong { color: #303b32; font-weight: 800; }

    .rp-schedule-strip {
        grid-column: 2 / -1;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(155px, 1fr));
        gap: 7px;
        min-width: 0;
    }
    .rp-schedule-entry {
        position: relative;
        min-width: 0;
        min-height: 62px;
        padding: 9px 10px 8px 13px;
        border: 1px solid #d6e0d1;
        border-radius: 9px;
        background: #f5f8f1;
    }
    .rp-schedule-entry::before {
        position: absolute;
        inset: 8px auto 8px 0;
        width: 3px;
        border-radius: 999px;
        background: #718a69;
        content: '';
    }
    .rp-schedule-entry.is-wake-start::before { background: #9a7132; }
    .rp-schedule-entry.is-wake-end::before { background: #557c9a; }
    .rp-schedule-entry.is-ceremony::before { background: #4f8060; }
    .rp-schedule-entry.is-interment::before { background: #a45a50; }
    .rp-schedule-entry strong,
    .rp-schedule-entry span,
    .rp-schedule-entry small { display: block; }
    .rp-schedule-entry strong {
        overflow: hidden;
        color: #303b32;
        font-size: 12px;
        font-weight: 800;
        line-height: 1.25;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .rp-schedule-entry span {
        overflow: hidden;
        margin-top: 3px;
        color: #667269;
        font-size: 10px;
        font-weight: 650;
        line-height: 1.3;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .rp-schedule-entry small {
        overflow: hidden;
        margin-top: 2px;
        color: #7a857d;
        font-size: 10px;
        line-height: 1.25;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .rp-tags {
        display: flex;
        align-items: center;
        gap: 5px;
        flex-wrap: wrap;
        margin-top: 6px;
    }
    .rp-chip {
        display: inline-flex;
        align-items: center;
        padding: 3px 8px;
        border-radius: 7px;
        border: 1px solid transparent;
        font-size: 11px;
        font-weight: 750;
        text-transform: none;
        letter-spacing: 0;
        line-height: 1.2;
    }

    .rp-view-btn {
        padding: 7px 14px;
        font-size: 10px;
        font-weight: 800;
        text-transform: none;
        letter-spacing: 0;
        border-radius: 9px;
        background: #3E4A3D;
        color: #fff;
        text-decoration: none;
        opacity: 0;
        pointer-events: none;
        transform: translateX(5px);
        transition: opacity .15s ease, transform .15s ease, background .15s;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .rp-item:hover .rp-view-btn,
    .rp-item:focus-within .rp-view-btn {
        opacity: 1;
        pointer-events: auto;
        transform: translateX(0);
    }
    .rp-view-btn:hover { background: #2f3a2e; }

    /* ── Conflict panel (Needs Attention tab) ─────────────────── */
    .rp-conflict-panel {
        margin-top: 10px;
        padding: 10px 12px;
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 9px;
    }
    .rp-conflict-panel__title {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #92400e;
        display: flex;
        align-items: center;
        gap: 5px;
        margin-bottom: 6px;
    }
    .rp-conflict-row {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 5px 0;
        border-top: 1px solid #fef3c7;
    }
    .rp-conflict-row:first-of-type { border-top: none; }
    .rp-conflict-badge {
        font-size: 10px;
        font-weight: 700;
        font-family: monospace;
        padding: 2px 7px;
        border-radius: 6px;
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
        white-space: nowrap;
    }
    .rp-conflict-name {
        font-size: 11px;
        font-weight: 600;
        color: #78350f;
    }
    .rp-conflict-time {
        font-size: 10px;
        font-weight: 700;
        color: #92400e;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 3px;
    }
    .rp-conflict-name-time {
        display: flex;
        align-items: center;
        gap: 6px;
        flex: 1;
    }
    html[data-theme='dark'] .rp-conflict-panel { background: #1c1200; border-color: #78350f; }
    html[data-theme='dark'] .rp-conflict-panel__title { color: #fbbf24; }
    html[data-theme='dark'] .rp-conflict-row { border-top-color: #2d1f00; }
    html[data-theme='dark'] .rp-conflict-badge { background: #2d1f00; color: #fbbf24; border-color: #78350f; }
    html[data-theme='dark'] .rp-conflict-name { color: #fcd34d; }

    /* ── Chip colour palette ──────────────────────────────────── */
    .chip-red    { background: #fee2e2; color: #b91c1c; border-color: #fecaca; }
    .chip-orange { background: #ffedd5; color: #c2410c; border-color: #fed7aa; }
    .chip-blue   { background: #dbeafe; color: #1d4ed8; border-color: #bfdbfe; }
    .chip-indigo { background: #e0e7ff; color: #4338ca; border-color: #c7d2fe; }
    .chip-green  { background: #d1fae5; color: #065f46; border-color: #a7f3d0; }
    .chip-slate  { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }

    /* ── Dark mode ────────────────────────────────────────────── */
    html[data-theme='dark'] .rp-filter-card,
    html[data-theme='dark'] .rp-tabs-card {
        background: transparent;
        border-color: transparent;
    }
    html[data-theme='dark'] .rp-header-filters { background: rgba(15, 32, 56, .97); border-color: #2f4668; }
    html[data-theme='dark'] .rp-field input,
    html[data-theme='dark'] .rp-field select { background: #132844; border-color: #3b5677; color: #eef5ff !important; }
    html[data-theme='dark'] .rp-field select option { background: #132844; color: #eef5ff; }
    html[data-theme='dark'] .rp-filter-card__title { color: #7fa3c8; }
    html[data-theme='dark'] .rp-filter-reset { color: #B9D2E8; background: #162b47; border-color: #335074; }
    html[data-theme='dark'] .rp-filter-reset:hover { color: #a8c8e8; background: #1a3251; border-color: #44658f; }
    html[data-theme='dark'] .rp-field label { color: #6a8baa; }
    html[data-theme='dark'] .rp-filter-note { color: #506880; }
    html[data-theme='dark'] .rp-tabs-row { border-bottom-color: #1e3a57; }
    html[data-theme='dark'] .rp-tab { border-color: transparent; }
    html[data-theme='dark'] .rp-tab.is-active { background: transparent; border-bottom-color: #8fb0d1; color: #f8fbff; }
    html[data-theme='dark'] .rp-tab.is-idle { background: transparent; color: #c7d7ef; }
    html[data-theme='dark'] .rp-tab.is-idle:hover { background: transparent; border-bottom-color: #4d6f98; color: #fff; }
    html[data-theme='dark'] .is-active .rp-tab__count { background: #9fc58f; color: #142b19; }
    html[data-theme='dark'] .is-idle .rp-tab__count { background: #477552; color: #ffffff; }
    html[data-theme='dark'] .rp-empty { background: #12243c; border-color: #355074; }
    html[data-theme='dark'] .rp-item { background: #132844; border-color: #2f4a6b; }
    html[data-theme='dark'] .rp-item:hover { background: #17304e; border-color: #466890; }
    html[data-theme='dark'] .rp-item.rp-item--focused {
        background: #1c3a42;
        border-color: #527f76;
        box-shadow: inset 3px 0 0 #77a597, 0 3px 10px rgba(0, 0, 0, .16);
    }
    html[data-theme='dark'] .rp-identity-value,
    html[data-theme='dark'] .rp-detail strong { color: #f1f5f9; }
    html[data-theme='dark'] .rp-identity-label,
    html[data-theme='dark'] .rp-item__reason,
    html[data-theme='dark'] .rp-item__action-copy { color: #b7c7d9; }
    html[data-theme='dark'] .rp-detail { background: #102138; border-color: #2f4a6b; color: #c2d0df; }
    html[data-theme='dark'] .rp-schedule-entry { background: #102138; border-color: #2f4a6b; }
    html[data-theme='dark'] .rp-schedule-entry strong { color: #f1f5f9; }
    html[data-theme='dark'] .rp-schedule-entry span { color: #b7c7d9; }
    html[data-theme='dark'] .rp-schedule-entry small { color: #93a7ba; }
    html[data-theme='dark'] .rp-view-btn { background: #1e4070; }
    html[data-theme='dark'] .rp-view-btn:hover { background: #245090; }
    html[data-theme='dark'] .rp-tab[data-tip]::after { background: #0b1a2e; color: #c8daf0; }
    html[data-theme='dark'] .rp-tab[data-tip]::before { border-top-color: #0b1a2e; }

    /* ── Responsive ───────────────────────────────────────────── */
    @media (max-width: 1200px) {
        .rp-item__info { grid-template-columns: 1fr 1fr; }
        .rp-item__info--schedule { grid-template-columns: 1fr; }
        .rp-case-identity { grid-column: 1 / -1; }
        .rp-details { grid-column: 1 / -1; }
        .rp-schedule-strip { grid-column: 1 / -1; }
    }
    @media (max-width: 900px) {
        .rp-topline { align-items: flex-start; flex-direction: column; }
        .rp-filter-card { align-items: stretch; }
        .rp-filter-card__head { justify-content: space-between; }
        .rp-filter-grid { justify-content: flex-start; }
        .rp-field,
        .rp-field--status,
        .rp-field--branch { flex: 1 1 220px; }
        .rp-header-filters { justify-content: flex-start; flex-wrap: wrap; width: 100%; }
        .rp-header-filters .rp-field,
        .rp-header-filters .rp-field--status,
        .rp-header-filters .rp-field--branch { flex: 1 1 150px; width: auto; }
    }
    @media (max-width: 640px) {
        .rp-wrap { padding: 0 14px 24px; }
        .rp-filter-grid { display: grid; grid-template-columns: 1fr; }
        .rp-item__body { flex-direction: column; gap: 12px; }
        .rp-item__info { display: flex; flex-direction: column; align-items: stretch; gap: 8px; }
        .rp-case-identity { grid-template-columns: 1fr; gap: 8px; }
        .rp-schedule-strip { grid-template-columns: 1fr; width: 100%; }
        .rp-identity-value { white-space: normal; }
        .rp-item__actions { align-items: flex-start; width: 100%; flex-direction: row; flex-wrap: wrap; }
        .rp-tabs-row { overflow-x: auto; flex-wrap: nowrap; }
    }
</style>

<div class="rp-wrap">

    {{-- ── Filter card ──────────────────────────────────────────── --}}
    {{-- ── Tabs + list card ─────────────────────────────────────── --}}
    @php
        $activeTab = $activeTab ?? 'all';

        $tabMap = [
            'all'      => $reminders,
            'current_wake' => collect($currentWakeItems ?? []),
            'today'    => $reminders->whereIn('type', ['wake_start_today', 'wake_end_today', 'service_today', 'interment_today']),
            'upcoming' => $reminders->whereIn('type', ['upcoming_wake_start', 'upcoming_wake_end', 'upcoming_service', 'upcoming_interment']),
            'warnings' => $reminders->whereIn('type', ['interment_approaching', 'interment_completed', 'interment_date_completed', 'interment_date_today_balance']),
            'unpaid'   => $reminders->where('type', 'balance'),
        ];

        // Tab definitions — new order & labels
        $tabDefs = [
            'schedules' => ['label' => 'Schedules', 'icon' => 'bi-calendar-week', 'tooltip' => 'Wake periods, ceremonies, and interments in one calendar.'],
            'warnings' => ['label' => 'Needs Attention', 'icon' => 'bi-bell', 'tooltip' => 'Cases requiring payment follow-up.'],
        ];

        // Type → chip style map
        $typeChip = [
            'current_wake'       => ['label' => 'Currently in Wake', 'class' => 'chip-green'],
            'wake_start_today'   => ['label' => 'Wake Begins Today','class' => 'chip-blue'],
            'wake_end_today'     => ['label' => 'Wake End Today',   'class' => 'chip-blue'],
            'service_today'      => ['label' => 'Funeral Ceremony Today', 'class' => 'chip-blue'],
            'interment_today'    => ['label' => 'Interment Today',   'class' => 'chip-blue'],
            'upcoming_wake_start'=> ['label' => 'Wake Begins',       'class' => 'chip-indigo'],
            'upcoming_wake_end'  => ['label' => 'Wake End',          'class' => 'chip-indigo'],
            'upcoming_service'   => ['label' => 'Funeral Ceremony',  'class' => 'chip-indigo'],
            'upcoming_interment' => ['label' => 'Interment',         'class' => 'chip-indigo'],
            'interment_approaching' => ['label' => 'Upcoming Interment With Balance', 'class' => 'chip-orange'],
            'interment_completed'   => ['label' => 'Balance After Interment',          'class' => 'chip-red'],
            'interment_date_completed' => ['label' => 'Balance After Scheduled Interment Date', 'class' => 'chip-red'],
            'interment_date_today_balance' => ['label' => 'Interment Today — Balance to Review', 'class' => 'chip-orange'],
            'same_day_service_schedule' => ['label' => 'Same-Day Service Schedule', 'class' => 'chip-blue'],
            'same_day_interment_schedule' => ['label' => 'Same-Day Interment Schedule', 'class' => 'chip-blue'],
        ];

        // Payment status badge
        $payChip = [
            'UNPAID'  => ['label' => 'No Payment Yet',  'class' => 'chip-red'],
            'PARTIAL' => ['label' => 'Partially Paid', 'class' => 'chip-orange'],
            'PAID'    => ['label' => 'Paid',    'class' => 'chip-green'],
        ];
    @endphp

    <div class="rp-tabs-card">

        {{-- Tab row --}}
        <div class="rp-tabs-row">
            @foreach($tabDefs as $key => $def)
                @php
                    // alert_type is a deep-link filter used by notification cards.
                    // Clear it during tab navigation so each tab shows its full dataset.
                    $tabQuery = array_merge(request()->except(['page', 'alert_type', 'focus_case']), ['tab' => $key]);
                @endphp
                <a
                    href="{{ route($remindersRoute, $tabQuery) }}"
                    class="rp-tab {{ $activeTab === $key ? 'is-active' : 'is-idle' }}"
                    @if($activeTab === $key) aria-current="page" @endif
                >
                    <i class="bi {{ $def['icon'] }}"></i>
                    {{ $def['label'] }}
                    @if($key !== 'schedules' && isset($counts[$key]))
                        <span class="rp-tab__count">{{ $counts[$key] ?? 0 }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Build display items — always grouped by case_id across all tabs --}}
        @php
            $rawItems = $tabMap[$activeTab] ?? collect();

            // Group every tab by case_id so the same case never appears twice in one tab.
            // All alert types for the same case are merged into one card with multiple tags.
            $displayCards = $rawItems
                ->groupBy('case_id')
                ->map(function ($group) {
                    $first = $group->first();

                    // Collect same-day schedule entries for staff awareness.
                    $conflicts = $group
                        ->filter(fn ($item) => str_starts_with($item['type'], 'same_day_'))
                        ->pluck('conflict')
                        ->filter()
                        ->values()
                        ->toArray();

                    return [
                        'case_id'   => $first['case_id'] ?? null,
                        'case_code' => $first['case_code'] ?? 'N/A',
                        'case'      => $first['case'] ?? null,
                        'types'     => $group->pluck('type')->unique()->values()->toArray(),
                        'events'    => app(\App\Services\ReminderService::class)->sortReminders($group)->values(),
                        'date'      => $first['date'] ?? null,
                        'conflicts' => $conflicts, // schedule conflicts with other cases
                    ];
                })
                ->values();
        @endphp

        @if($activeTab === 'warnings')
            <div class="reminder-attention-heading"><h2>Needs Attention</h2><p>Review payment follow-ups for approaching or past interment schedules.</p></div>
        @endif
        @if($activeTab === 'schedules')
            @include('staff.partials.schedule-calendar')
        @elseif($displayCards->isEmpty())
            <div class="rp-empty">
                <i class="bi bi-calendar-check text-slate-300 text-3xl mb-3 block"></i>
                <p class="text-sm font-semibold text-slate-500 mb-1">
                    @if(($activeTab ?? 'all') === 'today')
                        No wake, ceremony, or interment events scheduled today.
                    @elseif(($activeTab ?? 'all') === 'current_wake')
                        No cases are currently in wake.
                    @elseif(($activeTab ?? 'all') === 'upcoming')
                        No upcoming wake, ceremony, or interment events scheduled.
                    @elseif(($activeTab ?? 'all') === 'warnings')
                        No case updates require follow-up.
                    @elseif(($activeTab ?? 'all') === 'unpaid')
                        No cases with remaining balances.
                    @else
                        No reminders or schedules found.
                    @endif
                </p>
                <p class="text-[11px] text-slate-400">Try a wider date range or clear the filters.</p>
            </div>
        @else
            <div class="rp-list">
                @foreach($displayCards as $card)
                    @php
                        $case          = $card['case'];
                        $paymentStatus = $case->payment_status ?? null;
                        $isUnpaid      = in_array($paymentStatus, ['UNPAID', 'PARTIAL'], true);
                        $types         = $card['types'];
                        $scheduleEvents = collect($card['events'] ?? []);
                        $isOperationalScheduleTab = in_array($activeTab, ['current_wake', 'today', 'upcoming'], true);
                        $typePriority = ['interment_completed', 'interment_date_completed', 'interment_approaching', 'interment_date_today_balance', 'same_day_interment_schedule', 'same_day_service_schedule', 'current_wake', 'wake_start_today', 'wake_end_today', 'service_today', 'interment_today', 'upcoming_wake_start', 'upcoming_wake_end', 'upcoming_interment', 'balance', 'upcoming_service'];
                        $dominantType = collect($typePriority)->first(fn($type) => in_array($type, $types, true)) ?? ($types[0] ?? 'alert');

                        // Build unique chip list — avoid showing "Unpaid Balance" chip + pay status chip redundantly
                        if ($activeTab === 'warnings') {
                            $chips = collect($types)
                                ->reject(fn($type) => in_array($type, ['interment_completed', 'interment_date_completed'], true))
                                ->map(fn($type) => $typeChip[$type] ?? null)
                                ->filter()
                                ->unique('label')
                                ->values();
                        } else {
                            $chips = collect();
                        }

                        // Only show pay-status chip if it adds info not already covered by type chips
                        $typeChipLabels = $chips->pluck('label')->map(fn($l) => strtolower($l))->toArray();
                        $showPayChip = $paymentStatus
                            && !in_array(strtolower($paymentStatus), ['paid'])
                            && !in_array('unpaid balance', $typeChipLabels)
                            && isset($payChip[$paymentStatus]);

                        $formatTime = function ($time) {
                            if (empty($time)) return null;
                            try { return \Carbon\Carbon::parse($time)->format('g:i A'); } catch (\Throwable $e) { return $time; }
                        };
                        $intermentSchedule = ($case && !empty($case->interment_at))
                            ? $case->interment_at->format('M d, Y') . (($time = $formatTime($case->interment_time ?? null)) ? ' at ' . $time : '')
                            : null;
                        $serviceSchedule = ($case && !empty($case->funeral_service_at))
                            ? $case->funeral_service_at->format('M d, Y') . (($time = $formatTime($case->funeral_service_time ?? null)) ? ' at ' . $time : '')
                            : null;
                        $wakeStartSchedule = ($case && !empty($case->wake_start_date))
                            ? $case->wake_start_date->format('M d, Y') . (($time = $formatTime($case->wake_start_time ?? null)) ? ' at ' . $time : '')
                            : null;
                        $wakeEndSchedule = ($case && !empty($case->wake_end_date))
                            ? $case->wake_end_date->format('M d, Y') . (($time = $formatTime($case->wake_end_time ?? null)) ? ' at ' . $time : '')
                            : null;
                        $showBalance = $isUnpaid
                            && $case
                            && in_array($activeTab, ['all', 'warnings', 'unpaid'], true)
                            && (bool) array_intersect($types, ['balance', 'interment_approaching', 'interment_completed', 'interment_date_completed', 'interment_date_today_balance']);
                        $showInterment = $intermentSchedule && match ($activeTab) {
                            'today' => in_array('interment_today', $types, true),
                            'upcoming' => (bool) array_intersect($types, ['upcoming_service', 'upcoming_interment']),
                            'warnings' => (bool) array_intersect($types, ['interment_approaching', 'interment_completed', 'interment_date_completed', 'interment_date_today_balance']),
                            'unpaid' => false,
                            default => (bool) array_intersect($types, ['interment_today', 'upcoming_interment', 'interment_approaching', 'interment_completed', 'interment_date_completed', 'interment_date_today_balance']),
                        };
                        $showService = $serviceSchedule && match ($activeTab) {
                            'today' => in_array('service_today', $types, true),
                            'upcoming' => in_array('upcoming_service', $types, true),
                            default => (bool) array_intersect($types, ['service_today', 'upcoming_service']),
                        };
                        $reason = [
                            'current_wake' => 'The wake period is currently active for this case.',
                            'interment_completed' => 'The interment has passed and this case still has a remaining balance.',
                            'interment_date_completed' => 'The scheduled interment date has passed and this case still has a remaining balance.',
                            'interment_approaching' => 'The interment is within the next 24 hours and this case has a remaining balance.',
                            'interment_date_today_balance' => 'Interment is scheduled today, but no interment time was recorded and a remaining balance remains.',
                            'same_day_service_schedule', 'same_day_interment_schedule' => 'Another service or interment is scheduled on the same day.',
                            'service_today' => 'Funeral Ceremony scheduled for today.',
                            'interment_today' => 'Interment scheduled for today.',
                            'upcoming_interment' => 'Upcoming interment schedule.',
                            'upcoming_service' => 'Upcoming Funeral Ceremony schedule.',
                            'balance' => 'This case has a remaining balance.',
                        ][$dominantType] ?? 'Case reminder for staff review.';

                        if (in_array($dominantType, ['interment_completed', 'interment_date_completed'], true)) {
                            $reason = $paymentStatus === 'UNPAID'
                                ? 'Scheduled interment time has passed, but no payment has been recorded. Please review the case.'
                                : 'Scheduled interment time has passed, and a remaining balance is still recorded. Please review the payment record.';
                        } elseif ($dominantType === 'interment_approaching') {
                            $scheduleDay = $case?->interment_at?->isToday() ? 'today' : 'tomorrow';
                            $reason = $paymentStatus === 'UNPAID'
                                ? "Interment is scheduled {$scheduleDay}, and no payment has been recorded. Please review the case."
                                : "Interment is scheduled {$scheduleDay}, and a remaining balance is recorded. Please review the payment record.";
                        } elseif (in_array($dominantType, ['same_day_service_schedule', 'same_day_interment_schedule'], true)) {
                            $conflictTypes = collect($card['conflicts'])->pluck('type')->unique();
                            $scheduleName = $conflictTypes->count() === 1
                                ? ($conflictTypes->first() === 'interment' ? 'interment' : 'service')
                                : 'service or interment';
                            $reason = "Another {$scheduleName} is scheduled on the same day. Please review the schedules for coordination.";
                        }

                        $moveCompletedDescription = $activeTab === 'warnings' && in_array($dominantType, ['interment_completed', 'interment_date_completed'], true);

                        $isPaymentAction = $activeTab === 'warnings';
                        $actionUrl = $isPaymentAction
                            ? (request()->routeIs('admin.*')
                                ? route('admin.payment-monitoring', [
                                    'q' => $card['case_code'],
                                    'payment_status' => 'WITH_BALANCE',
                                    'tab' => 'summary',
                                ])
                                : route('payments.history', [
                                    'q' => $card['case_code'],
                                    'payment_status' => 'WITH_BALANCE',
                                    'tab' => 'summary',
                                ]))
                            : route('funeral-cases.show', [
                                'funeral_case' => $card['case_id'],
                                'return_to' => request()->fullUrl(),
                            ]);

                        // Meta label for the right column
                        $metaLabel = collect($types)->map(fn($t) => ucfirst(str_replace('_', ' ', $t)))->join(' · ');
                    @endphp

                    <div
                        id="case-reminder-{{ $card['case_id'] }}"
                        class="rp-item {{ (int) ($focusedCaseId ?? 0) === (int) $card['case_id'] ? 'rp-item--focused' : '' }}"
                    >
                        <div class="rp-item__body">

                            {{-- Left info --}}
                            <div class="rp-item__info {{ $isOperationalScheduleTab ? 'rp-item__info--schedule' : '' }}">
                                <div class="rp-case-identity">
                                    <div class="rp-identity-field">
                                        <span class="rp-identity-label">Case ID</span>
                                        <span class="rp-identity-value">{{ $card['case_code'] }}</span>
                                    </div>
                                    <div class="rp-identity-field rp-identity-field--deceased">
                                        <span class="rp-identity-label">Deceased</span>
                                        <span class="rp-identity-value" title="{{ $case->deceased?->full_name ?? 'Not recorded' }}">{{ $case->deceased?->full_name ?? 'Not recorded' }}</span>
                                    </div>
                                    <div class="rp-identity-field">
                                        <span class="rp-identity-label">Client</span>
                                        <span class="rp-identity-value" title="{{ $case->client?->full_name ?? 'Not recorded' }}">{{ $case->client?->full_name ?? 'Not recorded' }}</span>
                                    </div>
                                </div>
                                @if($isOperationalScheduleTab)
                                    <div class="rp-schedule-strip" aria-label="{{ $activeTab === 'current_wake' ? 'Wake information' : 'Case schedule events' }}">
                                        @if($activeTab === 'current_wake')
                                            @php $wakeItem = $scheduleEvents->first(); @endphp
                                            <div class="rp-schedule-entry">
                                                <strong>Day {{ $wakeItem['current_day'] ?? 1 }} of {{ $wakeItem['total_days'] ?? 1 }}</strong>
                                                <span>Wake Progress</span>
                                                <progress class="schedule-wake-progress" value="{{ $wakeItem['current_day'] ?? 1 }}" max="{{ max(1, $wakeItem['total_days'] ?? 1) }}" aria-label="Wake day progress">Day {{ $wakeItem['current_day'] ?? 1 }}</progress>
                                            </div>
                                            <div class="rp-schedule-entry">
                                                <strong>{{ $wakeStartSchedule ?? 'Not set' }}</strong>
                                                <span>Wake Start</span>
                                            </div>
                                            <div class="rp-schedule-entry">
                                                <strong>{{ $wakeEndSchedule ?? 'Not set' }}</strong>
                                                <span>Expected Wake End</span>
                                            </div>
                                        @else
                                            @foreach($scheduleEvents as $scheduleEvent)
                                                @php
                                                    $scheduleType = (string) ($scheduleEvent['type'] ?? '');
                                                    $scheduleClass = str_contains($scheduleType, 'wake_start')
                                                        ? 'is-wake-start'
                                                        : (str_contains($scheduleType, 'wake_end')
                                                            ? 'is-wake-end'
                                                            : (str_contains($scheduleType, 'interment') ? 'is-interment' : 'is-ceremony'));
                                                @endphp
                                                <div class="rp-schedule-entry {{ $scheduleClass }}">
                                                    <strong>{{ $scheduleEvent['date']?->format($activeTab === 'today' ? 'g:i A' : 'M d, Y · g:i A') ?? 'Date and time not set' }}</strong>
                                                    <span>{{ $scheduleEvent['label'] ?? 'Scheduled Event' }}</span>
                                                    <small><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $scheduleEvent['location'] ?: 'Location not set' }}</small>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                @elseif($moveCompletedDescription)
                                    {{-- Description is shown beside the action for completed interments. --}}
                                @elseif(in_array($activeTab, ['all', 'unpaid'], true) && $showBalance && $showPayChip)
                                    <div class="rp-payment-status">
                                        <span class="rp-chip {{ $payChip[$paymentStatus]['class'] }}">{{ $payChip[$paymentStatus]['label'] }}</span>
                                    </div>
                                @else
                                    <p class="rp-item__reason">{{ $reason }}</p>
                                @endif

                                @unless($isOperationalScheduleTab)
                                <div class="rp-details">
                                    @if($showInterment)
                                        <span class="rp-detail"><i class="bi bi-calendar-event"></i><strong>Interment</strong> {{ $intermentSchedule }}</span>
                                    @endif
                                    @if($showService)
                                        <span class="rp-detail"><i class="bi bi-calendar-event"></i><strong>Service</strong> {{ $serviceSchedule }}</span>
                                    @endif
                                    @if($showBalance)
                                        <span class="rp-detail"><i class="bi bi-wallet2"></i><strong>Remaining Balance</strong> PHP {{ number_format((float)($case->balance_amount ?? 0), 2) }}</span>
                                    @endif
                                </div>
                                @endunless


                            </div>

                            {{-- Right actions --}}
                            <div class="rp-item__actions">
                                @if($moveCompletedDescription)
                                    <p class="rp-item__action-copy">{{ $reason }}</p>
                                @endif
                                @if($chips->isNotEmpty())
                                    <div class="rp-tags">
                                        @foreach($chips as $chip)
                                            <span class="rp-chip {{ $chip['class'] }}">{{ $chip['label'] }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                @if($card['case_id'])
                                    <a
                                        href="{{ $actionUrl }}"
                                        class="rp-view-btn"
                                    >
                                        <i class="bi {{ $isPaymentAction ? 'bi-wallet2' : 'bi-arrow-up-right-circle' }}" aria-hidden="true"></i>
                                        {{ $isPaymentAction ? 'View in Payment Monitoring' : 'View Case' }}
                                    </a>
                                @endif
                            </div>

                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>
@include('staff.partials.schedule-styles')

<script>
    window.handleReminderDueWindowChange = function (select) {
        const form = select.form;
        const customField = document.getElementById('rpCustomDateField');
        const dateInput = document.getElementById('rp_date');

        if (select.value === 'custom') {
            customField?.classList.remove('is-hidden');
            dateInput?.focus();
            return;
        }

        if (dateInput) {
            dateInput.value = '';
        }

        form?.submit();
    };
</script>
@include('staff.partials.reminder-navigation')
@endsection

@include('staff.partials.schedule-visual-styles')
