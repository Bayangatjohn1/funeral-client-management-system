<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\FuneralCase;
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
            'interment_from' => ['nullable', 'date'],
            'interment_to' => ['nullable', 'date', 'after_or_equal:interment_from'],
        ], [
            'q.regex' => 'Search may contain letters, numbers, spaces, apostrophes, periods, and hyphens only.',
        ]);

        $branchId = $validated['branch_id'] ?? null;
        $q = $validated['q'] ?? null;
        $paymentStatus = $validated['payment_status'] ?? null;
        $caseStatus = $validated['case_status'] ?? null;
        $verificationStatus = $validated['verification_status'] ?? null;
        $intermentFrom = $validated['interment_from'] ?? null;
        $intermentTo = $validated['interment_to'] ?? null;

        $cases = FuneralCase::with(['branch', 'client', 'deceased'])
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->when($paymentStatus, fn ($query) => $query->where('payment_status', $paymentStatus))
            ->when($caseStatus, fn ($query) => $query->where('case_status', $caseStatus))
            ->when($verificationStatus, fn ($query) => $query->where('verification_status', $verificationStatus))
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

        return view('admin.reports.master_cases', compact(
            'cases',
            'branches',
            'branchId',
            'q',
            'paymentStatus',
            'caseStatus',
            'verificationStatus',
            'intermentFrom',
            'intermentTo'
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

        return back()->with('success', 'Case verification marked as DISPUTED.');
    }

    public function sales(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'interment_from' => ['nullable', 'date'],
            'interment_to' => ['nullable', 'date', 'after_or_equal:interment_from'],
        ]);

        $branchId = $validated['branch_id'] ?? null;
        $dateFrom = $validated['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $validated['date_to'] ?? now()->toDateString();
        $intermentFrom = $validated['interment_from'] ?? null;
        $intermentTo = $validated['interment_to'] ?? null;

        $base = FuneralCase::query()
            ->where('verification_status', 'VERIFIED')
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
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
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId));

        $totalCases = (clone $base)->count();
        $paidCases = (clone $base)->where('payment_status', 'PAID')->count();
        $partialCases = (clone $base)->where('payment_status', 'PARTIAL')->count();
        $unpaidCases = (clone $base)->where('payment_status', 'UNPAID')->count();
        $totalSales = (clone $base)->where('payment_status', 'PAID')->sum('total_amount');
        $totalCollected = (clone $base)->sum('total_paid');
        $totalOutstanding = (clone $base)->sum('balance_amount');

        $branches = Branch::orderBy('branch_code')->get();
        $branchSales = $branches->map(function ($branch) use ($dateFrom, $dateTo, $intermentFrom, $intermentTo) {
            $query = FuneralCase::where('branch_id', $branch->id)
                ->where('verification_status', 'VERIFIED')
                ->whereDate('created_at', '>=', $dateFrom)
                ->whereDate('created_at', '<=', $dateTo)
                ->when($intermentFrom || $intermentTo, function ($q) use ($intermentFrom, $intermentTo) {
                    $q->whereHas('deceased', function ($dq) use ($intermentFrom, $intermentTo) {
                        if ($intermentFrom) {
                            $dq->whereRaw('DATE(COALESCE(interment_at, interment)) >= ?', [$intermentFrom]);
                        }
                        if ($intermentTo) {
                            $dq->whereRaw('DATE(COALESCE(interment_at, interment)) <= ?', [$intermentTo]);
                        }
                    });
                });

            return [
                'branch' => $branch,
                'cases' => (clone $query)->count(),
                'paid_cases' => (clone $query)->where('payment_status', 'PAID')->count(),
                'partial_cases' => (clone $query)->where('payment_status', 'PARTIAL')->count(),
                'unpaid_cases' => (clone $query)->where('payment_status', 'UNPAID')->count(),
                'sales' => (float) (clone $query)->where('payment_status', 'PAID')->sum('total_amount'),
                'collected' => (float) (clone $query)->sum('total_paid'),
                'outstanding' => (float) (clone $query)->sum('balance_amount'),
            ];
        });

        return view('admin.reports.sales', compact(
            'branches',
            'branchId',
            'dateFrom',
            'dateTo',
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
}
