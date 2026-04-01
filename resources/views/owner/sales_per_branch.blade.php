@extends('layouts.panel')

@section('page_title', 'Reporting & Export')

@section('content')
<style>
    .print-report-header {
        display: none;
    }

    @media print {
        @page {
            size: A4 landscape;
            margin: 12mm;
        }

        body {
            background: #fff !important;
        }

        aside,
        header,
        .no-print {
            display: none !important;
        }

        .print-report-header {
            display: block !important;
            margin-bottom: 10px;
        }

        main {
            padding: 0 !important;
        }

        #reports {
            border: 0 !important;
            padding: 0 !important;
            margin-bottom: 10px !important;
        }

        #reports form {
            display: block !important;
        }

        #reports label {
            font-size: 10px;
            color: #334155;
        }

        #reports input,
        #reports select {
            border: 0 !important;
            padding: 0 !important;
            font-size: 12px !important;
            color: #0f172a;
            background: transparent !important;
            appearance: none;
        }

        table {
            width: 100% !important;
            border-collapse: collapse;
            font-size: 11px !important;
        }

        th,
        td {
            border: 1px solid #cbd5e1 !important;
            padding: 4px 6px !important;
            white-space: normal !important;
            word-break: break-word;
        }

        .print-avoid-break {
            break-inside: avoid;
            page-break-inside: avoid;
        }
    }
</style>

<div class="print-report-header">
    <div style="font-weight:700; font-size:18px;">Sabangan Caguioa Funeral Home</div>
    <div style="font-size:13px; margin-top:2px;">Reporting and Export</div>
    <div style="font-size:12px; margin-top:2px;">
        Generated: {{ now()->format('Y-m-d H:i') }}
    </div>
</div>

@if($errors->any())
    <div class="mb-4 bg-red-50 border p-3 text-red-700 rounded no-print">
        {{ $errors->first() }}
    </div>
@endif

