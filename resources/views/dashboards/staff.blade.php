@extends('layouts.panel')

@section('page_title', 'Dashboard')
@section('page_desc', 'Daily operations and follow-up center')
@section('hide_layout_topbar', '1')

@section('content')
@include('staff.partials.schedule-styles')
<style>
html[data-theme] body.panel-shell-body.staff-ui .staff-dashboard-v2 .staff-grid{align-items:stretch}
html[data-theme] body.panel-shell-body.staff-ui .staff-dashboard-v2 :is([data-activity-card],[data-schedules-card]){height:100%;min-height:0!important;display:flex;flex-direction:column}
html[data-theme] body.panel-shell-body.staff-ui .staff-dashboard-v2 :is([data-activity-card],[data-schedules-card])>.staff-card-head{flex:0 0 auto}
html[data-theme] body.panel-shell-body.staff-ui .staff-dashboard-v2 :is([data-activity-panel],[data-schedule-panel]){flex:1;min-height:0;overflow:visible}
html[data-theme] body.panel-shell-body.staff-ui .staff-dashboard-v2 [data-schedules-card] .staff-list{max-height:none;overflow:visible;align-content:start}
html[data-theme] body.panel-shell-body.staff-ui .staff-dashboard-v2 :is([data-activity-panel],[data-schedule-panel])[hidden]{display:none!important}
@media(max-width:1179px){html[data-theme] body.panel-shell-body.staff-ui .staff-dashboard-v2 :is([data-activity-card],[data-schedules-card]){height:auto;max-height:none}}
html[data-theme] body.panel-shell-body.staff-ui .staff-dashboard-v2 [data-schedules-card] .ref-schedule-card{padding:12px 14px}
html[data-theme] body.panel-shell-body.staff-ui .staff-dashboard-v2 [data-schedules-card] .staff-list{gap:10px;padding:16px 20px}
html[data-theme] body.panel-shell-body.staff-ui .staff-dashboard-v2 [data-schedules-card] :is(.ref-schedule-top,.ref-schedule-person){margin-bottom:4px;gap:8px}
html[data-theme] body.panel-shell-body.staff-ui .staff-dashboard-v2 [data-activity-panel=cases]{display:flex;flex-direction:column}
html[data-theme] body.panel-shell-body.staff-ui .staff-dashboard-v2 .dashboard-activity-pagination{margin-top:auto}
</style>
@php
    $branchName = $dashboardBranch->branch_name ?? 'Main Branch';
    $branchLabel = trim(($dashboardBranch->branch_code ?? 'BR001') . ' - ' . $branchName);
    $sameTimeCounts = $scheduleGroups->flatMap(fn ($group) => $group['events'])->filter(fn ($event) => $event['has_time'] && $event['date'])->groupBy(fn ($event) => $event['date']->format('Y-m-d H:i'))->map->count();


    $attentionItems = $attentionReminders;
    $todayItems = collect($todaySchedule ?? [])->take(5)->values();
    $upcomingItems = collect($upcomingSchedule ?? [])->take(5)->values();

@endphp



