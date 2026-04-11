@extends('layouts.panel')

@section('page_title', 'Branch Analytics')
@section('page_desc', 'Analyze branch trends, revenue, and operational metrics.')

@section('content')
<div class="owner-page-shell">
@if($errors->any())
    <div class="mb-4 bg-red-50 border p-3 text-red-700 rounded">
        {{ $errors->first() }}
    </div>
@endif

<div class="mb-4 flex flex-wrap gap-2">
    <a href="{{ route('owner.analytics', array_merge(request()->query(), ['branch_id' => null])) }}"
       class="px-4 py-2 rounded border text-sm {{ !$branchId ? 'bg-[#9C5A1A] border-[#9C5A1A] text-white' : 'bg-white hover:bg-slate-50' }}">
        All
    </a>
    @foreach($branches as $branch)
        <a href="{{ route('owner.analytics', array_merge(request()->query(), ['branch_id' => $branch->id])) }}"
           class="px-4 py-2 rounded border text-sm {{ (string) $branchId === (string) $branch->id ? 'bg-[#9C5A1A] border-[#9C5A1A] text-white' : 'bg-white hover:bg-slate-50' }}">
            {{ $branch->branch_name }}
        </a>
    @endforeach
</div>

<div class="mb-5 bg-white border rounded p-4">
    <form method="GET" action="{{ route('owner.analytics') }}" class="flex flex-wrap items-end gap-2">
        <input type="hidden" name="branch_id" value="{{ $branchId }}">

        <button name="range" value="TODAY" class="px-3 py-2 rounded text-sm border {{ $range === 'TODAY' ? 'bg-[#9C5A1A] text-white border-[#9C5A1A]' : 'hover:bg-slate-50' }}">Today</button>
        <button name="range" value="THIS_MONTH" class="px-3 py-2 rounded text-sm border {{ $range === 'THIS_MONTH' ? 'bg-[#9C5A1A] text-white border-[#9C5A1A]' : 'hover:bg-slate-50' }}">This Month</button>
        <button name="range" value="THIS_YEAR" class="px-3 py-2 rounded text-sm border {{ $range === 'THIS_YEAR' ? 'bg-[#9C5A1A] text-white border-[#9C5A1A]' : 'hover:bg-slate-50' }}">This Year</button>
        <button name="range" value="CUSTOM" class="px-3 py-2 rounded text-sm border {{ $range === 'CUSTOM' ? 'bg-[#9C5A1A] text-white border-[#9C5A1A]' : 'hover:bg-slate-50' }}">Custom</button>

        @if($range === 'CUSTOM')
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#9C5A1A]">
            <input type="date" name="date_to" value="{{ $dateTo }}" class="border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#9C5A1A]">
            <button class="px-3 py-2 rounded text-sm bg-[#9C5A1A] text-white hover:bg-[#7A440F]">Apply Custom</button>
        @endif
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-4 mb-6">
    <div class="bg-white rounded-lg border p-4 border-[#d6b073]">
        <div class="text-sm text-[#9C5A1A]">Total Service Amount</div>
        <div class="text-3xl font-semibold mt-1 text-[#9C5A1A]">{{ number_format($totalSales, 2) }}</div>
    </div>
    <div class="bg-white rounded-lg border p-4">
        <div class="text-sm text-gray-500">Total Cases</div>
        <div class="text-3xl font-semibold mt-1">{{ $totalCases }}</div>
    </div>
    <div class="bg-white rounded-lg border p-4">
        <div class="text-sm text-gray-500">Ongoing</div>
        <div class="text-3xl font-semibold mt-1 text-red-700">{{ $statusCounts['ongoing'] }}</div>
    </div>
    <div class="bg-white rounded-lg border p-4">
        <div class="text-sm text-gray-500">Paid</div>
        <div class="text-3xl font-semibold mt-1 text-green-700">{{ $statusCounts['paid'] }}</div>
    </div>
    <div class="bg-white rounded-lg border p-4">
        <div class="text-sm text-gray-500">Partial</div>
        <div class="text-3xl font-semibold mt-1 text-amber-700">{{ $statusCounts['partial'] }}</div>
    </div>
    <div class="bg-white rounded-lg border p-4">
        <div class="text-sm text-gray-500">Unpaid</div>
        <div class="text-3xl font-semibold mt-1 text-red-700">{{ $statusCounts['unpaid'] }}</div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="bg-white rounded-lg border p-4">
        <div class="text-sm text-gray-500">Collected Amount</div>
        <div class="text-2xl font-semibold mt-1">{{ number_format($totalCollected, 2) }}</div>
    </div>
    <div class="bg-white rounded-lg border p-4">
        <div class="text-sm text-gray-500">Outstanding Balance</div>
        <div class="text-2xl font-semibold mt-1 text-red-700">{{ number_format($totalOutstanding, 2) }}</div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-6">
    <div class="bg-white border rounded p-4 xl:col-span-2">
        <div class="font-semibold mb-3 text-[#9C5A1A]">
            @if($chart['mode'] === 'all')
                Service Amount and Case Volume by Branch
            @else
                {{ $selectedBranch?->branch_name }} Focused Performance
            @endif
        </div>
        <canvas id="barChart" height="120"></canvas>
    </div>
    <div class="bg-white border rounded p-4">
        <div class="font-semibold mb-3 text-[#9C5A1A]">
            @if($chart['mode'] === 'all')
                Branch Service Amount Share
            @else
                Payment Mix
            @endif
        </div>
        <canvas id="donutChart" height="160"></canvas>
    </div>
</div>

<div class="bg-white border rounded p-4">
    <div class="font-semibold mb-3 text-[#9C5A1A]">Service Amount Trend</div>
    <canvas id="lineChart" height="90"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function () {
        const payload = @json($chart);
        const barCtx = document.getElementById('barChart');
        const donutCtx = document.getElementById('donutChart');
        const lineCtx = document.getElementById('lineChart');
        if (!barCtx || !donutCtx || !lineCtx || !payload) return;

        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: payload.bar.labels,
                datasets: [
                    {
                        label: 'Service Amount',
                        data: payload.bar.revenue,
                        backgroundColor: payload.bar.colors,
                        yAxisID: 'yRevenue'
                    },
                    {
                        label: 'Cases',
                        data: payload.bar.volume,
                        backgroundColor: 'rgba(220, 38, 38, 0.35)',
                        borderColor: '#b91c1c',
                        borderWidth: 1,
                        yAxisID: 'yCases'
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    yRevenue: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true
                    },
                    yCases: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });

        new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: payload.donut.labels,
                datasets: [{
                    data: payload.donut.values,
                    backgroundColor: payload.donut.colors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true
            }
        });

        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: payload.line.labels,
                datasets: [{
                    label: 'Service Amount',
                    data: payload.line.data,
                    borderColor: '#9C5A1A',
                    backgroundColor: 'rgba(140, 64, 4, 0.12)',
                    tension: 0.25,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    })();
</script>
</div>
@endsection

