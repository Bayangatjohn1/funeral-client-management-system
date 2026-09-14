<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\FuneralCase;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BranchAnalyticsService
{
    public function ownerPageData(array $filters, ?array $branchScope = null): array
    {
        $range = $filters['range'] ?? 'TODAY';
        [$dateFrom, $dateTo] = $this->resolveRange(
            $range,
            $filters['date_from'] ?? null,
            $filters['date_to'] ?? null
        );

        [$startAt, $endAt] = $this->dateBounds($dateFrom, $dateTo);
        $branchId = $filters['branch_id'] ?? null;

        if ($branchScope['forced_branch_id'] ?? null) {
            $branchId = (int) $branchScope['forced_branch_id'];
        }

        $branches = Branch::query()
            ->when(
                $branchScope && ! ($branchScope['can_select_all'] ?? false),
                fn ($query) => $query->where('id', (int) ($branchScope['forced_branch_id'] ?? 0))
            )
            ->orderBy('branch_code')
            ->get();
        $branchColors = $this->branchColorMap($branches);
        $base = $this->verifiedCasesQuery($startAt, $endAt, $branchId);
        $summary = $this->aggregateSummary(clone $base);

        $totalCases = (int) ($summary->total_cases ?? 0);
        $statusCounts = [
            'paid' => (int) ($summary->paid_cases ?? 0),
            'partial' => (int) ($summary->partial_cases ?? 0),
            'unpaid' => (int) ($summary->unpaid_cases ?? 0),
            'ongoing' => (int) ($summary->ongoing_cases ?? 0),
        ];

        $selectedBranch = $branchId ? $branches->firstWhere('id', (int) $branchId) : null;
        $casesPerBranch = collect();

        if (! $branchId) {
            $branchStats = $this->aggregateRowsByBranch(
                FuneralCase::query()
                    ->whereBetween('created_at', [$startAt, $endAt])
                    ->where('verification_status', 'VERIFIED')
            );
            $periodStats = $this->branchFocusedBarData(null, $dateFrom, $dateTo, $range);

            $casesPerBranch = $branches->map(function (Branch $branch) use ($branchStats) {
                $stats = $branchStats->get($branch->id);

                return [
                    'branch' => $branch,
                    'cases' => (int) ($stats->total_cases ?? 0),
                    'paid' => (int) ($stats->paid_cases ?? 0),
                    'partial' => (int) ($stats->partial_cases ?? 0),
                    'unpaid' => (int) ($stats->unpaid_cases ?? 0),
                    'sales' => (float) ($stats->gross_amount ?? 0),
                ];
            });

            $chart = [
                'mode' => 'all',
                'bar' => [
                    'labels' => $casesPerBranch->map(fn ($row) => $row['branch']->branch_name)->values(),
                    'revenue' => $casesPerBranch->pluck('sales')->values(),
                    'volume' => $casesPerBranch->pluck('cases')->values(),
                    'colors' => $casesPerBranch->map(fn ($row) => $branchColors[$row['branch']->id] ?? '#8c4004')->values(),
                ],
                'donut' => [
                    'labels' => $casesPerBranch->map(fn ($row) => $row['branch']->branch_name)->values(),
                    'values' => $casesPerBranch->pluck('sales')->values(),
                    'colors' => $casesPerBranch->map(fn ($row) => $branchColors[$row['branch']->id] ?? '#8c4004')->values(),
                ],
                'period' => [
                    'labels' => $periodStats['labels'],
                    'cases' => $periodStats['volume'],
                    'service_amount' => $periodStats['revenue'],
                    'collected_amount' => $periodStats['collected'],
                    'outstanding_balance' => $periodStats['outstanding'],
                ],
                'line' => $this->revenueTrendData(null, $dateFrom, $dateTo, $range),
            ];
        } else {
            $color = $selectedBranch ? ($branchColors[$selectedBranch->id] ?? '#8c4004') : '#8c4004';
            $branchBar = $this->branchFocusedBarData((int) $branchId, $dateFrom, $dateTo, $range);

            $chart = [
                'mode' => 'single',
                'bar' => [
                    'labels' => $branchBar['labels'],
                    'revenue' => $branchBar['revenue'],
                    'volume' => $branchBar['volume'],
                    'colors' => array_fill(0, count($branchBar['labels']), $color),
                ],
                'donut' => [
                    'labels' => ['Paid', 'Partial', 'Unpaid'],
                    'values' => [$statusCounts['paid'], $statusCounts['partial'], $statusCounts['unpaid']],
                    'colors' => ['#15803d', '#d97706', '#b91c1c'],
                ],
                'period' => [
                    'labels' => $branchBar['labels'],
                    'cases' => $branchBar['volume'],
                    'service_amount' => $branchBar['revenue'],
                    'collected_amount' => $branchBar['collected'],
                    'outstanding_balance' => $branchBar['outstanding'],
                ],
                'line' => $this->revenueTrendData((int) $branchId, $dateFrom, $dateTo, $range),
            ];
        }

        return [
            'branches' => $branches,
            'branchId' => $branchId,
            'selectedBranch' => $selectedBranch,
            'range' => $range,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'totalCases' => $totalCases,
            'totalSales' => (float) ($summary->gross_amount ?? 0),
            'totalCollected' => (float) ($summary->collected_amount ?? 0),
            'totalOutstanding' => (float) ($summary->remaining_balance ?? 0),
            'statusCounts' => $statusCounts,
            'casesPerBranch' => $casesPerBranch,
            'chart' => $chart,
            'allAnalyticsCases' => $this->analyticsCases(clone $base),
        ];
    }

    public function reportRows(array $filters, array $branchScope): Collection
    {
        $query = FuneralCase::query()
            ->select('branch_id')
            ->where('verification_status', 'VERIFIED')
            ->with('branch:id,branch_code,branch_name')
            ->groupBy('branch_id')
            ->orderBy('branch_id');

        $this->applyAggregateSelects($query);
        $this->applyBranchScope($query, $filters, $branchScope);
        $this->applyDateFilters($query, $filters['date_from'] ?? null, $filters['date_to'] ?? null, 'created_at');
        $this->applyDateFilters($query, $filters['interment_from'] ?? null, $filters['interment_to'] ?? null, 'interment_at');

        return $query->get()->map(fn ($row) => [
            'branch_id' => (int) $row->branch_id,
            'branch' => $this->branchName($row->branch),
            'total_cases' => (int) $row->total_cases,
            'paid_cases' => (int) $row->paid_cases,
            'partial_cases' => (int) $row->partial_cases,
            'unpaid_cases' => (int) $row->unpaid_cases,
            'gross_amount' => (float) $row->gross_amount,
            'collected_amount' => (float) $row->collected_amount,
            'remaining_balance' => (float) $row->remaining_balance,
        ])->values();
    }

    public function reportChartData(array $filters, array $branchScope): array
    {
        $dateFrom = $filters['date_from'] ?? now()->toDateString();
        $dateTo = $filters['date_to'] ?? now()->toDateString();
        $range = $this->inferRange($dateFrom, $dateTo);
        $branchId = null;

        if ($branchScope['forced_branch_id'] ?? null) {
            $branchId = (int) $branchScope['forced_branch_id'];
        } elseif (($branchScope['can_select_all'] ?? false) && ! empty($filters['branch_id'])) {
            $branchId = (int) $filters['branch_id'];
        }

        $data = $this->ownerPageData([
            'range' => $range,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'branch_id' => $branchId,
        ]);

        return $data['chart'];
    }

    public function serviceAmountExpression(): string
    {
        return "
            CASE
                WHEN COALESCE(total_paid, 0) > 0 AND COALESCE(balance_amount, 0) = 0 THEN COALESCE(total_paid, 0)
                WHEN COALESCE(total_paid, 0) > 0 AND COALESCE(balance_amount, 0) > 0 THEN COALESCE(total_paid, 0) + COALESCE(balance_amount, 0)
                WHEN COALESCE(total_paid, 0) = 0 AND COALESCE(balance_amount, 0) > 0 THEN COALESCE(balance_amount, 0)
                ELSE COALESCE(total_amount, 0)
            END
        ";
    }

    private function verifiedCasesQuery(Carbon $startAt, Carbon $endAt, mixed $branchId = null): Builder
    {
        return FuneralCase::query()
            ->whereBetween('created_at', [$startAt, $endAt])
            ->where('verification_status', 'VERIFIED')
            ->when($branchId, fn ($query) => $query->where('branch_id', (int) $branchId));
    }

    private function aggregateSummary(Builder $query): object
    {
        return $this->applyAggregateSelects($query)->first();
    }

    private function aggregateRowsByBranch(Builder $query): Collection
    {
        return $this->applyAggregateSelects($query->select('branch_id'))
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');
    }

    private function applyAggregateSelects(Builder $query): Builder
    {
        $serviceAmountExpr = $this->serviceAmountExpression();

        return $query
            ->selectRaw('COUNT(*) as total_cases')
            ->selectRaw("SUM(CASE WHEN COALESCE(total_paid, 0) > 0 AND COALESCE(balance_amount, 0) = 0 THEN 1 ELSE 0 END) as paid_cases")
            ->selectRaw("SUM(CASE WHEN COALESCE(total_paid, 0) > 0 AND COALESCE(balance_amount, 0) > 0 THEN 1 ELSE 0 END) as partial_cases")
            ->selectRaw("SUM(CASE WHEN COALESCE(total_paid, 0) = 0 AND COALESCE(balance_amount, 0) > 0 THEN 1 ELSE 0 END) as unpaid_cases")
            ->selectRaw("SUM(CASE WHEN case_status IN ('DRAFT', 'ACTIVE') THEN 1 ELSE 0 END) as ongoing_cases")
            ->selectRaw("COALESCE(SUM({$serviceAmountExpr}), 0) as gross_amount")
            ->selectRaw('COALESCE(SUM(total_paid), 0) as collected_amount')
            ->selectRaw('COALESCE(SUM(balance_amount), 0) as remaining_balance');
    }

    private function analyticsCases(Builder $query): Collection
    {
        return $query
            ->with(['branch:id,branch_code,branch_name', 'client:id,full_name,first_name,last_name', 'deceased:id,full_name,first_name,last_name'])
            ->latest('created_at')
            ->get()
            ->map(function (FuneralCase $case) {
                $totalPaid = (float) ($case->total_paid ?? 0);
                $balance = (float) ($case->balance_amount ?? 0);
                $serviceAmount = $this->serviceAmountForCase($case);
                $paymentStatus = match (true) {
                    $totalPaid > 0 && $balance <= 0 => 'PAID',
                    $totalPaid > 0 && $balance > 0 => 'PARTIAL',
                    $totalPaid <= 0 && $balance > 0 => 'UNPAID',
                    default => $case->payment_status ?: 'UNPAID',
                };

                return [
                    'caseCode' => $case->case_number ?: $case->case_code,
                    'branchId' => $case->branch_id ? (int) $case->branch_id : null,
                    'branchCode' => (string) ($case->branch?->branch_code ?? ''),
                    'branchName' => (string) ($case->branch?->branch_name ?? ''),
                    'client' => $this->personName($case->client),
                    'deceased' => $this->personName($case->deceased),
                    'caseStatus' => $case->case_status ?: '-',
                    'paymentStatus' => $paymentStatus,
                    'collectionStatus' => $balance > 0 ? 'OUTSTANDING' : 'COLLECTED',
                    'caseDate' => optional($case->created_at)->toDateString(),
                    'caseDateLabel' => optional($case->created_at)->format('M d, Y') ?: '-',
                    'totalAmount' => $serviceAmount,
                    'totalPaid' => $totalPaid,
                    'balanceAmount' => $balance,
                ];
            })
            ->values();
    }

    private function revenueTrendData(?int $branchId, string $dateFrom, string $dateTo, string $range): array
    {
        [$start, $end] = $this->dateBounds($dateFrom, $dateTo);
        $base = $this->verifiedCasesQuery($start, $end, $branchId);
        $serviceAmountExpr = $this->serviceAmountExpression();
        $labels = [];
        $data = [];

        if ($range === 'THIS_YEAR') {
            $rows = (clone $base)
                ->selectRaw("YEAR(created_at) as yr, MONTH(created_at) as mo, COALESCE(SUM({$serviceAmountExpr}), 0) as total")
                ->groupByRaw('YEAR(created_at), MONTH(created_at)')
                ->orderByRaw('YEAR(created_at), MONTH(created_at)')
                ->get()
                ->mapWithKeys(fn ($row) => [sprintf('%04d-%02d', (int) $row->yr, (int) $row->mo) => (float) $row->total]);

            $cursor = $start->copy()->startOfMonth();
            while ($cursor->lte($end)) {
                $key = $cursor->format('Y-m');
                $labels[] = $cursor->format('M');
                $data[] = (float) ($rows[$key] ?? 0);
                $cursor->addMonth();
            }
        } else {
            $rows = (clone $base)
                ->selectRaw("DATE(created_at) as bucket, COALESCE(SUM({$serviceAmountExpr}), 0) as total")
                ->groupByRaw('DATE(created_at)')
                ->orderByRaw('DATE(created_at)')
                ->get()
                ->mapWithKeys(fn ($row) => [(string) $row->bucket => (float) $row->total]);

            $cursor = $start->copy();
            while ($cursor->lte($end)) {
                $key = $cursor->toDateString();
                $labels[] = $cursor->format('M d');
                $data[] = (float) ($rows[$key] ?? 0);
                $cursor->addDay();
            }
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function branchFocusedBarData(?int $branchId, string $dateFrom, string $dateTo, string $range): array
    {
        [$start, $end] = $this->dateBounds($dateFrom, $dateTo);
        $serviceAmountExpr = $this->serviceAmountExpression();
        $base = $this->verifiedCasesQuery($start, $end, $branchId);
        $labels = [];
        $revenue = [];
        $volume = [];
        $collected = [];
        $outstanding = [];

        if ($range === 'THIS_YEAR') {
            $rows = (clone $base)
                ->selectRaw('YEAR(created_at) as yr, MONTH(created_at) as mo')
                ->selectRaw("COALESCE(SUM({$serviceAmountExpr}), 0) as revenue")
                ->selectRaw('COUNT(*) as volume')
                ->selectRaw('COALESCE(SUM(total_paid), 0) as collected')
                ->selectRaw('COALESCE(SUM(balance_amount), 0) as outstanding')
                ->groupByRaw('YEAR(created_at), MONTH(created_at)')
                ->orderByRaw('YEAR(created_at), MONTH(created_at)')
                ->get()
                ->mapWithKeys(fn ($row) => [sprintf('%04d-%02d', (int) $row->yr, (int) $row->mo) => [
                    'revenue' => (float) $row->revenue,
                    'volume' => (int) $row->volume,
                    'collected' => (float) $row->collected,
                    'outstanding' => (float) $row->outstanding,
                ]]);

            $cursor = $start->copy()->startOfMonth();
            while ($cursor->lte($end)) {
                $row = $rows[$cursor->format('Y-m')] ?? null;
                $labels[] = $cursor->format('M');
                $revenue[] = (float) ($row['revenue'] ?? 0);
                $volume[] = (int) ($row['volume'] ?? 0);
                $collected[] = (float) ($row['collected'] ?? 0);
                $outstanding[] = (float) ($row['outstanding'] ?? 0);
                $cursor->addMonth();
            }
        } else {
            $dailyRows = (clone $base)
                ->selectRaw('DATE(created_at) as bucket')
                ->selectRaw("COALESCE(SUM({$serviceAmountExpr}), 0) as revenue")
                ->selectRaw('COUNT(*) as volume')
                ->selectRaw('COALESCE(SUM(total_paid), 0) as collected')
                ->selectRaw('COALESCE(SUM(balance_amount), 0) as outstanding')
                ->groupByRaw('DATE(created_at)')
                ->orderByRaw('DATE(created_at)')
                ->get()
                ->keyBy('bucket');

            $cursor = $start->copy()->startOfWeek();
            $limit = 0;
            while ($cursor->lte($end) && $limit < 12) {
                $weekStart = $cursor->copy()->startOfWeek()->startOfDay();
                $weekEnd = $cursor->copy()->endOfWeek()->endOfDay();
                $labels[] = $weekStart->format('M d') . ' - ' . $weekEnd->format('M d');

                $weekRevenue = 0.0;
                $weekVolume = 0;
                $weekCollected = 0.0;
                $weekOutstanding = 0.0;
                $day = $weekStart->copy();

                while ($day->lte($weekEnd)) {
                    if ($day->betweenIncluded($start, $end)) {
                        $row = $dailyRows->get($day->toDateString());
                        $weekRevenue += (float) ($row->revenue ?? 0);
                        $weekVolume += (int) ($row->volume ?? 0);
                        $weekCollected += (float) ($row->collected ?? 0);
                        $weekOutstanding += (float) ($row->outstanding ?? 0);
                    }

                    $day->addDay();
                }

                $revenue[] = $weekRevenue;
                $volume[] = $weekVolume;
                $collected[] = $weekCollected;
                $outstanding[] = $weekOutstanding;
                $cursor->addWeek();
                $limit++;
            }
        }

        return compact('labels', 'revenue', 'volume', 'collected', 'outstanding');
    }

    private function applyBranchScope(Builder $query, array $filters, array $branchScope): void
    {
        if ($branchScope['forced_branch_id'] ?? null) {
            $query->where('branch_id', (int) $branchScope['forced_branch_id']);
            return;
        }

        if (($branchScope['can_select_all'] ?? false) && ! empty($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }
    }

    private function applyDateFilters(Builder $query, ?string $dateFrom, ?string $dateTo, string $column): void
    {
        [$startAt, $endAt] = $this->parseNullableDateBounds($dateFrom, $dateTo);
        if ($startAt) {
            $query->where($column, '>=', $startAt);
        }
        if ($endAt) {
            $query->where($column, '<=', $endAt);
        }
    }

    private function parseNullableDateBounds(?string $dateFrom, ?string $dateTo): array
    {
        return [
            $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null,
            $dateTo ? Carbon::parse($dateTo)->endOfDay() : null,
        ];
    }

    private function dateBounds(string $dateFrom, string $dateTo): array
    {
        return [
            Carbon::parse($dateFrom)->startOfDay(),
            Carbon::parse($dateTo)->endOfDay(),
        ];
    }

    private function resolveRange(string $range, ?string $dateFrom, ?string $dateTo): array
    {
        if ($range === 'TODAY') {
            return [now()->toDateString(), now()->toDateString()];
        }
        if ($range === 'THIS_MONTH') {
            return [now()->startOfMonth()->toDateString(), now()->toDateString()];
        }
        if ($range === 'THIS_YEAR') {
            return [now()->startOfYear()->toDateString(), now()->toDateString()];
        }

        return [
            $dateFrom ?: now()->startOfMonth()->toDateString(),
            $dateTo ?: now()->toDateString(),
        ];
    }

    private function inferRange(string $dateFrom, string $dateTo): string
    {
        $from = Carbon::parse($dateFrom)->toDateString();
        $to = Carbon::parse($dateTo)->toDateString();

        if ($from === now()->toDateString() && $to === now()->toDateString()) {
            return 'TODAY';
        }

        if ($from === now()->startOfMonth()->toDateString()) {
            return 'THIS_MONTH';
        }

        if ($from === now()->startOfYear()->toDateString()) {
            return 'THIS_YEAR';
        }

        return 'CUSTOM';
    }

    private function branchColorMap(Collection $branches): array
    {
        $palette = ['#8c4004', '#15803d', '#b91c1c'];
        $map = [];

        foreach ($branches as $index => $branch) {
            $map[$branch->id] = $palette[$index] ?? '#8c4004';
        }

        return $map;
    }

    private function serviceAmountForCase(FuneralCase $case): float
    {
        $totalPaid = (float) ($case->total_paid ?? 0);
        $balance = (float) ($case->balance_amount ?? 0);

        return match (true) {
            $totalPaid > 0 && $balance <= 0 => $totalPaid,
            $totalPaid > 0 && $balance > 0 => $totalPaid + $balance,
            $totalPaid <= 0 && $balance > 0 => $balance,
            default => (float) ($case->total_amount ?? 0),
        };
    }

    private function branchName(?Branch $branch): string
    {
        if (! $branch) {
            return '-';
        }

        return trim(($branch->branch_code ? $branch->branch_code . ' - ' : '') . ($branch->branch_name ?? '')) ?: '-';
    }

    private function personName($model): string
    {
        if (! $model) {
            return '-';
        }

        $firstLast = trim(implode(' ', array_filter([
            $model->first_name ?? null,
            $model->last_name ?? null,
        ])));

        return $model->full_name
            ?? ($firstLast ?: null)
            ?? $model->name
            ?? '-';
    }
}
