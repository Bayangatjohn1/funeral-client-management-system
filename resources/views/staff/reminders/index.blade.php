@extends('layouts.panel')

@section('page_title', 'Reminders & Schedule')
@section('page_desc', 'Track upcoming schedules, payment reminders, and important branch alerts.')

@section('content')
@php
    $remindersRoute = request()->routeIs('admin.*') ? 'admin.reminders.index' : 'staff.reminders.index';
    $dashboardUrl   = request()->routeIs('admin.*') ? url('/admin') : url('/staff');
@endphp
<style>
    /* ── Page wrapper ─────────────────────────────────────────── */
    .rp-wrap {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 24px 36px;
        display: flex;
        flex-direction: column;
        gap: 16px;
        box-sizing: border-box;
    }

    /* ── Back button ──────────────────────────────────────────── */
    .rp-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        width: fit-content;
        padding: 7px 14px;
        border: 1px solid #d9e3ee;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #3a3f3a;
        background: #FAFAF7;
        text-decoration: none;
        transition: background .15s, border-color .15s, color .15s;
    }
    .rp-back:hover { background: #F3F0E8; border-color: #3E4A3D; color: #3E4A3D; }
    .rp-back:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(62,74,61,0.18); }

    /* ── Filter card ──────────────────────────────────────────── */
    .rp-filter-card {
        background: #ffffff;
        border: 1px solid #e4ebf3;
        border-radius: 16px;
        padding: 20px 22px;
    }
    .rp-filter-card__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
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

    .rp-filter-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 14px;
        align-items: end;
    }
    .rp-filter-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 14px;
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
        gap: 5px;
    }
    .rp-field label {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.09em;
        color: #8a9590;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .rp-field label i { font-size: 11px; color: #aab4ae; }
    .rp-field input,
    .rp-field select {
        width: 100%;
        height: 40px;
        min-height: 40px;
        margin-top: 0 !important;
        border-radius: 10px;
        box-sizing: border-box;
    }

    /* ── Tabs card ────────────────────────────────────────────── */
    .rp-tabs-card {
        background: #ffffff;
        border: 1px solid #e4ebf3;
        border-radius: 16px;
        padding: 20px 22px;
    }
    .rp-tabs-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 18px;
        padding-bottom: 18px;
        border-bottom: 1px solid #eef2f7;
    }

    /* ── Tab + tooltip ────────────────────────────────────────── */
    .rp-tab {
        position: relative;
        padding: 8px 14px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        border-radius: 10px;
        border: 1px solid #dbe4ee;
        transition: color .15s, border-color .15s, background-color .15s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        white-space: nowrap;
    }
    .rp-tab.is-active {
        background: #3E4A3D;
        border-color: #3E4A3D;
        color: #ffffff;
        cursor: default;
    }
    .rp-tab.is-idle {
        background: #ffffff;
        color: #5F685F;
    }
    .rp-tab.is-idle:hover {
        border-color: #3E4A3D;
        color: #3E4A3D;
        background: #F3F0E8;
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
        min-width: 18px;
        padding: 1px 5px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 800;
        line-height: 1;
    }
    .is-active .rp-tab__count { background: rgba(255,255,255,0.22); color: #fff; }
    .is-idle  .rp-tab__count  { background: #eef2f7; color: #5e6d7e; }

    /* ── Empty state ──────────────────────────────────────────── */
    .rp-empty {
        padding: 44px 20px;
        background: #f8fbff;
        border: 1px dashed #d9e3ee;
        border-radius: 14px;
        text-align: center;
    }

    /* ── Item list ────────────────────────────────────────────── */
    .rp-list { display: grid; gap: 10px; }

    .rp-item {
        padding: 16px 18px;
        background: #ffffff;
        border: 1px solid #e4ebf3;
        border-left-width: 4px;
        border-radius: 12px;
        transition: border-color .16s, background .16s;
    }
    .rp-item:hover {
        border-color: #C9C5BB;
        border-left-color: inherit;
        background: #FAFAF9;
    }
    .rp-item__body {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }
    .rp-item__info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 4px; }
    .rp-item__actions { display: flex; flex-direction: column; align-items: flex-end; gap: 10px; flex-shrink: 0; }
    .rp-item__meta {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #a0aab0;
        text-align: right;
        line-height: 1.4;
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
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        line-height: 1.2;
    }

    .rp-view-btn {
        padding: 7px 14px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.09em;
        border-radius: 9px;
        background: #3E4A3D;
        color: #fff;
        text-decoration: none;
        transition: background .15s;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 5px;
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
        background: linear-gradient(180deg, #0f2038 0%, #112741 100%);
        border-color: #2f4668;
    }
    html[data-theme='dark'] .rp-filter-card__title { color: #7fa3c8; }
    html[data-theme='dark'] .rp-filter-reset { color: #6a8aa8; background: #162b47; border-color: #335074; }
    html[data-theme='dark'] .rp-filter-reset:hover { color: #a8c8e8; background: #1a3251; border-color: #44658f; }
    html[data-theme='dark'] .rp-back { background: #162b47; border-color: #335074; color: #d9e7fb; }
    html[data-theme='dark'] .rp-back:hover { background: #1a3251; border-color: #44658f; color: #fff; }
    html[data-theme='dark'] .rp-field label { color: #6a8baa; }
    html[data-theme='dark'] .rp-filter-note { color: #506880; }
    html[data-theme='dark'] .rp-tabs-row { border-bottom-color: #1e3a57; }
    html[data-theme='dark'] .rp-tab { border-color: #35557b; }
    html[data-theme='dark'] .rp-tab.is-active { background: #0d1a2f; border-color: #5578a8; color: #f8fbff; }
    html[data-theme='dark'] .rp-tab.is-idle { background: #16304e; color: #c7d7ef; }
    html[data-theme='dark'] .rp-tab.is-idle:hover { background: #1c3a5d; border-color: #4d6f98; color: #fff; }
    html[data-theme='dark'] .is-idle .rp-tab__count { background: #1e3a57; color: #8aaeca; }
    html[data-theme='dark'] .rp-empty { background: #12243c; border-color: #355074; }
    html[data-theme='dark'] .rp-item { background: #132844; border-color: #2f4a6b; }
    html[data-theme='dark'] .rp-item:hover { background: #17304e; border-color: #466890; }
    html[data-theme='dark'] .rp-view-btn { background: #1e4070; }
    html[data-theme='dark'] .rp-view-btn:hover { background: #245090; }
    html[data-theme='dark'] .rp-tab[data-tip]::after { background: #0b1a2e; color: #c8daf0; }
    html[data-theme='dark'] .rp-tab[data-tip]::before { border-top-color: #0b1a2e; }

    /* ── Responsive ───────────────────────────────────────────── */
    @media (max-width: 900px) {
        .rp-filter-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 640px) {
        .rp-wrap { padding: 0 14px 24px; }
        .rp-filter-grid { grid-template-columns: 1fr; }
        .rp-item__body { flex-direction: column; gap: 12px; }
        .rp-item__actions { align-items: flex-start; width: 100%; flex-direction: row; flex-wrap: wrap; }
    }
</style>

<div class="rp-wrap">

    {{-- ── Back button ──────────────────────────────────────────── --}}
    <a href="{{ $dashboardUrl }}" class="rp-back">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>

    {{-- ── Filter card ──────────────────────────────────────────── --}}
    <div class="rp-filter-card">
        <div class="rp-filter-card__head">
            <span class="rp-filter-card__title">
                <i class="bi bi-funnel-fill"></i> Filter Reminders
            </span>
        </div>

        <form method="GET" action="{{ route($remindersRoute) }}" class="rp-filter-grid" id="rpFilterForm">
            <input type="hidden" name="tab" value="{{ $activeTab ?? 'all' }}">

            {{-- Schedule Date --}}
            <div class="rp-field">
                <label for="rp_date"><i class="bi bi-calendar3"></i> Schedule Date</label>
                <input
                    id="rp_date"
                    type="date"
                    name="date"
                    value="{{ $filters['date'] ?? '' }}"
                    class="form-input"
                    onchange="this.form.submit()"
                >
            </div>

            {{-- Case Status (no Draft) --}}
            <div class="rp-field">
                <label for="rp_status"><i class="bi bi-tag"></i> Case Status</label>
                <select id="rp_status" name="case_status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="ACTIVE"    {{ ($filters['case_status'] ?? '') === 'ACTIVE'    ? 'selected' : '' }}>Active</option>
                    <option value="COMPLETED" {{ ($filters['case_status'] ?? '') === 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>

            {{-- Branch --}}
            <div class="rp-field">
                <label for="rp_branch"><i class="bi bi-building"></i> Branch</label>
                <select id="rp_branch" name="branch_id" class="form-select" onchange="this.form.submit()">
                    @forelse($branchChoices as $branch)
                        <option value="{{ $branch->id }}" {{ ($selectedBranchId ?? null) === $branch->id ? 'selected' : '' }}>
                            {{ $branch->branch_code }} — {{ $branch->branch_name }}
                        </option>
                    @empty
                        <option value="{{ $selectedBranchId ?? '' }}">Main Branch (operational)</option>
                    @endforelse
                </select>
            </div>
        </form>

        <div class="rp-filter-footer">
            <span class="rp-filter-note">
                <i class="bi bi-info-circle"></i>
                This page displays reminders and schedules for your assigned branch only.
            </span>
            @if(array_filter(request()->only(['date','case_status','branch_id'])))
                <a href="{{ route($remindersRoute, ['tab' => $activeTab ?? 'all']) }}" class="rp-filter-reset">
                    <i class="bi bi-x-circle"></i> Clear Filters
                </a>
            @endif
        </div>
    </div>

    {{-- ── Tabs + list card ─────────────────────────────────────── --}}
    @php
        $activeTab = $activeTab ?? 'all';

        $tabMap = [
            'all'      => $reminders,
            'today'    => $reminders->whereIn('type', ['service_today', 'interment_today']),
            'upcoming' => $reminders->whereIn('type', ['upcoming_service', 'upcoming_interment']),
            'warnings' => $reminders->where('type', 'schedule_warning'),
            'unpaid'   => $reminders->where('type', 'balance'),
        ];

        // Tab definitions — new order & labels
        $tabDefs = [
            'all'      => [
                'label'   => 'All Alerts',
                'icon'    => 'bi-clipboard-data',
                'tooltip' => 'Shows all reminders and schedules in one view.',
            ],
            'today'    => [
                'label'   => 'Today',
                'icon'    => 'bi-calendar-day',
                'tooltip' => 'Shows reminders and schedules due today.',
            ],
            'upcoming' => [
                'label'   => 'Upcoming',
                'icon'    => 'bi-hourglass-split',
                'tooltip' => 'Shows future service schedules, interment dates, and follow-ups.',
            ],
            'warnings' => [
                'label'   => 'Needs Attention',
                'icon'    => 'bi-exclamation-triangle',
                'tooltip' => 'Shows urgent reminders that require checking, such as overdue follow-ups or upcoming interment with unpaid balance.',
            ],
            'unpaid'   => [
                'label'   => 'Unpaid/Partial',
                'icon'    => 'bi-exclamation-octagon',
                'tooltip' => 'Shows cases with unpaid or partial payment status.',
            ],
        ];

        // Type → chip style map
        $typeChip = [
            'balance'            => ['label' => 'Unpaid Balance',    'class' => 'chip-red'],
            'service_today'      => ['label' => 'Service Today',     'class' => 'chip-blue'],
            'interment_today'    => ['label' => 'Interment Today',   'class' => 'chip-blue'],
            'upcoming_service'   => ['label' => 'Upcoming Service',  'class' => 'chip-indigo'],
            'upcoming_interment' => ['label' => 'Upcoming Interment','class' => 'chip-indigo'],
            'schedule_warning'   => ['label' => 'Needs Attention',   'class' => 'chip-orange'],
        ];

        // Border colour per dominant type
        $borderColors = [
            'balance'            => 'border-l-red-500',
            'service_today'      => 'border-l-blue-500',
            'interment_today'    => 'border-l-blue-500',
            'upcoming_service'   => 'border-l-indigo-400',
            'upcoming_interment' => 'border-l-indigo-400',
            'schedule_warning'   => 'border-l-orange-400',
        ];

        // Payment status badge
        $payChip = [
            'UNPAID'  => ['label' => 'Unpaid',  'class' => 'chip-red'],
            'PARTIAL' => ['label' => 'Partial', 'class' => 'chip-orange'],
            'PAID'    => ['label' => 'Paid',    'class' => 'chip-green'],
        ];
    @endphp

    <div class="rp-tabs-card">

        {{-- Tab row --}}
        <div class="rp-tabs-row">
            @foreach($tabDefs as $key => $def)
                <a
                    href="{{ route($remindersRoute, array_merge(request()->except('page'), ['tab' => $key])) }}"
                    class="rp-tab {{ $activeTab === $key ? 'is-active' : 'is-idle' }}"
                    data-tip="{{ $def['tooltip'] }}"
                >
                    <i class="bi {{ $def['icon'] }}"></i>
                    {{ $def['label'] }}
                    @if(isset($counts[$key]))
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

                    // Collect all conflict entries from schedule_warning items in this group.
                    // Each warning item may describe a different conflict date/type.
                    $conflicts = $group
                        ->where('type', 'schedule_warning')
                        ->pluck('conflict')
                        ->filter()
                        ->values()
                        ->toArray();

                    return [
                        'case_id'   => $first['case_id'] ?? null,
                        'case_code' => $first['case_code'] ?? 'N/A',
                        'case'      => $first['case'] ?? null,
                        'types'     => $group->pluck('type')->unique()->values()->toArray(),
                        'date'      => $first['date'] ?? null,
                        'conflicts' => $conflicts, // schedule conflicts with other cases
                    ];
                })
                ->values();
        @endphp

        @if($displayCards->isEmpty())
            <div class="rp-empty">
                <i class="bi bi-calendar-check text-slate-300 text-3xl mb-3 block"></i>
                <p class="text-sm font-semibold text-slate-500 mb-1">No matching reminders found.</p>
                <p class="text-[11px] text-slate-400">Try changing or clearing the filters.</p>
            </div>
        @else
            <div class="rp-list">
                @foreach($displayCards as $card)
                    @php
                        $case          = $card['case'];
                        $paymentStatus = $case->payment_status ?? null;
                        $isUnpaid      = in_array($paymentStatus, ['UNPAID', 'PARTIAL'], true);
                        $types         = $card['types'];
                        $dominantType  = $types[0] ?? 'alert';

                        // Build unique chip list — avoid showing "Unpaid Balance" chip + pay status chip redundantly
                        $chips = collect($types)->map(fn($t) => $typeChip[$t] ?? ['label' => ucfirst(str_replace('_', ' ', $t)), 'class' => 'chip-slate'])->unique('label')->values();

                        // Only show pay-status chip if it adds info not already covered by type chips
                        $typeChipLabels = $chips->pluck('label')->map(fn($l) => strtolower($l))->toArray();
                        $showPayChip = $paymentStatus
                            && !in_array(strtolower($paymentStatus), ['paid'])
                            && !in_array('unpaid balance', $typeChipLabels)
                            && isset($payChip[$paymentStatus]);

                        // Info lines
                        $balanceLine   = ($isUnpaid && $case) ? 'Outstanding Balance: PHP ' . number_format((float)($case->balance_amount ?? 0), 2) : null;
                        $intermentLine = ($case && !empty($case->interment_at))       ? 'Interment Date: '   . $case->interment_at->format('M d, Y')       : null;
                        $serviceLine   = ($case && !empty($case->funeral_service_at)) ? 'Service Date: '     . $case->funeral_service_at->format('M d, Y')  : null;
                        $dateLine      = (!$intermentLine && !$serviceLine && !empty($card['date'])) ? $card['date']->format('M d, Y') : null;

                        $borderClass = $borderColors[$dominantType] ?? 'border-l-slate-200';

                        // Meta label for the right column
                        $metaLabel = collect($types)->map(fn($t) => ucfirst(str_replace('_', ' ', $t)))->join(' · ');
                    @endphp

                    <div class="rp-item {{ $borderClass }}">
                        <div class="rp-item__body">

                            {{-- Left info --}}
                            <div class="rp-item__info">
                                <p class="text-sm font-bold text-slate-900 leading-snug">
                                    {{ $case->client->full_name ?? 'Client N/A' }}
                                </p>
                                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">
                                    Case No.: {{ $card['case_code'] }}
                                </p>

                                {{-- Detail lines --}}
                                @if($balanceLine)
                                    <p class="text-[11px] font-semibold text-red-600 mt-1">
                                        <i class="bi bi-cash-coin mr-1"></i>{{ $balanceLine }}
                                    </p>
                                @endif
                                @if($intermentLine)
                                    <p class="text-[11px] text-slate-600">
                                        <i class="bi bi-geo-alt mr-1"></i>{{ $intermentLine }}
                                    </p>
                                @endif
                                @if($serviceLine)
                                    <p class="text-[11px] text-slate-600">
                                        <i class="bi bi-calendar-event mr-1"></i>{{ $serviceLine }}
                                    </p>
                                @endif
                                @if($dateLine)
                                    <p class="text-[11px] font-semibold text-slate-500 mt-1">
                                        <i class="bi bi-clock mr-1"></i>{{ $dateLine }}
                                    </p>
                                @endif
                                @if(in_array('schedule_warning', $types))
                                    <p class="text-[10px] text-orange-500 mt-1">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        Conflicting schedule detected — another case is on the same date.
                                    </p>

                                    {{-- Conflicting cases panel --}}
                                    @if(!empty($card['conflicts']))
                                        @foreach($card['conflicts'] as $conflict)
                                            @if(!empty($conflict['cases']))
                                                <div class="rp-conflict-panel">
                                                    <div class="rp-conflict-panel__title">
                                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                                        {{ $conflict['type'] === 'interment' ? 'Interment' : 'Service' }} conflict on
                                                        {{ \Carbon\Carbon::parse($conflict['date'])->format('M d, Y') }}
                                                        — same date as:
                                                    </div>
                                                    @foreach($conflict['cases'] as $cc)
                                                        <div class="rp-conflict-row">
                                                            <span class="rp-conflict-badge">{{ $cc['case_code'] }}</span>
                                                            <span class="rp-conflict-name-time">
                                                                <span class="rp-conflict-name">{{ $cc['client_name'] }}</span>
                                                                @if(!empty($cc['time']))
                                                                    <span class="rp-conflict-time">
                                                                        <i class="bi bi-clock"></i> {{ $cc['time'] }}
                                                                    </span>
                                                                @endif
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        @endforeach
                                    @endif
                                @endif

                                {{-- Tags --}}
                                <div class="rp-tags">
                                    @foreach($chips as $chip)
                                        <span class="rp-chip {{ $chip['class'] }}">{{ $chip['label'] }}</span>
                                    @endforeach
                                    @if($showPayChip)
                                        <span class="rp-chip {{ $payChip[$paymentStatus]['class'] }}">
                                            {{ $payChip[$paymentStatus]['label'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Right actions --}}
                            <div class="rp-item__actions">
                                @if($card['case_id'])
                                    <a
                                        href="{{ route('funeral-cases.show', ['funeral_case' => $card['case_id'], 'return_to' => request()->fullUrl()]) }}"
                                        class="rp-view-btn"
                                    >
                                        <i class="bi bi-arrow-up-right-circle"></i> View Details
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
@endsection
