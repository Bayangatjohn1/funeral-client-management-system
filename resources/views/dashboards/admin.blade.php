@extends('layouts.panel')

@section('page_title', 'Administration Overview')
@section('page_desc', 'Monitor operations, branch performance, and system status.')

@section('header_actions')
@endsection

@section('content')
<div class="dashboard-fit-page">
<div class="admin-dashboard-shell w-full space-y-6 antialiased text-slate-900 animate-float-up">

    @if($errors->any())
        <div class="bg-red-50 border border-red-100 p-4 text-red-700 rounded-2xl text-[11px] font-black uppercase tracking-widest flex items-center gap-3 shadow-sm">
            <i class="bi bi-exclamation-octagon-fill text-lg"></i>
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Filters + Quick Actions --}}
    <div class="card-custom admin-top-controls">
        <form method="GET" action="{{ url('/admin') }}" class="admin-top-controls-form">
            <select name="branch_id" onchange="this.form.submit()" class="input-custom w-48">
                <option value="">HQ &amp; All Branches</option>
                @foreach($branches ?? [] as $branch)
                    <option value="{{ $branch->id }}" {{ (string) ($selectedBranchId ?? '') === (string) $branch->id ? 'selected' : '' }}>
                        {{ $branch->branch_code }} - {{ $branch->branch_name }}
                    </option>
                @endforeach
            </select>

            <select name="date_filter" onchange="this.form.submit()" class="input-custom w-40">
                <option value="all" {{ ($selectedDateFilter ?? 'this_month') === 'all' ? 'selected' : '' }}>All Time</option>
                <option value="today" {{ ($selectedDateFilter ?? 'this_month') === 'today' ? 'selected' : '' }}>Today</option>
                <option value="this_week" {{ ($selectedDateFilter ?? 'this_month') === 'this_week' ? 'selected' : '' }}>This Week</option>
                <option value="this_month" {{ ($selectedDateFilter ?? 'this_month') === 'this_month' ? 'selected' : '' }}>This Month</option>
                <option value="this_year" {{ ($selectedDateFilter ?? 'this_month') === 'this_year' ? 'selected' : '' }}>This Year</option>
            </select>

            <div class="flex items-center gap-2">
                <a href="{{ url('/admin') }}" class="btn-secondary-custom btn-sm">Reset</a>
            </div>
        </form>

        <div class="admin-top-controls-actions">
            <a href="{{ route('admin.users.create', ['return_to' => request()->fullUrl()]) }}" class="btn-secondary-custom btn-sm flex items-center gap-2">
                <i class="bi bi-person-plus-fill text-sm"></i> Add User
            </a>
            <a href="{{ route('admin.branches.create', ['return_to' => request()->fullUrl()]) }}" class="btn-primary-custom btn-sm flex items-center gap-2">
                <i class="bi bi-diagram-3-fill text-sm"></i> New Branch
            </a>
        </div>
    </div>

    {{-- 2. FINANCIAL HERO CARDS (Premium Fintech Look) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 lg:gap-6 admin-section-block">
        {{-- Collected Amount (Dark Premium) --}}
        <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 border border-slate-800 rounded-[2.5rem] p-8 lg:p-10 shadow-2xl relative overflow-hidden group flex flex-col justify-between min-h-[220px]">
            {{-- Abstract Background Elements --}}
            <div class="absolute -right-10 top-0 w-64 h-64 bg-gradient-to-bl from-[#9C5A1A]/30 to-transparent rounded-full blur-3xl group-hover:bg-[#9C5A1A]/40 transition-all duration-700"></div>
            <div class="absolute right-10 bottom-10 opacity-10 transform group-hover:scale-110 group-hover:rotate-12 transition-all duration-700">
                <i class="bi bi-shield-check text-9xl text-white"></i>
            </div>

            <div class="relative z-10 flex items-center justify-between mb-8">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 border border-white/10 backdrop-blur-md">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="text-[9px] font-black text-emerald-300 uppercase tracking-widest">System Updated Data</span>
                </div>
                <div class="w-12 h-12 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-white/50 text-xl">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>

            <div class="relative z-10">
                <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-black mb-2">Collected Amount</p>
                <h4 class="text-5xl lg:text-6xl font-black text-white font-heading tracking-tight">
                    <span class="text-emerald-400 font-sans mr-1">₱</span>{{ number_format((float) ($totalCollected ?? 0), 2) }}
                </h4>
            </div>
        </div>

        {{-- Outstanding Balance --}}
        <div class="card-custom relative flex flex-col justify-between min-h-[220px]">
            <div class="flex items-center justify-between mb-6">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-100 border border-slate-200">
                    <i class="bi bi-exclamation-circle-fill text-slate-500 text-[10px]"></i>
                    <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-[0.16em]">Outstanding</span>
                </div>
                <div class="w-12 h-12 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 text-xl">
                    <i class="bi bi-graph-down-arrow"></i>
                </div>
            </div>

            <div>
                <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500 font-semibold mb-2">Total Outstanding Balance</p>
                <h4 class="text-5xl lg:text-6xl font-black text-red-600 font-heading tracking-tight">
                    <span class="font-sans mr-1">₱</span>{{ number_format((float) ($totalOutstanding ?? 0), 2) }}
                </h4>
            </div>
        </div>
    </div>

    {{-- 3. CASE METRICS --}}
    <section class="space-y-3 section admin-section-block">
        <h3 class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Operational Health Metrics</h3>
        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
            @php
                $caseStats = [
                    ['label' => 'Total Record', 'val' => $totalCases ?? 0, 'icon' => 'bi-folder2-open', 'color' => 'text-slate-900'],
                    ['label' => 'Ongoing', 'val' => $ongoingCases ?? 0, 'icon' => 'bi-arrow-repeat', 'color' => 'text-slate-900'],
                    ['label' => 'Paid in Full', 'val' => $paidCases ?? 0, 'icon' => 'bi-check-circle', 'color' => 'text-emerald-600'],
                    ['label' => 'Partial Pay', 'val' => $partialCases ?? 0, 'icon' => 'bi-pie-chart', 'color' => 'text-amber-600'],
                    ['label' => 'Unsettled', 'val' => $unpaidCases ?? 0, 'icon' => 'bi-exclamation-triangle', 'color' => 'text-red-600'],
                    ['label' => 'Total Service Amount', 'val' => number_format((float) ($totalSales ?? 0), 2), 'icon' => 'bi-cash-coin', 'color' => 'text-emerald-600', 'is_money' => true],
                ];
            @endphp

            @foreach($caseStats as $s)
                <div class="stat-card flex flex-col gap-3 min-h-[140px]">
                    <div class="w-9 h-9 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-base">
                        <i class="bi {{ $s['icon'] }}"></i>
                    </div>
                    <div>
                        <div class="stat-label mb-1">{{ $s['label'] }}</div>
                        <div class="stat-value {{ $s['color'] }}">
                            {{ isset($s['is_money']) ? '₱' : '' }}{{ $s['val'] }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- 4. BRANCH PERFORMANCE BOARD --}}
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 lg:gap-6 section admin-section-block">
        
        {{-- Left: Elegant Service Amount List --}}
        <div class="xl:col-span-7 card-custom flex flex-col">
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-[12px] font-black uppercase tracking-widest text-slate-800 font-heading">Branch Service Amount Leadership</h3>
                <i class="bi bi-trophy text-xl text-amber-400"></i>
            </div>
            
            <div class="space-y-4 flex-1">
                @foreach($branchRevenueCards ?? [] as $index => $card)
                    <div class="flex items-center justify-between p-4 rounded-2xl border border-slate-100 hover:bg-slate-50 transition-all group">
                        <div class="flex items-center gap-4">
                            <div class="rank-badge {{ $index === 0 ? 'bg-[var(--accent)]' : '' }}">#{{ $index + 1 }}</div>
                            <div>
                                <h5 class="text-sm font-black text-slate-900 tracking-tight">{{ $card['branch']->branch_name ?? 'Branch' }}</h5>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-0.5">{{ $card['branch']->branch_code ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <h4 class="text-xl font-black text-slate-900 font-heading">₱{{ number_format((float) ($card['sales'] ?? 0), 2) }}</h4>
                            <p class="text-[9px] font-bold text-emerald-500 uppercase tracking-widest mt-0.5 group-hover:animate-pulse">Collected Amount</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Right: Case Volume Progress --}}
        <div class="xl:col-span-5 card-custom flex flex-col">
            <h3 class="text-[12px] font-black uppercase tracking-widest text-slate-800 mb-8 font-heading">Case Volume Distribution</h3>
            
            <div class="space-y-8 flex-1 flex flex-col justify-center">
                @php
                    $volumeCollection = collect($caseVolume ?? []);
                    $maxVolume = max(1, (float) $volumeCollection->max('count'));
                @endphp
                
                @forelse($volumeCollection as $row)
                    @php
                        $count = is_array($row) ? ($row['count'] ?? 0) : ($row->count ?? 0);
                        $branchCode = is_array($row) ? ($row['branch_code'] ?? '') : ($row->branch_code ?? '');
                        $branchName = is_array($row) ? ($row['branch_name'] ?? '') : ($row->branch_name ?? '');
                        $width = $maxVolume > 0 ? ($count / $maxVolume) * 100 : 0;
                    @endphp
                    <div class="group">
                        <div class="flex items-end justify-between mb-2">
                            <div>
                                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 block mb-0.5">{{ $branchCode }}</span>
                                <span class="text-xs font-black text-slate-900 truncate pr-4">{{ $branchName }}</span>
                            </div>
                            <span class="text-lg font-black text-[#9C5A1A] font-heading">{{ $count }}</span>
                        </div>
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-[#22324A] to-[#1A2636] transition-all duration-1000 w-0 group-hover:brightness-110 relative" style="width: {{ $width }}%">
                                <div class="absolute top-0 right-0 bottom-0 w-8 bg-gradient-to-l from-white/30 to-transparent"></div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <div class="w-16 h-16 mx-auto bg-slate-50 rounded-full flex items-center justify-center text-slate-300 text-2xl mb-4">
                            <i class="bi bi-bar-chart"></i>
                        </div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No branch data available</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- 5. SYSTEM AUDIT TIMELINE (Modern SaaS Look) --}}
    <div class="bg-white border border-slate-100 rounded-[2.5rem] p-8 lg:p-10 shadow-[0_8px_30px_rgb(0,0,0,0.03)]">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-8 gap-4">
            <div>
                <h3 class="text-[12px] font-black uppercase tracking-widest text-slate-800 font-heading">System Audit Log</h3>
                <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-widest">Monitoring User Actions & Security</p>
            </div>
            <a href="{{ route('admin.cases.index') }}" class="inline-flex items-center justify-center px-5 py-2.5 bg-slate-50 border border-slate-200 text-[11px] font-black text-slate-500 uppercase tracking-widest hover:text-slate-900 hover:bg-slate-100 rounded-full transition-colors w-full sm:w-auto">
                Open Master Records
            </a>
        </div>

        <div class="relative max-w-4xl mx-auto">
            {{-- Vertical Line --}}
            <div class="absolute left-[23px] top-4 bottom-4 w-px bg-slate-100"></div>

            <div class="space-y-6 relative">
                @php
                    $mockupLogs = [
                        ['time' => '10 mins ago', 'user' => 'Admin Juan', 'action' => 'Approved package void request for Case #1029', 'icon' => 'bi-shield-check', 'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50', 'ring' => 'ring-emerald-50'],
                        ['time' => '1 hour ago', 'user' => 'Staff Maria', 'action' => 'Encoded initial payment (₱15,000) for Case #1030', 'icon' => 'bi-cash-stack', 'color' => 'text-blue-500', 'bg' => 'bg-blue-50', 'ring' => 'ring-blue-50'],
                        ['time' => '3 hours ago', 'user' => 'Owner', 'action' => 'Updated Executive Package pricing matrix', 'icon' => 'bi-tags-fill', 'color' => 'text-[#9C5A1A]', 'bg' => 'bg-[#9C5A1A]/10', 'ring' => 'ring-[#9C5A1A]/5'],
                        ['time' => 'Yesterday', 'user' => 'Staff Pedro', 'action' => 'Created new intake record for Deceased: Dela Cruz', 'icon' => 'bi-file-earmark-plus-fill', 'color' => 'text-slate-500', 'bg' => 'bg-slate-100', 'ring' => 'ring-slate-50'],
                    ];
                @endphp

                @forelse($auditLogs ?? $mockupLogs as $log)
                    @php
                        $isArray = is_array($log);
                        $logAction = $isArray ? ($log['action'] ?? 'No action') : ($log->action ?? 'No action');
                        $logUser = $isArray ? ($log['user'] ?? 'System') : ($log->user ?? 'System');
                        $logTime = $isArray ? ($log['time'] ?? '-') : ($log->time ?? '-');
                        $logIcon = $isArray ? ($log['icon'] ?? 'bi-journal-text') : ($log->icon ?? 'bi-journal-text');
                        $logColor = $isArray ? ($log['color'] ?? 'text-slate-500') : ($log->color ?? 'text-slate-500');
                        $logBg = $isArray ? ($log['bg'] ?? 'bg-white') : ($log->bg ?? 'bg-white');
                        $logRing = $isArray ? ($log['ring'] ?? 'ring-white') : ($log->ring ?? 'ring-white');
                    @endphp
                    <div class="flex items-start gap-5 group">
                        {{-- Timeline Node --}}
                        <div class="relative z-10 w-12 h-12 rounded-full {{ $logBg }} flex items-center justify-center {{ $logColor }} text-lg shrink-0 shadow-sm ring-4 {{ $logRing }} group-hover:scale-110 transition-transform">
                            <i class="bi {{ $logIcon }}"></i>
                        </div>
                        
                        {{-- Log Content --}}
                        <div class="flex-1 bg-white border border-slate-100 rounded-2xl p-4 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-bold text-slate-900">{{ $logAction }}</p>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">
                                        Action by <span class="text-slate-700">{{ $logUser }}</span>
                                    </p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-slate-50 border border-slate-100 text-[9px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">
                                    {{ $logTime }}
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="pl-16 py-4 text-slate-400">
                        <p class="text-[10px] font-black uppercase tracking-widest">No recent system activities.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- 6. SYSTEM STATUS FOOTER --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 pt-0 admin-section-block">
        @php
            $configStats = [
                ['label' => 'Network Branches', 'val' => $branchCount ?? 0, 'icon' => 'bi-building'],
                ['label' => 'Active Terminals', 'val' => $activeStaffCount ?? 0, 'icon' => 'bi-laptop'],
                ['label' => 'Service Catalogs', 'val' => $activePackageCount ?? 0, 'icon' => 'bi-layers'],
            ];
        @endphp
        
        @foreach($configStats as $cs)
            <div class="bg-transparent border border-slate-200/60 rounded-[2rem] p-6 flex items-center gap-5 hover:bg-white hover:shadow-sm transition-all cursor-default">
                <div class="w-12 h-12 rounded-2xl bg-white border border-slate-100 flex items-center justify-center text-slate-400 text-xl shadow-sm">
                    <i class="bi {{ $cs['icon'] }}"></i>
                </div>
                <div>
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-0.5">{{ $cs['label'] }}</p>
                    <h4 class="text-xl font-black text-slate-900 font-heading">{{ $cs['val'] }}</h4>
                </div>
            </div>
        @endforeach
    </div>

</div>
</div>
@endsection