<div class="staff-dashboard-v2">
    <section class="staff-header-card panel-title-surface">
        <div class="staff-header-main">
            <div class="staff-header-title-group">
                <button
                    type="button"
                    id="mobileSidebarToggle"
                    class="mobile-menu-btn"
                    aria-label="Open navigation"
                    aria-expanded="false"
                    aria-controls="appSidebar"
                >
                    <i class="bi bi-list"></i>
                </button>
                <div class="staff-identity">
                    <div class="staff-page-title-row">
                        <h1 class="staff-title">Dashboard</h1>
                        <span class="staff-context-badge">Daily Operations</span>
                        <span class="staff-branch-chip">{{ $branchName }}</span>
                    </div>
                    <p class="staff-dashboard-description">Overview of cases, scheduled services, and recorded payments.</p>
                </div>
            </div>
            <div class="staff-payment-search-region">
                <form class="staff-payment-search" role="search" action="{{ route('payments.history') }}"
                      data-payment-case-search="{{ route('payments.recordable-cases') }}">
                    <label for="staff-payment-case-query">Find a case for payment</label>
                    <input type="hidden" name="payment_status" value="WITH_BALANCE">
                    <div class="staff-payment-search-field">
                        <div class="staff-payment-search-input">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input id="staff-payment-case-query" name="q" type="search" maxlength="100" autocomplete="off"
                                   role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="staff-payment-case-results"
                                   placeholder="Case ID, client or deceased name">
                        </div>
                        <button type="submit">Search</button>
                    </div>
                    <div class="staff-payment-search-popup" hidden>
                        <div id="staff-payment-case-results" role="listbox" aria-label="Cases with balances"></div>
                        <p role="status" aria-live="polite"></p>
                    </div>
                </form>
            </div>
            @include('partials.dashboard-header-controls', ['dashboardShowNotifications' => true, 'dashboardCompactNotifications' => true])
        </div>
    </section>

    <section class="staff-summary-grid" aria-label="Daily operations summary">
        <article class="staff-summary-card"><div class="staff-summary-label">Ongoing Cases <i class="bi bi-clipboard" aria-hidden="true"></i></div><strong data-dashboard-kpi-tone="info">{{ $ongoingCount }}</strong><p>Draft and active branch cases</p></article>
        <article class="staff-summary-card"><div class="staff-summary-label">Today's Schedule <i class="bi bi-calendar3" aria-hidden="true"></i></div><strong data-dashboard-kpi-tone="plan">{{ collect($todaySchedule)->count() }}</strong><p>Events in today's schedule</p></article>
        <article class="staff-summary-card"><div class="staff-summary-label">Today's Collections <i class="bi bi-credit-card" aria-hidden="true"></i></div><strong class="staff-summary-money" data-dashboard-kpi-tone="positive">&#8369; {{ number_format((float) $todayPaidTotal, 2) }}</strong><p>Current daily collection total</p></article>
        <article class="staff-summary-card"><div class="staff-summary-label">Needs Attention <i class="bi bi-exclamation-triangle" aria-hidden="true"></i></div><strong data-dashboard-kpi-tone="{{ $attentionItems->isEmpty() ? 'neutral' : 'warning' }}">{{ $attentionItems->count() }}</strong><p>Balances after scheduled interment</p></article>
    </section>

    <section class="staff-grid">
        <div class="staff-col staff-col-wide">
            <article class="staff-card staff-card--equal staff-card--hover-action" data-activity-card>
                <div class="staff-card-head staff-card-head--fixed-actions">
                    <div class="staff-card-head-main">
                        <div>
                            <div class="ref-panel-title"><h3>Recent Daily Activity</h3></div>
                            <p data-activity-copy>New cases recorded today in your branch</p>
                        </div>
                        <div class="staff-card-head-controls">
                            <div class="staff-tabs" data-activity-tabs>
                                <button type="button" class="staff-tab-btn is-active" data-activity-tab="cases" aria-pressed="true"><i class="bi bi-clipboard" aria-hidden="true"></i> Cases ({{ $recentCases->total() }})</button>
                                <button type="button" class="staff-tab-btn" data-activity-tab="payments" aria-pressed="false"><i class="bi bi-credit-card" aria-hidden="true"></i> Payments ({{ $recentPayments->count() }})</button>
                            </div>
                            <div class="staff-head-actions flex items-center gap-2">
                                <a href="{{ route('funeral-cases.index') }}" class="staff-link" data-activity-link="cases">Go to Case Records <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                                <a href="{{ route('payments.history') }}" class="staff-link hidden" data-activity-link="payments">Go to Payment Monitoring <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                @include('staff.partials.dashboard-activity')
            </article>

        </div>

        <div class="staff-col">
            <article class="staff-card staff-card--hover-action" data-schedules-card>
                <div class="staff-card-head staff-card-head--fixed-actions">
                    <div class="staff-card-head-main">
                        <div>
                            <h3>Schedules</h3>
                            <p data-schedule-copy>Today's scheduled events</p>
                        </div>
                        <div class="staff-card-head-controls">
                            <div class="staff-tabs" data-schedule-tabs>
                                <button type="button" class="staff-tab-btn is-active" data-schedule-tab="today" aria-pressed="true">Today ({{ collect($todaySchedule)->count() }})</button>
                                <button type="button" class="staff-tab-btn" data-schedule-tab="upcoming" aria-pressed="false" title="Upcoming events">Upcoming ({{ collect($upcomingSchedule)->count() }})</button>
                            </div>
                            <div class="staff-head-actions flex items-center gap-2">
                                <a href="{{ route('staff.reminders.index', ['tab' => 'today']) }}" class="staff-link" data-schedule-link="today">View All Schedules <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                                <a href="{{ route('staff.reminders.index', ['tab' => 'upcoming']) }}" class="staff-link hidden" data-schedule-link="upcoming">View All Schedules <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <div data-schedule-panel="today"><p class="dashboard-schedule-count">Showing {{ $todayItems->count() }} of {{ collect($todaySchedule)->count() }} events today.</p>
                    <div class="staff-list">
                        @forelse($todayItems as $item)
                            @include('staff.partials.dashboard-schedule', ['item' => $item, 'upcoming' => false])
                        @empty
                            <div class="staff-empty">
                                <i class="bi bi-calendar-x"></i>
                                No events scheduled today.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div data-schedule-panel="upcoming" hidden><p class="dashboard-schedule-count">Showing {{ $upcomingItems->count() }} of {{ collect($upcomingSchedule)->count() }} upcoming events.</p>
                    <div class="staff-list">
                        @forelse($upcomingItems as $item)
                            @include('staff.partials.dashboard-schedule', ['item' => $item, 'upcoming' => true])
                        @empty
                            <div class="staff-empty">
                                <i class="bi bi-calendar2-check"></i>
                                No upcoming events scheduled.
                            </div>
                        @endforelse
                    </div>
                </div>
            </article>

        </div>
    </section>
            <div class="staff-subgrid-two">
                <article id="outstanding-balances" class="staff-card staff-card--hover-action" data-outstanding-card>
                    <div class="staff-card-head">
                        <div class="staff-card-head-main">
                            <div>
                                <div class="ref-panel-title staff-balances-heading">
                                    <h3><i class="bi bi-wallet2" aria-hidden="true"></i> Outstanding Balances</h3>
                                </div>
                                <p>Cases with remaining balances</p>
                            </div>
                            <div class="staff-card-head-controls staff-card-head-controls--summary">
                                <div class="staff-head-actions">
                                    <a href="{{ route('payments.history', ['payment_status' => 'WITH_BALANCE']) }}" class="staff-link">Go to Payment Monitoring <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div data-balances-results tabindex="-1">
                        @include('staff.partials.dashboard-outstanding-balances')
                    </div>
                </article>

                <article class="staff-card staff-card--hover-action" data-attention-card>
                    <div class="staff-card-head">
                        <div class="staff-card-head-main">
                            <div>
                                <div class="ref-panel-title staff-urgent-heading">
                                    <h3 id="staff-urgent-cases"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Needs Attention</h3>
                                    <span class="staff-urgent-count">{{ $attentionItems->count() }} {{ Str::plural('Case', $attentionItems->count()) }}</span>
                                </div>
                                <p>Balances after scheduled interment</p>
                            </div>
                            <div class="staff-card-head-controls staff-card-head-controls--summary">
                                <div class="staff-head-actions">
                                    <a href="{{ route('staff.reminders.index', ['tab' => 'warnings']) }}" class="staff-link">View All <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <ul class="staff-list staff-urgent-list" aria-labelledby="staff-urgent-cases" tabindex="0">
                        @forelse($attentionItems as $item)
                            @php $daysSince = (int) $item['date']->copy()->startOfDay()->diffInDays($dashboardNow->copy()->startOfDay()); @endphp
                            <li class="staff-urgent-case">
                                <div>
                                    <div class="staff-urgent-case-name">
                                        <i class="bi bi-circle-fill" aria-hidden="true"></i>
                                        <strong>{{ $item['deceased_name'] }}</strong>
                                        <span class="ref-mono">{{ $item['case_code'] }}</span>
                                    </div>
                                    <span class="staff-attention-date">
                                        Interment: <time datetime="{{ $item['date']->toDateString() }}">{{ $item['date']->format('M d, Y') }}</time>
                                        · {{ $daysSince === 0 ? 'Earlier today' : $daysSince.' '.Str::plural('day', $daysSince).' ago' }}
                                    </span>
                                </div>
                                <div class="staff-urgent-case-actions">
                                    <span class="ref-balance-amount">&#8369; {{ number_format((float) $item['case']->balance_amount, 2) }}</span>
                                    <button type="button" class="staff-link" data-attention-dialog="attention-case-{{ $item['case_id'] }}" aria-label="View Summary for {{ $item['case_code'] }}">View Summary</button>
                                </div>
                            </li>
                        @empty
                            <li class="staff-empty">
                                <i class="bi bi-check2-circle"></i>
                                No balances after scheduled interment.
                            </li>
                        @endforelse
                    </ul>
                </article>
            </div>
</div>

<script>
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
                    ? 'New cases recorded today in your branch'
                    : 'Payments recorded today in your branch';
            }

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
                    ? "Today's scheduled events"
                    : 'Upcoming events';
            }

        };

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => activate(tab.dataset.scheduleTab));
        });

        activate('today');
    })();
</script>

@include('staff.partials.dashboard-activity-navigation')
@php
    $modalCases = $todayItems->merge($upcomingItems)->unique('case_id')->map(fn ($event) => ['case' => $event['case'], 'branch_label' => $branchLabel, 'events' => app(\App\Services\ReminderService::class)->caseSchedule($event['case'])]);
@endphp
@include('staff.partials.case-schedule-dialogs', ['dashboardScheduleModal' => true])
@include('staff.partials.dashboard-schedule-navigation')
@include('staff.partials.dashboard-attention-dialogs')
@endsection
