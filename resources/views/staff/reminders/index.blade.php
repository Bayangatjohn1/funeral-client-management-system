@extends('layouts.panel')

@section('page_title', 'Reminders & Schedule')

@section('content')
<div class="w-full mx-auto space-y-6 pb-10">
    <a href="{{ url('/staff') }}"
           class=" px-4 py-2 text-[11px] font-black uppercase tracking-widest bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100 text-slate-700">
            Back to Dashboard
        </a>
    {{-- Summary counters --}}
    
    {{-- Filters --}}
    <form method="GET" action="{{ route('staff.reminders.index') }}" class="grid md:grid-cols-4 gap-4">
            <input type="hidden" name="tab" value="{{ $activeTab ?? 'today' }}">

            <div class="col-span-2 md:col-span-1">
                
                <input type="date"
                       name="date"
                       value="{{ $filters['date'] ?? '' }}"
                       class="form-input mt-2"
                       onchange="this.form.submit()">
            </div>

            <div class="col-span-2 md:col-span-1">
                
                <select name="case_status" class="form-select mt-2" onchange="this.form.submit()">
                    <option value="">All</option>
                    <option value="DRAFT" {{ ($filters['case_status'] ?? '') === 'DRAFT' ? 'selected' : '' }}>Draft</option>
                    <option value="ACTIVE" {{ ($filters['case_status'] ?? '') === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                    <option value="COMPLETED" {{ ($filters['case_status'] ?? '') === 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>

            <div class="col-span-2 md:col-span-1">
                
                <select name="branch_id" class="form-select mt-2" onchange="this.form.submit()">
                    @forelse($branchChoices as $branch)
                        <option value="{{ $branch->id }}" {{ ($selectedBranchId ?? null) === $branch->id ? 'selected' : '' }}>
                            {{ $branch->branch_code }} — {{ $branch->branch_name }}
                        </option>
                    @empty
                        <option value="{{ $selectedBranchId ?? '' }}">Main branch (operational)</option>
                    @endforelse
                </select>
                <p class="text-[11px] text-slate-400 mt-1">Operational reminders limited to main branch.</p>
            </div>
        </form>

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

    {{-- Tabs --}}
    <div class="bg-white border border-slate-200 rounded-3xl p-4 md:p-6 shadow-sm">
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach([
                'today' => 'Today',
                'upcoming' => 'Upcoming',
                'unpaid' => 'Unpaid / Partial',
                'warnings' => 'Warnings',
                'all' => 'All Alerts',
            ] as $key => $label)
                <a href="{{ route('staff.reminders.index', array_merge(request()->except('page'), ['tab' => $key])) }}"
                   class="px-4 py-2 text-[11px] font-black uppercase tracking-widest rounded-lg border transition-colors {{ $activeTab === $key ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300' }}">
                    <i class="bi {{ $tabIcons[$key] ?? 'bi-dot' }} mr-2"></i>{{ $label }}

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
            <div class="p-8 bg-slate-50 border border-dashed border-slate-200 rounded-2xl text-center">
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

                <p class="text-[11px] text-slate-400">You’re all caught up 👍</p>
            </div>
        @else
            <div class="space-y-3">
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
                            ? 'Outstanding Balance: ₱' . number_format((float) ($case->balance_amount ?? 0), 2)
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

                    <div class="p-4 md:p-5 bg-white border border-slate-200 rounded-2xl border-l-4 {{ $colors[$item['type']] ?? 'border-l-slate-200' }} shadow-sm hover:shadow-md transition-all">
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1">
                                <p class="text-sm font-black text-slate-900 leading-none">
                                    {{ $case->client->full_name ?? 'Client N/A' }}
                                </p>

                                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">
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
                                            · {{ $item['date']->format('h:i A') }}
                                        @endif
                                    </p>
                                @endif

                                <div class="inline-flex items-center gap-2 flex-wrap">
                                    <span class="inline-flex items-center px-2 py-1 text-[10px] font-black uppercase tracking-widest rounded-md border {{ $badge[$item['type']] ?? 'bg-slate-50 text-slate-500 border-slate-200' }}">
                                        {{ $primaryLabel }}
                                    </span>

                                    <span class="inline-flex items-center px-2 py-1 text-[10px] font-black uppercase tracking-widest rounded-md border
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

                            <div class="flex flex-col items-end gap-2">
                                <span class="text-[11px] font-semibold text-slate-500">
                                    {{ ucfirst(str_replace('_', ' ', $item['type'] ?? 'alert')) }}
                                </span>

                                <a href="{{ route('funeral-cases.show', $item['case_id']) }}"
                                   class="px-3 py-2 text-[11px] font-black uppercase tracking-widest rounded-lg bg-slate-900 text-white hover:bg-[#9C5A1A] transition-colors">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection