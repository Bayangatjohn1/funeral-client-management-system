@extends('layouts.panel')

@section('page_title', 'Sales Reports')

@section('content')
@if($errors->any())
    <div class="mb-4 bg-red-50 border p-3 text-red-700 rounded">
        {{ $errors->first() }}
    </div>
@endif

<div class="mb-5 bg-white border rounded p-4">
    <form method="GET" action="{{ route('admin.reports.sales') }}" class="grid gap-3 md:grid-cols-6 items-end">
        <div class="space-y-1">
            <label class="label-section">Branch</label>
            <select name="branch_id" class="form-select w-full">
                <option value="">All Branches</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ (string) $branchId === (string) $branch->id ? 'selected' : '' }}>
                        {{ $branch->branch_code }} - {{ $branch->branch_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="space-y-1">
            <label class="label-section">Date From</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-input w-full">
        </div>
        <div class="space-y-1">
            <label class="label-section">Date To</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="form-input w-full">
        </div>
        <div class="space-y-1">
            <label class="label-section">Interment From</label>
            <input type="date" name="interment_from" value="{{ $intermentFrom ?? '' }}" class="form-input w-full">
        </div>
        <div class="space-y-1">
            <label class="label-section">Interment To</label>
            <input type="date" name="interment_to" value="{{ $intermentTo ?? '' }}" class="form-input w-full">
        </div>
        <div class="flex gap-2 justify-end md:justify-start">
            <button class="btn btn-primary-custom bg-[var(--brand-mid)] border-[var(--brand-mid)] hover:bg-[var(--brand-hover)] hover:border-[var(--brand-hover)] text-white px-4">
                Apply
            </button>
            <a href="{{ route('admin.reports.sales') }}" class="btn btn-outline">Reset</a>
        </div>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-4">
    <div class="stat-card">
        <div class="text-sm text-gray-500">Total Cases</div>
        <div class="text-2xl font-semibold mt-1">{{ $totalCases }}</div>
    </div>
    <div class="stat-card">
        <div class="text-sm text-gray-500">Paid Cases</div>
        <div class="text-2xl font-semibold mt-1 text-emerald-700">{{ $paidCases }}</div>
    </div>
    <div class="stat-card">
        <div class="text-sm text-gray-500">Partial Cases</div>
        <div class="text-2xl font-semibold mt-1 text-amber-700">{{ $partialCases }}</div>
    </div>
    <div class="stat-card">
        <div class="text-sm text-gray-500">Unpaid Cases</div>
        <div class="text-2xl font-semibold mt-1 text-red-700">{{ $unpaidCases }}</div>
    </div>
    <div class="stat-card border-[#d6b073]">
        <div class="text-sm text-[#9C5A1A]">Total Service Amount</div>
        <div class="text-2xl font-semibold mt-1 text-[#9C5A1A]">{{ number_format($totalSales, 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="text-sm text-gray-500">Collected Amount / Outstanding</div>
        <div class="text-sm font-semibold mt-1">{{ number_format($totalCollected, 2) }}</div>
        <div class="text-xs text-red-600 mt-1">Bal: {{ number_format($totalOutstanding, 2) }}</div>
    </div>
</div>

<div class="mt-6 bg-white border rounded overflow-x-auto">
    <div class="px-4 py-3 border-b font-semibold">Branch Sales Breakdown</div>
    <table class="table-base text-sm">
        <thead>
            <tr>
                <th class="p-2 border text-left">Branch</th>
                <th class="p-2 border text-left">Total Cases</th>
                <th class="p-2 border text-left">Paid Cases</th>
                <th class="p-2 border text-left">Partial Cases</th>
                <th class="p-2 border text-left">Unpaid Cases</th>
                <th class="p-2 border text-left">Sales (Paid)</th>
                <th class="p-2 border text-left">Collected Amount</th>
                <th class="p-2 border text-left">Outstanding</th>
            </tr>
        </thead>
        <tbody>
        @foreach($branchSales as $row)
            <tr>
                <td class="p-2 border">{{ $row['branch']->branch_code }} - {{ $row['branch']->branch_name }}</td>
                <td class="p-2 border">{{ $row['cases'] }}</td>
                <td class="p-2 border">{{ $row['paid_cases'] }}</td>
                <td class="p-2 border">{{ $row['partial_cases'] }}</td>
                <td class="p-2 border">{{ $row['unpaid_cases'] }}</td>
                <td class="p-2 border">{{ number_format($row['sales'], 2) }}</td>
                <td class="p-2 border">{{ number_format($row['collected'], 2) }}</td>
                <td class="p-2 border">{{ number_format($row['outstanding'], 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
