<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\FuneralCase;
use App\Models\Payment;
use App\Support\ReportingPeriod;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BranchAnalyticsService
{
    public function ownerPageData(array $filters, ?array $branchScope = null): array
    {
        $range = $filters['range'] ?? 'THIS_MONTH';
        $branchId = $filters['branch_id'] ?? null;

        if ($branchScope['forced_branch_id'] ?? null) {
            $branchId = (int) $branchScope['forced_branch_id'];
        }

        $period = ReportingPeriod::resolve($range, $filters['date_from'] ?? null, $filters['date_to'] ?? null);
        $range = $period['key'];
        $dateFrom = $period['date_from'];
        $dateTo = $period['date_to'];

        $branches = Branch::query()
            ->when(
                $branchScope && ! ($branchScope['can_select_all'] ?? false),
                fn ($query) => $query->where('id', (int) ($branchScope['forced_branch_id'] ?? 0))
            )
            ->orderBy('branch_code')
            ->get();
        $branchColors = $this->branchColorMap($branches);
        $base = $this->verifiedCasesQuery($period, $branchId);
        $summary = $this->aggregateSummary(clone $base);
        $collectionsReceived = $this->paymentPeriodTotal($period, $branchId);
        $cohortCollected = (float) ($summary->collected_amount ?? 0);

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
            $branchStats = $this->aggregateRowsByBranch($this->verifiedCasesQuery($period));
            $periodStats = $this->branchFocusedBarData(null, $period);

            $casesPerBranch = $branches->map(function (Branch $branch) use ($branchStats) {
                $stats = $branchStats->get($branch->id);

                return [
                    'branch' => $branch,
                    'cases' => (int) ($stats->total_cases ?? 0),
                    'paid' => (int) ($stats->paid_cases ?? 0),
                    'partial' => (int) ($stats->partial_cases ?? 0),
                    'unpaid' => (int) ($stats->unpaid_cases ?? 0),
                    'sales' => (float) ($stats->gross_amount ?? 0),
                    'collected' => (float) ($stats->collected_amount ?? 0),
                    'remaining' => (float) ($stats->remaining_balance ?? 0),
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
                    'available_from' => $periodStats['available_from'],
                    'available_to' => $periodStats['available_to'],
                ],
                'line' => ['labels' => $periodStats['labels'], 'data' => $periodStats['revenue']],
            ];
        } else {
            $color = $selectedBranch ? ($branchColors[$selectedBranch->id] ?? '#8c4004') : '#8c4004';
            $branchBar = $this->branchFocusedBarData((int) $branchId, $period);

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
                    'available_from' => $branchBar['available_from'],
                    'available_to' => $branchBar['available_to'],
                ],
                'line' => ['labels' => $branchBar['labels'], 'data' => $branchBar['revenue']],
            ];
        }

        return [
            'branches' => $branches,
            'branchId' => $branchId,
            'selectedBranch' => $selectedBranch,
            'range' => $range,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'availableDateFrom' => $chart['period']['available_from'],
            'availableDateTo' => $chart['period']['available_to'],
            'totalCases' => $totalCases,
            'totalSales' => (float) ($summary->gross_amount ?? 0),
            'totalCollected' => $collectionsReceived,
            'cohortCollected' => $cohortCollected,
            'collectionRate' => (float) ($summary->gross_amount ?? 0) > 0
                ? ($cohortCollected / (float) $summary->gross_amount) * 100
                : 0.0,
            'totalOutstanding' => (float) ($summary->remaining_balance ?? 0),
            'statusCounts' => $statusCounts,
            'casesPerBranch' => $casesPerBranch,
            'chart' => $chart,
            'allAnalyticsCases' => $this->analyticsCases(clone $base),
            'branchComparisonRows' => $casesPerBranch->map(fn ($row) => [
                'branch' => $row['branch'],
                'service' => $row['sales'],
                'cases' => $row['cases'],
                'cohortCollected' => $row['collected'],
                'cohortRemaining' => $row['remaining'],
                'rate' => $row['sales'] > 0 ? ($row['collected'] / $row['sales']) * 100 : null,
            ])->values(),
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
        $period = ($filters['date_from'] ?? null) || ($filters['date_to'] ?? null)
            ? ReportingPeriod::resolve('CUSTOM', $filters['date_from'] ?? null, $filters['date_to'] ?? null)
            : ReportingPeriod::resolve('ALL_DATES');
        ReportingPeriod::apply($query, 'COALESCE(service_requested_at, created_at)', $period);
        $this->applyDateFilters($query, $filters['interment_from'] ?? null, $filters['interment_to'] ?? null, 'interment_at');

        $collectionTotals = $this->paymentsByBranch($period, $filters, $branchScope);

        $caseRows = $query->get()->keyBy(fn ($row) => (int) $row->branch_id);
        $branchIds = $caseRows->keys()->merge($collectionTotals->keys()->map(fn ($id) => (int) $id))->unique()->values();
        $branches = Branch::query()->whereIn('id', $branchIds)->get()->keyBy('id');

        return $branchIds->map(function ($branchId) use ($caseRows, $collectionTotals, $branches) {
            $row = $caseRows->get((int) $branchId);
            return [
                'branch_id' => (int) $branchId,
                'branch' => $this->branchName($branches->get((int) $branchId)),
                'total_cases' => (int) ($row->total_cases ?? 0),
                'paid_cases' => (int) ($row->paid_cases ?? 0),
                'partial_cases' => (int) ($row->partial_cases ?? 0),
                'unpaid_cases' => (int) ($row->unpaid_cases ?? 0),
                'gross_amount' => (float) ($row->gross_amount ?? 0),
                'collected_amount' => (float) ($collectionTotals[(int) $branchId] ?? 0),
                'remaining_balance' => (float) ($row->remaining_balance ?? 0),
            ];
        })->values();
    }

    public function reportChartData(array $filters, array $branchScope): array
    {
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $branchId = null;

        if ($branchScope['forced_branch_id'] ?? null) {
            $branchId = (int) $branchScope['forced_branch_id'];
        } elseif (($branchScope['can_select_all'] ?? false) && ! empty($filters['branch_id'])) {
            $branchId = (int) $filters['branch_id'];
        }

        $range = (! $dateFrom && ! $dateTo) ? 'ALL_DATES' : 'CUSTOM';

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
        return 'COALESCE(total_amount, 0)';
    }

    private function verifiedCasesQuery(array $period, mixed $branchId = null): Builder
    {
        $query = FuneralCase::query()
            ->where('verification_status', 'VERIFIED')
            ->when($branchId, fn ($query) => $query->where('branch_id', (int) $branchId));
        ReportingPeriod::apply($query, 'COALESCE(service_requested_at, created_at)', $period);

        return $query;
    }

    private function paymentPeriodTotal(array $period, mixed $branchId = null): float
    {
        $query = Payment::query()
            ->validForReporting()
            ->whereHas('funeralCase', function (Builder $caseQuery) use ($branchId) {
                $caseQuery->where('verification_status', 'VERIFIED')
                    ->when($branchId, fn (Builder $query) => $query->where('branch_id', (int) $branchId));
            });
        ReportingPeriod::apply($query, 'COALESCE(paid_at, paid_date, created_at)', $period);

        return (float) $query->sum('amount');
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
                    'caseDate' => optional($case->service_requested_at ?: $case->created_at)->toDateString(),
                    'caseDateLabel' => optional($case->service_requested_at ?: $case->created_at)->format('M d, Y') ?: '-',
                    'totalAmount' => $serviceAmount,
                    'totalPaid' => $totalPaid,
                    'balanceAmount' => $balance,
                ];
            })
            ->values();
    }

    private function revenueTrendData(?int $branchId, array $period): array
    {
        [$start, $end, $hasAvailableData] = $this->chartBounds($period, $branchId);
        $boundedPeriod = ['start' => $start, 'end_exclusive' => $end->copy()->addDay()->startOfDay()];
        $base = $this->verifiedCasesQuery($boundedPeriod, $branchId);
        $serviceAmountExpr = $this->serviceAmountExpression();
        $labels = [];
        $data = [];
        $granularity = ReportingPeriod::granularity($boundedPeriod);

        if ($granularity === 'year') {
            $rows = (clone $base)
                ->selectRaw("YEAR(COALESCE(service_requested_at, created_at)) as yr, COALESCE(SUM({$serviceAmountExpr}), 0) as total")
                ->groupByRaw('YEAR(COALESCE(service_requested_at, created_at))')
                ->orderByRaw('YEAR(COALESCE(service_requested_at, created_at))')
                ->get()
                ->mapWithKeys(fn ($row) => [(string) $row->yr => (float) $row->total]);

            $cursor = $start->copy()->startOfYear();
            while ($cursor->lte($end)) {
                $key = $cursor->format('Y');
                $labels[] = $key;
                $data[] = (float) ($rows[$key] ?? 0);
                $cursor->addYear();
            }
        } elseif ($granularity === 'month') {
            $rows = (clone $base)
                ->selectRaw("YEAR(COALESCE(service_requested_at, created_at)) as yr, MONTH(COALESCE(service_requested_at, created_at)) as mo, COALESCE(SUM({$serviceAmountExpr}), 0) as total")
                ->groupByRaw('YEAR(COALESCE(service_requested_at, created_at)), MONTH(COALESCE(service_requested_at, created_at))')
                ->orderByRaw('YEAR(COALESCE(service_requested_at, created_at)), MONTH(COALESCE(service_requested_at, created_at))')
                ->get()
                ->mapWithKeys(fn ($row) => [sprintf('%04d-%02d', (int) $row->yr, (int) $row->mo) => (float) $row->total]);

            $cursor = $start->copy()->startOfMonth();
            while ($cursor->lte($end)) {
                $key = $cursor->format('Y-m');
                $labels[] = $start->year === $end->year ? $cursor->format('M') : $cursor->format('M Y');
                $data[] = (float) ($rows[$key] ?? 0);
                $cursor->addMonth();
            }
        } else {
            $rows = (clone $base)
                ->selectRaw("DATE(COALESCE(service_requested_at, created_at)) as bucket, COALESCE(SUM({$serviceAmountExpr}), 0) as total")
                ->groupByRaw('DATE(COALESCE(service_requested_at, created_at))')
                ->orderByRaw('DATE(COALESCE(service_requested_at, created_at))')
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

    private function branchFocusedBarData(?int $branchId, array $period): array
    {
        [$start, $end, $hasAvailableData] = $this->chartBounds($period, $branchId);
        $boundedPeriod = ['start' => $start, 'end_exclusive' => $end->copy()->addDay()->startOfDay()];
        $serviceAmountExpr = $this->serviceAmountExpression();
        $base = $this->verifiedCasesQuery($boundedPeriod, $branchId);
        $labels = [];
        $revenue = [];
        $volume = [];
        $collected = [];
        $outstanding = [];
        $granularity = ReportingPeriod::granularity($boundedPeriod);

        if ($granularity === 'year') {
            $rows = (clone $base)
                ->selectRaw('YEAR(COALESCE(service_requested_at, created_at)) as yr')
                ->selectRaw("COALESCE(SUM({$serviceAmountExpr}), 0) as revenue")
                ->selectRaw('COUNT(*) as volume')
                ->selectRaw('COALESCE(SUM(total_paid), 0) as collected')
                ->selectRaw('COALESCE(SUM(balance_amount), 0) as outstanding')
                ->groupByRaw('YEAR(COALESCE(service_requested_at, created_at))')
                ->orderByRaw('YEAR(COALESCE(service_requested_at, created_at))')
                ->get()
                ->keyBy('yr');

            $cursor = $start->copy()->startOfYear();
            while ($cursor->lte($end)) {
                $row = $rows->get((int) $cursor->year);
                $labels[] = $cursor->format('Y');
                $revenue[] = (float) ($row->revenue ?? 0);
                $volume[] = (int) ($row->volume ?? 0);
                $collected[] = (float) ($row->collected ?? 0);
                $outstanding[] = (float) ($row->outstanding ?? 0);
                $cursor->addYear();
            }
        } elseif ($granularity === 'month') {
            $rows = (clone $base)
                ->selectRaw('YEAR(COALESCE(service_requested_at, created_at)) as yr, MONTH(COALESCE(service_requested_at, created_at)) as mo')
                ->selectRaw("COALESCE(SUM({$serviceAmountExpr}), 0) as revenue")
                ->selectRaw('COUNT(*) as volume')
                ->selectRaw('COALESCE(SUM(total_paid), 0) as collected')
                ->selectRaw('COALESCE(SUM(balance_amount), 0) as outstanding')
                ->groupByRaw('YEAR(COALESCE(service_requested_at, created_at)), MONTH(COALESCE(service_requested_at, created_at))')
                ->orderByRaw('YEAR(COALESCE(service_requested_at, created_at)), MONTH(COALESCE(service_requested_at, created_at))')
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
                $labels[] = $start->year === $end->year ? $cursor->format('M') : $cursor->format('M Y');
                $revenue[] = (float) ($row['revenue'] ?? 0);
                $volume[] = (int) ($row['volume'] ?? 0);
                $collected[] = (float) ($row['collected'] ?? 0);
                $outstanding[] = (float) ($row['outstanding'] ?? 0);
                $cursor->addMonth();
            }
        } elseif ($granularity === 'week') {
            $dailyRows = (clone $base)
                ->selectRaw('DATE(COALESCE(service_requested_at, created_at)) as bucket')
                ->selectRaw("COALESCE(SUM({$serviceAmountExpr}), 0) as revenue")
                ->selectRaw('COUNT(*) as volume')
                ->selectRaw('COALESCE(SUM(total_paid), 0) as collected')
                ->selectRaw('COALESCE(SUM(balance_amount), 0) as outstanding')
                ->groupByRaw('DATE(COALESCE(service_requested_at, created_at))')
                ->orderByRaw('DATE(COALESCE(service_requested_at, created_at))')
                ->get()
                ->keyBy('bucket');

            $cursor = $start->copy()->startOfWeek();
            $limit = 0;
            while ($cursor->lte($end) && $limit < 12) {
                $weekStart = $cursor->copy()->startOfWeek()->startOfDay();
                $weekEnd = $cursor->copy()->endOfWeek()->endOfDay();
                $visibleStart = $weekStart->lt($start) ? $start->copy() : $weekStart;
                $visibleEnd = $weekEnd->gt($end) ? $end->copy() : $weekEnd;
                $labels[] = $visibleStart->isSameDay($visibleEnd)
                    ? $visibleStart->format('M d')
                    : $visibleStart->format('M d') . ' - ' . $visibleEnd->format('M d');

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
        } else {
            $dailyRows = (clone $base)
                ->selectRaw('DATE(COALESCE(service_requested_at, created_at)) as bucket')
                ->selectRaw("COALESCE(SUM({$serviceAmountExpr}), 0) as revenue")
                ->selectRaw('COUNT(*) as volume')
                ->selectRaw('COALESCE(SUM(balance_amount), 0) as outstanding')
                ->groupByRaw('DATE(COALESCE(service_requested_at, created_at))')
                ->orderByRaw('DATE(COALESCE(service_requested_at, created_at))')
                ->get()->keyBy('bucket');

            for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
                $row = $dailyRows->get($cursor->toDateString());
                $labels[] = $cursor->format('M d');
                $revenue[] = (float) ($row->revenue ?? 0);
                $volume[] = (int) ($row->volume ?? 0);
                $collected[] = 0.0;
                $outstanding[] = (float) ($row->outstanding ?? 0);
            }
        }

        // Collections use transaction received dates, not the case request-date cohort.
        $collected = $this->paymentSeries($branchId, $start, $end, $granularity, $labels);

        $availableFrom = $hasAvailableData ? $start->toDateString() : null;
        $availableTo = $hasAvailableData ? $end->toDateString() : null;

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'volume' => $volume,
            'collected' => $collected,
            'outstanding' => $outstanding,
            'available_from' => $availableFrom,
            'available_to' => $availableTo,
        ];
    }

    private function chartBounds(array $period, ?int $branchId): array
    {
        if ($period['start'] && $period['end_exclusive']) {
            return [$period['start']->copy(), $period['end_exclusive']->copy()->subDay()->endOfDay(), true];
        }

        $caseQuery = FuneralCase::query()
            ->where('verification_status', 'VERIFIED')
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId));
        $first = (clone $caseQuery)->min(\DB::raw('COALESCE(service_requested_at, created_at)'));
        $last = (clone $caseQuery)->max(\DB::raw('COALESCE(service_requested_at, created_at)'));
        $paymentQuery = Payment::query()
            ->validForReporting()
            ->whereHas('funeralCase', function (Builder $query) use ($branchId) {
                $query->where('verification_status', 'VERIFIED')
                    ->when($branchId, fn (Builder $scoped) => $scoped->where('branch_id', $branchId));
            });
        $firstPayment = (clone $paymentQuery)->min(\DB::raw('COALESCE(paid_at, paid_date, created_at)'));
        $lastPayment = (clone $paymentQuery)->max(\DB::raw('COALESCE(paid_at, paid_date, created_at)'));
        $first = collect([$first, $firstPayment])->filter()->min();
        $last = collect([$last, $lastPayment])->filter()->max();

        return [
            $first ? Carbon::parse($first)->startOfDay() : now()->startOfDay(),
            $last ? Carbon::parse($last)->endOfDay() : now()->endOfDay(),
            (bool) ($first || $last),
        ];
    }

    private function paymentsByBranch(array $period, array $filters, array $branchScope): Collection
    {
        $query = Payment::query()
            ->validForReporting()
            ->join('funeral_cases', 'funeral_cases.id', '=', 'payments.funeral_case_id')
            ->whereNull('funeral_cases.deleted_at')
            ->where('funeral_cases.verification_status', 'VERIFIED')
            ->selectRaw('funeral_cases.branch_id as cohort_branch_id')
            ->selectRaw('COALESCE(SUM(amount), 0) as total')
            ->groupBy('funeral_cases.branch_id');
        if ($branchScope['forced_branch_id'] ?? null) {
            $query->where('funeral_cases.branch_id', (int) $branchScope['forced_branch_id']);
        } elseif (($branchScope['can_select_all'] ?? false) && ! empty($filters['branch_id'])) {
            $query->where('funeral_cases.branch_id', (int) $filters['branch_id']);
        }
        ReportingPeriod::apply($query, 'COALESCE(payments.paid_at, payments.paid_date, payments.created_at)', $period);

        return $query->pluck('total', 'cohort_branch_id');
    }

    private function paymentSeries(?int $branchId, Carbon $start, Carbon $end, string $granularity, array $labels): array
    {
        $period = ['start' => $start->copy()->startOfDay(), 'end_exclusive' => $end->copy()->addDay()->startOfDay()];
        $query = Payment::query()
            ->validForReporting()
            ->whereHas('funeralCase', function (Builder $caseQuery) use ($branchId) {
                $caseQuery->where('verification_status', 'VERIFIED')
                    ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId));
            });
        ReportingPeriod::apply($query, 'COALESCE(paid_at, paid_date, created_at)', $period);
        $daily = $query
            ->selectRaw('DATE(COALESCE(paid_at, paid_date, created_at)) as bucket, COALESCE(SUM(amount), 0) as total')
            ->groupByRaw('DATE(COALESCE(paid_at, paid_date, created_at))')
            ->pluck('total', 'bucket');
        $series = array_fill(0, count($labels), 0.0);

        foreach ($daily as $date => $amount) {
            $paidDate = Carbon::parse($date);
            $index = match ($granularity) {
                'year' => array_search($paidDate->format('Y'), $labels, true),
                'month' => array_search($start->year === $end->year ? $paidDate->format('M') : $paidDate->format('M Y'), $labels, true),
                'week' => intdiv($start->copy()->startOfWeek()->diffInDays($paidDate->copy()->startOfWeek()), 7),
                default => array_search($paidDate->format('M d'), $labels, true),
            };
            if ($index !== false && isset($series[$index])) {
                $series[$index] += (float) $amount;
            }
        }

        return $series;
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
        return (float) ($case->total_amount ?? 0);
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
