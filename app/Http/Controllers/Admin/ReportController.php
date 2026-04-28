<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\FuneralCase;
use App\Models\Package;
use App\Models\User;
use App\Support\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function masterCases(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'q' => ['nullable', 'string', 'max:100', "regex:/^[A-Za-z0-9\\s.'-]+$/"],
            'payment_status' => ['nullable', 'in:PAID,PARTIAL,UNPAID'],
            'case_status' => ['nullable', 'in:DRAFT,ACTIVE,COMPLETED'],
            'verification_status' => ['nullable', 'in:PENDING,VERIFIED,DISPUTED'],
            'service_type' => ['nullable', 'string', 'max:100'],
            'package_id' => ['nullable', 'integer', 'exists:packages,id'],
            'encoded_by' => ['nullable', 'integer', 'exists:users,id'],
            'sort' => ['nullable', 'in:newest,oldest'],
            'date_preset' => ['nullable', 'in:TODAY,THIS_MONTH,THIS_YEAR,CUSTOM'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'interment_from' => ['nullable', 'date'],
            'interment_to' => ['nullable', 'date', 'after_or_equal:interment_from'],
        ], [
            'q.regex' => 'Search may contain letters, numbers, spaces, apostrophes, periods, and hyphens only.',
        ]);

        $requestedBranchId = $validated['branch_id'] ?? null;
        if ($request->user()?->isBranchAdmin()) {
            $requestedBranchId = $request->user()->branch_id;
        }
        $scopeBranchIds = $request->user()->branchScopeIds();
        if ($requestedBranchId && $scopeBranchIds !== null && !in_array((int) $requestedBranchId, $scopeBranchIds, true)) {
            abort(403, 'Branch is outside your admin scope.');
        }
        $branchId = $this->effectiveBranchId($request, $requestedBranchId);
        $q = $validated['q'] ?? null;
        $paymentStatus = $validated['payment_status'] ?? null;
        $caseStatus = $validated['case_status'] ?? null;
        $verificationStatus = $validated['verification_status'] ?? null;
        $serviceType = $validated['service_type'] ?? null;
        $packageId = $validated['package_id'] ?? null;
        $encodedBy = $validated['encoded_by'] ?? null;
        $sort = $validated['sort'] ?? 'newest';
        $dateFromInput = $validated['date_from'] ?? null;
        $dateToInput = $validated['date_to'] ?? null;
        $intermentFrom = $validated['interment_from'] ?? null;
        $intermentTo = $validated['interment_to'] ?? null;
        $datePreset = $validated['date_preset'] ?? (($dateFromInput || $dateToInput) ? 'CUSTOM' : '');
        if ($datePreset === '' && ($dateFromInput || $dateToInput)) {
            $datePreset = 'CUSTOM';
        }

        [$dateFrom, $dateTo] = [null, null];
        if ($datePreset === 'CUSTOM') {
            [$dateFrom, $dateTo] = [$dateFromInput, $dateToInput];
        } elseif ($datePreset !== '') {
            [$dateFrom, $dateTo] = match ($datePreset) {
                'TODAY' => [Carbon::today()->toDateString(), Carbon::today()->toDateString()],
                'THIS_MONTH' => [Carbon::today()->startOfMonth()->toDateString(), Carbon::today()->toDateString()],
                'THIS_YEAR' => [Carbon::today()->startOfYear()->toDateString(), Carbon::today()->toDateString()],
                default => [null, null],
            };
        }
        [$startAt, $endAt] = $this->parseDateBounds($dateFrom, $dateTo);

        $cases = FuneralCase::with(['branch', 'client', 'deceased', 'package', 'encodedBy'])
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->when($startAt, fn ($query) => $query->where('created_at', '>=', $startAt))
            ->when($endAt, fn ($query) => $query->where('created_at', '<=', $endAt))
            ->when($paymentStatus, fn ($query) => $query->where('payment_status', $paymentStatus))
            ->when($caseStatus, fn ($query) => $query->where('case_status', $caseStatus))
            ->when($verificationStatus, fn ($query) => $query->where('verification_status', $verificationStatus))
            ->when($serviceType, fn ($query) => $query->where('service_type', $serviceType))
            ->when($packageId, fn ($query) => $query->where('package_id', $packageId))
            ->when($encodedBy, fn ($query) => $query->where('encoded_by', $encodedBy))
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
            ->when($sort === 'oldest', fn ($query) => $query->oldest())
            ->when($sort !== 'oldest', fn ($query) => $query->latest())
            ->paginate(20)
            ->withQueryString();

        $branches = Branch::query()
            ->when($scopeBranchIds !== null, fn ($query) => $query->whereIn('id', $scopeBranchIds))
            ->orderBy('branch_code')
            ->get();

        $serviceTypes = FuneralCase::query()
            ->when($scopeBranchIds !== null, fn ($query) => $query->whereIn('branch_id', $scopeBranchIds))
            ->whereNotNull('service_type')
            ->where('service_type', '!=', '')
            ->distinct()
            ->orderBy('service_type')
            ->pluck('service_type');
        $packages = Package::query()->orderBy('name')->get(['id', 'name']);
        $encoders = User::query()
            ->whereIn('role', ['staff', 'admin'])
            ->when($scopeBranchIds !== null, fn ($query) => $query->whereIn('branch_id', $scopeBranchIds))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.reports.master_cases', compact(
            'cases',
            'branches',
            'branchId',
            'q',
            'paymentStatus',
            'caseStatus',
            'verificationStatus',
            'serviceType',
            'packageId',
            'encodedBy',
            'sort',
            'datePreset',
            'dateFrom',
            'dateTo',
            'intermentFrom',
            'intermentTo',
            'serviceTypes',
            'packages',
            'encoders'
        ));
    }

    public function updateVerification(Request $request, FuneralCase $funeral_case)
    {
        $validated = $request->validate([
            'verification_status' => ['required', 'in:VERIFIED,DISPUTED'],
            'verification_note' => ['nullable', 'string', 'max:500'],
        ]);

        if ($funeral_case->entry_source !== 'OTHER_BRANCH') {
            return back()->withErrors([
                'verification' => 'Only other-branch records require verification workflow.',
            ]);
        }

        if ($validated['verification_status'] === 'VERIFIED') {
            if ($funeral_case->case_status !== 'COMPLETED' || $funeral_case->payment_status !== 'PAID') {
                return back()->withErrors([
                    'verification' => 'Case must be completed and fully paid before verification.',
                ]);
            }

            $funeral_case->update([
                'verification_status' => 'VERIFIED',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'verification_note' => $validated['verification_note'] ?: 'Verified by admin.',
            ]);

            AuditLogger::log(
                action: 'case.verified',
                actionType: 'status_change',
                entityType: 'funeral_case',
                entityId: $funeral_case->id,
                metadata: [
                    'case_code' => $funeral_case->case_code,
                    'verification_status' => 'VERIFIED',
                    'note' => $funeral_case->verification_note,
                ],
                branchId: $funeral_case->branch_id
            );

            return back()->with('success', 'Case verification marked as VERIFIED.');
        }

        if (empty($validated['verification_note'])) {
            return back()->withErrors([
                'verification' => 'Please provide verification note for disputed records.',
            ]);
        }

        $funeral_case->update([
            'verification_status' => 'DISPUTED',
            'verified_by' => null,
            'verified_at' => null,
            'verification_note' => $validated['verification_note'],
        ]);

        AuditLogger::log(
            action: 'case.disputed',
            actionType: 'status_change',
            entityType: 'funeral_case',
            entityId: $funeral_case->id,
            metadata: [
                'case_code' => $funeral_case->case_code,
                'verification_status' => 'DISPUTED',
                'note' => $validated['verification_note'],
            ],
            branchId: $funeral_case->branch_id
        );

        return back()->with('success', 'Case verification marked as DISPUTED.');
    }

    public function sales(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'date_preset' => ['nullable', 'in:ANY,TODAY,LAST_7_DAYS,LAST_30_DAYS,THIS_MONTH,CUSTOM'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'interment_from' => ['nullable', 'date'],
            'interment_to' => ['nullable', 'date', 'after_or_equal:interment_from'],
        ]);

        $requestedBranchId = $validated['branch_id'] ?? null;
        $scopeBranchIds = $request->user()->branchScopeIds();
        if ($requestedBranchId && $scopeBranchIds !== null && !in_array((int) $requestedBranchId, $scopeBranchIds, true)) {
            abort(403, 'Branch is outside your admin scope.');
        }
        $branchId = $this->effectiveBranchId($request, $requestedBranchId);
        $dateFromInput = $validated['date_from'] ?? null;
        $dateToInput = $validated['date_to'] ?? null;
        $dateFrom = null;
        $dateTo = null;
        $intermentFrom = $validated['interment_from'] ?? null;
        $intermentTo = $validated['interment_to'] ?? null;
        $datePreset = $validated['date_preset'] ?? (($dateFromInput || $dateToInput) ? 'CUSTOM' : 'THIS_MONTH');

        if ($datePreset === 'CUSTOM') {
            $dateFrom = $dateFromInput;
            $dateTo = $dateToInput;
        } elseif ($datePreset !== 'ANY') {
            [$dateFrom, $dateTo] = match ($datePreset) {
                'TODAY' => [Carbon::today()->toDateString(), Carbon::today()->toDateString()],
                'LAST_7_DAYS' => [Carbon::today()->subDays(6)->toDateString(), Carbon::today()->toDateString()],
                'LAST_30_DAYS' => [Carbon::today()->subDays(29)->toDateString(), Carbon::today()->toDateString()],
                'THIS_MONTH' => [Carbon::today()->startOfMonth()->toDateString(), Carbon::today()->toDateString()],
                default => [null, null],
            };
        }

        [$startAt, $endAt] = $this->parseDateBounds($dateFrom, $dateTo);

        $intermentFilter = function ($query) use ($intermentFrom, $intermentTo) {
            $query->whereHas('deceased', function ($dq) use ($intermentFrom, $intermentTo) {
                if ($intermentFrom) {
                    $dq->whereRaw('DATE(COALESCE(interment_at, interment)) >= ?', [$intermentFrom]);
                }
                if ($intermentTo) {
                    $dq->whereRaw('DATE(COALESCE(interment_at, interment)) <= ?', [$intermentTo]);
                }
            });
        };

        // Base query without branch filter — used for per-branch aggregates.
        $allBase = FuneralCase::query()
            ->where('verification_status', 'VERIFIED')
            ->when($startAt, fn ($query) => $query->where('created_at', '>=', $startAt))
            ->when($endAt, fn ($query) => $query->where('created_at', '<=', $endAt))
            ->when($intermentFrom || $intermentTo, $intermentFilter);

        $base = (clone $allBase)->when($branchId, fn ($query) => $query->where('branch_id', $branchId));

        // Single query for all summary KPIs.
        $summary = (clone $base)
            ->selectRaw('COUNT(*) as total_cases')
            ->selectRaw("SUM(CASE WHEN payment_status = 'PAID' THEN 1 ELSE 0 END) as paid_cases")
            ->selectRaw("SUM(CASE WHEN payment_status = 'PARTIAL' THEN 1 ELSE 0 END) as partial_cases")
            ->selectRaw("SUM(CASE WHEN payment_status = 'UNPAID' THEN 1 ELSE 0 END) as unpaid_cases")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_status = 'PAID' THEN total_amount ELSE 0 END), 0) as total_sales")
            ->selectRaw('COALESCE(SUM(total_paid), 0) as total_collected')
            ->selectRaw('COALESCE(SUM(balance_amount), 0) as total_outstanding')
            ->first();
        $totalCases       = (int)   ($summary->total_cases      ?? 0);
        $paidCases        = (int)   ($summary->paid_cases       ?? 0);
        $partialCases     = (int)   ($summary->partial_cases    ?? 0);
        $unpaidCases      = (int)   ($summary->unpaid_cases     ?? 0);
        $totalSales       = (float) ($summary->total_sales      ?? 0);
        $totalCollected   = (float) ($summary->total_collected  ?? 0);
        $totalOutstanding = (float) ($summary->total_outstanding ?? 0);

        // Single grouped query for all branches — replaces N×7 individual queries.
        $branches = Branch::query()
            ->when($scopeBranchIds !== null, fn ($query) => $query->whereIn('id', $scopeBranchIds))
            ->orderBy('branch_code')
            ->get();
        $branchAggregates = (clone $allBase)
            ->select('branch_id')
            ->selectRaw('COUNT(*) as cases')
            ->selectRaw("SUM(CASE WHEN payment_status = 'PAID' THEN 1 ELSE 0 END) as paid_cases")
            ->selectRaw("SUM(CASE WHEN payment_status = 'PARTIAL' THEN 1 ELSE 0 END) as partial_cases")
            ->selectRaw("SUM(CASE WHEN payment_status = 'UNPAID' THEN 1 ELSE 0 END) as unpaid_cases")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_status = 'PAID' THEN total_amount ELSE 0 END), 0) as sales")
            ->selectRaw('COALESCE(SUM(total_paid), 0) as collected')
            ->selectRaw('COALESCE(SUM(balance_amount), 0) as outstanding')
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');

        $branchSales = $branches->map(function ($branch) use ($branchAggregates) {
            $row = $branchAggregates->get($branch->id);
            return [
                'branch'        => $branch,
                'cases'         => (int)   ($row->cases         ?? 0),
                'paid_cases'    => (int)   ($row->paid_cases    ?? 0),
                'partial_cases' => (int)   ($row->partial_cases ?? 0),
                'unpaid_cases'  => (int)   ($row->unpaid_cases  ?? 0),
                'sales'         => (float) ($row->sales         ?? 0),
                'collected'     => (float) ($row->collected     ?? 0),
                'outstanding'   => (float) ($row->outstanding   ?? 0),
            ];
        });

        return view('admin.reports.sales', compact(
            'branches',
            'branchId',
            'datePreset',
            'dateFrom',
            'dateTo',
            'dateFromInput',
            'dateToInput',
            'intermentFrom',
            'intermentTo',
            'totalCases',
            'paidCases',
            'partialCases',
            'unpaidCases',
            'totalSales',
            'totalCollected',
            'totalOutstanding',
            'branchSales'
        ));
    }

    private function effectiveBranchId(Request $request, mixed $requestedBranchId): ?int
    {
        $user = $request->user();

        if ($user?->isBranchAdmin()) {
            return $user->branch_id ? (int) $user->branch_id : null;
        }

        return filled($requestedBranchId) ? (int) $requestedBranchId : null;
    }
}
