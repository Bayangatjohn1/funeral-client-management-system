@extends('layouts.panel')

@section('page_title', 'Executive Board')

@section('header_actions')
    <div class="hidden md:flex items-center gap-2">
        <span class="px-3 py-1.5 bg-slate-50 text-slate-500 border border-slate-200 rounded-full text-[9px] font-black uppercase tracking-widest flex items-center gap-2 shadow-sm">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            System Updated Data
        </span>
    </div>
@endsection

@section('content')
<div class="w-full mx-auto space-y-8 pb-16 antialiased text-slate-900 animate-float-up">

    {{-- 1. PREMIUM BRANCH TABS (Segmented Control) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-900 font-heading tracking-tight leading-none mb-1">Financial Snapshot</h2>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Real-time consolidated data</p>
        </div>

        <div class="flex overflow-x-auto gap-1.5 p-1.5 bg-slate-100/80 backdrop-blur-md border border-slate-200 rounded-2xl w-max shadow-inner">
            <a href="{{ route('owner.dashboard', array_merge(request()->query(), ['branch_id' => null])) }}"
               class="px-5 py-2 rounded-xl text-[11px] font-black uppercase tracking-widest transition-all {{ !$branchId ? 'bg-white text-slate-900 shadow-sm border border-slate-200/50' : 'text-slate-500 hover:text-slate-800 hover:bg-white/50' }}">
                All Branches
            </a>
            @foreach($branches ?? [] as $branch)
                <a href="{{ route('owner.dashboard', array_merge(request()->query(), ['branch_id' => $branch->id])) }}"
                   class="px-5 py-2 rounded-xl text-[11px] font-black uppercase tracking-widest transition-all {{ (string) $branchId === (string) $branch->id ? 'bg-white text-slate-900 shadow-sm border border-slate-200/50' : 'text-slate-500 hover:text-slate-800 hover:bg-white/50' }}">
                    {{ $branch->branch_name }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- 2. MAIN FINANCIAL HERO (The "Money" Row) --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 lg:gap-8">
        
        {{-- Total Service Amount (Ultimate Hero Card) --}}
        <div class="xl:col-span-1 bg-gradient-to-br from-slate-900 via-slate-800 to-[#1a0e05] border border-slate-800 rounded-[2rem] p-8 lg:p-10 shadow-2xl relative overflow-hidden group flex flex-col justify-between min-h-[200px]">
            <div class="absolute -right-10 top-0 w-64 h-64 bg-[#d6b073]/10 rounded-full blur-3xl group-hover:bg-[#d6b073]/20 transition-all duration-700"></div>
            
            <div class="relative z-10 flex items-center justify-between mb-4">
                <p class="text-[10px] uppercase tracking-[0.2em] text-[#d6b073] font-black">Total Service Amount</p>
                <i class="bi bi-graph-up-arrow text-[#d6b073] text-xl opacity-50 group-hover:opacity-100 group-hover:-translate-y-1 transition-all"></i>
            </div>
            <div class="relative z-10">
                <h4 class="text-5xl lg:text-6xl font-black text-white font-heading tracking-tight">
                    <span class="text-slate-500 font-sans mr-1">₱</span>{{ number_format((float) ($totalSales ?? 0), 2) }}
                </h4>
            </div>
        </div>

        {{-- Collected & Outstanding (Secondary Hero Cards) --}}
        <div class="xl:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
            {{-- Collected Amount --}}
            <div class="bg-white border border-slate-200 rounded-[2rem] p-8 shadow-sm relative overflow-hidden group flex flex-col justify-between hover:border-slate-300 transition-colors">
                <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-emerald-500/5 rounded-full blur-2xl group-hover:bg-emerald-500/10 transition-all duration-700"></div>
                <div class="relative z-10 flex items-center justify-between mb-6">
                    <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-black">Collected Amount</p>
                    <div class="w-10 h-10 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-600">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
                <div class="relative z-10">
                    <h4 class="text-4xl lg:text-5xl font-black text-slate-900 font-heading tracking-tight">
                        <span class="text-slate-300 font-sans mr-1">₱</span>{{ number_format((float) ($totalCollected ?? 0), 2) }}
                    </h4>
                </div>
            </div>

            {{-- Outstanding Balance --}}
            <div class="bg-white border border-slate-200 rounded-[2rem] p-8 shadow-sm relative overflow-hidden group flex flex-col justify-between hover:border-rose-200 transition-colors">
                <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-rose-500/5 rounded-full blur-2xl group-hover:bg-rose-500/10 transition-all duration-700"></div>
                <div class="relative z-10 flex items-center justify-between mb-6">
                    <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-black">Outstanding Balance</p>
                    <div class="w-10 h-10 rounded-full bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-500 group-hover:bg-rose-500 group-hover:text-white transition-all">
                        <i class="bi bi-exclamation-circle"></i>
                    </div>
                </div>
                <div class="relative z-10">
                    <h4 class="text-4xl lg:text-5xl font-black text-rose-700 font-heading tracking-tight">
                        <span class="text-slate-300 font-sans mr-1">₱</span>{{ number_format((float) ($totalOutstanding ?? 0), 2) }}
                    </h4>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. OPERATIONAL VOLUME GRID --}}
    <section class="space-y-4 pt-4">
        <h3 class="px-2 text-xs font-black uppercase tracking-[0.2em] text-slate-400">Case Operation Status</h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 lg:gap-5">
            @php
                // PROFESSIONAL PALETTE: Subdued, elegant colors instead of bright traffic lights
                $opStats = [
                    ['label' => 'Total Cases', 'val' => $totalCases ?? 0, 'color' => 'text-slate-900', 'border' => 'border-b-slate-800'],
                    ['label' => 'Ongoing Services', 'val' => $ongoingCases ?? 0, 'color' => 'text-slate-700', 'border' => 'border-b-slate-400'],
                    ['label' => 'Fully Paid', 'val' => $paidCases ?? 0, 'color' => 'text-emerald-700', 'border' => 'border-b-emerald-600'],
                    ['label' => 'Partial Payments', 'val' => $partialCases ?? 0, 'color' => 'text-[#d6b073]', 'border' => 'border-b-[#d6b073]'],
                    ['label' => 'Unpaid Accounts', 'val' => $unpaidCases ?? 0, 'color' => 'text-rose-700', 'border' => 'border-b-rose-600'],
                ];
            @endphp

            @foreach($opStats as $s)
                <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm border-b-[4px] {{ $s['border'] }} hover:-translate-y-1 hover:shadow-md transition-all duration-300 flex flex-col justify-center">
                    <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-2 block">{{ $s['label'] }}</span>
                    <h4 class="text-3xl font-black font-heading {{ $s['color'] }} tracking-tight leading-none">{{ $s['val'] }}</h4>
                </div>
            @endforeach
        </div>
    </section>

    {{-- 4. COMPARATIVE ANALYTICS --}}
    <div class="bg-white border border-slate-200 rounded-[2.5rem] p-8 lg:p-10 shadow-sm mt-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-10 border-b border-slate-100 pb-6">
            <div>
                <h3 class="text-xl font-black text-slate-900 font-heading tracking-tight">Summary of Comparative Analytics</h3>
                <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-widest">Branch vs Branch Performance Matrix</p>
            </div>
            <a href="{{ route('owner.analytics', ['branch_id' => $branchId]) }}" class="inline-flex items-center justify-center px-6 py-3 bg-slate-50 border border-slate-200 text-[11px] font-black text-slate-600 uppercase tracking-widest hover:text-white hover:bg-slate-900 hover:border-slate-900 rounded-xl transition-all shadow-sm group">
                Detailed Analytics 
                <i class="bi bi-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
            </a>
        </div>

        @php
            $branchCardsCollection = collect($branchCards ?? []);
            $maxSales = max(1, (float) ($branchCardsCollection->max('sales') ?? 1));
            
            // PROFESSIONAL CORPORATE PALETTE FOR CHARTS
            // Ginagamit ang Deep Slate, Brand Brown, Muted Gold, at Steel Gray.
            $corporateColors = ['#0f172a', '#9C5A1A', '#d6b073', '#475569', '#94a3b8'];
        @endphp
        
        <div class="space-y-8">
            @forelse($branchCardsCollection as $index => $row)
                @php
                    $sales = (float) ($row['sales'] ?? 0);
                    $width = (int) round(($sales / $maxSales) * 100);
                    $code = $row['branch']->branch_code ?? 'N/A';
                    $name = $row['branch']->branch_name ?? 'Branch';
                    
                    // Pinipili ang kulay mula sa Corporate Palette array pataas
                    $barColor = $corporateColors[$index % count($corporateColors)];
                @endphp
                <div class="group">
                    <div class="flex items-end justify-between mb-3">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-0.5">{{ $code }}</span>
                            <span class="text-sm font-black text-slate-800 tracking-wide">{{ $name }}</span>
                        </div>
                        <span class="text-2xl font-black text-slate-900 font-heading">
                            <span class="text-slate-300 text-lg font-sans">₱</span>{{ number_format($sales, 2) }}
                        </span>
                    </div>
                    <div class="w-full h-4 rounded-full bg-slate-100 overflow-hidden shadow-inner relative">
                        {{-- Solid Elegant Colors for Data Visualization --}}
                        <div class="h-full rounded-full transition-all duration-1000 relative" 
                             style="width: {{ max(2, $width) }}%; background-color: {{ $barColor }};">
                             <div class="absolute inset-0 bg-gradient-to-r from-white/10 to-transparent"></div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-10">
                    <i class="bi bi-bar-chart-steps text-4xl text-slate-200 mb-3 block"></i>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No branch analytics available</p>
                </div>
            @endforelse
        </div>
    </div>

</div>
@endsection


