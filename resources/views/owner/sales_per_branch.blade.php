@extends('layouts.panel')

@section('page_title', 'Reporting & Export')
@section('page_desc', 'Compare sales, collections, and balances across branches.')

@section('content')
@php
    $selectedBranch = ($branches ?? collect())->firstWhere('id', (int) ($filters['branch_id'] ?? 0));
    $selectedBranchLabel = $selectedBranch
        ? trim(($selectedBranch->branch_code ?? '') . ' - ' . ($selectedBranch->branch_name ?? ''))
        : 'All Branches';
    $dateRangeLabel = ($filters['date_from'] ?? 'Start') . ' - ' . ($filters['date_to'] ?? 'Today');
    $intermentRangeLabel = filled($filters['interment_from'] ?? null) || filled($filters['interment_to'] ?? null)
        ? (($filters['interment_from'] ?? 'Start') . ' - ' . ($filters['interment_to'] ?? 'Today'))
        : null;
    $hasFilters = filled($filters['branch_id'] ?? null)
        || filled($filters['interment_from'] ?? null)
        || filled($filters['interment_to'] ?? null);
    $maxSales = max(1, (float) ($branchSummary->max('sales') ?? 1));
    $summaryCards = [
        ['icon' => 'bi-folder2-open', 'label' => 'Total Cases', 'value' => number_format($totalCases), 'desc' => 'Verified case records'],
        ['icon' => 'bi-check2-circle', 'label' => 'Paid Cases', 'value' => number_format($paidCases), 'desc' => 'Fully settled records'],
        ['icon' => 'bi-hourglass-split', 'label' => 'Partial Cases', 'value' => number_format($partialCases), 'desc' => 'With remaining balance'],
        ['icon' => 'bi-exclamation-circle', 'label' => 'Unpaid Cases', 'value' => number_format($unpaidCases), 'desc' => 'Needs collection follow-up'],
        ['icon' => 'bi-receipt', 'label' => 'Service Amount', 'value' => 'PHP ' . number_format($totalSales, 2), 'desc' => 'Total service value'],
        ['icon' => 'bi-cash-stack', 'label' => 'Collected / Outstanding', 'value' => 'PHP ' . number_format($totalCollected, 2), 'desc' => 'Bal ' . number_format($totalOutstanding, 2)],
    ];
@endphp

