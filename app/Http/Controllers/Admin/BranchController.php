<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\FuneralCase;
use App\Support\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $monthStart = Carbon::now()->startOfMonth();
        $nextMonthStart = (clone $monthStart)->addMonth();

        $query = Branch::withCount('funeralCases')
            ->withSum([
                'funeralCases as current_month_revenue_sum' => fn ($query) => $query
                    ->where('created_at', '>=', $monthStart)
                    ->where('created_at', '<', $nextMonthStart),
            ], 'total_amount');

        if ($q = $request->input('q')) {
            $query->where(function ($builder) use ($q) {
                $builder->where('branch_name', 'like', "%{$q}%")
                        ->orWhere('branch_code', 'like', "%{$q}%")
                        ->orWhere('address', 'like', "%{$q}%");
            });
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        if ($request->filled('branch_id')) {
            $query->where('id', (int) $request->input('branch_id'));
        }

        [$sortCol, $sortDir] = match ($request->input('sort', 'code_asc')) {
            'name_asc'     => ['branch_name', 'asc'],
            'records_desc' => ['funeral_cases_count', 'desc'],
            'records_asc'  => ['funeral_cases_count', 'asc'],
            'revenue_desc' => ['current_month_revenue_sum', 'desc'],
            'revenue_asc'  => ['current_month_revenue_sum', 'asc'],
            default        => ['branch_code', 'asc'],
        };
        $query->orderBy($sortCol, $sortDir);

        $branches = $query->paginate(20)->withQueryString();
        $nextCode = $this->nextBranchCode();

        $stats      = Branch::selectRaw('COUNT(*) as total, SUM(is_active) as active_count')->first();
        $totalRecords = FuneralCase::count();
        $mainBranch = Branch::where('branch_code', 'BR001')->first();
        $branchKpis = $this->branchKpis();

        return view('admin.branches.index', [
            'branches'       => $branches,
            'nextCode'       => $nextCode,
            'totalBranches'  => (int) ($stats->total ?? 0),
            'activeBranches' => (int) ($stats->active_count ?? 0),
            'totalRecords'   => $totalRecords,
            'mainBranch'     => $mainBranch,
            'branchKpis'     => $branchKpis,
        ]);
    }

    public function create()
    {
        $nextCode = $this->nextBranchCode();

        return view('admin.branches.create', compact('nextCode'));
    }

    public function store(Request $request)
    {
        $this->trimBranchInput($request);

        $validated = $request->validate($this->branchValidationRules(), $this->branchValidationMessages());

        $request->merge([
            'branch_name' => $this->normalizeBranchName((string) $validated['branch_name']),
        ]);

        $branch = Branch::create([
            'branch_code' => $this->nextBranchCode(),
            'branch_name' => $request->input('branch_name'),
            'address' => $validated['address'],
            'is_active' => $request->boolean('is_active'),
        ]);

        AuditLogger::log(
            action: 'branch.created',
            actionType: 'create',
            entityType: 'branch',
            entityId: $branch->id,
            metadata: [
                'branch_code' => $branch->branch_code,
                'branch_name' => $branch->branch_name,
                'address' => $branch->address,
                'is_active' => $branch->is_active,
            ],
            branchId: $branch->id
        );

        $returnTo = $request->input('return_to');
        if ($returnTo) {
            return redirect()->to($returnTo)->with('success', 'Branch created successfully.');
        }

        return redirect()->route('admin.branches.index')->with('success', 'Branch created successfully.');
    }

    public function edit(Branch $branch)
    {
        return view('admin.branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $this->trimBranchInput($request);

        $validated = $request->validate($this->branchValidationRules(), $this->branchValidationMessages());

        $request->merge([
            'branch_name' => $this->normalizeBranchName((string) $validated['branch_name']),
        ]);

        if ($this->isProtectedMainBranch($branch) && !$request->boolean('is_active')) {
            return back()->withErrors([
                'is_active' => 'Main branch (BR001) must remain active.',
            ])->withInput();
        }

        $before = [
            'branch_name' => $branch->branch_name,
            'address' => $branch->address,
            'is_active' => $branch->is_active,
        ];

        $branch->update([
            'branch_name' => $request->input('branch_name'),
            'address' => $validated['address'],
            'is_active' => $request->boolean('is_active'),
        ]);

        AuditLogger::log(
            action: 'branch.updated',
            actionType: 'update',
            entityType: 'branch',
            entityId: $branch->id,
            metadata: [
                'branch_code' => $branch->branch_code,
                'before' => $before,
                'after' => [
                    'branch_name' => $branch->branch_name,
                    'address' => $branch->address,
                    'is_active' => $branch->is_active,
                ],
            ],
            branchId: $branch->id
        );

        $returnTo = $request->input('return_to');
        if ($returnTo) {
            return redirect()->to($returnTo)->with('success', 'Branch updated successfully.');
        }

        return redirect()->route('admin.branches.index')->with('success', 'Branch updated successfully.');
    }

    public function toggleStatus(Branch $branch)
    {
        if ($this->isProtectedMainBranch($branch) && $branch->is_active) {
            return redirect()->route('admin.branches.index')
                ->withErrors(['is_active' => 'Main branch (BR001) must remain active.']);
        }

        $previousStatus = $branch->is_active;

        $branch->update([
            'is_active' => !$branch->is_active,
        ]);

        AuditLogger::log(
            action: 'branch.status_toggled',
            actionType: 'status_change',
            entityType: 'branch',
            entityId: $branch->id,
            metadata: [
                'branch_code' => $branch->branch_code,
                'branch_name' => $branch->branch_name,
                'from' => $previousStatus ? 'active' : 'inactive',
                'to' => $branch->is_active ? 'active' : 'inactive',
            ],
            branchId: $branch->id
        );

        return redirect()->route('admin.branches.index')
            ->with('success', 'Branch status updated successfully.');
    }

    private function nextBranchCode(): string
    {
        $max = Branch::pluck('branch_code')
            ->map(function ($code) {
                return (int) preg_replace('/\D+/', '', (string) $code);
            })
            ->max();

        $next = ($max ?? 0) + 1;

        return 'BR' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    private function branchKpis(): array
    {
        $monthStart = Carbon::now()->startOfMonth();
        $nextMonthStart = (clone $monthStart)->addMonth();
        $previousMonthStart = (clone $monthStart)->subMonth();

        $rankedBranches = Branch::query()
            ->withCount([
                'funeralCases as total_cases_count',
                'funeralCases as current_month_cases_count' => fn ($query) => $query
                    ->where('created_at', '>=', $monthStart)
                    ->where('created_at', '<', $nextMonthStart),
                'funeralCases as previous_month_cases_count' => fn ($query) => $query
                    ->where('created_at', '>=', $previousMonthStart)
                    ->where('created_at', '<', $monthStart),
            ])
            ->withSum([
                'funeralCases as current_month_revenue_sum' => fn ($query) => $query
                    ->where('created_at', '>=', $monthStart)
                    ->where('created_at', '<', $nextMonthStart),
                'funeralCases as previous_month_revenue_sum' => fn ($query) => $query
                    ->where('created_at', '>=', $previousMonthStart)
                    ->where('created_at', '<', $monthStart),
            ], 'total_amount')
            ->orderBy('branch_code')
            ->get()
            ->each(function (Branch $branch): void {
                $branch->current_month_revenue_sum = (float) ($branch->current_month_revenue_sum ?? 0);
                $branch->previous_month_revenue_sum = (float) ($branch->previous_month_revenue_sum ?? 0);
                $branch->current_month_cases_count = (int) ($branch->current_month_cases_count ?? 0);
                $branch->previous_month_cases_count = (int) ($branch->previous_month_cases_count ?? 0);
            });

        $activeBranches = $rankedBranches->where('is_active', true)->values();
        $totalBranches = $rankedBranches->count();
        $activeCount = $activeBranches->count();
        $currentMonthCases = (int) $rankedBranches->sum('current_month_cases_count');
        $previousMonthCases = (int) $rankedBranches->sum('previous_month_cases_count');
        $currentMonthRevenue = (float) $rankedBranches->sum('current_month_revenue_sum');
        $branchAverageRevenue = $activeCount > 0 ? round($currentMonthRevenue / $activeCount, 2) : 0.0;

        $revenueRankings = $activeBranches
            ->sortBy([
                ['current_month_revenue_sum', 'desc'],
                ['current_month_cases_count', 'desc'],
                ['branch_code', 'asc'],
            ])
            ->values();

        $topBranch = $revenueRankings->first();
        $lowestBranch = $revenueRankings
            ->sortBy([
                ['current_month_revenue_sum', 'asc'],
                ['current_month_cases_count', 'asc'],
                ['branch_code', 'asc'],
            ])
            ->values()
            ->first();

        return [
            'activeCoverage' => [
                'value' => "{$activeCount}/{$totalBranches}",
                'label' => 'Active Branches',
                'insight' => $totalBranches > 0
                    ? number_format(($activeCount / max($totalBranches, 1)) * 100, 0) . '% coverage'
                    : 'No branches yet',
                'comparison' => ($totalBranches - $activeCount) > 0
                    ? ($totalBranches - $activeCount) . ' inactive'
                    : 'All branches active',
                'href' => route('admin.branches.index', ['status' => 'active']),
                'icon' => 'bi-building-check',
                'action' => 'View Active Branches',
            ],
            'monthlyActivity' => [
                'value' => number_format($currentMonthCases),
                'label' => 'Cases This Month',
                'insight' => $this->trendLabel($currentMonthCases, $previousMonthCases),
                'comparison' => 'Revenue: PHP ' . number_format($currentMonthRevenue, 2),
                'href' => route('admin.cases.index', ['date_preset' => 'THIS_MONTH']),
                'icon' => 'bi-graph-up-arrow',
                'action' => 'View Cases',
            ],
            'topBranch' => [
                'value' => $topBranch ? 'PHP ' . number_format((float) $topBranch->current_month_revenue_sum, 2) : 'PHP 0.00',
                'label' => 'Highest Sales Branch',
                'insight' => $topBranch ? $topBranch->branch_code . ' - ' . $topBranch->branch_name : 'No active branch',
                'comparison' => $topBranch
                    ? '#1 sales | ' . number_format((int) $topBranch->current_month_cases_count) . ' cases'
                    : 'No activity yet',
                'href' => $topBranch
                    ? route('admin.branches.index', ['branch_id' => $topBranch->id, 'highlight_branch' => $topBranch->id])
                    : route('admin.branches.index', ['status' => 'active']),
                'icon' => 'bi-trophy',
                'action' => 'View Branch',
            ],
            'lowestActivity' => [
                'value' => $lowestBranch ? 'PHP ' . number_format((float) $lowestBranch->current_month_revenue_sum, 2) : 'PHP 0.00',
                'label' => 'Lowest Sales Branch',
                'insight' => $lowestBranch ? $lowestBranch->branch_code . ' - ' . $lowestBranch->branch_name : 'No active branch',
                'comparison' => $lowestBranch
                    ? $this->salesAverageComparison((float) $lowestBranch->current_month_revenue_sum, $branchAverageRevenue) . ' | ' . number_format((int) $lowestBranch->current_month_cases_count) . ' cases'
                    : 'No activity yet',
                'href' => $lowestBranch
                    ? route('admin.branches.index', ['branch_id' => $lowestBranch->id, 'highlight_branch' => $lowestBranch->id])
                    : route('admin.branches.index', ['status' => 'active']),
                'icon' => 'bi-activity',
                'action' => 'View Branch',
            ],
        ];
    }

    private function trendLabel(int $current, int $previous): string
    {
        if ($previous === 0) {
            return $current > 0 ? 'New activity this month' : 'No month activity yet';
        }

        $change = (($current - $previous) / $previous) * 100;
        $direction = $change >= 0 ? 'up' : 'down';

        return abs($change) < 0.1
            ? 'Flat vs last month'
            : number_format(abs($change), 0) . "% {$direction} vs last month";
    }

    private function salesAverageComparison(float $branchRevenue, float $average): string
    {
        if ($average <= 0) {
            return 'Sales avg: PHP 0.00';
        }

        $delta = $branchRevenue - $average;

        if (abs($delta) < 0.01) {
            return 'At sales average';
        }

        return 'PHP ' . number_format(abs($delta), 2) . ' ' . ($delta >= 0 ? 'above' : 'below') . ' sales avg';
    }

    private function isProtectedMainBranch(Branch $branch): bool
    {
        return strtoupper((string) $branch->branch_code) === 'BR001';
    }

    private function normalizeBranchName(string $name): string
    {
        $name = preg_replace('/\d+/', '', $name);
        $name = preg_replace('/\s+/', ' ', (string) $name);
        return trim((string) $name);
    }

    private function branchValidationRules(): array
    {
        return [
            'branch_name' => ['required', 'string', 'max:255', 'regex:/^[\pL\pM][\pL\pM\s\'.&-]*$/u'],
            'address' => ['required', 'string', 'max:255', $this->validBranchAddressRule()],
            'is_active' => 'boolean',
        ];
    }

    private function branchValidationMessages(): array
    {
        return [
            'branch_name.required' => 'Branch name is required.',
            'branch_name.regex' => 'Branch name must contain letters only.',
            'address.required' => 'Address is required.',
        ];
    }

    private function trimBranchInput(Request $request): void
    {
        $request->merge([
            'branch_name' => trim(preg_replace('/\s+/', ' ', (string) $request->input('branch_name'))),
            'address' => trim(preg_replace('/\s+/', ' ', (string) $request->input('address'))),
        ]);
    }

    private function validBranchAddressRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $value = trim((string) $value);

            if ($value === '' || ! preg_match('/[\pL\pM]/u', $value) || preg_match('/^\d+$/', $value)) {
                $fail('Address must include a valid place name.');
            }
        };
    }
}
