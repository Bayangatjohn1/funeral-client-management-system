<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\FuneralCase;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/** Read-only executive metrics with explicit case-date and receipt-date bases. */
class OwnerDashboardOverview
{
    public function cases(?int $branchId): Builder
    {
        return FuneralCase::query()->where('verification_status', 'VERIFIED')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }

    public function payments(?int $branchId): Builder
    {
        return Payment::query()->whereHas('funeralCase', fn ($q) => $q->where('verification_status', 'VERIFIED'))
            ->where(fn ($q) => $q->whereNull('status')->orWhereIn('status', ['POSTED', 'VALID']))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }

    public function risks(?int $branchId): Builder
    {
        return $this->cases($branchId)->where('balance_amount', '>', 0)
            ->where(function ($q) {
                $q->whereDate('interment_at', '<', now()->toDateString())
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('interment_at')->whereHas('deceased', fn ($d) =>
                            $d->whereRaw('DATE(COALESCE(interment_at, interment)) < ?', [now()->toDateString()]));
                    });
            });
    }

    public function receivedBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereRaw('COALESCE(paid_at, paid_date, created_at) >= ?', [$start])
            ->whereRaw('COALESCE(paid_at, paid_date, created_at) <= ?', [$end]);
    }

    public function data(array $filters): array
    {
        $branch = $filters['branch_id'] ? (int) $filters['branch_id'] : null;
        $start = Carbon::parse($filters['date_from'])->startOfDay();
        $end = Carbon::parse($filters['date_to'])->endOfDay();
        $days = (int) $start->diffInDays($end->copy()->startOfDay());
        $previousStart = match ($filters['range']) {
            'THIS_WEEK' => $start->copy()->subWeek(),
            'THIS_MONTH', 'LAST_MONTH' => $start->copy()->subMonthNoOverflow(),
            'THIS_YEAR' => $start->copy()->subYearNoOverflow(),
            'THIS_QUARTER' => $start->copy()->subMonthsNoOverflow(3),
            default => $start->copy()->subDays($days + 1),
        };
        $previousEnd = $previousStart->copy()->addDays($days)->endOfDay();
        if (in_array($filters['range'], ['THIS_MONTH', 'LAST_MONTH'], true)) {
            $previousEnd = $previousEnd->min($previousStart->copy()->endOfMonth());
        }
        $previousEnd = $previousEnd->min($start->copy()->subDay()->endOfDay());
        $periodCases = $this->cases($branch)->whereBetween('created_at', [$start, $end]);
        $periodPayments = $this->receivedBetween($this->payments($branch), $start, $end);
        $outstanding = $this->cases($branch)->where('balance_amount', '>', 0);
        $risks = $this->risks($branch);
        $current = [
            'cases' => (clone $periodCases)->count(),
            'service' => (float) (clone $periodCases)->sum('total_amount'),
            'collections' => (float) (clone $periodPayments)->sum('amount'),
            'outstanding' => (float) (clone $outstanding)->sum('balance_amount'),
        ];
        $previous = [
            'cases' => $this->cases($branch)->whereBetween('created_at', [$previousStart, $previousEnd])->count(),
            'service' => (float) $this->cases($branch)->whereBetween('created_at', [$previousStart, $previousEnd])->sum('total_amount'),
            'collections' => (float) $this->receivedBetween($this->payments($branch), $previousStart, $previousEnd)->sum('amount'),
        ];
        $comparison = [];
        foreach ($previous as $key => $value) {
            $comparison[$key] = $filters['range'] === 'ALL_TIME' ? null : ($value > 0 ? round(($current[$key] - $value) / $value * 100, 1) : null);
        }
        $caseRows = (clone $periodCases)->selectRaw('DATE(created_at) as bucket, COUNT(*) as amount')->groupByRaw('DATE(created_at)')->get();
        $paymentRows = (clone $periodPayments)->selectRaw('DATE(COALESCE(paid_at, paid_date, created_at)) as bucket, SUM(amount) as amount')
            ->groupByRaw('DATE(COALESCE(paid_at, paid_date, created_at))')->get();
        $monthly = $days > 62;
        $bucketFormat = $monthly ? 'Y-m' : 'Y-m-d';
        $caseBuckets = $caseRows->groupBy(fn ($row) => Carbon::parse($row->bucket)->format($bucketFormat))->map(fn ($rows) => $rows->sum('amount'));
        $paymentBuckets = $paymentRows->groupBy(fn ($row) => Carbon::parse($row->bucket)->format($bucketFormat))->map(fn ($rows) => $rows->sum('amount'));
        $points = [];
        $cursor = $monthly ? $start->copy()->startOfMonth() : $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->format($bucketFormat);
            $points[] = ['label' => $cursor->format($monthly ? 'M Y' : 'M d'), 'from' => $cursor->copy()->max($start)->toDateString(), 'to' => ($monthly ? $cursor->copy()->endOfMonth() : $cursor->copy())->min($end)->toDateString(), 'cases' => (int) ($caseBuckets[$key] ?? 0), 'collections' => (float) ($paymentBuckets[$key] ?? 0)];
            $monthly ? $cursor->addMonth() : $cursor->addDay();
        }
        $branchCases = (clone $periodCases)->selectRaw('branch_id, COUNT(*) as cases, SUM(total_amount) as service')->groupBy('branch_id')->get()->keyBy('branch_id');
        // Collection efficiency uses payments for the same selected case cohort, across payment dates.
        $cohortPayments = $this->payments(null)
            ->whereIn('funeral_case_id', (clone $periodCases)->select('id'))
            ->whereRaw('COALESCE(payments.paid_at, payments.paid_date, payments.created_at) <= ?', [now()])
            ->join('funeral_cases as ranked_cases', 'ranked_cases.id', '=', 'payments.funeral_case_id')
            ->selectRaw('ranked_cases.branch_id, SUM(payments.amount) as collected')
            ->groupBy('ranked_cases.branch_id')->get()->keyBy('branch_id');
        $branchPayments = (clone $periodPayments)->selectRaw('branch_id, SUM(amount) as collections')->groupBy('branch_id')->get()->keyBy('branch_id');
        $branchBalances = (clone $outstanding)->selectRaw('branch_id, SUM(balance_amount) as outstanding')->groupBy('branch_id')->get()->keyBy('branch_id');
        $branchRisks = (clone $risks)->selectRaw('branch_id, COUNT(*) as risks')->groupBy('branch_id')->get()->keyBy('branch_id');
        $branchRows = Branch::when($branch, fn ($q) => $q->whereKey($branch))->orderBy('branch_code')->get()->map(fn ($b) => [
            'branch' => $b, 'cases' => (int) ($branchCases[$b->id]->cases ?? 0),
            'service' => (float) ($branchCases[$b->id]->service ?? 0),
            'collections' => (float) ($branchPayments[$b->id]->collections ?? 0),
            'cohortCollected' => (float) ($cohortPayments[$b->id]->collected ?? 0),
            'cohortRemaining' => max(0, (float) ($branchCases[$b->id]->service ?? 0) - (float) ($cohortPayments[$b->id]->collected ?? 0)),
            'rate' => (float) ($branchCases[$b->id]->service ?? 0) > 0
                ? (float) ($cohortPayments[$b->id]->collected ?? 0) / (float) $branchCases[$b->id]->service * 100 : null,
            'outstanding' => (float) ($branchBalances[$b->id]->outstanding ?? 0),
            'risks' => (int) ($branchRisks[$b->id]->risks ?? 0),
        ]);
        return compact('current', 'previous', 'comparison', 'points', 'branchRows', 'start', 'end', 'previousStart', 'previousEnd') + [
            'riskCount' => (clone $risks)->count(), 'riskAmount' => (float) (clone $risks)->sum('balance_amount'),
            'balanceCount' => (clone $outstanding)->count(), 'paymentCount' => (clone $periodPayments)->count(),
        ];
    }
}