<style>
    .print-report-header {
        display: none;
    }

    .owner-sales-report {
        overflow-y: auto;
    }

    .owner-sales-toolbar {
        display: grid;
        gap: 10px;
    }

    .owner-sales-filter-grid {
        display: grid;
        grid-template-columns: minmax(220px, 1.3fr) repeat(4, minmax(150px, 1fr)) auto auto;
        gap: 8px;
        align-items: end;
    }

    .owner-sales-field {
        display: grid;
        gap: 5px;
        min-width: 0;
    }

    .owner-sales-field label {
        color: var(--records-muted, var(--ink-muted));
        font-size: 11px;
        font-weight: 650;
        line-height: 1.2;
    }

    .owner-sales-field-control {
        position: relative;
    }

    .owner-sales-field-control > i {
        position: absolute;
        left: 12px;
        top: 50%;
        z-index: 1;
        transform: translateY(-50%);
        color: var(--records-muted, var(--ink-muted));
        font-size: 13px;
        pointer-events: none;
    }

    .owner-sales-control {
        width: 100%;
        min-height: 40px;
        border: 1px solid var(--records-border, var(--border));
        border-radius: var(--radius-ops, 8px);
        background: var(--records-card-alt, #DCE6D6);
        color: var(--records-text, var(--ink));
        padding: 8px 10px 8px 34px;
        font-size: 13px;
        font-weight: 650;
        outline: none;
        box-shadow: none;
        transition: background-color .16s ease, border-color .16s ease, color .16s ease;
    }

    select.owner-sales-control {
        cursor: pointer;
        appearance: none;
        padding-right: 34px;
        background-image: none;
    }

    .owner-sales-control:hover {
        background-color: var(--records-hover, #C5D3BC);
        border-color: var(--records-border-strong, #8EA083);
    }

    .owner-sales-control:focus {
        border-color: var(--brand);
        background-color: var(--card);
        box-shadow: 0 0 0 4px var(--color-focus-ring) !important;
    }

    .owner-sales-actions,
    .owner-sales-export-actions,
    .owner-sales-chip-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }

    .owner-sales-chip-row {
        padding-top: 2px;
    }

    .owner-sales-progress-card {
        padding: 14px;
    }

    .owner-sales-progress-list {
        display: grid;
        gap: 10px;
    }

    .owner-sales-progress-row {
        display: grid;
        grid-template-columns: minmax(160px, .9fr) minmax(0, 1fr) auto;
        gap: 10px;
        align-items: center;
    }

    .owner-sales-progress-label,
    .owner-sales-money {
        color: var(--records-text, var(--ink));
        font-size: 13px;
        font-weight: 700;
    }

    .owner-sales-progress-track {
        height: 8px;
        overflow: hidden;
        border: 1px solid var(--records-border, var(--border));
        border-radius: 999px;
        background: var(--records-card-alt, #DCE6D6);
    }

    .owner-sales-progress-fill {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: var(--brand);
    }

    .owner-sales-branch-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    .owner-sales-branch-card {
        border: 1px solid var(--records-border, var(--border));
        border-radius: var(--radius-ops, 8px);
        background: var(--records-card-alt, #DCE6D6);
        padding: 12px;
    }

    .owner-sales-branch-title {
        color: var(--records-text, var(--ink));
        font-family: var(--font-heading);
        font-size: 15px;
        font-weight: 700;
        line-height: 1.25;
    }

    .owner-sales-branch-metrics {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        margin-top: 10px;
        color: var(--records-muted, var(--ink-muted));
        font-size: 12px;
        font-weight: 600;
    }

    .owner-sales-branch-metrics strong {
        display: block;
        color: var(--records-text, var(--ink));
        font-size: 13px;
        font-weight: 750;
        font-variant-numeric: tabular-nums;
    }

    .owner-sales-table {
        min-width: 1180px;
    }

    .owner-sales-table .table-col-number {
        text-align: right;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }

    @media (max-width: 1280px) {
        .owner-sales-filter-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .owner-sales-actions,
        .owner-sales-export-actions {
            align-self: stretch;
        }
    }

    @media (max-width: 900px) {
        .owner-sales-branch-grid,
        .owner-sales-progress-row {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .owner-sales-filter-grid {
            grid-template-columns: 1fr;
        }

        .owner-sales-actions .ops-btn-outline,
        .owner-sales-actions .ops-btn-primary,
        .owner-sales-export-actions .ops-btn-outline {
            width: 100%;
        }
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

        main,
        .owner-page-shell,
        .owner-sales-report {
            height: auto !important;
            min-height: 0 !important;
            overflow: visible !important;
            padding: 0 !important;
        }

        .table-system-card,
        .ops-stat-card,
        .owner-sales-branch-card {
            break-inside: avoid;
            page-break-inside: avoid;
            border-color: #C9C5BB !important;
            background: #fff !important;
        }

        table {
            width: 100% !important;
            border-collapse: collapse;
            font-size: 11px !important;
        }

        th,
        td {
            border: 1px solid #C9C5BB !important;
            padding: 4px 6px !important;
            white-space: normal !important;
            word-break: break-word;
        }
    }
</style>

<div class="owner-page-shell owner-sales-report">
    <div class="print-report-header">
        <div style="font-weight:700; font-size:18px;">Sabangan Caguioa Funeral Home</div>
        <div style="font-size:13px; margin-top:2px;">Reporting and Export</div>
        <div style="font-size:12px; margin-top:2px;">Generated: {{ now()->format('Y-m-d H:i') }}</div>
    </div>

    @if($errors->any())
        <div class="flash-error no-print">{{ $errors->first() }}</div>
    @endif

    <section class="ops-toolbar-shell no-print" aria-label="Report filters and export actions">
        <form method="GET" action="{{ route('owner.sales.index') }}" class="owner-sales-toolbar">
            <div class="owner-sales-filter-grid">
                <div class="owner-sales-field">
                    <label for="ownerSalesBranch">Branch</label>
                    <span class="owner-sales-field-control">
                        <i class="bi bi-building" aria-hidden="true"></i>
                        <select id="ownerSalesBranch" name="branch_id" class="owner-sales-control">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (string) $filters['branch_id'] === (string) $branch->id ? 'selected' : '' }}>
                                    {{ $branch->branch_code }} - {{ $branch->branch_name }}
                                </option>
                            @endforeach
                        </select>
                    </span>
                </div>

                <div class="owner-sales-field">
                    <label for="ownerSalesDateFrom">Date From</label>
                    <span class="owner-sales-field-control">
                        <i class="bi bi-calendar3" aria-hidden="true"></i>
                        <input id="ownerSalesDateFrom" type="date" name="date_from" value="{{ $filters['date_from'] }}" class="owner-sales-control">
                    </span>
                </div>

                <div class="owner-sales-field">
                    <label for="ownerSalesDateTo">Date To</label>
                    <span class="owner-sales-field-control">
                        <i class="bi bi-calendar3" aria-hidden="true"></i>
                        <input id="ownerSalesDateTo" type="date" name="date_to" value="{{ $filters['date_to'] }}" class="owner-sales-control">
                    </span>
                </div>

                <div class="owner-sales-field">
                    <label for="ownerSalesIntermentFrom">Interment From</label>
                    <span class="owner-sales-field-control">
                        <i class="bi bi-calendar-event" aria-hidden="true"></i>
                        <input id="ownerSalesIntermentFrom" type="date" name="interment_from" value="{{ $filters['interment_from'] ?? '' }}" class="owner-sales-control">
                    </span>
                </div>

                <div class="owner-sales-field">
                    <label for="ownerSalesIntermentTo">Interment To</label>
                    <span class="owner-sales-field-control">
                        <i class="bi bi-calendar-event" aria-hidden="true"></i>
                        <input id="ownerSalesIntermentTo" type="date" name="interment_to" value="{{ $filters['interment_to'] ?? '' }}" class="owner-sales-control">
                    </span>
                </div>

                <div class="owner-sales-actions">
                    <button type="submit" class="ops-btn-primary">
                        <i class="bi bi-funnel" aria-hidden="true"></i>
                        <span>Apply</span>
                    </button>
                    <a href="{{ route('owner.sales.index') }}" class="ops-btn-outline">
                        <i class="bi bi-x-circle" aria-hidden="true"></i>
                        <span>Reset</span>
                    </a>
                </div>

                <div class="owner-sales-export-actions">
                    <a href="{{ route('owner.sales.export', request()->query()) }}" class="ops-btn-outline">
                        <i class="bi bi-filetype-csv" aria-hidden="true"></i>
                        <span>Export CSV</span>
                    </a>
                    <button type="button" onclick="window.print()" class="ops-btn-outline">
                        <i class="bi bi-printer" aria-hidden="true"></i>
                        <span>Print</span>
                    </button>
                </div>
            </div>

            <div class="owner-sales-chip-row" aria-label="Applied report filters">
                <span class="case-compact-chip">
                    <i class="bi bi-building" aria-hidden="true"></i>Branch: {{ $selectedBranchLabel }}
                </span>
                <span class="case-compact-chip">
                    <i class="bi bi-calendar3" aria-hidden="true"></i>Date: {{ $dateRangeLabel }}
                </span>
                @if($intermentRangeLabel)
                    <span class="case-compact-chip">
                        <i class="bi bi-calendar-event" aria-hidden="true"></i>Interment: {{ $intermentRangeLabel }}
                    </span>
                @endif
                @if($hasFilters)
                    <a href="{{ route('owner.sales.index') }}" class="case-compact-reset">
                        <i class="bi bi-x-circle" aria-hidden="true"></i>
                        <span>Clear</span>
                    </a>
                @endif
            </div>

            <div class="owner-sales-chip-row" aria-label="Quick date presets">
                <a href="{{ route('owner.sales.index', array_merge(request()->query(), ['preset' => 'THIS_MONTH'])) }}" class="case-compact-chip">
                    <i class="bi bi-calendar-month" aria-hidden="true"></i>This Month
                </a>
                <a href="{{ route('owner.sales.index', array_merge(request()->query(), ['preset' => 'LAST_30_DAYS'])) }}" class="case-compact-chip">
                    <i class="bi bi-calendar-range" aria-hidden="true"></i>Last 30 Days
                </a>
            </div>
        </form>
    </section>

    <section class="ops-stat-grid" aria-label="Report summary">
        @foreach($summaryCards as $card)
            <article class="ops-stat-card">
                <div class="ops-stat-card__inner">
                    <span class="ops-stat-card__icon">
                        <i class="bi {{ $card['icon'] }}" aria-hidden="true"></i>
                    </span>
                    <span>
                        <span class="ops-stat-card__label">{{ $card['label'] }}</span>
                        <strong class="ops-stat-card__value">{{ $card['value'] }}</strong>
                        <span class="ops-stat-card__desc">{{ $card['desc'] }}</span>
                    </span>
                </div>
            </article>
        @endforeach
    </section>

    <section class="table-system-card owner-sales-progress-card">
        <div class="table-system-head !px-0 !pt-0">
            <h2 class="table-system-title">Service Amount per Branch</h2>
            <p class="table-system-copy">Branch-level service value for the active report filters.</p>
        </div>
        <div class="owner-sales-progress-list">
            @forelse($branchSummary as $row)
                @php($width = (int) round(((float) $row['sales'] / $maxSales) * 100))
                <div class="owner-sales-progress-row">
                    <span class="owner-sales-progress-label">{{ $row['branch']->branch_code }} - {{ $row['branch']->branch_name }}</span>
                    <span class="owner-sales-progress-track" aria-hidden="true">
                        <span class="owner-sales-progress-fill" style="width: {{ max(2, $width) }}%"></span>
                    </span>
                    <span class="owner-sales-money">PHP {{ number_format($row['sales'], 2) }}</span>
                </div>
            @empty
                <div class="table-system-empty">No branch data found.</div>
            @endforelse
        </div>
    </section>

    <section class="owner-sales-branch-grid" aria-label="Branch summaries">
        @forelse($branchSummary as $row)
            <article class="owner-sales-branch-card">
                <h3 class="owner-sales-branch-title">{{ $row['branch']->branch_code }} - {{ $row['branch']->branch_name }}</h3>
                <div class="owner-sales-branch-metrics">
                    <span><strong>{{ number_format($row['total_cases']) }}</strong>Total Cases</span>
                    <span><strong>{{ number_format($row['paid_cases']) }}</strong>Paid Cases</span>
                    <span><strong>{{ number_format($row['partial_cases']) }}</strong>Partial Cases</span>
                    <span><strong>{{ number_format($row['unpaid_cases']) }}</strong>Unpaid Cases</span>
                    <span><strong>PHP {{ number_format($row['collected'], 2) }}</strong>Collected</span>
                    <span><strong>PHP {{ number_format($row['outstanding'], 2) }}</strong>Outstanding</span>
                </div>
            </article>
        @empty
            <div class="ops-empty p-4">No branch data found.</div>
        @endforelse
    </section>

    <section class="table-system-card">
        <div class="table-system-head">
            <h2 class="table-system-title">Case List</h2>
            <p class="table-system-copy">Verified case records matching the current report filters.</p>
        </div>
        <div class="table-system-list">
            <div class="table-wrapper table-system-wrap">
                <table class="table-base table-system-table owner-sales-table">
                    <thead>
                        <tr>
                            <th class="text-left">Case</th>
                            <th class="text-left">Service Date</th>
                            <th class="text-left">Branch</th>
                            <th class="text-left">Client</th>
                            <th class="text-left">Deceased</th>
                            <th class="text-left">Interment</th>
                            <th class="text-left">Case Status</th>
                            <th class="text-left">Payment Status</th>
                            <th class="table-col-number">Total</th>
                            <th class="table-col-number">Total Paid</th>
                            <th class="table-col-number">Balance</th>
                            <th class="text-left no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($cases as $case)
                        <tr>
                            <td>
                                <div class="table-primary whitespace-nowrap">{{ $case->case_code }}</div>
                                <div class="table-secondary">Encoded {{ $case->created_at?->format('M d, Y') }}</div>
                            </td>
                            <td class="whitespace-nowrap">{{ $case->created_at?->format('Y-m-d') }}</td>
                            <td>
                                <div class="table-primary whitespace-nowrap">{{ $case->branch?->branch_code ?? '-' }}</div>
                                <div class="table-secondary">{{ \Illuminate\Support\Str::limit($case->branch?->branch_name ?? '-', 24) }}</div>
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($case->client?->full_name ?? '-', 28) }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($case->deceased?->full_name ?? '-', 30) }}</td>
                            <td class="whitespace-nowrap">{{ $case->deceased?->interment_at?->format('Y-m-d H:i') ?? $case->deceased?->interment?->format('Y-m-d') ?? '-' }}</td>
                            <td><x-status-badge :status="$case->case_status" :label="\Illuminate\Support\Str::headline(strtolower((string) $case->case_status))" /></td>
                            <td><x-status-badge :status="$case->payment_status" :label="\Illuminate\Support\Str::headline(strtolower((string) $case->payment_status))" /></td>
                            <td class="table-col-number">{{ number_format($case->total_amount, 2) }}</td>
                            <td class="table-col-number">{{ number_format((float) $case->total_paid, 2) }}</td>
                            <td class="table-col-number">{{ number_format((float) $case->balance_amount, 2) }}</td>
                            <td class="no-print">
                                <a href="{{ route('owner.cases.show', ['funeral_case' => $case, 'return_to' => request()->fullUrl()]) }}" class="ops-btn-outline">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                    <span>View</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="table-system-empty">No case records found for selected filters.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <div class="no-print">
        @if($cases->hasPages()){{ $cases->links() }}@endif
    </div>
</div>

@endsection
