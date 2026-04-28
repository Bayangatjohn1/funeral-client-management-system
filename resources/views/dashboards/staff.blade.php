@extends('layouts.panel')

@section('page_title', 'Staff Dashboard')
@section('page_desc', 'Daily operations and follow-up center')

@section('content')
@php
    $staffFirstName = \Illuminate\Support\Str::of(auth()->user()->name ?? 'Staff')->trim()->explode(' ')->first();
    $branchLabel = trim(($dashboardBranch->branch_code ?? 'BR001') . ' - ' . ($dashboardBranch->branch_name ?? 'Main Branch'));
    $todayLabel = now()->format('l, F j, Y');

    $attentionItems = collect($attentionReminders ?? [])->take(3)->values();
    $todayItems = collect($todaySchedule ?? [])->take(3)->values();
    $upcomingItems = collect($upcomingSchedule ?? [])->take(3)->values();

    $outstandingCases = collect($unpaidCases ?? [])
        ->filter(fn ($case) => (float) ($case->balance_amount ?? 0) > 0)
        ->take(3)
        ->values();

    $monthLabel = now()->format('M Y');
@endphp

<style>
    .staff-dashboard-v2 {
        color: #0f172a;
        display: flex;
        flex-direction: column;
        gap: 1.15rem;
        width: 100%;
        max-width: 100%;
        overflow-x: clip;
        box-sizing: border-box;
        padding: 0 clamp(.4rem, .9vw, 1rem) 1rem;
        margin: 0 auto;
    }

    .staff-dashboard-v2 > section {
        width: 100%;
        min-width: 0;
    }

    .staff-card {
        background: #ffffff;
        border: 1px solid #d9e2ec;
        border-radius: 1.1rem;
        overflow: hidden;
        min-width: 0;
    }

    .staff-card--equal {
        display: flex;
        flex-direction: column;
    }

    .staff-card--equal .overflow-x-auto {
        flex: 1 1 auto;
    }

    .staff-header-card {
        background: linear-gradient(180deg, #fcfcfd 0%, #f6f7f9 100%);
        border: 1px solid #d6dbe3;
        border-radius: 1.2rem;
        border-top-left-radius: 0;
        border-top-right-radius: 0;
        padding: 1.1rem 1.2rem;
        display: grid;
        gap: 1rem;
    }

    .staff-header-main {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .staff-title {
        font-family: var(--font-heading);
        font-size: 1.8rem;
        line-height: 1.08;
        letter-spacing: -0.03em;
        color: #0b1422;
        margin: 0;
    }

    .staff-subtitle {
        margin-top: .35rem;
        color: #64748b;
        font-size: .98rem;
    }

    .staff-branch-chip {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: .23rem .72rem;
        font-size: .82rem;
        font-weight: 700;
        letter-spacing: .02em;
        color: var(--accent);
        background: rgba(37, 99, 235, 0.08);
        border: 1px solid rgba(37, 99, 235, 0.20);
    }

    .staff-tools {
        display: flex;
        align-items: center;
        gap: .6rem;
    }

    .staff-pill {
        display: inline-flex;
        align-items: center;
        gap: .42rem;
        border: 1px solid #d7dde6;
        background: #f8fafc;
        border-radius: .75rem;
        padding: .52rem .74rem;
        font-size: .9rem;
        color: #64748b;
        white-space: nowrap;
    }

    .staff-pill i {
        color: #2563eb;
        font-size: .86rem;
    }

    .staff-action-grid {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: .7rem;
    }

    .staff-action-card {
        display: flex;
        align-items: center;
        gap: .78rem;
        border: 1px solid #d9e2ec;
        border-radius: 1rem;
        padding: .92rem .98rem;
        transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease, background-color .16s ease, color .16s ease;
    }

    .staff-action-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 14px 26px rgba(15, 23, 42, .08);
    }

    .staff-action-card .icon {
        width: 2.35rem;
        height: 2.35rem;
        border-radius: .72rem;
        display: grid;
        place-items: center;
        font-size: 1rem;
    }

    .staff-action-card .title {
        font-weight: 700;
        font-size: 1.04rem;
        line-height: 1.18;
        margin: 0;
        color: inherit;
        text-rendering: geometricPrecision;
    }

    .staff-action-card .desc {
        margin: .12rem 0 0;
        font-size: .9rem;
        color: inherit;
    }

    .staff-action-card.is-dark {
        background: linear-gradient(180deg, #223651 0%, #1a2d46 100%);
        border-color: #2c4769;
        color: #f8fbff;
    }

    .staff-action-card.is-dark .icon {
        background: rgba(226, 232, 240, .18);
        color: #f8fbff;
    }

    .staff-action-card.is-dark .desc {
        color: #d8e4f2;
    }

    .staff-action-card.is-dark .title {
        color: #f8fbff;
    }

    .staff-action-card.is-green {
        background: linear-gradient(180deg, #1a8076 0%, #176f66 100%);
        border-color: #21766e;
        color: #f2fbfa;
    }

    .staff-action-card.is-green .icon {
        background: rgba(236, 253, 250, .18);
        color: #ecfeff;
    }

    .staff-action-card.is-green .desc {
        color: #d4f4ee;
    }

    .staff-action-card.is-green .title {
        color: #f0fffb;
    }

    .staff-action-card.is-light {
        background: linear-gradient(180deg, #f7f9fc 0%, #f2f6fb 100%);
        border-color: #d3deea;
        color: #1a2b41;
    }

    .staff-action-card.is-light .icon {
        background: #e7eef7;
        color: #3d5573;
    }

    .staff-action-card.is-light .desc {
        color: #5f748d;
    }

    .staff-action-card.is-light .title {
        color: #1a2b41;
    }

    .staff-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 1rem;
        width: 100%;
        min-width: 0;
        align-items: start;
    }

    .staff-col {
        display: grid;
        gap: 1rem;
        align-content: start;
        min-width: 0;
    }

    .staff-col-wide {
        grid-column: 1 / -1;
    }

    .staff-subgrid-two {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 1rem;
        min-width: 0;
    }

    .staff-card-head {
        padding: 1rem 1.1rem;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .75rem;
        flex-wrap: wrap;
    }

    .staff-card-head > div {
        min-width: 0;
        flex: 1 1 auto;
    }

    .staff-card-head--fixed-actions {
        flex-wrap: nowrap;
        align-items: flex-start;
    }

    .staff-card-head--fixed-actions .staff-head-actions {
        margin-left: auto;
        flex: 0 0 auto;
    }

    .staff-card-head h3 {
        margin: 0;
        font-size: 1.05rem;
        line-height: 1.15;
        color: #0f172a;
    }

    .staff-card-head p {
        margin: .12rem 0 0;
        color: #64748b;
        font-size: .92rem;
    }

    .staff-link {
        font-weight: 600;
        color: #0b4f9f;
        font-size: .92rem;
        white-space: nowrap;
    }

    .staff-link:hover {
        color: #0a63cc;
    }

    .staff-tabs {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .22rem;
        border-radius: .72rem;
        border: 1px solid #d6e0eb;
        background: #f5f8fc;
    }

    .staff-tab-btn {
        border: 0;
        background: transparent;
        color: #51657e;
        border-radius: .58rem;
        padding: .32rem .62rem;
        font-size: .82rem;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
    }

    .staff-tab-btn:hover {
        color: #24384f;
        background: #ebf1f8;
    }

    .staff-tab-btn.is-active {
        background: #dfeaf7;
        color: #15395f;
        box-shadow: inset 0 0 0 1px #c5d8ef;
    }

    .staff-tab-panel[hidden] {
        display: none !important;
    }

    .staff-card-tabs-bar {
        padding: .6rem 1.1rem 0;
        border-bottom: 1px solid #e2e8f0;
    }

    .staff-card-head--compact {
        padding: .1rem 0 .6rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .staff-card--month {
        padding: .8rem 1rem;
    }

    .staff-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        min-width: 640px;
    }

    .staff-table thead th {
        text-align: left;
        font-size: .73rem;
        letter-spacing: .08em;
        color: #64748b;
        border-bottom: 1px solid #e2e8f0;
        padding: .7rem .95rem;
        background: #f8fafc;
        white-space: nowrap;
    }

    .staff-table td {
        border-bottom: 1px solid #e2e8f0;
        padding: .78rem .95rem;
        vertical-align: middle;
        font-size: .95rem;
        overflow-wrap: anywhere;
    }

    .staff-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .staff-table-code {
        color: #0b4f9f;
        font-weight: 700;
    }

    .staff-muted {
        color: #64748b;
        font-size: .88rem;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: .34rem;
        border-radius: 999px;
        padding: .24rem .66rem;
        border: 1px solid transparent;
        font-size: .79rem;
        font-weight: 600;
    }

    .status-pill.active {
        background: #dcfce7;
        border-color: #bbf7d0;
        color: #047857;
    }

    .status-pill.completed {
        background: #f1f5f9;
        border-color: #e2e8f0;
        color: #475569;
    }

    .status-pill.draft {
        background: #fef3c7;
        border-color: #fde68a;
        color: #92400e;
    }

    .status-pill.unpaid {
        background: #fee2e2;
        border-color: #fecaca;
        color: #b91c1c;
    }

    .staff-money {
        font-weight: 700;
        color: #0f766e;
    }

    .staff-list {
        padding: .35rem .8rem .8rem;
        display: grid;
        gap: .55rem;
    }

    .staff-list-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .8rem;
        padding: .7rem .68rem;
        border-radius: .8rem;
        border: 1px solid #e2e8f0;
        background: #fcfdff;
        min-width: 0;
        flex-wrap: wrap;
    }

    .staff-list-row > div {
        min-width: 0;
    }

    .staff-list-row > .text-right {
        margin-left: auto;
        flex-shrink: 0;
    }

    .staff-list-row strong {
        color: #0f172a;
        font-size: 1rem;
        display: block;
        line-height: 1.2;
        overflow-wrap: anywhere;
    }

    .staff-list-row small {
        color: #64748b;
        font-size: .89rem;
        overflow-wrap: anywhere;
    }

    .staff-balance-cta {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: .62rem;
        border: 1px solid #111827;
        background: #0b1118;
        color: #f8fafc;
        font-size: .83rem;
        font-weight: 700;
        padding: .4rem .72rem;
    }

    .staff-balance-cta:hover {
        background: #111827;
        color: #ffffff;
    }

    .staff-attention-pill {
        font-size: .73rem;
        font-weight: 700;
        border-radius: 999px;
        padding: .18rem .52rem;
        border: 1px solid transparent;
        white-space: nowrap;
    }

    .staff-attention-pill.danger {
        background: #fee2e2;
        color: #b91c1c;
        border-color: #fecaca;
    }

    .staff-attention-pill.warning {
        background: #fef3c7;
        color: #92400e;
        border-color: #fde68a;
    }

    .staff-attention-pill.info {
        background: #e2e8f0;
        color: #334155;
        border-color: #cbd5e1;
    }

    .staff-empty {
        text-align: center;
        color: #64748b;
        padding: 1.2rem .9rem;
    }

    .staff-empty i {
        display: block;
        font-size: 1.45rem;
        color: #94a3b8;
        margin-bottom: .25rem;
    }

    .staff-upcoming-date {
        width: 3.2rem;
        min-width: 3.2rem;
        border-radius: .72rem;
        border: 1px solid #d9e2ec;
        background: #f8fafc;
        text-align: center;
        padding: .35rem .15rem;
    }

    .staff-upcoming-date strong {
        display: block;
        font-size: 1.02rem;
        line-height: 1.1;
        color: #0f172a;
    }

    .staff-upcoming-date span {
        font-size: .73rem;
        text-transform: uppercase;
        color: #64748b;
    }

    .staff-monthly-list {
        margin: 0;
        padding: .15rem 0 0;
        list-style: none;
    }

    .staff-monthly-list li {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .7rem;
        border-top: 1px solid #e2e8f0;
        padding: .72rem 0;
        color: #475569;
    }

    .staff-monthly-list li:first-child {
        border-top: 0;
    }

    .staff-monthly-list strong {
        color: #0f172a;
    }

    .staff-monthly-list strong.good {
        color: #0f766e;
    }

    .staff-monthly-list strong.warn {
        color: #b91c1c;
    }

    .recent-cases-pagination {
        border-top: 1px solid #e2e8f0;
        padding: .5rem .8rem;
    }

    .recent-cases-pagination .table-paginator {
        justify-content: flex-end;
    }

    .recent-cases-pagination .table-paginator-meta {
        display: none;
    }

    @media (min-width: 700px) {
        .staff-action-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 860px) and (max-width: 1179px) {
        .staff-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .staff-grid > .staff-col:last-child,
        .staff-col-wide {
            grid-column: 1 / -1;
        }

        .staff-subgrid-two {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 1180px) {
        .staff-grid {
            grid-template-columns: minmax(0, 2.2fr) minmax(340px, 0.95fr);
            align-items: start;
            gap: 1.1rem;
        }

        .staff-col-wide {
            grid-column: auto;
        }

        .staff-subgrid-two {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 1360px) {
        .staff-action-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }

    @media (max-width: 699px) {
        .staff-dashboard-v2 {
            padding: 0 .3rem .8rem;
        }

        .staff-header-card {
            padding: .95rem;
        }

        .staff-card-head {
            padding: .9rem .9rem;
        }

        .staff-list {
            padding: .3rem .65rem .7rem;
        }

        .staff-card-head--fixed-actions {
            flex-wrap: wrap;
        }

        .staff-card-head--fixed-actions .staff-head-actions {
            margin-left: 0;
        }

        .staff-balance-cta {
            width: 100%;
            margin-top: .35rem;
        }
    }

    html[data-theme='dark'] .staff-dashboard-v2 {
        color: #e2e8f0;
    }

    html[data-theme='dark'] .staff-card,
    html[data-theme='dark'] .staff-header-card,
    html[data-theme='dark'] .staff-list-row,
    html[data-theme='dark'] .staff-upcoming-date {
        background: #182638;
        border-color: #3f536b;
    }

    html[data-theme='dark'] .staff-header-card {
        background: linear-gradient(180deg, #1c2d43 0%, #182638 100%);
    }

    html[data-theme='dark'] .staff-title,
    html[data-theme='dark'] .staff-card-head h3,
    html[data-theme='dark'] .staff-list-row strong,
    html[data-theme='dark'] .staff-monthly-list strong,
    html[data-theme='dark'] .staff-upcoming-date strong {
        color: #f8fbff;
    }

    html[data-theme='dark'] .staff-subtitle,
    html[data-theme='dark'] .staff-card-head p,
    html[data-theme='dark'] .staff-muted,
    html[data-theme='dark'] .staff-list-row small,
    html[data-theme='dark'] .staff-monthly-list li,
    html[data-theme='dark'] .staff-upcoming-date span,
    html[data-theme='dark'] .staff-empty {
        color: #9fb1c8;
    }

    html[data-theme='dark'] .staff-table thead th {
        background: #202d3f;
        border-bottom-color: #3f536b;
        color: #9fb1c8;
    }

    html[data-theme='dark'] .staff-table td,
    html[data-theme='dark'] .staff-card-head,
    html[data-theme='dark'] .recent-cases-pagination,
    html[data-theme='dark'] .staff-monthly-list li {
        border-color: #3f536b;
    }

    html[data-theme='dark'] .staff-action-card.is-dark {
        background: linear-gradient(180deg, #263c58 0%, #1d314b 100%);
        border-color: #446081;
        color: #f8fbff;
    }

    html[data-theme='dark'] .staff-action-card.is-dark .icon {
        background: rgba(248, 251, 255, .16);
        color: #f8fbff;
    }

    html[data-theme='dark'] .staff-action-card.is-dark .desc {
        color: #d6e4f4;
    }

    html[data-theme='dark'] .staff-action-card.is-dark .title {
        color: #f8fbff;
    }

    html[data-theme='dark'] .staff-action-card.is-green {
        background: linear-gradient(180deg, #1e625d 0%, #1b5753 100%);
        border-color: #2d7a75;
        color: #effcf9;
    }

    html[data-theme='dark'] .staff-action-card.is-green .icon {
        background: rgba(239, 252, 249, .18);
        color: #e8fffb;
    }

    html[data-theme='dark'] .staff-action-card.is-green .desc {
        color: #c9efe8;
    }

    html[data-theme='dark'] .staff-action-card.is-green .title {
        color: #ecfffb;
    }

    html[data-theme='dark'] .staff-action-card.is-light {
        background: linear-gradient(180deg, #223148 0%, #1b2a3f 100%);
        border-color: #3f536b;
        color: #f8fbff;
    }

    html[data-theme='dark'] .staff-action-card.is-light .desc {
        color: #b8c7da;
    }

    html[data-theme='dark'] .staff-action-card.is-light .icon {
        background: #2b3a50;
        color: #dbe6f3;
    }

    html[data-theme='dark'] .staff-action-card.is-light .title {
        color: #f1f6fd;
    }

    html[data-theme='dark'] .staff-pill {
        background: #223148;
        border-color: #3f536b;
        color: #d9e2ef;
    }

    html[data-theme='dark'] .staff-branch-chip {
        background: rgba(194, 122, 43, .16);
        border-color: rgba(194, 122, 43, .45);
        color: #f6c081;
    }

    html[data-theme='dark'] .staff-link {
        color: #8ec5ff;
    }

    html[data-theme='dark'] .staff-link:hover {
        color: #bfdbfe;
    }

    html[data-theme='dark'] .staff-tabs {
        background: #1f3046;
        border-color: #3f536b;
    }

    html[data-theme='dark'] .staff-tab-btn {
        color: #b7c7da;
    }

    html[data-theme='dark'] .staff-tab-btn:hover {
        color: #e5edf6;
        background: #2a3e58;
    }

    html[data-theme='dark'] .staff-tab-btn.is-active {
        background: #2b4666;
        color: #f8fbff;
        box-shadow: inset 0 0 0 1px #42698f;
    }

    html[data-theme='dark'] .staff-card-tabs-bar {
        border-bottom-color: #3f536b;
    }

    html[data-theme='dark'] .staff-card-head--compact {
        border-bottom-color: #3f536b;
    }

    html[data-theme='dark'] .status-pill.active {
        background: rgba(34, 197, 94, .15);
        border-color: rgba(34, 197, 94, .28);
        color: #86efac;
    }

    html[data-theme='dark'] .status-pill.completed {
        background: rgba(148, 163, 184, .15);
        border-color: rgba(148, 163, 184, .3);
        color: #cbd5e1;
    }

    html[data-theme='dark'] .status-pill.draft {
        background: rgba(245, 185, 66, .15);
        border-color: rgba(245, 185, 66, .3);
        color: #fcd34d;
    }

    html[data-theme='dark'] .status-pill.unpaid {
        background: rgba(239, 68, 68, .15);
        border-color: rgba(239, 68, 68, .3);
        color: #fda4af;
    }

    html[data-theme='dark'] .staff-money,
    html[data-theme='dark'] .staff-monthly-list strong.good {
        color: #6ee7b7;
    }

    html[data-theme='dark'] .staff-monthly-list strong.warn {
        color: #fda4af;
    }
</style>

<div class="staff-dashboard-v2">
    <section class="staff-header-card">
        <div class="staff-header-main">
            <div>
                <h1 class="staff-title">Good morning, {{ $staffFirstName }}</h1>
                <p class="staff-subtitle">Your daily workspace - <span class="staff-branch-chip">{{ $branchLabel }}</span></p>
            </div>
            <div class="staff-tools">
                <div class="staff-pill"><i class="bi bi-calendar3"></i> {{ $todayLabel }}</div>
            </div>
        </div>

        <div class="staff-action-grid">
            <a href="{{ route('intake.main.create') }}" class="staff-action-card is-dark">
                <div class="icon"><i class="bi bi-plus-lg"></i></div>
                <div>
                    <p class="title">Record New Case</p>
                    <p class="desc">Create a funeral record</p>
                </div>
            </a>

            <a href="{{ route('payments.index') }}" class="staff-action-card is-green">
                <div class="icon"><i class="bi bi-credit-card-2-front"></i></div>
                <div>
                    <p class="title">Record Payment</p>
                    <p class="desc">Post payment to a case</p>
                </div>
            </a>

            <a href="{{ route('funeral-cases.index') }}" class="staff-action-card is-light">
                <div class="icon"><i class="bi bi-clipboard-data"></i></div>
                <div>
                    <p class="title">Case Records</p>
                    <p class="desc">View all branch cases</p>
                </div>
            </a>

            <a href="{{ route('payments.history') }}" class="staff-action-card is-light">
                <div class="icon"><i class="bi bi-clock-history"></i></div>
                <div>
                    <p class="title">Payment History</p>
                    <p class="desc">Browse all payments</p>
                </div>
            </a>
        </div>
    </section>

    <section class="staff-grid">
        <div class="staff-col staff-col-wide">
            <article class="staff-card staff-card--equal" data-activity-card>
                <div class="staff-card-head staff-card-head--fixed-actions">
                    <div>
                        <h3>Recent Activity</h3>
                        <p data-activity-copy>Latest cases in your branch</p>
                    </div>
                    <div class="staff-head-actions flex items-center gap-2">
                        <a href="{{ route('funeral-cases.index') }}" class="staff-link" data-activity-link="cases">View all <i class="bi bi-arrow-right"></i></a>
                        <a href="{{ route('payments.history') }}" class="staff-link hidden" data-activity-link="payments">View all <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>

                <div class="staff-card-tabs-bar">
                    <div class="staff-tabs" data-activity-tabs>
                        <button type="button" class="staff-tab-btn is-active" data-activity-tab="cases" aria-pressed="true">Recent Cases</button>
                        <button type="button" class="staff-tab-btn" data-activity-tab="payments" aria-pressed="false">Recent Payments</button>
                    </div>
                </div>

                <div data-activity-panel="cases">
                    <div class="overflow-x-auto">
                        <table class="staff-table">
                            <thead>
                                <tr>
                                    <th>Ref</th>
                                    <th>Deceased - Client</th>
                                    <th>Status</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentCases as $case)
                                    @php
                                        $statusClass = match($case->case_status) {
                                            'ACTIVE' => 'active',
                                            'COMPLETED' => 'completed',
                                            default => 'draft',
                                        };
                                    @endphp
                                    <tr>
                                        <td><span class="staff-table-code">{{ $case->case_code }}</span></td>
                                        <td>
                                            <strong>{{ $case->deceased->full_name ?? 'Unknown' }}</strong>
                                            <div class="staff-muted">{{ $case->client->full_name ?? 'No client record' }}</div>
                                        </td>
                                        <td>
                                            <span class="status-pill {{ $statusClass }}">{{ \Illuminate\Support\Str::title(strtolower($case->case_status)) }}</span>
                                        </td>
                                        <td class="text-right staff-money">&#8369; {{ number_format((float) $case->total_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="staff-empty">
                                            <i class="bi bi-inbox"></i>
                                            No recent case records.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($recentCases->hasPages())
                        <div class="recent-cases-pagination">
                            {{ $recentCases->onEachSide(1)->links() }}
                        </div>
                    @endif
                </div>

                <div data-activity-panel="payments" hidden>
                    <div class="overflow-x-auto">
                        <table class="staff-table">
                            <thead>
                                <tr>
                                    <th class="text-center">Deceased - Client</th>
                                    <th class="text-center">Method - Date</th>
                                    <th class="text-center">Payment Status</th>
                                    <th class="text-center">Amount</th>
                                    <th class="text-center">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentPayments as $payment)
                                    @php
                                        $paidAt = $payment->paid_at ?? $payment->created_at;
                                        $caseCode = $payment->funeralCase?->case_code ?? 'N/A';
                                        $deceasedName = $payment->funeralCase?->deceased?->full_name ?? 'Unknown';
                                        $clientName = $payment->funeralCase?->client?->full_name ?? 'No client';
                                        $method = $payment->method ? \Illuminate\Support\Str::title(strtolower((string) $payment->method)) : 'Payment';
                                        $rawStatus = strtoupper((string) ($payment->payment_status_after_payment ?: $payment->funeralCase?->payment_status ?: 'UNPAID'));
                                        $statusLabel = match($rawStatus) {
                                            'PAID' => 'Fully Paid',
                                            'PARTIAL' => 'Partial',
                                            default => 'Unpaid',
                                        };
                                        $statusClass = match($rawStatus) {
                                            'PAID' => 'completed',
                                            'PARTIAL' => 'draft',
                                            default => 'unpaid',
                                        };
                                        $balanceValue = (float) ($payment->balance_after_payment ?? $payment->funeralCase?->balance_amount ?? 0);
                                    @endphp
                                    <tr>
                                        <td class="text-center">
                                            <strong>{{ $deceasedName }}</strong>
                                            <div class="staff-muted">{{ $caseCode }} - {{ $clientName }}</div>
                                        </td>
                                        <td class="text-center">
                                            <span class="status-pill completed">{{ $method }}</span>
                                            <div class="staff-muted">{{ optional($paidAt)->format('M d, Y') }}</div>
                                        </td>
                                        <td class="text-center">
                                            <span class="status-pill {{ $statusClass }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td class="text-center staff-money">&#8369; {{ number_format((float) $payment->amount, 2) }}</td>
                                        <td class="text-center {{ $balanceValue > 0 ? 'text-red-600' : 'staff-money' }}">
                                            &#8369; {{ number_format($balanceValue, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="staff-empty">
                                            <i class="bi bi-receipt"></i>
                                            No payment records yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </article>

            <div class="staff-subgrid-two">
                <article class="staff-card" data-attention-card>
                    <div class="staff-card-head">
                        <div>
                            <h3>Needs Attention</h3>
                            <p>Cases requiring follow-up</p>
                        </div>
                        <a href="{{ route('staff.reminders.index') }}" class="staff-pill" style="padding:.3rem .62rem;">{{ $attentionItems->count() }} Alert{{ $attentionItems->count() === 1 ? '' : 's' }}</a>
                    </div>

                    <div class="staff-list">
                        @forelse($attentionItems as $item)
                            @php
                                $pillClass = match($item['severity'] ?? 'info') {
                                    'danger' => 'danger',
                                    'warning' => 'warning',
                                    default => 'info',
                                };
                            @endphp
                            <a href="{{ route('funeral-cases.show', $item['case_id']) }}" class="staff-list-row">
                                <div>
                                    <strong>{{ $item['deceased_name'] }} - {{ $item['case_code'] }}</strong>
                                    <small>
                                        {{ $item['label'] }}
                                        @if(!empty($item['date']))
                                            - {{ $item['date']->format('M d, Y') }}
                                        @endif
                                    </small>
                                </div>
                                <span class="staff-attention-pill {{ $pillClass }}">{{ $item['label'] }}</span>
                            </a>
                        @empty
                            <div class="staff-empty">
                                <i class="bi bi-check2-circle"></i>
                                No urgent follow-up.
                            </div>
                        @endforelse
                    </div>
                </article>

                <article class="staff-card" data-outstanding-card>
                    <div class="staff-card-head">
                        <div>
                            <h3>Outstanding Balances</h3>
                            <p>Unpaid or partial cases</p>
                        </div>
                        @if($outstandingCases->isEmpty())
                            <span class="staff-pill" style="padding:.3rem .62rem; color:#047857; border-color:#bbf7d0; background:#dcfce7;">
                                <i class="bi bi-check2"></i> All settled
                            </span>
                        @endif
                    </div>

                    <div class="staff-list">
                        @forelse($outstandingCases as $case)
                            <div class="staff-list-row">
                                <div>
                                    <strong>{{ $case->deceased->full_name ?? 'Unknown' }} - {{ $case->case_code }}</strong>
                                    <small>{{ $case->client->full_name ?? 'No client record' }}</small>
                                </div>
                                <div class="text-right">
                                    <div class="staff-money" style="color:#b91c1c;">&#8369; {{ number_format((float) $case->balance_amount, 2) }}</div>
                                    <a href="{{ route('payments.index') }}" class="staff-balance-cta">Record Payment</a>
                                </div>
                            </div>
                        @empty
                            <div class="staff-empty">
                                <i class="bi bi-patch-check"></i>
                                No outstanding balances. All cases are fully paid.
                            </div>
                        @endforelse
                    </div>
                </article>
            </div>
        </div>

        <div class="staff-col">
            <article class="staff-card" data-schedules-card>
                <div class="staff-card-head staff-card-head--fixed-actions">
                    <div>
                        <h3>Schedules</h3>
                        <p data-schedule-copy>Today's services and events</p>
                    </div>
                    <div class="staff-head-actions flex items-center gap-2">
                        <a href="{{ route('staff.reminders.index', ['alert_type' => 'service_today']) }}" class="staff-link" data-schedule-link="today">View all <i class="bi bi-arrow-right"></i></a>
                        <a href="{{ route('staff.reminders.index', ['tab' => 'upcoming']) }}" class="staff-link hidden" data-schedule-link="upcoming">View all <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>

                <div class="staff-card-tabs-bar">
                    <div class="staff-tabs" data-schedule-tabs>
                        <button type="button" class="staff-tab-btn is-active" data-schedule-tab="today" aria-pressed="true">Today</button>
                        <button type="button" class="staff-tab-btn" data-schedule-tab="upcoming" aria-pressed="false">Upcoming</button>
                    </div>
                </div>

                <div data-schedule-panel="today">
                    <div class="staff-list">
                        @forelse($todayItems as $item)
                            <a href="{{ route('funeral-cases.show', $item['case_id']) }}" class="staff-list-row">
                                <div>
                                    <strong>{{ $item['deceased_name'] }}</strong>
                                    <small>{{ $item['case_code'] }}</small>
                                </div>
                                <div class="text-right">
                                    <div class="staff-money" style="color:#0b4f9f;">
                                        {{ $item['date']?->isStartOfDay() ? $item['date']?->format('M d') : $item['date']?->format('h:i A') }}
                                    </div>
                                    <small class="staff-muted">{{ $item['label'] }}</small>
                                </div>
                            </a>
                        @empty
                            <div class="staff-empty">
                                <i class="bi bi-calendar-x"></i>
                                No services today.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div data-schedule-panel="upcoming" hidden>
                    <div class="staff-list">
                        @forelse($upcomingItems as $item)
                            <a href="{{ route('funeral-cases.show', $item['case_id']) }}" class="staff-list-row" style="align-items:center;">
                                <div class="staff-upcoming-date">
                                    <strong>{{ $item['date']?->format('d') }}</strong>
                                    <span>{{ $item['date']?->format('M') }}</span>
                                </div>
                                <div style="min-width:0; flex:1;">
                                    <strong>{{ $item['deceased_name'] }}</strong>
                                    <small>{{ $item['case_code'] }} - {{ $item['label'] }}</small>
                                </div>
                                <div class="staff-money" style="color:#0b4f9f;">{{ $item['date']?->format('h:i A') }}</div>
                            </a>
                        @empty
                            <div class="staff-empty">
                                <i class="bi bi-calendar2-check"></i>
                                No upcoming services queued.
                            </div>
                        @endforelse
                    </div>
                </div>
            </article>

            <article class="staff-card staff-card--month" data-month-card>
                <div class="staff-card-head staff-card-head--compact">
                    <h3 style="margin:0;">This Month</h3>
                    <p style="margin:0;">{{ $monthLabel }}</p>
                </div>
                <ul class="staff-monthly-list">
                    <li>
                        <span>Cases Encoded</span>
                        <strong>{{ number_format((int) $monthCasesEncoded) }}</strong>
                    </li>
                    <li>
                        <span>Collected Payments</span>
                        <strong class="good">&#8369; {{ number_format((float) $monthPaymentsCollected, 2) }}</strong>
                    </li>
                    <li>
                        <span>Outstanding</span>
                        <strong class="{{ (float) $outstandingBalanceTotal > 0 ? 'warn' : 'good' }}">&#8369; {{ number_format((float) $outstandingBalanceTotal, 2) }}</strong>
                    </li>
                </ul>
            </article>
        </div>
    </section>
</div>

<script>
    (function () {
        const desktopQuery = window.matchMedia('(min-width: 1180px)');
        const activityCard = document.querySelector('[data-activity-card]');
        const schedulesCard = document.querySelector('[data-schedules-card]');
        const attentionCard = document.querySelector('[data-attention-card]');
        const outstandingCard = document.querySelector('[data-outstanding-card]');
        const monthCard = document.querySelector('[data-month-card]');

        const syncHeights = () => {
            if (activityCard) activityCard.style.minHeight = '';
            if (schedulesCard) schedulesCard.style.minHeight = '';
            if (attentionCard) attentionCard.style.minHeight = '';
            if (outstandingCard) outstandingCard.style.minHeight = '';
            if (monthCard) monthCard.style.minHeight = '';

            if (!desktopQuery.matches) return;

            if (activityCard && schedulesCard) {
                const topRowHeight = Math.max(activityCard.offsetHeight, schedulesCard.offsetHeight);
                activityCard.style.minHeight = topRowHeight + 'px';
                schedulesCard.style.minHeight = topRowHeight + 'px';
            }

            const lowerCards = [attentionCard, outstandingCard, monthCard].filter(Boolean);
            if (lowerCards.length > 1) {
                const lowerRowHeight = Math.max(...lowerCards.map((card) => card.offsetHeight));
                lowerCards.forEach((card) => {
                    card.style.minHeight = lowerRowHeight + 'px';
                });
            }
        };

        const syncNextFrame = () => requestAnimationFrame(syncHeights);

        window.addEventListener('resize', syncNextFrame);
        window.addEventListener('load', syncNextFrame);
        window.addEventListener('staff-dashboard-reflow', syncNextFrame);

        syncNextFrame();
    })();

    (function () {
        const tabRoot = document.querySelector('[data-activity-tabs]');
        if (!tabRoot) return;

        const tabs = [...tabRoot.querySelectorAll('[data-activity-tab]')];
        const panels = {
            cases: document.querySelector('[data-activity-panel="cases"]'),
            payments: document.querySelector('[data-activity-panel="payments"]')
        };
        const links = {
            cases: document.querySelector('[data-activity-link="cases"]'),
            payments: document.querySelector('[data-activity-link="payments"]')
        };
        const copy = document.querySelector('[data-activity-copy]');

        const activate = (key) => {
            tabs.forEach((tab) => {
                const active = tab.dataset.activityTab === key;
                tab.classList.toggle('is-active', active);
                tab.setAttribute('aria-pressed', active ? 'true' : 'false');
            });

            Object.entries(panels).forEach(([panelKey, panelEl]) => {
                if (!panelEl) return;
                panelEl.hidden = panelKey !== key;
            });

            Object.entries(links).forEach(([linkKey, linkEl]) => {
                if (!linkEl) return;
                linkEl.classList.toggle('hidden', linkKey !== key);
            });

            if (copy) {
                copy.textContent = key === 'cases'
                    ? 'Latest cases in your branch'
                    : 'Latest payments recorded';
            }

            window.dispatchEvent(new Event('staff-dashboard-reflow'));
        };

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => activate(tab.dataset.activityTab));
        });

        activate('cases');
    })();

    (function () {
        const tabRoot = document.querySelector('[data-schedule-tabs]');
        if (!tabRoot) return;

        const tabs = [...tabRoot.querySelectorAll('[data-schedule-tab]')];
        const panels = {
            today: document.querySelector('[data-schedule-panel="today"]'),
            upcoming: document.querySelector('[data-schedule-panel="upcoming"]')
        };
        const links = {
            today: document.querySelector('[data-schedule-link="today"]'),
            upcoming: document.querySelector('[data-schedule-link="upcoming"]')
        };
        const copy = document.querySelector('[data-schedule-copy]');

        const activate = (key) => {
            tabs.forEach((tab) => {
                const active = tab.dataset.scheduleTab === key;
                tab.classList.toggle('is-active', active);
                tab.setAttribute('aria-pressed', active ? 'true' : 'false');
            });

            Object.entries(panels).forEach(([panelKey, panelEl]) => {
                if (!panelEl) return;
                panelEl.hidden = panelKey !== key;
            });

            Object.entries(links).forEach(([linkKey, linkEl]) => {
                if (!linkEl) return;
                linkEl.classList.toggle('hidden', linkKey !== key);
            });

            if (copy) {
                copy.textContent = key === 'today'
                    ? "Today's services and events"
                    : 'Next schedules in queue';
            }

            window.dispatchEvent(new Event('staff-dashboard-reflow'));
        };

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => activate(tab.dataset.scheduleTab));
        });

        activate('today');
    })();
</script>
@endsection

