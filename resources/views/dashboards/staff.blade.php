@extends('layouts.panel')

{{-- 1. CLEANER HEADER TITLE --}}
@section('page_title', 'Overview')

@section('content')
<div class="w-full mx-auto space-y-8 pb-12 antialiased text-slate-900">
    
    {{-- 1. HEADER SECTION --}}
    <div class="flex flex-col xl:flex-row gap-6 items-start xl:items-center justify-between bg-white p-6 md:p-8 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
        {{-- Subtle background accent --}}
        <div class="absolute right-0 top-0 w-64 h-full bg-gradient-to-l from-slate-50 to-transparent pointer-events-none"></div>

        <div class="relative z-10">
            <h2 class="text-3xl font-black text-slate-900 font-heading tracking-tight leading-none mb-2"> Welcome to dashboard</h2>
            <p class="text-sm text-slate-500 font-medium">Case Work & Records <span class="text-[#9C5A1A] font-black uppercase tracking-widest text-[11px] ml-1 bg-[#9C5A1A]/10 px-2 py-1 rounded-md">Sabangan Caguioa - Main</span></p>
        </div>

        {{-- Search Bar --}}
        <form method="GET" action="{{ route('funeral-cases.index') }}" class="relative w-full lg:max-w-md group z-10">
            <input type="hidden" name="case_status" value="ACTIVE">
            <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg group-focus-within:text-[#9C5A1A] transition-colors">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" name="q"
                    class="w-full pl-12 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-[#9C5A1A] focus:ring-4 focus:ring-[#9C5A1A]/10 transition-all outline-none shadow-sm"
                    placeholder="Search Deceased or Ref Code..."
                    value="{{ request('q') }}">
            </div>
        </form>
    </div>

    {{-- 2. QUICK ACTIONS --}}
    {{-- 2. QUICK ACTIONS --}}
    <section class="space-y-4">
        
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 lg:gap-5">
            @php
                $actions = [
                    // 1. NORMAL BUTTONS: White Cards
                    [
                        'route' => 'funeral-cases.index', 
                        'params' => ['case_status' => 'ACTIVE'], 
                        'label' => 'Active Cases', 
                        'desc' => 'Cases in Progress',
                        'icon' => 'M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 
                        'bg' => 'bg-white hover:border-[#9C5A1A] border-slate-200 shadow-sm group',
                        'text' => 'text-slate-800',
                        'desc_text' => 'text-slate-400',
                        'icon_bg' => 'bg-slate-50 text-slate-400 group-hover:bg-[#9C5A1A] group-hover:text-white'
                    ],
                    [
                        'route' => 'funeral-cases.completed', 
                        'label' => 'Completed Cases', 
                        'desc' => 'Completed Records', 
                        'icon' => 'M5 13l4 4L19 7', 
                        'bg' => 'bg-white hover:border-[#9C5A1A] border-slate-200 shadow-sm group',
                        'text' => 'text-slate-800',
                        'desc_text' => 'text-slate-400',
                        'icon_bg' => 'bg-slate-50 text-slate-400 group-hover:bg-[#9C5A1A] group-hover:text-white'
                    ],
                    [
                        'route' => 'funeral-cases.other-reports', 
                        'label' => 'Branch Reports', 
                        'desc' => 'All Branch Reports', 
                        'icon' => 'M8 17l4 4 4-4m0-5l-4-4-4 4', 
                        'bg' => 'bg-white hover:border-[#9C5A1A] border-slate-200 shadow-sm group',
                        'text' => 'text-slate-800',
                        'desc_text' => 'text-slate-400',
                        'icon_bg' => 'bg-slate-50 text-slate-400 group-hover:bg-[#9C5A1A] group-hover:text-white',
                        'cond' => !empty($canEncodeAnyBranch)
                    ],
                    [
                        'route' => 'payments.index', 
                        'label' => 'Case Payments', 
                        'desc' => 'Payment Status', 
                        'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 
                        'bg' => 'bg-white hover:border-[#9C5A1A] border-slate-200 shadow-sm group',
                        'text' => 'text-slate-800',
                        'desc_text' => 'text-slate-400',
                        'icon_bg' => 'bg-slate-50 text-slate-400 group-hover:bg-[#9C5A1A] group-hover:text-white',
                        'cond' => !empty($canEncodeAnyBranch)
                    ],
                    // 2. HIGHLIGHTED BUTTON: New Case (Brand Color) - moved near end
                    [
                        'route' => 'intake.main.create', 
                        'label' => 'New Case', 
                        'desc' => 'Create New Record', 
                        'icon' => 'M12 4v16m8-8H4', 
                        'bg' => 'bg-[#9C5A1A] hover:bg-[#6a3003] border-transparent shadow-md',
                        'text' => 'text-white',
                        'desc_text' => 'text-white/80',
                        'icon_bg' => 'bg-white/20 text-white'
                    ],
                    // 3. HIGHLIGHTED BUTTON: Branch Report (Dark Slate) - now last
                    [
                        'route' => 'intake.other.create', 
                        'label' => 'Branch Report', 
                        'desc' => 'Record from Other Branch', 
                        'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5', 
                        'bg' => 'bg-slate-800 hover:bg-slate-900 border-transparent shadow-md',
                        'text' => 'text-white',
                        'desc_text' => 'text-white/80',
                        'icon_bg' => 'bg-white/20 text-white',
                        'cond' => !empty($canEncodeAnyBranch)
                    ],
                ];
            @endphp

            @foreach($actions as $action)
                @if(!isset($action['cond']) || $action['cond'])
                <a href="{{ route($action['route'], $action['params'] ?? []) }}" 
                   class="flex flex-col items-center text-center p-5 {{ $action['bg'] }} border rounded-2xl hover:-translate-y-1 transition-all duration-300">
                    
                    {{-- Icon Container --}}
                    <div class="w-12 h-12 flex items-center justify-center rounded-xl {{ $action['icon_bg'] }} transition-colors">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $action['icon'] }}"/></svg>
                    </div>
                    
                    {{-- Main Label --}}
                    <h3 class="mt-4 text-xs font-black uppercase tracking-widest leading-tight {{ $action['text'] }}">
                        {{ $action['label'] }}
                    </h3>
                    
                    {{-- Sub Description --}}
                    <p class="mt-1 text-[11px] font-bold uppercase tracking-widest {{ $action['desc_text'] }}">
                        {{ $action['desc'] }}
                    </p>
                </a>
                @endif
            @endforeach
        </div>
    </section>
        {{-- 4. OPERATIONAL BOARD --}}
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        {{-- Recent Logs Table --}}
        <div class="xl:col-span-8 bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden flex flex-col">
            <div class="p-6 md:p-8 border-b border-slate-100 flex items-center justify-between bg-white">
                <div>
                    <h3 class="text-lg font-black text-slate-900 font-heading tracking-tight">Recent Cases</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Latest Case Activity</p>
                </div>
                <a href="{{ route('funeral-cases.index') }}" class="px-4 py-2 bg-slate-50 hover:bg-slate-100 text-slate-600 text-[11px] font-black uppercase tracking-widest rounded-lg transition-colors border border-slate-200">
                    View All
                </a>
            </div>
            
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-[11px] font-black text-slate-400 bg-slate-50 uppercase tracking-widest border-b border-slate-200">
                            <th class="px-6 md:px-8 py-4 whitespace-nowrap">Ref. Code</th>
                            <th class="px-6 md:px-8 py-4 whitespace-nowrap">Client / Representative</th>
                            <th class="px-6 md:px-8 py-4 whitespace-nowrap">Deceased Profile</th>
                            <th class="px-6 md:px-8 py-4 text-center whitespace-nowrap">Status</th>
                            <th class="px-6 md:px-8 py-4 text-right whitespace-nowrap">Service Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse(($recentCases ?? []) as $case)
                            <tr class="hover:bg-slate-50/80 transition-all group">
                                <td class="px-6 md:px-8 py-5 text-sm font-black text-slate-900 whitespace-nowrap">{{ $case->case_code }}</td>
                                <td class="px-6 md:px-8 py-5 text-sm font-bold text-slate-500 whitespace-nowrap">{{ $case->client->full_name ?? '-' }}</td>
                                <td class="px-6 md:px-8 py-5 text-sm font-black text-slate-900 uppercase tracking-tight whitespace-nowrap">{{ $case->deceased->full_name ?? '-' }}</td>
                                <td class="px-6 md:px-8 py-5 text-center whitespace-nowrap">
                                    <span class="inline-flex rounded-md px-3 py-1.5 text-[9px] font-black uppercase tracking-widest {{ $case->case_status === 'ACTIVE' ? 'bg-[#9C5A1A]/10 text-[#9C5A1A] border border-[#9C5A1A]/20' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                        {{ $case->case_status }}
                                    </span>
                                </td>
                                <td class="px-6 md:px-8 py-5 text-right text-sm font-black text-emerald-600 font-sans whitespace-nowrap">
                                    &#8369;{{ number_format((float) $case->total_amount, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-16 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-400">
                                        <i class="bi bi-inbox text-3xl mb-3 opacity-50"></i>
                                        <p class="text-[11px] font-black uppercase tracking-widest">No recent cases yet</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Sidebar Widgets --}}
        <div class="xl:col-span-4 space-y-6">
            
            {{-- Needs Attention --}}
            <div class="bg-white border border-slate-200 rounded-3xl p-6 md:p-8 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xs font-black uppercase tracking-widest text-slate-800">Needs Attention</h3>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('staff.reminders.index') }}" class="text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-[#9C5A1A] transition-colors">View All</a>
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
                    @endphp
                    @forelse(($attentionReminders ?? []) as $item)
                        <a href="{{ route('funeral-cases.show', $item['case_id']) }}" class="flex items-center justify-between p-4 bg-white border border-slate-200 rounded-2xl border-l-4 {{ $severityBorders[$item['severity']] ?? 'border-l-slate-200' }} hover:shadow-md transition-all group">
                            <div class="space-y-1">
                                <p class="text-sm font-black text-slate-900 leading-none uppercase group-hover:text-[#9C5A1A] transition-colors">{{ $item['deceased_name'] }}</p>
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">{{ $item['case_code'] }}</p>
                                @if($item['date'])
                                    <p class="text-[11px] font-semibold text-slate-500">{{ $item['date']->format('M d, Y') }}</p>
                                @endif
                            </div>
                            <span class="inline-flex items-center gap-2 px-3 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-lg border {{ $badgeColors[$item['severity']] ?? 'bg-slate-50 text-slate-500 border-slate-200' }}">
                                {{ $item['label'] }}
                                <i class="bi bi-chevron-right text-[11px]"></i>
                            </span>
                        </a>
                    @empty
                        <div class="p-6 bg-slate-50 border border-slate-100 rounded-2xl text-center">
                            <i class="bi bi-shield-check text-emerald-500 text-2xl mb-2 block"></i>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No urgent follow-up</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Daily Interment Schedule --}}
            <div class="bg-white border border-slate-200 rounded-3xl p-6 md:p-8 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xs font-black uppercase tracking-widest text-slate-800">Today's Schedule</h3>
                    <a href="{{ route('staff.reminders.index', ['alert_type' => 'service_today']) }}" class="text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-[#9C5A1A] transition-colors">View All</a>
                </div>
                
                <div class="space-y-3">
                    @forelse(($todaySchedule ?? []) as $item)
                        <a href="{{ route('funeral-cases.show', $item['case_id']) }}" class="flex items-center gap-4 p-4 bg-white border border-slate-200 rounded-2xl border-l-4 border-l-[#9C5A1A] hover:shadow-md transition-all group">
                            <div class="text-center bg-slate-50 border border-slate-100 p-2.5 rounded-xl min-w-[70px]">
                                <span class="block text-[8px] font-black uppercase text-[#9C5A1A] leading-none mb-1">{{ $item['label'] }}</span>
                                @if($item['date']?->isStartOfDay())
                                    <span class="block text-[11px] font-black text-slate-900 uppercase">{{ $item['date']->format('M d') }}</span>
                                @else
                                    <span class="block text-xs font-black text-slate-900 uppercase">{{ $item['date']?->format('h:i A') }}</span>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm font-black text-slate-900 leading-none uppercase group-hover:text-[#9C5A1A] transition-colors">{{ $item['deceased_name'] }}</p>
                                <p class="text-[10px] font-bold text-slate-400 mt-1.5 uppercase tracking-widest">{{ $item['case_code'] }}</p>
                            </div>
                        </a>
                    @empty
                        <div class="p-6 bg-slate-50 border border-slate-100 rounded-2xl text-center">
                            <i class="bi bi-calendar-x text-slate-300 text-2xl mb-2 block"></i>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No schedule today</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
    {{-- 3. ANALYTICS & SNAPSHOT --}}
    

        {{-- Population & Workload Stats --}}
        <div class="xl:col-span-3 grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-5">
            @php
                $stats = [
                    ['label' => 'Total Clients', 'val' => $clientCount ?? 0, 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 005.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                    ['label' => 'Case Load', 'val' => $caseCount ?? 0, 'icon' => 'M9 12h6m-3-3v6m-9 1h18a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
                    ['label' => 'Ongoing Services', 'val' => $ongoingCount ?? 0, 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'text-[#9C5A1A]', 'border' => 'border-b-[#9C5A1A]'],
                    ['label' => 'Attention Required', 'val' => $unpaidCount ?? 0, 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', 'color' => 'text-red-600', 'border' => 'border-b-red-500'],
                ];
            @endphp

            @foreach($stats as $s)
                <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm hover:shadow-md transition-all border-b-4 {{ $s['border'] ?? 'border-b-slate-300' }} flex flex-col justify-center">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ $s['label'] }}</span>
                        <svg class="h-5 w-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $s['icon'] }}"/></svg>
                    </div>
                    <h4 class="text-4xl font-black font-heading {{ $s['color'] ?? 'text-slate-900' }} tracking-tight">{{ $s['val'] }}</h4>
                </div>
            @endforeach
        </div>
    </div>

    
</div>
@endsection



