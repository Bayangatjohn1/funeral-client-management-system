<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\FuneralCase;
use App\Support\AuditLogger;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $query = Branch::withCount('funeralCases');

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

        [$sortCol, $sortDir] = match ($request->input('sort', 'code_asc')) {
            'name_asc'     => ['branch_name', 'asc'],
            'records_desc' => ['funeral_cases_count', 'desc'],
            default        => ['branch_code', 'asc'],
        };
        $query->orderBy($sortCol, $sortDir);

        $branches = $query->paginate(20)->withQueryString();
        $nextCode = $this->nextBranchCode();

        $stats      = Branch::selectRaw('COUNT(*) as total, SUM(is_active) as active_count')->first();
        $totalRecords = FuneralCase::count();
        $mainBranch = Branch::where('branch_code', 'BR001')->first();

        return view('admin.branches.index', [
            'branches'       => $branches,
            'nextCode'       => $nextCode,
            'totalBranches'  => (int) ($stats->total ?? 0),
            'activeBranches' => (int) ($stats->active_count ?? 0),
            'totalRecords'   => $totalRecords,
            'mainBranch'     => $mainBranch,
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
