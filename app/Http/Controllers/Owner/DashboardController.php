<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\FuneralCase;
use App\Models\Package;
use App\Services\BranchAnalyticsService;
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
        $branchCards = $branches->when($branchId, fn ($items) => $items->where('id', (int) $branchId))->map(function ($branch) use ($branchStats) {
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

        $selectedBranch = $branchId ? $branches->firstWhere('id', (int) $branchId) : null;
        $topPackages = (clone $base)
                ->whereNotNull('service_package')
                ->selectRaw('service_package, COUNT(*) as total_cases, COALESCE(SUM(total_amount), 0) as total_sales')
                ->groupBy('service_package')
                ->orderByDesc('total_cases')
                ->orderByDesc('total_sales')
                ->limit(5)
                ->get();
        $trendByDate = (clone $base)
            ->selectRaw('DATE(created_at) as recorded_date, COALESCE(SUM(total_amount), 0) as value')
            ->groupByRaw('DATE(created_at)')->orderBy('recorded_date')->get();
        $trendMonthly = $startAt->diffInDays($endAt) > 62;
        $revenueTrend = $trendByDate
            ->groupBy(fn ($row) => Carbon::parse($row->recorded_date)->format($trendMonthly ? 'Y-m-01' : 'Y-m-d'))
            ->map(fn ($rows, $date) => ['label' => Carbon::parse($date)->format($trendMonthly ? 'M Y' : 'M d'), 'value' => (float) $rows->sum('value')])
            ->values();
        $attentionCases = (clone $base)->where('balance_amount', '>', 0)
            ->whereDate('interment_at', '<', now()->toDateString());
        $attentionCount = (clone $attentionCases)->count();
        $attentionBalance = (float) (clone $attentionCases)->sum('balance_amount');

        $overview = app(\App\Support\OwnerDashboardOverview::class)->data($filters);
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
            'branches',
            'branchId',
            'dateFrom',
            'dateTo',
            'range',
            'selectedBranch',
            'topPackages', 'revenueTrend', 'attentionCount', 'attentionBalance', 'overview'
        ));
    }

    public function dashboardDetails(Request $request)
    {
        $input = $request->validate([
            'summary' => 'nullable|boolean', 'kind' => 'required|in:cases,service,collections,outstanding,risks', 'q' => 'nullable|string|max:100',
            'dashboard_range' => 'nullable|in:TODAY,THIS_WEEK,LAST_30_DAYS,THIS_MONTH,LAST_MONTH,THIS_QUARTER,THIS_YEAR,ALL_TIME,CUSTOM',
            'dashboard_branch' => 'nullable|integer|exists:branches,id', 'dashboard_from' => 'nullable|date', 'dashboard_to' => 'nullable|date|after_or_equal:dashboard_from',
        ]);
        $filters = $this->validatedDashboardFilters($request);
        $data = app(\App\Support\OwnerDashboardOverview::class);
        $branchId = $filters['branch_id'] ? (int) $filters['branch_id'] : null;
        $kind = $input['kind'];
        $isPayments = $kind === 'collections';
        $query = match ($kind) {
            'collections' => $data->receivedBetween($data->payments($branchId), Carbon::parse($filters['date_from'])->startOfDay(), Carbon::parse($filters['date_to'])->endOfDay())->with(['funeralCase.client', 'branch']),
            'outstanding' => $data->cases($branchId)->where('balance_amount', '>', 0)->with(['client', 'branch']),
            'risks' => $data->risks($branchId)->with(['client', 'branch']),
            default => $data->cases($branchId)->whereBetween('created_at', [Carbon::parse($filters['date_from'])->startOfDay(), Carbon::parse($filters['date_to'])->endOfDay()])->with(['client', 'branch']),
        };
        $amountColumn = $isPayments ? 'amount' : (in_array($kind, ['outstanding','risks']) ? 'balance_amount' : 'total_amount');
        $scopeAmount = (float) (clone $query)->sum($amountColumn);
        if ($search = trim($input['q'] ?? '')) {
            $query->where(function ($q) use ($search, $isPayments) {
                if ($isPayments) {
                    $q->where('payment_record_no', 'like', '%'.$search.'%')->orWhere('receipt_number', 'like', '%'.$search.'%')
                        ->orWhereHas('funeralCase', fn ($c) => $c->where('case_code', 'like', '%'.$search.'%')->orWhereHas('client', fn ($f) => $f->where('full_name', 'like', '%'.$search.'%')));
                } else {
                    $q->where('case_code', 'like', '%'.$search.'%')->orWhereHas('client', fn ($f) => $f->where('full_name', 'like', '%'.$search.'%'));
                }
            });
        }
        $matchingAmount = (float) (clone $query)->sum($amountColumn);
        $dashboardUrl = route('owner.dashboard', [
            'branch_id' => $branchId, 'range' => $input['dashboard_range'] ?? $filters['range'],
            'date_from' => $input['dashboard_from'] ?? $filters['date_from'], 'date_to' => $input['dashboard_to'] ?? $filters['date_to'],
        ]);
        if ($request->boolean('summary')) {
            $recordCount = (clone $query)->count();
            $context = ['dashboard_branch'=>$branchId, 'dashboard_range'=>$input['dashboard_range'] ?? $filters['range'], 'dashboard_from'=>$input['dashboard_from'] ?? $filters['date_from'], 'dashboard_to'=>$input['dashboard_to'] ?? $filters['date_to']];
            $destination = ['branch_id'=>$branchId] + $context;
            if ($isPayments) {
                $destination += ['range'=>'CUSTOM','date_from'=>$filters['date_from'],'date_to'=>$filters['date_to']];
                $recordsUrl = route('reports.index', $destination + ['report_type'=>'sales']);
            } else {
                $destination += ['report_type'=>'master_cases', 'verification_status'=>'VERIFIED', 'date_basis'=>'recording'];
                if (in_array($kind,['outstanding','risks'])) $destination['collection_state'] = $kind==='risks' ? 'AFTER_INTERMENT' : 'WITH_BALANCE';
                else $destination += ['date_from'=>$filters['date_from'],'date_to'=>$filters['date_to']];
                $recordsUrl = route('reports.index', $destination);
            }
            return response()->json(['html'=>view('owner.partials.executive-summary',compact('kind','recordCount','scopeAmount','recordsUrl','isPayments'))->render()]);
        }
        if ($isPayments) $query->orderByRaw('COALESCE(paid_at, paid_date, created_at) DESC');
        $records = $query->orderByDesc('id')->paginate(20)->withQueryString();
        return response()->json([
            'html' => view('owner.partials.executive-details', compact('records', 'isPayments', 'kind', 'scopeAmount', 'matchingAmount', 'search', 'dashboardUrl'))->render(),
            'pagination' => view('owner.partials.executive-pagination', compact('records'))->render(), 'count' => $records->total(),
        ]);
    }

    public function collections(Request $request)
    {
        $input = $request->validate(['q'=>'nullable|string|max:100','dashboard_branch'=>'nullable|integer|exists:branches,id','dashboard_range'=>'nullable|in:TODAY,THIS_WEEK,LAST_30_DAYS,THIS_MONTH,LAST_MONTH,THIS_QUARTER,THIS_YEAR,ALL_TIME,CUSTOM','dashboard_from'=>'nullable|date','dashboard_to'=>'nullable|date|after_or_equal:dashboard_from']);
        $filters = $this->validatedDashboardFilters($request);
        $branchId = $filters['branch_id'] ? (int)$filters['branch_id'] : null;
        $dateFrom = $filters['date_from']; $dateTo = $filters['date_to'];
        $data = app(\App\Support\OwnerDashboardOverview::class);
        $query = $data->receivedBetween($data->payments($branchId),Carbon::parse($dateFrom)->startOfDay(),Carbon::parse($dateTo)->endOfDay())->with(['funeralCase.client','branch']);
        $scopeAmount = (float)(clone $query)->sum('amount'); $search = trim($input['q'] ?? '');
        if($search) $query->where(fn($q)=>$q->where('payment_record_no','like','%'.$search.'%')->orWhere('receipt_number','like','%'.$search.'%')->orWhereHas('funeralCase',fn($c)=>$c->where('case_code','like','%'.$search.'%')->orWhereHas('client',fn($f)=>$f->where('full_name','like','%'.$search.'%'))));
        $matchingAmount = (float)(clone $query)->sum('amount');
        $records = $query->orderByRaw('COALESCE(paid_at, paid_date, created_at) DESC')->orderByDesc('id')->paginate(20)->withQueryString();
        $branches = Branch::orderBy('branch_code')->get();
        $dashboardUrl = route('owner.dashboard',['branch_id'=>$input['dashboard_branch'] ?? null,'range'=>$input['dashboard_range'] ?? 'THIS_MONTH','date_from'=>$input['dashboard_from'] ?? null,'date_to'=>$input['dashboard_to'] ?? null]);
        $caseReturnUrl = $request->fullUrl(); $isPayments=true; $kind='collections';
        return view('owner.collections',compact('records','branches','branchId','dateFrom','dateTo','scopeAmount','matchingAmount','search','dashboardUrl','caseReturnUrl','isPayments','kind'));
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

        $funeral_case->load(['branch', 'client', 'deceased', 'reportedBranch', 'encodedBy', 'payments.recordedBy', 'package.packageInclusions', 'package.packageFreebies', 'caseAddOns', 'serviceDetail', 'tarpaulinAttachment.uploader', 'funeralContract.generator']);

        return view('owner.cases.show', compact('funeral_case'));
    }

    public function analytics(Request $request)
    {
        $validated = $request->validate([
            'dashboard_branch'=>'nullable|integer|exists:branches,id', 'dashboard_range'=>'nullable|in:TODAY,THIS_WEEK,LAST_30_DAYS,THIS_MONTH,LAST_MONTH,THIS_QUARTER,THIS_YEAR,ALL_TIME,CUSTOM', 'dashboard_from'=>'nullable|date', 'dashboard_to'=>'nullable|date|after_or_equal:dashboard_from',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'range' => 'nullable|in:TODAY,THIS_MONTH,THIS_YEAR,CUSTOM',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $data = app(BranchAnalyticsService::class)->ownerPageData($validated);
        $data['packageBreakdown'] = app(\App\Support\OwnerDashboardOverview::class)
            ->cases(isset($validated['branch_id']) ? (int) $validated['branch_id'] : null)
            ->whereBetween('created_at', [Carbon::parse($data['dateFrom'])->startOfDay(), Carbon::parse($data['dateTo'])->endOfDay()])
            ->whereNotNull('service_package')
            ->selectRaw('service_package, COUNT(*) as total_cases, SUM(total_amount) as service_value')
            ->groupBy('service_package')->orderByDesc('total_cases')->limit(10)->get();

        return view('owner.analytics', array_merge($data, [
            'analyticsRouteName' => 'owner.analytics',
            'analyticsBranchScope' => [
                'forced_branch_id' => null,
                'can_select_all' => true,
            ],
        ]));
    }

    public function history(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|integer|exists:branches,id',
            'q' => "nullable|string|max:100|regex:/^[A-Za-z0-9\\s.'-]+$/",
            'date_preset' => 'nullable|in:TODAY,THIS_MONTH,THIS_YEAR,CUSTOM',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'sort' => 'nullable|in:newest,oldest',
            'per_page' => 'nullable|integer|in:10,25,50,100',
            'case_status' => 'nullable|in:DRAFT,ACTIVE,COMPLETED',
            'payment_status' => 'nullable|in:PAID,PARTIAL,UNPAID,WITH_BALANCE',
            'collection_state' => 'nullable|in:WITH_PAYMENTS,WITH_BALANCE,AFTER_INTERMENT',
            'dashboard_range' => 'nullable|in:TODAY,THIS_WEEK,LAST_30_DAYS,THIS_MONTH,LAST_MONTH,THIS_QUARTER,THIS_YEAR,ALL_TIME,CUSTOM',
            'dashboard_branch' => 'nullable|integer|exists:branches,id', 'dashboard_from' => 'nullable|date', 'dashboard_to' => 'nullable|date|after_or_equal:dashboard_from',
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

        $historyBase = ($validated['collection_state'] ?? null) === 'AFTER_INTERMENT' ? app(\App\Support\OwnerDashboardOverview::class)->risks(null) : FuneralCase::query();
        $cases = $historyBase->with(['branch', 'client', 'deceased', 'package', 'encodedBy'])
            ->where('verification_status', 'VERIFIED')
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->when($startAt && $endAt, fn ($query) => $query->whereBetween('created_at', [$startAt, $endAt]))
            ->when($startAt && !$endAt, fn ($query) => $query->where('created_at', '>=', $startAt))
            ->when(!$startAt && $endAt, fn ($query) => $query->where('created_at', '<=', $endAt))
            ->when($caseStatus, fn ($query) => $query->where('case_status', $caseStatus))
            ->when($paymentStatus, fn ($query) => $paymentStatus === 'WITH_BALANCE' ? $query->where('balance_amount', '>', 0) : $query->where('payment_status', $paymentStatus))
            ->when(($validated['collection_state'] ?? null) === 'WITH_PAYMENTS', fn ($query) => $query->where('total_paid', '>', 0))
            ->when(in_array($validated['collection_state'] ?? null, ['WITH_BALANCE', 'AFTER_INTERMENT'], true), fn ($query) => $query->where('balance_amount', '>', 0))
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
            ->orderBy('created_at', ($validated['sort'] ?? 'newest') === 'oldest' ? 'asc' : 'desc')
            ->orderBy('id', ($validated['sort'] ?? 'newest') === 'oldest' ? 'asc' : 'desc')
            ->paginate((int) ($validated['per_page'] ?? 25))
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
            'range' => 'nullable|in:TODAY,THIS_WEEK,LAST_30_DAYS,THIS_MONTH,LAST_MONTH,THIS_QUARTER,THIS_YEAR,ALL_TIME,CUSTOM',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $range = $validated['range'] ?? 'THIS_MONTH';
        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        if ($range === 'TODAY') {
            $dateFrom = now()->toDateString();
            $dateTo = now()->toDateString();
        } elseif ($range === 'THIS_WEEK') {
            $dateFrom = now()->startOfWeek()->toDateString();
            $dateTo = now()->toDateString();
        } elseif ($range === 'LAST_MONTH') {
            $dateFrom = now()->subMonthNoOverflow()->startOfMonth()->toDateString();
            $dateTo = now()->subMonthNoOverflow()->endOfMonth()->toDateString();
        } elseif ($range === 'THIS_QUARTER') {
            $dateFrom = now()->firstOfQuarter()->toDateString();
            $dateTo = now()->toDateString();
        } elseif ($range === 'ALL_TIME') {
            $firstCase = FuneralCase::min('created_at');
            $firstPayment = \App\Models\Payment::selectRaw('MIN(COALESCE(paid_at, paid_date, created_at)) as first_date')->value('first_date');
            $first = collect([$firstCase, $firstPayment])->filter()->sort()->first();
            $dateFrom = $first ? Carbon::parse($first)->toDateString() : now()->toDateString();
            $dateTo = now()->toDateString();
        } elseif ($range === 'LAST_30_DAYS') {
            $dateFrom = now()->subDays(29)->toDateString();
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

    private function applyAggregateSelects(Builder $query): Builder
    {
        return $query
            ->selectRaw('COUNT(*) as total_cases')
            ->selectRaw("SUM(CASE WHEN payment_status = 'PAID' THEN 1 ELSE 0 END) as paid_cases")
            ->selectRaw("SUM(CASE WHEN payment_status = 'PARTIAL' THEN 1 ELSE 0 END) as partial_cases")
            ->selectRaw("SUM(CASE WHEN payment_status = 'UNPAID' THEN 1 ELSE 0 END) as unpaid_cases")
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_sales')
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
