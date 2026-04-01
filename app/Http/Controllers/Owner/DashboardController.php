<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\FuneralCase;
use Carbon\Carbon;
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

        $base = FuneralCase::query()
            ->where('verification_status', 'VERIFIED')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);

        $totalCases = (clone $base)->count();
        $paidCases = (clone $base)->where('payment_status', 'PAID')->count();
        $partialCases = (clone $base)->where('payment_status', 'PARTIAL')->count();
        $unpaidCases = (clone $base)->where('payment_status', 'UNPAID')->count();
        $totalSales = (clone $base)->where('payment_status', 'PAID')->sum('total_amount');
        $totalCollected = (clone $base)->sum('total_paid');
        $totalOutstanding = (clone $base)->sum('balance_amount');
        $ongoingCases = (clone $base)
            ->where(function ($q) {
                $q->whereIn('case_status', ['DRAFT', 'ACTIVE'])
                    ->orWhereHas('deceased', function ($dq) {
                        $dq->whereNull('interment');
                    });
            })
            ->count();

        $branches = Branch::orderBy('branch_code')->get();
        $branchCards = $branches->map(function ($branch) use ($dateFrom, $dateTo) {
            $query = FuneralCase::where('branch_id', $branch->id);
            $query->where('verification_status', 'VERIFIED');
            $query->whereDate('created_at', '>=', $dateFrom)
                ->whereDate('created_at', '<=', $dateTo);

            return [
                'branch' => $branch,
                'total_cases' => (clone $query)->count(),
                'paid_cases' => (clone $query)->where('payment_status', 'PAID')->count(),
                'partial_cases' => (clone $query)->where('payment_status', 'PARTIAL')->count(),
                'unpaid_cases' => (clone $query)->where('payment_status', 'UNPAID')->count(),
                'sales' => (clone $query)->where('payment_status', 'PAID')->sum('total_amount'),
                'collected' => (clone $query)->sum('total_paid'),
                'outstanding' => (clone $query)->sum('balance_amount'),
            ];
        });

        $branchRevenue = $branchCards->mapWithKeys(function ($row) {
            return [$row['branch']->branch_code => (float) $row['sales']];
        });

        $recentCases = FuneralCase::with(['branch', 'client', 'deceased'])
            ->where('verification_status', 'VERIFIED')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->latest()
            ->take(10)
            ->get();

        $selectedBranch = $branchId ? $branches->firstWhere('id', (int) $branchId) : null;
        $topPackages = collect();
        if ($branchId) {
            $topPackages = FuneralCase::query()
                ->where('verification_status', 'VERIFIED')
                ->where('branch_id', $branchId)
                ->whereDate('created_at', '>=', $dateFrom)
                ->whereDate('created_at', '<=', $dateTo)
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

        $casesQuery = FuneralCase::with(['branch', 'client', 'deceased'])
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
            ->whereDate('created_at', '>=', $filters['date_from'])
            ->whereDate('created_at', '<=', $filters['date_to']);

        $totalCases = (clone $casesQuery)->count();
        $paidCases = (clone $casesQuery)->where('payment_status', 'PAID')->count();
        $partialCases = (clone $casesQuery)->where('payment_status', 'PARTIAL')->count();
        $unpaidCases = (clone $casesQuery)->where('payment_status', 'UNPAID')->count();
        $totalSales = (clone $casesQuery)->where('payment_status', 'PAID')->sum('total_amount');
        $totalCollected = (clone $casesQuery)->sum('total_paid');
        $totalOutstanding = (clone $casesQuery)->sum('balance_amount');

        $branchSet = $filters['branch_id']
            ? $branches->where('id', (int) $filters['branch_id'])->values()
            : $branches;

        $branchSummary = $branchSet->map(function ($branch) use ($filters) {
            $query = FuneralCase::query()
                ->where('verification_status', 'VERIFIED')
                ->where('branch_id', $branch->id)
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
                ->whereDate('created_at', '>=', $filters['date_from'])
                ->whereDate('created_at', '<=', $filters['date_to']);

            return [
                'branch' => $branch,
                'total_cases' => (clone $query)->count(),
                'paid_cases' => (clone $query)->where('payment_status', 'PAID')->count(),
                'partial_cases' => (clone $query)->where('payment_status', 'PARTIAL')->count(),
                'unpaid_cases' => (clone $query)->where('payment_status', 'UNPAID')->count(),
                'sales' => (clone $query)->where('payment_status', 'PAID')->sum('total_amount'),
                'collected' => (clone $query)->sum('total_paid'),
                'outstanding' => (clone $query)->sum('balance_amount'),
            ];
        });

        $cases = (clone $casesQuery)->latest()->paginate(20)->withQueryString();

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
            ->whereDate('created_at', '>=', $filters['date_from'])
            ->whereDate('created_at', '<=', $filters['date_to'])
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

        $funeral_case->load(['branch', 'client', 'deceased', 'reportedBranch', 'encodedBy', 'payments.recordedBy']);

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
        $branchId = $validated['branch_id'] ?? null;

        $branches = Branch::orderBy('branch_code')->get();
        $branchColors = $this->branchColorMap($branches);
        $base = FuneralCase::query()->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->where('verification_status', 'VERIFIED')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $totalCases = (clone $base)->count();
        $statusCounts = [
            'paid' => (clone $base)->where('payment_status', 'PAID')->count(),
            'partial' => (clone $base)->where('payment_status', 'PARTIAL')->count(),
            'unpaid' => (clone $base)->where('payment_status', 'UNPAID')->count(),
            'ongoing' => (clone $base)->whereIn('case_status', ['DRAFT', 'ACTIVE'])->count(),
        ];
        $totalSales = (clone $base)->where('payment_status', 'PAID')->sum('total_amount');
        $totalCollected = (clone $base)->sum('total_paid');
        $totalOutstanding = (clone $base)->sum('balance_amount');

        $chart = [];
        $selectedBranch = $branchId ? $branches->firstWhere('id', (int) $branchId) : null;

        if (!$branchId) {
            $casesPerBranch = $branches->map(function ($branch) use ($dateFrom, $dateTo) {
                $query = FuneralCase::where('branch_id', $branch->id)
                    ->where('verification_status', 'VERIFIED')
                    ->whereDate('created_at', '>=', $dateFrom)
                    ->whereDate('created_at', '<=', $dateTo);

                return [
                    'branch' => $branch,
                    'cases' => (clone $query)->count(),
                    'paid' => (clone $query)->where('payment_status', 'PAID')->count(),
                    'partial' => (clone $query)->where('payment_status', 'PARTIAL')->count(),
                    'unpaid' => (clone $query)->where('payment_status', 'UNPAID')->count(),
                    'sales' => (float) (clone $query)->where('payment_status', 'PAID')->sum('total_amount'),
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
            $casesPerBranch = collect();
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
                        (clone $base)->where('payment_status', 'PAID')->count(),
                        (clone $base)->where('payment_status', 'PARTIAL')->count(),
                        (clone $base)->where('payment_status', 'UNPAID')->count(),
                    ],
                    'colors' => ['#15803d', '#d97706', '#b91c1c'],
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
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'interment_from' => 'nullable|date',
            'interment_to' => 'nullable|date|after_or_equal:interment_from',
        ], [
            'q.regex' => 'Search may contain letters, numbers, spaces, apostrophes, periods, and hyphens only.',
        ]);

        $branchId = $validated['branch_id'] ?? null;
        $q = $validated['q'] ?? null;
        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;
        $intermentFrom = $validated['interment_from'] ?? null;
        $intermentTo = $validated['interment_to'] ?? null;

        $cases = FuneralCase::with(['branch', 'client', 'deceased'])
            ->where('verification_status', 'VERIFIED')
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->when($dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->when($intermentFrom || $intermentTo, function ($query) use ($intermentFrom, $intermentTo) {
                $query->whereHas('deceased', function ($dq) use ($intermentFrom, $intermentTo) {
                    if ($intermentFrom) {
                        $dq->whereRaw('DATE(COALESCE(interment_at, interment)) >= ?', [$intermentFrom]);
                    }
                    if ($intermentTo) {
                        $dq->whereRaw('DATE(COALESCE(interment_at, interment)) <= ?', [$intermentTo]);
                    }
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

        return view('owner.cases.history', compact(
            'cases',
            'branches',
            'branchId',
            'q',
            'dateFrom',
            'dateTo',
            'intermentFrom',
            'intermentTo'
        ));
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
            'range' => 'nullable|in:TODAY,THIS_MONTH,LAST_30_DAYS,CUSTOM',
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
        } elseif ($range === 'LAST_30_DAYS') {
            $dateFrom = now()->subDays(29)->toDateString();
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

        $labels = [];
        $data = [];
        if ($range === 'THIS_YEAR') {
            $cursor = $start->copy()->startOfMonth();
            while ($cursor->lte($end)) {
                $from = $cursor->copy()->startOfMonth();
                $to = $cursor->copy()->endOfMonth();
                $labels[] = $cursor->format('M');
                $query = FuneralCase::query()
                    ->whereBetween('created_at', [$from, $to])
                    ->where('verification_status', 'VERIFIED')
                    ->where('payment_status', 'PAID');
                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }
                $data[] = (float) $query->sum('total_amount');
                $cursor->addMonth();
            }
        } else {
            $cursor = $start->copy();
            while ($cursor->lte($end)) {
                $from = $cursor->copy()->startOfDay();
                $to = $cursor->copy()->endOfDay();
                $labels[] = $cursor->format('M d');
                $query = FuneralCase::query()
                    ->whereBetween('created_at', [$from, $to])
                    ->where('verification_status', 'VERIFIED')
                    ->where('payment_status', 'PAID');
                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }
                $data[] = (float) $query->sum('total_amount');
                $cursor->addDay();
            }
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function buildBranchFocusedBarData(int $branchId, string $dateFrom, string $dateTo, string $range): array
    {
        $start = Carbon::parse($dateFrom)->startOfDay();
        $end = Carbon::parse($dateTo)->endOfDay();
        $labels = [];
        $revenue = [];
        $volume = [];

        if ($range === 'THIS_YEAR') {
            $cursor = $start->copy()->startOfMonth();
            while ($cursor->lte($end)) {
                $from = $cursor->copy()->startOfMonth();
                $to = $cursor->copy()->endOfMonth();
                $labels[] = $cursor->format('M');
                $query = FuneralCase::where('branch_id', $branchId)
                    ->where('verification_status', 'VERIFIED')
                    ->whereBetween('created_at', [$from, $to]);
                $revenue[] = (float) (clone $query)->where('payment_status', 'PAID')->sum('total_amount');
                $volume[] = (int) (clone $query)->count();
                $cursor->addMonth();
            }
        } else {
            $cursor = $start->copy()->startOfWeek();
            $limit = 0;
            while ($cursor->lte($end) && $limit < 12) {
                $from = $cursor->copy()->startOfWeek()->startOfDay();
                $to = $cursor->copy()->endOfWeek()->endOfDay();
                $labels[] = $from->format('M d') . ' - ' . $to->format('M d');
                $query = FuneralCase::where('branch_id', $branchId)
                    ->where('verification_status', 'VERIFIED')
                    ->whereBetween('created_at', [$from, $to]);
                $revenue[] = (float) (clone $query)->where('payment_status', 'PAID')->sum('total_amount');
                $volume[] = (int) (clone $query)->count();
                $cursor->addWeek();
                $limit++;
            }
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'volume' => $volume,
        ];
    }
}
