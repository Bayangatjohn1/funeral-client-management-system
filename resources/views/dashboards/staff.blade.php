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
    $branchLabel = trim(($dashboardBranch->branch_code ?? 'BR001') . ' - ' . ($dashboardBranch->branch_name ?? 'Main Branch'));
    $todayLabel = now()->format('l, F j, Y');
    $sameTimeCounts = $scheduleGroups->flatMap(fn ($group) => $group['events'])->filter(fn ($event) => $event['has_time'] && $event['date'])->groupBy(fn ($event) => $event['date']->format('Y-m-d H:i'))->map->count();


    $attentionItems = collect($attentionReminders ?? [])->take(3)->values();
    $todayItems = collect($todaySchedule ?? [])->take(5)->values();
    $upcomingItems = collect($upcomingSchedule ?? [])->take(5)->values();

    $outstandingCases = collect($unpaidCases ?? [])
        ->filter(fn ($case) => (float) ($case->balance_amount ?? 0) > 0)
        ->take(3)
        ->values();

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
                    <div class="staff-page-title-row"><h1 class="staff-title"><x-page-title-icon icon="grid" inline />Dashboard</h1><span class="staff-context-badge">Daily Operations</span></div>
                    <p class="staff-dashboard-description">Overview of cases, scheduled services, and recorded payments.</p>
                    <span class="staff-branch-chip">{{ $branchLabel }}</span>
                </div>
            </div>
            @include('partials.dashboard-header-controls', ['dashboardDateLabel' => $todayLabel, 'dashboardShowNotifications' => true, 'dashboardCompactNotifications' => true])
        </div>
    </section>

    <section class="staff-summary-grid" aria-label="Daily operations summary">
        <article class="staff-summary-card"><div class="staff-summary-label">Ongoing Cases <i class="bi bi-clipboard" aria-hidden="true"></i></div><strong>{{ $ongoingCount }}</strong><p>Draft and active branch cases</p></article>
        <article class="staff-summary-card"><div class="staff-summary-label">Today's Schedule <i class="bi bi-calendar3" aria-hidden="true"></i></div><strong>{{ collect($todaySchedule)->count() }}</strong><p>Events in today's schedule</p></article>
        <article class="staff-summary-card"><div class="staff-summary-label">Today's Collections <i class="bi bi-credit-card" aria-hidden="true"></i></div><strong class="staff-summary-money">&#8369; {{ number_format((float) $todayPaidTotal, 2) }}</strong><p>Current daily collection total</p></article>
        <article class="staff-summary-card"><div class="staff-summary-label">Needs Attention <i class="bi bi-exclamation-triangle" aria-hidden="true"></i></div><strong>{{ collect($attentionReminders)->count() }}</strong><p>Existing case follow-up reminders</p></article>
    </section>

    <section aria-label="Quick actions">
        <div class="staff-action-grid">
            <a href="{{ route('intake.main.create') }}" class="staff-action-card is-light">
                <div class="icon"><i class="bi bi-plus-lg"></i></div>
                <div>
                    <p class="title">Record New Case</p>
                    <p class="desc">Create a funeral record</p>
                </div>
                <span class="staff-action-arrow" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></span>
            </a>

            <a href="{{ route('funeral-cases.index') }}" class="staff-action-card is-light">
                <div class="icon"><i class="bi bi-clipboard-data"></i></div>
                <div>
                    <p class="title">Case Records</p>
                    <p class="desc">View all branch cases</p>
                </div>
                <span class="staff-action-arrow" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></span>
            </a>

            <a href="{{ route('payments.history') }}" class="staff-action-card is-light">
                <div class="icon"><i class="bi bi-clock-history"></i></div>
                <div>
                    <p class="title">Payment Monitoring</p>
                    <p class="desc">Browse all payments</p>
                </div>
                <span class="staff-action-arrow" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></span>
            </a>
        </div>
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
                <article class="staff-card staff-card--hover-action" data-attention-card>
                    <div class="staff-card-head">
                        <div class="staff-card-head-main">
                            <div>
                                <div class="ref-panel-title"><h3>Needs Attention</h3><span class="ref-attention-count">{{ collect($attentionReminders)->count() }} pending</span></div>
                                <p>Cases requiring follow-up</p>
                            </div>
                            <div class="staff-card-head-controls staff-card-head-controls--summary">
                                <div class="staff-head-actions">
                                    <a href="{{ route('staff.reminders.index', ['tab' => 'warnings']) }}" class="staff-link">View All <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="staff-list">
                        @forelse($attentionItems as $item)
                            @php
                                $pillClass = match($item['severity'] ?? 'info') {
                                    'danger' => 'danger',
                                    'warning' => 'warning',
                                    default => 'info',
                                };
                                $scheduleLabel = match($item['type'] ?? '') {
                                    'interment_completed', 'interment_date_completed', 'interment_approaching', 'interment_date_today_balance' => 'Interment',
                                    'same_day_interment_schedule' => 'Interment',
                                    'same_day_service_schedule' => 'Service',
                                    default => 'Schedule',
                                };
                            @endphp
                            <div class="ref-attention-card">
                                <div>
                                    <div class="ref-attention-top"><strong>{{ $item['case_code'] }}</strong><span>{{ $item['deceased_name'] }}</span></div>
                                    <small class="ref-attention-message"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i><span>
                                        {{ $item['message'] ?? $item['label'] }}
                                    </span></small>
                                    @if(!empty($item['date']))
                                        <span class="staff-attention-date">
                                            {{ $scheduleLabel }}: {{ $item['date']->format('M d, Y') }}{{ !empty($item['has_time']) ? ' · '.$item['date']->format('h:i A') : ' · Time not recorded' }}
                                        </span>
                                    @endif
                                </div>
                                <div class="dashboard-attention-actions"><span class="staff-attention-pill {{ $pillClass }}">{{ $item['label'] }}</span><button type="button" class="dashboard-view-icon" data-attention-dialog="attention-case-{{ $item['case_id'] }}" aria-label="View Payment Summary" data-tip="View Payment Summary"><i class="bi bi-wallet2" aria-hidden="true"></i></button></div>
                            </div>
                        @empty
                            <div class="staff-empty">
                                <i class="bi bi-check2-circle"></i>
                                No case updates requiring follow-up.
                            </div>
                        @endforelse
                    </div>
                </article>

                <article class="staff-card staff-card--hover-action" data-outstanding-card>
                    <div class="staff-card-head">
                        <div class="staff-card-head-main">
                            <div>
                                <h3>Outstanding Balances</h3>
                                <p>Cases with remaining balances</p>
                            </div>
                            <div class="staff-card-head-controls staff-card-head-controls--summary">
                                <div class="staff-head-actions">
                                    <a href="{{ route('payments.history') }}" class="staff-link">Go to Payment Monitoring <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="staff-list">
                        @forelse($outstandingCases as $case)
                            <div class="ref-balance-card">
                                <a class="ref-balance-case" href="{{ route('funeral-cases.show', ['funeral_case' => $case->id, 'return_to' => request()->fullUrl()]) }}">
                                    <strong><span class="ref-mono">{{ $case->case_code }}</span> · {{ $case->client?->full_name ?? 'No client record' }}</strong>
                                    <small>For: {{ $case->deceased?->full_name ?? 'Unknown' }}</small>
                                </a>
                                <div class="ref-balance-actions"><div class="text-right">
                                    <div class="ref-balance-amount">&#8369; {{ number_format((float) $case->balance_amount, 2) }}</div>
                                    <span class="status-pill staff-payment-state {{ $case->payment_status === 'PARTIAL' ? 'draft' : 'unpaid' }}">
                                        {{ $case->payment_status === 'PARTIAL' ? 'Partially Paid' : 'No Payment Yet' }}
                                    </span>
                                </div><a href="{{ route('payments.history', ['case_id' => $case->id, 'record_payment' => 1]) }}" class="ref-collect"><i class="bi bi-credit-card" aria-hidden="true"></i> Collect</a></div>
                            </div>
                        @empty
                            <div class="staff-empty">
                                <i class="bi bi-patch-check"></i>
                                No cases with remaining balances.
                            </div>
                        @endforelse
                    </div>
                </article>
            </div>
</div>

<script>
    (function () {
        const desktopQuery = window.matchMedia('(min-width: 1180px)');
        const activityCard = document.querySelector('[data-activity-card]');
        const schedulesCard = document.querySelector('[data-schedules-card]');
        const attentionCard = document.querySelector('[data-attention-card]');
        const outstandingCard = document.querySelector('[data-outstanding-card]');

        const syncHeights = () => {
            if (activityCard) activityCard.style.minHeight = '';
            if (schedulesCard) schedulesCard.style.minHeight = '';
            if (attentionCard) attentionCard.style.minHeight = '';
            if (outstandingCard) outstandingCard.style.minHeight = '';

            if (!desktopQuery.matches) return;

            const lowerCards = [attentionCard, outstandingCard].filter(Boolean);
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
                    ? 'New cases recorded today in your branch'
                    : 'Payments recorded today in your branch';
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
                    ? "Today's scheduled events"
                    : 'Upcoming events';
            }

            window.dispatchEvent(new Event('staff-dashboard-reflow'));
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