<div class="mb-5 bg-white border rounded p-4 print-avoid-break" id="reports">
    <form method="GET" action="{{ route('owner.sales.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
        <div>
            <label class="block text-sm font-medium mb-1">Branch</label>
            <select name="branch_id" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#9C5A1A]">
                <option value="">All Branches</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ (string) $filters['branch_id'] === (string) $branch->id ? 'selected' : '' }}>
                        {{ $branch->branch_code }} - {{ $branch->branch_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Date From</label>
            <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#9C5A1A]">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Date To</label>
            <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#9C5A1A]">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Interment From</label>
            <input type="date" name="interment_from" value="{{ $filters['interment_from'] ?? '' }}" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#9C5A1A]">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Interment To</label>
            <input type="date" name="interment_to" value="{{ $filters['interment_to'] ?? '' }}" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#9C5A1A]">
        </div>
        <div class="flex gap-2 no-print">
            <button class="px-3 py-2 rounded text-sm bg-[#9C5A1A] text-white hover:bg-[#7A440F]">Apply</button>
            <a href="{{ route('owner.sales.index') }}" class="border px-3 py-2 rounded text-sm">Reset</a>
        </div>
        <div class="flex gap-2 no-print">
            <a href="{{ route('owner.sales.export', request()->query()) }}" class="border px-3 py-2 rounded text-sm">Export CSV</a>
            <button type="button" onclick="window.print()" class="border px-3 py-2 rounded text-sm">Print</button>
        </div>
    </form>
    <div class="flex flex-wrap gap-2 mt-3 no-print">
        <a href="{{ route('owner.sales.index', array_merge(request()->query(), ['preset' => 'THIS_MONTH'])) }}" class="border px-3 py-1.5 rounded text-xs hover:bg-slate-50">This Month</a>
        <a href="{{ route('owner.sales.index', array_merge(request()->query(), ['preset' => 'LAST_30_DAYS'])) }}" class="border px-3 py-1.5 rounded text-xs hover:bg-slate-50">Last 30 Days</a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-6 print-avoid-break">
    <div class="bg-white rounded-lg border p-4">
        <div class="text-sm text-gray-500">Total Cases</div>
        <div class="text-2xl font-semibold mt-1">{{ $totalCases }}</div>
    </div>
    <div class="bg-white rounded-lg border p-4">
        <div class="text-sm text-gray-500">Paid Cases</div>
        <div class="text-2xl font-semibold mt-1 text-emerald-700">{{ $paidCases }}</div>
    </div>
    <div class="bg-white rounded-lg border p-4">
        <div class="text-sm text-gray-500">Partial Cases</div>
        <div class="text-2xl font-semibold mt-1 text-amber-700">{{ $partialCases }}</div>
    </div>
    <div class="bg-white rounded-lg border p-4">
        <div class="text-sm text-gray-500">Unpaid Cases</div>
        <div class="text-2xl font-semibold mt-1 text-red-600">{{ $unpaidCases }}</div>
    </div>
    <div class="bg-white rounded-lg border p-4">
        <div class="text-sm text-gray-500">Total Service Amount</div>
        <div class="text-2xl font-semibold mt-1">{{ number_format($totalSales, 2) }}</div>
    </div>
    <div class="bg-white rounded-lg border p-4">
        <div class="text-sm text-gray-500">Collected Amount / Outstanding</div>
        <div class="text-sm font-semibold mt-1">{{ number_format($totalCollected, 2) }}</div>
        <div class="text-xs text-red-600 mt-1">Bal: {{ number_format($totalOutstanding, 2) }}</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6 print-avoid-break">
    @forelse($branchSummary as $row)
        <div class="bg-white border rounded-lg p-4">
            <div class="font-semibold">{{ $row['branch']->branch_code }} - {{ $row['branch']->branch_name }}</div>
            <div class="text-sm text-gray-600 mt-2">Total Cases: {{ $row['total_cases'] }}</div>
            <div class="text-sm text-gray-600">Paid Cases: {{ $row['paid_cases'] }}</div>
            <div class="text-sm text-gray-600">Partial Cases: {{ $row['partial_cases'] }}</div>
            <div class="text-sm text-gray-600">Unpaid Cases: {{ $row['unpaid_cases'] }}</div>
            <div class="text-sm text-gray-600">Sales (Paid): {{ number_format($row['sales'], 2) }}</div>
            <div class="text-sm text-gray-600">Collected Amount: {{ number_format($row['collected'], 2) }}</div>
            <div class="text-sm text-red-600">Outstanding: {{ number_format($row['outstanding'], 2) }}</div>
        </div>
    @empty
        <div class="bg-white border rounded-lg p-4 text-sm text-gray-500">No branch data found.</div>
    @endforelse
</div>

<div class="bg-white border rounded p-4 mb-6 print-avoid-break">
    <div class="font-semibold mb-3">Service Amount per Branch</div>
    @php($maxSales = max(1, (float) ($branchSummary->max('sales') ?? 1)))
    <div class="space-y-3">
        @foreach($branchSummary as $row)
            @php($width = (int) round(((float) $row['sales'] / $maxSales) * 100))
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span>{{ $row['branch']->branch_code }} - {{ $row['branch']->branch_name }}</span>
                    <span>{{ number_format($row['sales'], 2) }}</span>
                </div>
                <div class="w-full h-3 rounded bg-slate-100">
                    <div class="h-3 rounded bg-[#9C5A1A]" style="width: {{ max(2, $width) }}%"></div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="bg-white border rounded overflow-x-auto">
    <div class="px-4 py-2 border-b font-semibold">Case List</div>
    <table class="w-full text-sm">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-2 border text-left">Case Code</th>
                <th class="p-2 border text-left">Service Date</th>
                <th class="p-2 border text-left">Branch</th>
                <th class="p-2 border text-left">Client</th>
                <th class="p-2 border text-left">Deceased</th>
                <th class="p-2 border text-left">Interment</th>
                <th class="p-2 border text-left">Case Status</th>
                <th class="p-2 border text-left">Payment Status</th>
                <th class="p-2 border text-left">Total</th>
                <th class="p-2 border text-left">Total Paid</th>
                <th class="p-2 border text-left">Balance</th>
                <th class="p-2 border text-left no-print">Action</th>
            </tr>
        </thead>
        <tbody>
        @forelse($cases as $case)
            <tr class="hover:bg-gray-50">
                <td class="p-2 border">{{ $case->case_code }}</td>
                <td class="p-2 border">{{ $case->created_at?->format('Y-m-d') }}</td>
                <td class="p-2 border">{{ $case->branch?->branch_code ?? '-' }}</td>
                <td class="p-2 border">{{ $case->client?->full_name ?? '-' }}</td>
                <td class="p-2 border">{{ $case->deceased?->full_name ?? '-' }}</td>
                <td class="p-2 border">{{ $case->deceased?->interment_at?->format('Y-m-d H:i') ?? $case->deceased?->interment?->format('Y-m-d') ?? '-' }}</td>
                <td class="p-2 border">{{ $case->case_status }}</td>
                <td class="p-2 border">{{ $case->payment_status }}</td>
                <td class="p-2 border">{{ number_format($case->total_amount, 2) }}</td>
                <td class="p-2 border">{{ number_format((float) $case->total_paid, 2) }}</td>
                <td class="p-2 border">{{ number_format((float) $case->balance_amount, 2) }}</td>
                <td class="p-2 border no-print">
                    <a href="{{ route('owner.cases.show', $case) }}" class="underline">View</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="14" class="p-3 text-center text-gray-500">No case records found for selected filters.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4 no-print">
    {{ $cases->links() }}
</div>

@endsection

