@extends('layouts.panel')

{{-- 1. CLEANER HEADER TITLE --}}
@section('page_title', 'Overview')
@section('page_desc', 'Case Work & Records.')

@section('content')
@php
    $staffFirstName = \Illuminate\Support\Str::of(auth()->user()->name ?? 'Staff')->trim()->explode(' ')->first();
@endphp
<style>
    .staff-surface-card {
        background: linear-gradient(180deg, #f8fbff 0%, #f2f6fc 100%);
        border: 1px solid #d3deea;
        border-radius: 1.5rem;
    }

    .staff-surface-card__head {
        background: rgba(255, 255, 255, 0.82);
        border-bottom: 1px solid #dde7f2;
    }

    .staff-surface-card .staff-soft-row {
        background: #ffffff;
        border-color: #dee7f1;
    }

    .staff-surface-card .staff-soft-row:hover {
        background: #f5f9fe;
    }

    .staff-surface-card .staff-soft-empty {
        background: #f4f8fd;
        border-color: #dde6f0;
    }

    .staff-surface-card .staff-soft-table-head {
        background: #edf3fa;
        border-bottom-color: #d8e3ef;
    }

    .staff-search-input,
    .staff-search-input:focus,
    .staff-search-input:active {
        box-shadow: none !important;
        -webkit-box-shadow: none !important;
        filter: none !important;
    }

    .recent-cases-pagination .table-paginator {
        justify-content: flex-end;
    }

    .recent-cases-pagination .table-paginator-meta {
        display: none;
    }

    html[data-theme='dark'] .staff-surface-card {
        background: linear-gradient(180deg, #172333 0%, #132030 100%);
        border-color: #32445b;
        box-shadow: none;
    }

    html[data-theme='dark'] .staff-surface-card__head {
        background: rgba(15, 23, 36, 0.42);
        border-bottom-color: #334a62;
    }

    html[data-theme='dark'] .staff-surface-card .staff-soft-table-head {
        background: #1e3044;
        border-bottom-color: #334a62;
    }

    html[data-theme='dark'] .staff-surface-card .staff-soft-row {
        background: #18283a;
        border-color: #2f435a;
    }

    html[data-theme='dark'] .staff-surface-card .staff-soft-row:hover {
        background: #1d3045;
    }

    html[data-theme='dark'] .staff-surface-card .staff-soft-empty {
        background: #18283a;
        border-color: #2f435a;
    }

    html[data-theme='dark'] .staff-search-input {
        background: #1a2638 !important;
        border-color: #40526a !important;
        color: #e5edf6 !important;
    }

    html[data-theme='dark'] .staff-search-input:focus,
    html[data-theme='dark'] .staff-search-input:active {
        background: #1f2d42 !important;
        border-color: #d4a373 !important;
        color: #f8fbff !important;
    }

    html[data-theme='dark'] .staff-view-all-btn {
        background: #1f2d42 !important;
        border-color: #3f536b !important;
        color: #cdd8e8 !important;
    }

    html[data-theme='dark'] .staff-view-all-btn:hover {
        background: #273950 !important;
        border-color: #5a6d85 !important;
        color: #f8fbff !important;
    }

    html[data-theme='dark'] .staff-attention-pill {
        background: #2a1f29 !important;
        border-color: #5e3a4b !important;
    }

    html[data-theme='dark'] .staff-attention-pill.staff-attention-pill--danger {
        background: #3a1f2a !important;
        border-color: #7a3a52 !important;
        color: #f7b8c4 !important;
    }

    html[data-theme='dark'] .staff-attention-pill.staff-attention-pill--warning {
        background: #3b2f1f !important;
        border-color: #7c6340 !important;
        color: #f3d09b !important;
    }

    html[data-theme='dark'] .staff-attention-pill.staff-attention-pill--primary {
        background: #1f2d43 !important;
        border-color: #3b5f8a !important;
        color: #b7d2ff !important;
    }

    html[data-theme='dark'] .staff-attention-pill.staff-attention-pill--info {
        background: #243244 !important;
        border-color: #435a75 !important;
        color: #c7d5e8 !important;
    }
</style>
<div class="dashboard-fit-page w-full antialiased text-slate-900">

    {{-- Unified container: header + search + quick actions + content --}}
    <div class="dashboard-fit-shell bg-white border border-slate-200 p-5 md:p-6 space-y-6">

        {{-- 1. HEADER + SEARCH --}}
        <div class="flex flex-col xl:flex-row gap-4 items-start xl:items-center justify-between relative overflow-hidden">
            <div class="relative z-10">
                <p class="text-[10px] font-medium uppercase tracking-[0.14em] text-slate-400 mb-1">Staff Workspace</p>
                <h2 class="text-2xl font-bold text-slate-900 font-heading tracking-tight leading-none mb-2">Welcome back, {{ $staffFirstName }}</h2>
                <p class="text-xs text-slate-500 font-normal">Monitor case records, follow-ups, and today's schedules in one place.
                    <span class="inline-flex items-center text-[#9C5A1A] font-medium uppercase tracking-[0.12em] text-[10px] ml-1 bg-[#9C5A1A]/10 border border-[#9C5A1A]/20 px-2 py-[4px] rounded-md">Sabangan Caguioa - Main</span>
                </p>
            </div>

            {{-- Search Bar --}}
            <form method="GET" action="{{ route('funeral-cases.index') }}" class="relative w-full lg:max-w-sm group z-10">
                <input type="hidden" name="case_status" value="ACTIVE">
                <div class="relative">
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 text-base group-focus-within:text-[#9C5A1A] transition-colors pointer-events-none">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="q"
                        class="staff-search-input w-full pl-4 pr-11 py-3 bg-white border border-slate-200 rounded-xl text-sm font-normal text-slate-700 placeholder:text-slate-400 placeholder:font-normal focus:bg-white focus:border-[#9C5A1A] transition-all outline-none"
                        placeholder="Search by case code, client, or deceased name..."
                        aria-label="Search cases"
                        value="{{ request('q') }}">
                </div>
            </form>
</div>
        {{-- 2. QUICK ACTIONS --}}
        <section class="space-y-4">
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 lg:gap-4">
                @php
                    $actions = [
                        [
                            'route' => 'funeral-cases.index', 
                            'params' => ['tab' => 'active', 'case_status' => 'ACTIVE'], 
                            'label' => 'Active Cases', 
                            'desc' => 'Cases in Progress',
                            'icon' => 'M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 
                            'bg' => 'bg-white hover:border-[#9C5A1A] border-slate-200 group',
                            'text' => 'text-slate-800',
                            'desc_text' => 'text-slate-400',
                            'icon_bg' => 'bg-slate-50 text-slate-400 group-hover:bg-[#9C5A1A] group-hover:text-white'
                        ],
                        [
                            'route' => 'funeral-cases.index', 
                            'params' => ['tab' => 'completed'],
                            'label' => 'Completed Cases', 
                            'desc' => 'Completed Records', 
                            'icon' => 'M5 13l4 4L19 7', 
                            'bg' => 'bg-white hover:border-[#9C5A1A] border-slate-200 group',
                            'text' => 'text-slate-800',
                            'desc_text' => 'text-slate-400',
                            'icon_bg' => 'bg-slate-50 text-slate-400 group-hover:bg-[#9C5A1A] group-hover:text-white'
                        ],
                        [
                            'route' => 'funeral-cases.other-reports', 
                            'label' => 'Branch Reports', 
                            'desc' => 'All Branch Reports', 
                            'icon' => 'M8 17l4 4 4-4m0-5l-4-4-4 4', 
                            'bg' => 'bg-white hover:border-[#9C5A1A] border-slate-200 group',
                            'text' => 'text-slate-800',
                            'desc_text' => 'text-slate-400',
                            'icon_bg' => 'bg-slate-50 text-slate-400 group-hover:bg-[#9C5A1A] group-hover:text-white'
                        ],
                        [
                            'route' => 'payments.index', 
                            'label' => 'Case Payments', 
                            'desc' => 'Payment Status', 
                            'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 
                            'bg' => 'bg-white hover:border-[#9C5A1A] border-slate-200 group',
                            'text' => 'text-slate-800',
                            'desc_text' => 'text-slate-400',
                            'icon_bg' => 'bg-slate-50 text-slate-400 group-hover:bg-[#9C5A1A] group-hover:text-white'
                        ],
                        [
                            'route' => 'intake.main.create', 
                            'label' => 'New Case', 
                            'desc' => 'Create New Record', 
                            'icon' => 'M12 4v16m8-8H4', 
                            'bg' => 'bg-[#8a5a27] hover:bg-[#774a1f] border-transparent',
                            'text' => 'text-white',
                            'desc_text' => 'text-white/80',
                            'icon_bg' => 'bg-white/20 text-white'
                        ],
                        [
                            'route' => 'intake.other.create', 
                            'label' => 'Branch Report', 
                            'desc' => 'Record from Other Branch', 
                            'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5', 
                            'bg' => 'bg-slate-700 hover:bg-slate-800 border-transparent',
                            'text' => 'text-white',
                            'desc_text' => 'text-white/80',
                            'icon_bg' => 'bg-white/20 text-white',
                        ],
                    ];
                @endphp

                @foreach($actions as $action)
                    <a href="{{ route($action['route'], $action['params'] ?? []) }}" 
                       class="flex flex-col items-center text-center p-4 {{ $action['bg'] }} border rounded-xl hover:-translate-y-1 transition-all duration-300">
                        
                        {{-- Icon Container --}}
                        <div class="w-10 h-10 flex items-center justify-center rounded-lg {{ $action['icon_bg'] }} transition-colors">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $action['icon'] }}"/></svg>
                        </div>
                        
                        {{-- Main Label --}}
                        <h3 class="mt-3 text-xs font-medium uppercase tracking-widest leading-tight {{ $action['text'] }}">
                            {{ $action['label'] }}
                        </h3>
                        
                        {{-- Sub Description --}}
                        <p class="mt-1 text-[10px] font-medium uppercase tracking-widest {{ $action['desc_text'] }}">
                            {{ $action['desc'] }}
                        </p>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- 4. OPERATIONAL BOARD --}}
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        {{-- Recent Logs Table --}}
        <div class="xl:col-span-8 staff-surface-card overflow-hidden flex flex-col">
            <div class="staff-surface-card__head p-6 md:p-8 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-900 font-heading tracking-tight">Recent Cases</h3>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-widest mt-1">Latest Case Activity</p>
                </div>
                <a href="{{ route('funeral-cases.index') }}" class="staff-view-all-btn px-4 py-2 bg-slate-50 hover:bg-slate-100 text-slate-600 text-[11px] font-medium uppercase tracking-widest rounded-lg transition-colors border border-slate-200">
                    View All
                </a>
            </div>
            
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="staff-soft-table-head text-[11px] font-semibold text-slate-400 uppercase tracking-widest border-b border-slate-200">
                            <th class="px-4 md:px-5 py-3">Ref. Code</th>
                            <th class="px-4 md:px-5 py-3">Client / Representative</th>
                            <th class="px-4 md:px-5 py-3">Deceased Profile</th>
                            <th class="px-4 md:px-5 py-3 text-center">Status</th>
                            <th class="px-4 md:px-5 py-3 text-right">Service Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentCases as $case)
                            <tr class="staff-soft-row transition-all group">
                                <td class="px-4 md:px-5 py-4 text-sm font-heading font-bold text-slate-800">{{ $case->case_code }}</td>
                                <td class="px-4 md:px-5 py-4 text-sm font-normal text-slate-600">{{ $case->client->full_name ?? '-' }}</td>
                                <td class="px-4 md:px-5 py-4 text-sm font-normal text-slate-800 uppercase tracking-tight">{{ $case->deceased->full_name ?? '-' }}</td>
                                <td class="px-4 md:px-5 py-4 text-center">
                                    <span class="inline-flex rounded-md px-3 py-1.5 text-[9px] font-semibold uppercase tracking-widest {{ $case->case_status === 'ACTIVE' ? 'bg-[#9C5A1A]/10 text-[#9C5A1A] border border-[#9C5A1A]/20' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                        {{ $case->case_status }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-5 py-4 text-right text-sm font-normal text-emerald-600 font-sans">
                                    &#8369;{{ number_format((float) $case->total_amount, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-16 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-400">
                                        <i class="bi bi-inbox text-3xl mb-3 opacity-50"></i>
                                        <p class="text-[11px] font-medium uppercase tracking-widest">No recent cases yet</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($recentCases->hasPages())
                <div class="table-system-pagination recent-cases-pagination">
                    {{ $recentCases->onEachSide(1)->links() }}
                </div>
            @endif
        </div>

        {{-- Sidebar Widgets --}}
        <div class="xl:col-span-4 space-y-6">
            
            {{-- Needs Attention --}}
            <div class="staff-surface-card p-6 md:p-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xs font-medium uppercase tracking-widest text-slate-800">Needs Attention</h3>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('staff.reminders.index') }}" class="text-[10px] font-medium uppercase tracking-widest text-slate-500 hover:text-[#9C5A1A] transition-colors">View All</a>
                        <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                    </div>
                </div>
                
                <div class="space-y-3">
                    @php
                        $severityBorders = [
                            'danger' => 'border-l-red-500',
                            'warning' => 'border-l-orange-400',
                            'primary' => 'border-l-blue-500',
                            'info' => 'border-l-slate-300',
                        ];
                        $badgeColors = [
                            'danger' => 'bg-red-100 text-red-600 border-red-200',
                            'warning' => 'bg-orange-100 text-orange-700 border-orange-200',
                            'primary' => 'bg-blue-100 text-blue-700 border-blue-200',
                            'info' => 'bg-slate-100 text-slate-600 border-slate-200',
                        ];
                        $severityPills = [
                            'danger' => 'staff-attention-pill--danger',
                            'warning' => 'staff-attention-pill--warning',
                            'primary' => 'staff-attention-pill--primary',
                            'info' => 'staff-attention-pill--info',
                        ];
                    @endphp
                    @forelse(($attentionReminders ?? []) as $item)
                        <a href="{{ route('funeral-cases.show', $item['case_id']) }}" class="staff-soft-row flex items-center justify-between p-4 border rounded-2xl border-l-4 {{ $severityBorders[$item['severity']] ?? 'border-l-slate-200' }} transition-all group">
                            <div class="space-y-1">
                                <p class="text-sm font-medium text-slate-900 leading-none uppercase group-hover:text-[#9C5A1A] transition-colors">{{ $item['deceased_name'] }}</p>
                                <p class="text-[11px] font-medium text-slate-400 uppercase tracking-widest">{{ $item['case_code'] }}</p>
                                @if($item['date'])
                                    <p class="text-[11px] font-normal text-slate-500">{{ $item['date']->format('M d, Y') }}</p>
                                @endif
                            </div>
                            <span class="staff-attention-pill inline-flex items-center gap-2 px-3 py-1.5 text-[10px] font-medium uppercase tracking-widest rounded-lg border {{ $badgeColors[$item['severity']] ?? 'bg-slate-50 text-slate-500 border-slate-200' }} {{ $severityPills[$item['severity']] ?? 'staff-attention-pill--info' }}">
                                {{ $item['label'] }}
                                <i class="bi bi-chevron-right text-[11px]"></i>
                            </span>
                        </a>
                    @empty
                        <div class="staff-soft-empty p-6 border rounded-2xl text-center">
                            <i class="bi bi-shield-check text-emerald-500 text-2xl mb-2 block"></i>
                            <p class="text-[10px] font-medium text-slate-400 uppercase tracking-widest">No urgent follow-up</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Daily Interment Schedule --}}
            <div class="staff-surface-card p-6 md:p-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xs font-medium uppercase tracking-widest text-slate-800">Today's Schedule</h3>
                    <a href="{{ route('staff.reminders.index', ['alert_type' => 'service_today']) }}" class="text-[10px] font-medium uppercase tracking-widest text-slate-500 hover:text-[#9C5A1A] transition-colors">View All</a>
                </div>
                
                <div class="space-y-3">
                    @forelse(($todaySchedule ?? []) as $item)
                        <a href="{{ route('funeral-cases.show', $item['case_id']) }}" class="staff-soft-row flex items-center gap-4 p-4 border rounded-2xl border-l-4 border-l-[#9C5A1A] transition-all group">
                            <div class="text-center bg-[#f4f8fd] border border-[#dde7f3] p-2.5 rounded-xl min-w-[70px]">
                                <span class="block text-[8px] font-medium uppercase text-[#9C5A1A] leading-none mb-1">{{ $item['label'] }}</span>
                                @if($item['date']?->isStartOfDay())
                                    <span class="block text-[11px] font-medium text-slate-900 uppercase">{{ $item['date']->format('M d') }}</span>
                                @else
                                    <span class="block text-xs font-medium text-slate-900 uppercase">{{ $item['date']?->format('h:i A') }}</span>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-900 leading-none uppercase group-hover:text-[#9C5A1A] transition-colors">{{ $item['deceased_name'] }}</p>
                                <p class="text-[10px] font-medium text-slate-400 mt-1.5 uppercase tracking-widest">{{ $item['case_code'] }}</p>
                            </div>
                        </a>
                    @empty
                        <div class="staff-soft-empty p-6 border rounded-2xl text-center">
                            <i class="bi bi-calendar-x text-slate-300 text-2xl mb-2 block"></i>
                            <p class="text-[10px] font-medium text-slate-400 uppercase tracking-widest">No schedule today</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
