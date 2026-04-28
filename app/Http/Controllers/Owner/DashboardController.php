<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\FuneralCase;
use App\Models\Package;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $filters = $this->validatedDashboardFilters($request);
        $branchId = $filters['branch_id'];
        $dateFrom = $filters['date_from'];
        $dateTo = $filters['date_to'];
        $range = $filters['range'];
        [$startAt, $endAt] = $this->parseDateBounds($dateFrom, $dateTo);

        $base = FuneralCase::query()
            ->where('verification_status', 'VERIFIED')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$startAt, $endAt]);

        $summary = $this->buildAggregateSummary((clone $base));
        $totalCases = (int) ($summary->total_cases ?? 0);
        $paidCases = (int) ($summary->paid_cases ?? 0);
        $partialCases = (int) ($summary->partial_cases ?? 0);
        $unpaidCases = (int) ($summary->unpaid_cases ?? 0);
        $totalSales = (float) ($summary->total_sales ?? 0);
        $totalCollected = (float) ($summary->total_collected ?? 0);
        $totalOutstanding = (float) ($summary->total_outstanding ?? 0);
        $ongoingCases = (clone $base)
            ->where(function ($q) {
                $q->whereIn('case_status', ['DRAFT', 'ACTIVE'])
                    ->orWhereHas('deceased', function ($dq) {
                        $dq->whereNull('interment');
                    });
            })
            ->count();

        $branches = Branch::orderBy('branch_code')->get();
        $branchStats = $this->buildAggregateRowsByBranch(
            FuneralCase::query()
                ->where('verification_status', 'VERIFIED')
                ->whereBetween('created_at', [$startAt, $endAt])
        );
        $branchCards = $branches->map(function ($branch) use ($branchStats) {
            $stats = $branchStats->get($branch->id);
            return [
                'branch' => $branch,
                'total_cases' => (int) ($stats->total_cases ?? 0),
                'paid_cases' => (int) ($stats->paid_cases ?? 0),
                'partial_cases' => (int) ($stats->partial_cases ?? 0),
                'unpaid_cases' => (int) ($stats->unpaid_cases ?? 0),
                'sales' => (float) ($stats->total_sales ?? 0),
                'collected' => (float) ($stats->total_collected ?? 0),
                'outstanding' => (float) ($stats->total_outstanding ?? 0),
            ];
        });

        $branchRevenue = $branchCards->mapWithKeys(function ($row) {
            return [$row['branch']->branch_code => (float) $row['sales']];
        });

        $recentCases = FuneralCase::with(['branch', 'client', 'deceased'])
            ->where('verification_status', 'VERIFIED')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$startAt, $endAt])
            ->latest()
            ->take(10)
            ->get();

        $selectedBranch = $branchId ? $branches->firstWhere('id', (int) $branchId) : null;
        $topPackages = collect();
        if ($branchId) {
            $topPackages = FuneralCase::query()
                ->where('verification_status', 'VERIFIED')
                ->where('branch_id', $branchId)
                ->whereBetween('created_at', [$startAt, $endAt])
                ->where('payment_status', 'PAID')
                ->whereNotNull('service_package')
                ->selectRaw('service_package, COUNT(*) as total_cases, COALESCE(SUM(total_amount), 0) as total_sales')
                ->groupBy('service_package')
                ->orderByDesc('total_cases')
                ->orderByDesc('total_sales')
                ->limit(5)
                ->get();
        }

        return view('dashboards.owner', compact(
            'totalCases',
            'paidCases',
            'partialCases',
            'unpaidCases',
            'totalSales',
            'totalCollected',
            'totalOutstanding',
            'ongoingCases',
            'branchRevenue',
            'branchCards',
            'recentCases',
            'branches',
            'branchId',
            'dateFrom',
            'dateTo',
            'range',
            'selectedBranch',
            'topPackages'
        ));
    }

    public function salesPerBranch(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $branches = Branch::orderBy('branch_code')->get();
        [$startAt, $endAt] = $this->parseDateBounds($filters['date_from'], $filters['date_to']);

        $baseQuery = FuneralCase::query()
            ->where('verification_status', 'VERIFIED')
            ->when($filters['branch_id'], fn ($q) => $q->where('branch_id', $filters['branch_id']))
            ->when($filters['interment_from'] || $filters['interment_to'], function ($q) use ($filters) {
                $q->whereHas('deceased', function ($dq) use ($filters) {
                    if ($filters['interment_from']) {
                        $dq->whereRaw('DATE(COALESCE(interment_at, interment)) >= ?', [$filters['interment_from']]);
                    }
                    if ($filters['interment_to']) {
                        $dq->whereRaw('DATE(COALESCE(interment_at, interment)) <= ?', [$filters['interment_to']]);
                    }
                });
            })
            ->whereBetween('created_at', [$startAt, $endAt]);

        $summary = $this->buildAggregateSummary((clone $baseQuery));
        $totalCases = (int) ($summary->total_cases ?? 0);
        $paidCases = (int) ($summary->paid_cases ?? 0);
        $partialCases = (int) ($summary->partial_cases ?? 0);
        $unpaidCases = (int) ($summary->unpaid_cases ?? 0);
        $totalSales = (float) ($summary->total_sales ?? 0);
        $totalCollected = (float) ($summary->total_collected ?? 0);
        $totalOutstanding = (float) ($summary->total_outstanding ?? 0);

        $branchSet = $filters['branch_id']
            ? $branches->where('id', (int) $filters['branch_id'])->values()
            : $branches;
        $branchRows = $this->buildAggregateRowsByBranch((clone $baseQuery));
        $branchSummary = $branchSet->map(function ($branch) use ($branchRows) {
            $row = $branchRows->get($branch->id);
            return [
                'branch' => $branch,
                'total_cases' => (int) ($row->total_cases ?? 0),
                'paid_cases' => (int) ($row->paid_cases ?? 0),
                'partial_cases' => (int) ($row->partial_cases ?? 0),
                'unpaid_cases' => (int) ($row->unpaid_cases ?? 0),
                'sales' => (float) ($row->total_sales ?? 0),
                'collected' => (float) ($row->total_collected ?? 0),
                'outstanding' => (float) ($row->total_outstanding ?? 0),
            ];
        });

        $cases = (clone $baseQuery)
            ->with(['branch', 'client', 'deceased'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('owner.sales_per_branch', [
            'branches' => $branches,
            'filters' => $filters,
            'totalCases' => $totalCases,
            'paidCases' => $paidCases,
            'partialCases' => $partialCases,
            'unpaidCases' => $unpaidCases,
            'totalSales' => $totalSales,
            'totalCollected' => $totalCollected,
            'totalOutstanding' => $totalOutstanding,
            'branchSummary' => $branchSummary,
            'cases' => $cases,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        [$startAt, $endAt] = $this->parseDateBounds($filters['date_from'], $filters['date_to']);

        $rows = FuneralCase::with(['branch', 'client', 'deceased'])
            ->where('verification_status', 'VERIFIED')
            ->when($filters['branch_id'], fn ($q) => $q->where('branch_id', $filters['branch_id']))
            ->when($filters['interment_from'] || $filters['interment_to'], function ($q) use ($filters) {
                $q->whereHas('deceased', function ($dq) use ($filters) {
                    if ($filters['interment_from']) {
                        $dq->whereRaw('DATE(COALESCE(interment_at, interment)) >= ?', [$filters['interment_from']]);
                    }
                    if ($filters['interment_to']) {
                        $dq->whereRaw('DATE(COALESCE(interment_at, interment)) <= ?', [$filters['interment_to']]);
                    }
                });
            })
            ->whereBetween('created_at', [$startAt, $endAt])
            ->latest()
            ->get();

        $fileName = 'owner-report-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Case Code',
                'Request Date',
                'Branch',
                'Client',
                'Deceased',
                'Case Status',
                'Payment Status',
                'Interment At',
                'Total Amount',
                'Total Paid',
                'Balance Amount',
            ]);

            foreach ($rows as $case) {
                fputcsv($handle, [
                    $case->case_code,
                    optional($case->service_requested_at)->format('Y-m-d') ?: optional($case->created_at)->format('Y-m-d'),
                    $case->branch?->branch_name ?? '',
                    $case->client?->full_name ?? '',
                    $case->deceased?->full_name ?? '',
                    $case->case_status,
                    $case->payment_status,
                    $case->deceased?->interment_at?->format('Y-m-d H:i') ?? $case->deceased?->interment?->format('Y-m-d') ?? '',
                    $case->total_amount,
                    $case->total_paid,
                    $case->balance_amount,
                ]);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    public function show(FuneralCase $funeral_case)
    {
        if (($funeral_case->verification_status ?? 'VERIFIED') !== 'VERIFIED') {
            abort(404);
        }

        $funeral_case->load(['branch', 'client', 'deceased', 'reportedBranch', 'encodedBy', 'payments.recordedBy', 'package']);

        return view('owner.cases.show', compact('funeral_case'));
    }

    public function analytics(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|integer|exists:branches,id',
            'range' => 'nullable|in:TODAY,THIS_MONTH,THIS_YEAR,CUSTOM',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $range = $validated['range'] ?? 'TODAY';
        [$dateFrom, $dateTo] = $this->resolveAnalyticsRange(
            $range,
            $validated['date_from'] ?? null,
            $validated['date_to'] ?? null
        );
        $startAt = Carbon::parse($dateFrom)->startOfDay();
        $endAt = Carbon::parse($dateTo)->endOfDay();
        $branchId = $validated['branch_id'] ?? null;

        $branches = Branch::orderBy('branch_code')->get();
        $branchColors = $this->branchColorMap($branches);
        $base = FuneralCase::query()->whereBetween('created_at', [$startAt, $endAt])
            ->where('verification_status', 'VERIFIED')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
        $serviceAmountExpr = "
            CASE
                WHEN COALESCE(total_paid, 0) > 0 AND COALESCE(balance_amount, 0) = 0 THEN COALESCE(total_paid, 0)
                WHEN COALESCE(total_paid, 0) > 0 AND COALESCE(balance_amount, 0) > 0 THEN COALESCE(total_paid, 0) + COALESCE(balance_amount, 0)
                WHEN COALESCE(total_paid, 0) = 0 AND COALESCE(balance_amount, 0) > 0 THEN COALESCE(balance_amount, 0)
                ELSE 0
            END
        ";

        $summary = (clone $base)
            ->selectRaw('COUNT(*) as total_cases')
            ->selectRaw("SUM(CASE WHEN COALESCE(total_paid, 0) > 0 AND COALESCE(balance_amount, 0) = 0 THEN 1 ELSE 0 END) as paid_cases")
            ->selectRaw("SUM(CASE WHEN COALESCE(total_paid, 0) > 0 AND COALESCE(balance_amount, 0) > 0 THEN 1 ELSE 0 END) as partial_cases")
            ->selectRaw("SUM(CASE WHEN COALESCE(total_paid, 0) = 0 AND COALESCE(balance_amount, 0) > 0 THEN 1 ELSE 0 END) as unpaid_cases")
            ->selectRaw("SUM(CASE WHEN case_status IN ('DRAFT', 'ACTIVE') THEN 1 ELSE 0 END) as ongoing_cases")
            ->selectRaw("COALESCE(SUM({$serviceAmountExpr}), 0) as total_sales")
            ->selectRaw('COALESCE(SUM(total_paid), 0) as total_collected')
            ->selectRaw('COALESCE(SUM(balance_amount), 0) as total_outstanding')
            ->first();

        $totalCases = (int) ($summary->total_cases ?? 0);
        $statusCounts = [
            'paid' => (int) ($summary->paid_cases ?? 0),
            'partial' => (int) ($summary->partial_cases ?? 0),
            'unpaid' => (int) ($summary->unpaid_cases ?? 0),
            'ongoing' => (int) ($summary->ongoing_cases ?? 0),
        ];
        $totalSales = (float) ($summary->total_sales ?? 0);
        $totalCollected = (float) ($summary->total_collected ?? 0);
        $totalOutstanding = (float) ($summary->total_outstanding ?? 0);

        $chart = [];
        $selectedBranch = $branchId ? $branches->firstWhere('id', (int) $branchId) : null;
        $casesPerBranch = collect();

        if (!$branchId) {
            $branchStats = FuneralCase::query()
                ->select('branch_id')
                ->whereBetween('created_at', [$startAt, $endAt])
                ->where('verification_status', 'VERIFIED')
                ->selectRaw('COUNT(*) as cases')
                ->selectRaw("SUM(CASE WHEN COALESCE(total_paid, 0) > 0 AND COALESCE(balance_amount, 0) = 0 THEN 1 ELSE 0 END) as paid")
                ->selectRaw("SUM(CASE WHEN COALESCE(total_paid, 0) > 0 AND COALESCE(balance_amount, 0) > 0 THEN 1 ELSE 0 END) as partial")
                ->selectRaw("SUM(CASE WHEN COALESCE(total_paid, 0) = 0 AND COALESCE(balance_amount, 0) > 0 THEN 1 ELSE 0 END) as unpaid")
                ->selectRaw("COALESCE(SUM({$serviceAmountExpr}), 0) as sales")
                ->groupBy('branch_id')
                ->get()
                ->keyBy('branch_id');

            $casesPerBranch = $branches->map(function ($branch) use ($branchStats) {
                $stats = $branchStats->get($branch->id);
                return [
                    'branch' => $branch,
                    'cases' => (int) ($stats->cases ?? 0),
                    'paid' => (int) ($stats->paid ?? 0),
                    'partial' => (int) ($stats->partial ?? 0),
                    'unpaid' => (int) ($stats->unpaid ?? 0),
                    'sales' => (float) ($stats->sales ?? 0),
                ];
            });

            $chart = [
                'mode' => 'all',
                'bar' => [
                    'labels' => $casesPerBranch->map(fn ($r) => $r['branch']->branch_name)->values(),
                    'revenue' => $casesPerBranch->pluck('sales')->values(),
                    'volume' => $casesPerBranch->pluck('cases')->values(),
                    'colors' => $casesPerBranch->map(fn ($r) => $branchColors[$r['branch']->id] ?? '#8c4004')->values(),
                ],
                'donut' => [
                    'labels' => $casesPerBranch->map(fn ($r) => $r['branch']->branch_name)->values(),
                    'values' => $casesPerBranch->pluck('sales')->values(),
                    'colors' => $casesPerBranch->map(fn ($r) => $branchColors[$r['branch']->id] ?? '#8c4004')->values(),
                ],
                'line' => $this->buildRevenueTrendData(null, $dateFrom, $dateTo, $range),
            ];
        } else {
            $color = $selectedBranch ? ($branchColors[$selectedBranch->id] ?? '#8c4004') : '#8c4004';

            $branchBar = $this->buildBranchFocusedBarData((int) $branchId, $dateFrom, $dateTo, $range);
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
                    'values' => [
                        $statusCounts['paid'],
                        $statusCounts['partial'],
                        $statusCounts['unpaid'],
                    ],
                    'colors' => ['#15803d', '#d97706', '#b91c1c'],
                ],
                'period' => [
                    'labels' => $branchBar['labels'],
                    'cases' => $branchBar['volume'],
                    'service_amount' => $branchBar['revenue'],
                    'collected_amount' => $branchBar['collected'],
                    'outstanding_balance' => $branchBar['outstanding'],
                ],
                'line' => $this->buildRevenueTrendData((int) $branchId, $dateFrom, $dateTo, $range),
            ];
        }

        return view('owner.analytics', compact(
            'branches',
            'branchId',
            'selectedBranch',
            'range',
            'dateFrom',
            'dateTo',
            'totalCases',
            'totalSales',
            'totalCollected',
            'totalOutstanding',
            'statusCounts',
            'casesPerBranch',
            'chart'
        ));
    }

    public function history(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|integer|exists:branches,id',
            'q' => "nullable|string|max:100|regex:/^[A-Za-z0-9\\s.'-]+$/",
            'date_preset' => 'nullable|in:TODAY,THIS_MONTH,THIS_YEAR,CUSTOM',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'case_status' => 'nullable|in:DRAFT,ACTIVE,COMPLETED',
            'payment_status' => 'nullable|in:PAID,PARTIAL,UNPAID',
            'service_type' => 'nullable|string|max:100',
            'package_id' => 'nullable|integer|exists:packages,id',
            'interment_from' => 'nullable|date',
            'interment_to' => 'nullable|date|after_or_equal:interment_from',
        ], [
            'q.regex' => 'Search may contain letters, numbers, spaces, apostrophes, periods, and hyphens only.',
        ]);

        $branchId = $validated['branch_id'] ?? null;
        $q = $validated['q'] ?? null;
        $datePreset = $validated['date_preset'] ?? ((($validated['date_from'] ?? null) || ($validated['date_to'] ?? null)) ? 'CUSTOM' : '');
        [$dateFrom, $dateTo] = $this->resolveHistoryDateRange(
            $datePreset,
            $validated['date_from'] ?? null,
            $validated['date_to'] ?? null
        );
        $caseStatus = $validated['case_status'] ?? null;
        $paymentStatus = $validated['payment_status'] ?? null;
        $serviceType = $validated['service_type'] ?? null;
        $packageId = $validated['package_id'] ?? null;
        $intermentFrom = $validated['interment_from'] ?? null;
        $intermentTo = $validated['interment_to'] ?? null;
        [$startAt, $endAt] = $this->parseDateBounds($dateFrom, $dateTo);

        $cases = FuneralCase::with(['branch', 'client', 'deceased'])
            ->where('verification_status', 'VERIFIED')
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->when($startAt && $endAt, fn ($query) => $query->whereBetween('created_at', [$startAt, $endAt]))
            ->when($startAt && !$endAt, fn ($query) => $query->where('created_at', '>=', $startAt))
            ->when(!$startAt && $endAt, fn ($query) => $query->where('created_at', '<=', $endAt))
            ->when($caseStatus, fn ($query) => $query->where('case_status', $caseStatus))
            ->when($paymentStatus, fn ($query) => $query->where('payment_status', $paymentStatus))
            ->when($serviceType, fn ($query) => $query->where('service_type', $serviceType))
            ->when($packageId, fn ($query) => $query->where('package_id', $packageId))
            ->when($intermentFrom || $intermentTo, function ($query) use ($intermentFrom, $intermentTo) {
                $query->where(function ($outer) use ($intermentFrom, $intermentTo) {
                    $outer->where(function ($caseDate) use ($intermentFrom, $intermentTo) {
                        if ($intermentFrom) {
                            $caseDate->whereDate('interment_at', '>=', $intermentFrom);
                        }
                        if ($intermentTo) {
                            $caseDate->whereDate('interment_at', '<=', $intermentTo);
                        }
                    })->orWhereHas('deceased', function ($dq) use ($intermentFrom, $intermentTo) {
                        if ($intermentFrom) {
                            $dq->whereRaw('DATE(COALESCE(interment_at, interment)) >= ?', [$intermentFrom]);
                        }
                        if ($intermentTo) {
                            $dq->whereRaw('DATE(COALESCE(interment_at, interment)) <= ?', [$intermentTo]);
                        }
                    });
                });
            })
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('case_code', 'like', "%{$q}%")
                        ->orWhereHas('client', fn ($q2) => $q2->where('full_name', 'like', "%{$q}%"))
                        ->orWhereHas('deceased', fn ($q3) => $q3->where('full_name', 'like', "%{$q}%"));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $branches = Branch::orderBy('branch_code')->get();
        $serviceTypes = FuneralCase::query()
            ->where('verification_status', 'VERIFIED')
            ->whereNotNull('service_type')
            ->where('service_type', '!=', '')
            ->distinct()
            ->orderBy('service_type')
            ->pluck('service_type');
        $packages = Package::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('owner.cases.history', compact(
            'cases',
            'branches',
            'branchId',
            'q',
            'datePreset',
            'dateFrom',
            'dateTo',
            'caseStatus',
            'paymentStatus',
            'serviceType',
            'packageId',
            'intermentFrom',
            'intermentTo',
            'serviceTypes',
            'packages'
        ));
    }

    private function resolveHistoryDateRange(?string $preset, ?string $dateFrom, ?string $dateTo): array
    {
        return match ($preset) {
            'TODAY' => [now()->toDateString(), now()->toDateString()],
            'THIS_MONTH' => [now()->startOfMonth()->toDateString(), now()->toDateString()],
            'THIS_YEAR' => [now()->startOfYear()->toDateString(), now()->toDateString()],
            'CUSTOM' => [$dateFrom, $dateTo],
            default => [null, null],
        };
    }

    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|integer|exists:branches,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'preset' => 'nullable|in:THIS_MONTH,LAST_30_DAYS',
            'interment_from' => 'nullable|date',
            'interment_to' => 'nullable|date|after_or_equal:interment_from',
        ]);

        $dateFrom = $validated['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $validated['date_to'] ?? now()->toDateString();
        if (($validated['preset'] ?? null) === 'THIS_MONTH') {
            $dateFrom = now()->startOfMonth()->toDateString();
            $dateTo = now()->toDateString();
        }
        if (($validated['preset'] ?? null) === 'LAST_30_DAYS') {
            $dateFrom = now()->subDays(29)->toDateString();
            $dateTo = now()->toDateString();
        }

        return [
            'branch_id' => $validated['branch_id'] ?? null,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'interment_from' => $validated['interment_from'] ?? null,
            'interment_to' => $validated['interment_to'] ?? null,
        ];
    }

    private function validatedDashboardFilters(Request $request): array
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|integer|exists:branches,id',
            'range' => 'nullable|in:TODAY,THIS_MONTH,THIS_YEAR,CUSTOM',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $range = $validated['range'] ?? 'THIS_MONTH';
        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        if ($range === 'TODAY') {
            $dateFrom = now()->toDateString();
            $dateTo = now()->toDateString();
        } elseif ($range === 'THIS_MONTH') {
            $dateFrom = now()->startOfMonth()->toDateString();
            $dateTo = now()->toDateString();
        } elseif ($range === 'THIS_YEAR') {
            $dateFrom = now()->startOfYear()->toDateString();
            $dateTo = now()->toDateString();
        } else {
            $dateFrom = $dateFrom ?: now()->startOfMonth()->toDateString();
            $dateTo = $dateTo ?: now()->toDateString();
        }

        return [
            'branch_id' => $validated['branch_id'] ?? null,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'range' => $range,
        ];
    }

    private function resolveAnalyticsRange(string $range, ?string $dateFrom, ?string $dateTo): array
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

    private function branchColorMap($branches): array
    {
        $palette = ['#8c4004', '#15803d', '#b91c1c'];
        $map = [];
        $index = 0;
        foreach ($branches as $branch) {
            $map[$branch->id] = $palette[$index] ?? '#8c4004';
            $index++;
        }

        return $map;
    }

    private function buildRevenueTrendData(?int $branchId, string $dateFrom, string $dateTo, string $range): array
    {
        $start = Carbon::parse($dateFrom)->startOfDay();
        $end = Carbon::parse($dateTo)->endOfDay();
        $serviceAmountExpr = "
            CASE
                WHEN COALESCE(total_paid, 0) > 0 AND COALESCE(balance_amount, 0) = 0 THEN COALESCE(total_paid, 0)
                WHEN COALESCE(total_paid, 0) > 0 AND COALESCE(balance_amount, 0) > 0 THEN COALESCE(total_paid, 0) + COALESCE(balance_amount, 0)
                WHEN COALESCE(total_paid, 0) = 0 AND COALESCE(balance_amount, 0) > 0 THEN COALESCE(balance_amount, 0)
                ELSE 0
            END
        ";

        $labels = [];
        $data = [];
        $base = FuneralCase::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('verification_status', 'VERIFIED')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        if ($range === 'THIS_YEAR') {
            $monthlyRows = (clone $base)
                ->selectRaw("YEAR(created_at) as yr, MONTH(created_at) as mo, COALESCE(SUM({$serviceAmountExpr}), 0) as total")
                ->groupByRaw('YEAR(created_at), MONTH(created_at)')
                ->orderByRaw('YEAR(created_at), MONTH(created_at)')
                ->get();

            $monthlyLookup = $monthlyRows->mapWithKeys(function ($row) {
                $key = sprintf('%04d-%02d', (int) $row->yr, (int) $row->mo);
                return [$key => (float) $row->total];
            });

            $cursor = $start->copy()->startOfMonth();
            while ($cursor->lte($end)) {
                $key = $cursor->format('Y-m');
                $labels[] = $cursor->format('M');
                $data[] = (float) ($monthlyLookup[$key] ?? 0);
                $cursor->addMonth();
            }
        } else {
            $dailyRows = (clone $base)
                ->selectRaw("DATE(created_at) as bucket, COALESCE(SUM({$serviceAmountExpr}), 0) as total")
                ->groupByRaw('DATE(created_at)')
                ->orderByRaw('DATE(created_at)')
                ->get();

            $dailyLookup = $dailyRows->mapWithKeys(function ($row) {
                return [(string) $row->bucket => (float) $row->total];
            });

            $cursor = $start->copy();
            while ($cursor->lte($end)) {
                $key = $cursor->toDateString();
                $labels[] = $cursor->format('M d');
                $data[] = (float) ($dailyLookup[$key] ?? 0);
                $cursor->addDay();
            }
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function buildBranchFocusedBarData(int $branchId, string $dateFrom, string $dateTo, string $range): array
    {
        $start = Carbon::parse($dateFrom)->startOfDay();
        $end = Carbon::parse($dateTo)->endOfDay();
        $serviceAmountExpr = "
            CASE
                WHEN COALESCE(total_paid, 0) > 0 AND COALESCE(balance_amount, 0) = 0 THEN COALESCE(total_paid, 0)
                WHEN COALESCE(total_paid, 0) > 0 AND COALESCE(balance_amount, 0) > 0 THEN COALESCE(total_paid, 0) + COALESCE(balance_amount, 0)
                WHEN COALESCE(total_paid, 0) = 0 AND COALESCE(balance_amount, 0) > 0 THEN COALESCE(balance_amount, 0)
                ELSE 0
            END
        ";
        $labels = [];
        $revenue = [];
        $volume = [];
        $collected = [];
        $outstanding = [];
        $base = FuneralCase::query()
            ->where('branch_id', $branchId)
            ->where('verification_status', 'VERIFIED')
            ->whereBetween('created_at', [$start, $end]);

        if ($range === 'THIS_YEAR') {
            $monthlyRows = (clone $base)
                ->selectRaw('YEAR(created_at) as yr, MONTH(created_at) as mo')
                ->selectRaw("COALESCE(SUM({$serviceAmountExpr}), 0) as revenue")
                ->selectRaw('COUNT(*) as volume')
                ->selectRaw('COALESCE(SUM(total_paid), 0) as collected')
                ->selectRaw('COALESCE(SUM(balance_amount), 0) as outstanding')
                ->groupByRaw('YEAR(created_at), MONTH(created_at)')
                ->orderByRaw('YEAR(created_at), MONTH(created_at)')
                ->get();

            $monthlyLookup = $monthlyRows->mapWithKeys(function ($row) {
                $key = sprintf('%04d-%02d', (int) $row->yr, (int) $row->mo);
                return [$key => [
                    'revenue' => (float) $row->revenue,
                    'volume' => (int) $row->volume,
                    'collected' => (float) $row->collected,
                    'outstanding' => (float) $row->outstanding,
                ]];
            });

            $cursor = $start->copy()->startOfMonth();
            while ($cursor->lte($end)) {
                $key = $cursor->format('Y-m');
                $row = $monthlyLookup[$key] ?? null;
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
                    if ($day->lt($start) || $day->gt($end)) {
                        $day->addDay();
                        continue;
                    }

                    $row = $dailyRows->get($day->toDateString());
                    if ($row) {
                        $weekRevenue += (float) $row->revenue;
                        $weekVolume += (int) $row->volume;
                        $weekCollected += (float) $row->collected;
                        $weekOutstanding += (float) $row->outstanding;
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

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'volume' => $volume,
            'collected' => $collected,
            'outstanding' => $outstanding,
        ];
    }

    private function applyAggregateSelects(Builder $query): Builder
    {
        return $query
            ->selectRaw('COUNT(*) as total_cases')
            ->selectRaw("SUM(CASE WHEN payment_status = 'PAID' THEN 1 ELSE 0 END) as paid_cases")
            ->selectRaw("SUM(CASE WHEN payment_status = 'PARTIAL' THEN 1 ELSE 0 END) as partial_cases")
            ->selectRaw("SUM(CASE WHEN payment_status = 'UNPAID' THEN 1 ELSE 0 END) as unpaid_cases")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_status = 'PAID' THEN total_amount ELSE 0 END), 0) as total_sales")
            ->selectRaw('COALESCE(SUM(total_paid), 0) as total_collected')
            ->selectRaw('COALESCE(SUM(balance_amount), 0) as total_outstanding');
    }

    private function buildAggregateSummary(Builder $query): object
    {
        return $this->applyAggregateSelects($query)->first();
    }

    private function buildAggregateRowsByBranch(Builder $query)
    {
        return $this->applyAggregateSelects($query->select('branch_id'))
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');
    }
}
