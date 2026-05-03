@extends('layouts.panel')

@section('page_title', 'Reminders & Schedule')
@section('page_desc', 'View upcoming schedules, follow-ups, and reminder alerts.')

@section('content')
@php
    $remindersRoute = request()->routeIs('admin.*') ? 'admin.reminders.index' : 'staff.reminders.index';
    $dashboardUrl = request()->routeIs('admin.*') ? url('/admin') : url('/staff');
@endphp
<style>
    .reminders-page {
        width: 100%;
        margin: 0 auto;
        padding-bottom: 24px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .reminders-toolbar {
        border: 1px solid #e4ebf3;
        border-radius: 16px;
        background: #ffffff;
        padding: 14px;
        box-shadow: none;
        display: grid;
        gap: 12px;
    }

    .reminders-back-btn {
        width: fit-content;
        padding: 8px 12px;
        border: 1px solid #d9e3ee;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #333333;
        background: #FAFAF7;
    }

    .reminders-back-btn:hover {
        background: #f1f5f9;
        border-color: #cfdbe8;
    }

    .reminders-filter-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(220px, 1fr));
        gap: 12px;
        align-items: end;
    }

    .reminders-filter-control input,
    .reminders-filter-control select {
        width: 100%;
        min-height: 44px;
        margin-top: 0 !important;
    }

    .reminders-filter-note {
        font-size: 11px;
        color: #7A8076;
        margin-top: 6px;
    }

    .reminders-tabs-card {
        background: #ffffff;
        border: 1px solid #e4ebf3;
        border-radius: 16px;
        padding: 14px;
        box-shadow: none;
    }

    .reminders-tab {
        padding: 9px 14px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        border-radius: 10px;
        border: 1px solid #dbe4ee;
        transition: color .15s ease, border-color .15s ease, background-color .15s ease;
        display: inline-flex;
        align-items: center;
    }

    .reminders-tab.is-active {
        background: #3E4A3D;
        border-color: #3E4A3D;
        color: #ffffff;
    }

    .reminders-tab.is-idle {
        background: #ffffff;
        color: #5F685F;
    }

    .reminders-tab.is-idle:hover {
        border-color: #c9d7e6;
        color: #333333;
        background: #FAFAF7;
    }

    .reminders-empty {
        padding: 30px 16px;
        background: #f8fbff;
        border: 1px dashed #d9e3ee;
        border-radius: 14px;
        text-align: center;
    }

    .reminders-list {
        display: grid;
        gap: 10px;
    }

    .reminders-item {
        padding: 16px 18px;
        background: #ffffff;
        border: 1px solid #e4ebf3;
        border-radius: 14px;
        box-shadow: none !important;
        transition: border-color .16s ease, background-color .16s ease;
    }

    .reminders-item:hover {
        border-color: #d2deeb;
        background: #fcfdff;
    }

    .reminders-item-main {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
    }

    .reminders-side-actions {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 8px;
        flex-shrink: 0;
    }

    .reminders-chip {
        display: inline-flex;
        align-items: center;
        padding: 4px 8px;
        border-radius: 8px;
        border: 1px solid transparent;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        line-height: 1;
    }

    html[data-theme='dark'] .reminders-toolbar,
    html[data-theme='dark'] .reminders-tabs-card {
        background: linear-gradient(180deg, #0f2038 0%, #112741 100%);
        border-color: #2f4668;
    }

    html[data-theme='dark'] .reminders-back-btn {
        background: #162b47;
        border-color: #335074;
        color: #d9e7fb;
    }

    html[data-theme='dark'] .reminders-back-btn:hover {
        background: #1a3251;
        border-color: #44658f;
        color: #ffffff;
    }

    html[data-theme='dark'] .reminders-filter-note {
        color: #8ea8c7;
    }

    html[data-theme='dark'] .reminders-tab {
        border-color: #35557b;
    }

    html[data-theme='dark'] .reminders-tab.is-active {
        background: #0d1a2f;
        border-color: #5578a8;
        color: #f8fbff;
    }

    html[data-theme='dark'] .reminders-tab.is-idle {
        background: #16304e;
        color: #c7d7ef;
    }

    html[data-theme='dark'] .reminders-tab.is-idle:hover {
        background: #1c3a5d;
        border-color: #4d6f98;
        color: #ffffff;
    }

    html[data-theme='dark'] .reminders-empty {
        background: #12243c;
        border-color: #355074;
    }

    html[data-theme='dark'] .reminders-item {
        background: #132844;
        border-color: #2f4a6b;
    }

    html[data-theme='dark'] .reminders-item:hover {
        background: #17304e;
        border-color: #466890;
    }

    html[data-theme='dark'] .reminders-page .bg-red-100 {
        background: #4d2230;
        border-color: #7a3448;
    }

    html[data-theme='dark'] .reminders-page .bg-blue-100 {
        background: #1f3656;
        border-color: #32547f;
    }

    html[data-theme='dark'] .reminders-page .bg-indigo-50 {
        background: #243456;
        border-color: #37507d;
    }

    html[data-theme='dark'] .reminders-page .bg-orange-100 {
        background: #4a321f;
        border-color: #7c5732;
    }

    html[data-theme='dark'] .reminders-page .bg-emerald-50 {
        background: #1d3f37;
        border-color: #2f6155;
    }

    html[data-theme='dark'] .reminders-page .bg-slate-50 {
        background: #263a56;
        border-color: #3f5677;
    }

    @media (max-width: 1024px) {
        .reminders-filter-grid {
            grid-template-columns: 1fr;
        }

        .reminders-item-main {
            flex-direction: column;
            align-items: stretch;
        }

        .reminders-side-actions {
            align-items: flex-start;
        }
    }
</style>

<div class="reminders-page">
    <section class="reminders-toolbar">
        <a href="{{ $dashboardUrl }}" class="reminders-back-btn">
            Back to Dashboard
        </a>

        <form method="GET" action="{{ route($remindersRoute) }}" class="reminders-filter-grid">
            <input type="hidden" name="tab" value="{{ $activeTab ?? 'today' }}">

            <div class="reminders-filter-control">
                <input
                    type="date"
                    name="date"
                    value="{{ $filters['date'] ?? '' }}"
                    class="form-input"
                    onchange="this.form.submit()"
                >
            </div>

            <div class="reminders-filter-control">
                <select name="case_status" class="form-select" onchange="this.form.submit()">
                    <option value="">All</option>
                    <option value="DRAFT" {{ ($filters['case_status'] ?? '') === 'DRAFT' ? 'selected' : '' }}>Draft</option>
                    <option value="ACTIVE" {{ ($filters['case_status'] ?? '') === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                    <option value="COMPLETED" {{ ($filters['case_status'] ?? '') === 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>

            <div class="reminders-filter-control">
                <select name="branch_id" class="form-select" onchange="this.form.submit()">
                    @forelse($branchChoices as $branch)
                        <option value="{{ $branch->id }}" {{ ($selectedBranchId ?? null) === $branch->id ? 'selected' : '' }}>
                            {{ $branch->branch_code }} - {{ $branch->branch_name }}
                        </option>
                    @empty
                        <option value="{{ $selectedBranchId ?? '' }}">Main branch (operational)</option>
                    @endforelse
                </select>
                <p class="reminders-filter-note">Operational reminders limited to main branch.</p>
            </div>
        </form>
    </section>

    @php
        $tabMap = [
            'today' => $reminders->whereIn('type', ['service_today', 'interment_today']),
            'upcoming' => $reminders->whereIn('type', ['upcoming_service', 'upcoming_interment']),
            'unpaid' => $reminders->where('type', 'balance'),
            'warnings' => $reminders->where('type', 'schedule_warning'),
            'all' => $reminders,
        ];

        $activeTab = $activeTab ?? 'today';

        $colors = [
            'balance' => 'border-l-red-500',
            'service_today' => 'border-l-blue-500',
            'interment_today' => 'border-l-blue-500',
            'upcoming_service' => 'border-l-indigo-400',
            'upcoming_interment' => 'border-l-indigo-400',
            'schedule_warning' => 'border-l-orange-400',
        ];

        $badge = [
            'balance' => 'bg-red-100 text-red-700 border-red-200',
            'service_today' => 'bg-blue-100 text-blue-700 border-blue-200',
            'interment_today' => 'bg-blue-100 text-blue-700 border-blue-200',
            'upcoming_service' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
            'upcoming_interment' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
            'schedule_warning' => 'bg-orange-100 text-orange-700 border-orange-200',
        ];

        $tabIcons = [
            'today' => 'bi-calendar-day',
            'upcoming' => 'bi-hourglass-split',
            'unpaid' => 'bi-exclamation-octagon',
            'warnings' => 'bi-exclamation-triangle',
            'all' => 'bi-clipboard-data',
        ];
    @endphp

    <section class="reminders-tabs-card">
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach([
                'today' => 'Today',
                'upcoming' => 'Upcoming',
                'unpaid' => 'Unpaid / Partial',
                'warnings' => 'Warnings',
                'all' => 'All Alerts',
            ] as $key => $label)
                <a
                    href="{{ route($remindersRoute, array_merge(request()->except('page'), ['tab' => $key])) }}"
                    class="reminders-tab {{ $activeTab === $key ? 'is-active' : 'is-idle' }}"
                >
                    <i class="bi {{ $tabIcons[$key] ?? 'bi-dot' }} mr-2"></i>
                    {{ $label }}

                    @if(array_key_exists($key, $counts))
                        <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 text-[10px] rounded-full {{ $activeTab === $key ? 'bg-white text-slate-900' : 'bg-slate-100 text-slate-600' }}">
                            {{ $counts[$key] ?? 0 }}
                        </span>
                    @endif
                </a>
            @endforeach
        </div>

        @php
            $items = $tabMap[$activeTab] ?? collect();
        @endphp

        @if($items->isEmpty())
            <div class="reminders-empty">
                <i class="bi bi-emoji-smile text-slate-300 text-3xl mb-2 block"></i>

                <p class="text-sm font-semibold text-slate-500">
                    @switch($activeTab)
                        @case('today')
                            No schedules for today
                            @break
                        @case('unpaid')
                            No unpaid balances
                            @break
                        @case('upcoming')
                            No upcoming schedules
                            @break
                        @case('warnings')
                            No conflicting schedules
                            @break
                        @default
                            No items
                    @endswitch
                </p>

                <p class="text-[11px] text-slate-400">You're all caught up.</p>
            </div>
        @else
            <div class="reminders-list">
                @foreach($items as $item)
                    @php
                        $case = $item['case'] ?? null;
                        $paymentStatus = $case->payment_status ?? null;

                        $isUnpaid = in_array($paymentStatus, ['UNPAID', 'PARTIAL'], true);

                        $primaryLabel = $isUnpaid
                            ? 'Unpaid Balance'
                            : ucfirst(str_replace('_', ' ', $item['type'] ?? 'alert'));

                        $icon = $isUnpaid
                            ? 'bi-cash-coin text-red-600'
                            : 'bi-info-circle text-slate-500';

                        $amountLine = ($isUnpaid && $case)
                            ? 'Outstanding Balance: PHP ' . number_format((float) ($case->balance_amount ?? 0), 2)
                            : null;

                        $supportLine = null;

                        if ($case && !empty($case->interment_at)) {
                            $supportLine = 'Interment: ' . $case->interment_at->format('M d, Y');
                        } elseif ($case && !empty($case->funeral_service_at)) {
                            $supportLine = 'Service: ' . $case->funeral_service_at->format('M d, Y');
                        } elseif ($case && !empty($case->paid_at)) {
                            $supportLine = 'Last payment: ' . $case->paid_at->format('M d, Y');
                        }
                    @endphp

                    <div class="reminders-item border-l-4 {{ $colors[$item['type']] ?? 'border-l-slate-200' }}">
                        <div class="reminders-item-main">
                            <div class="space-y-1">
                                <p class="text-sm font-bold text-slate-900 leading-none">
                                    {{ $case->client->full_name ?? 'Client N/A' }}
                                </p>

                                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-widest">
                                    {{ $item['case_code'] ?? 'N/A' }}
                                </p>

                                <div class="flex items-center gap-2 text-[11px] font-semibold text-slate-600">
                                    <i class="bi {{ $icon }}"></i>
                                    <span>{{ $primaryLabel }}</span>
                                </div>

                                @if($amountLine)
                                    <p class="text-[11px] text-slate-600">{{ $amountLine }}</p>
                                @endif

                                @if($supportLine)
                                    <p class="text-[11px] text-slate-500">{{ $supportLine }}</p>
                                @elseif(!empty($item['date']))
                                    <p class="text-[11px] font-semibold text-slate-500">
                                        {{ $item['date']->format('M d, Y') }}
                                        @if(method_exists($item['date'], 'isStartOfDay') && !$item['date']->isStartOfDay())
                                            - {{ $item['date']->format('h:i A') }}
                                        @endif
                                    </p>
                                @endif

                                <div class="inline-flex items-center gap-2 flex-wrap">
                                    <span class="reminders-chip {{ $badge[$item['type']] ?? 'bg-slate-50 text-slate-500 border-slate-200' }}">
                                        {{ $primaryLabel }}
                                    </span>

                                    <span class="reminders-chip
                                        {{ $paymentStatus === 'UNPAID'
                                            ? 'bg-red-100 text-red-700 border-red-200'
                                            : ($paymentStatus === 'PARTIAL'
                                                ? 'bg-orange-100 text-orange-700 border-orange-200'
                                                : 'bg-emerald-50 text-emerald-700 border-emerald-200') }}">
                                        {{ $paymentStatus ?? 'N/A' }}
                                    </span>
                                </div>

                                @if(($item['type'] ?? null) === 'schedule_warning')
                                    <p class="text-[11px] text-orange-600">
                                        Warning = similar or conflicting schedule (non-blocking)
                                    </p>
                                @endif
                            </div>

                            <div class="reminders-side-actions">
                                <span class="text-[11px] font-semibold text-slate-500">
                                    {{ ucfirst(str_replace('_', ' ', $item['type'] ?? 'alert')) }}
                                </span>

                                <a
                                    href="{{ route('funeral-cases.show', ['funeral_case' => $item['case_id'], 'return_to' => request()->fullUrl()]) }}"
                                    class="px-3 py-2 text-[11px] font-bold uppercase tracking-widest rounded-lg bg-slate-900 text-white hover:bg-[#3E4A3D] transition-colors"
                                >
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
