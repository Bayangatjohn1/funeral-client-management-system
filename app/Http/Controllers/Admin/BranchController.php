<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::withCount('funeralCases')
            ->orderBy('branch_code')
            ->paginate(20)
            ->withQueryString();

        $nextCode = $this->nextBranchCode();

        return view('admin.branches.index', compact('branches', 'nextCode'));
    }

    public function create()
    {
        $nextCode = $this->nextBranchCode();

        return view('admin.branches.create', compact('nextCode'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'branch_name' => $this->normalizeBranchName((string) $request->input('branch_name')),
        ]);

        $validated = $request->validate([
            'branch_name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z][A-Za-z\s\'.&-]*$/'],
            'address' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ], [
            'branch_name.regex' => 'Branch name must contain letters only (no numbers).',
        ]);

        Branch::create([
            'branch_code' => $this->nextBranchCode(),
            'branch_name' => $validated['branch_name'],
            'address' => $validated['address'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.branches.index')->with('success', 'Branch created successfully.');
    }

    public function edit(Branch $branch)
    {
        return view('admin.branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $request->merge([
            'branch_name' => $this->normalizeBranchName((string) $request->input('branch_name')),
        ]);

        $validated = $request->validate([
            'branch_name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z][A-Za-z\s\'.&-]*$/'],
            'address' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ], [
            'branch_name.regex' => 'Branch name must contain letters only (no numbers).',
        ]);

        if ($this->isProtectedMainBranch($branch) && !$request->boolean('is_active')) {
            return back()->withErrors([
                'is_active' => 'Main branch (BR001) must remain active.',
            ])->withInput();
        }

        $branch->update([
            'branch_name' => $validated['branch_name'],
            'address' => $validated['address'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.branches.index')->with('success', 'Branch updated successfully.');
    }

    public function toggleStatus(Branch $branch)
    {
        if ($this->isProtectedMainBranch($branch) && $branch->is_active) {
            return redirect()->route('admin.branches.index')
                ->withErrors(['is_active' => 'Main branch (BR001) must remain active.']);
        }

        $branch->update([
            'is_active' => !$branch->is_active,
        ]);

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
}
