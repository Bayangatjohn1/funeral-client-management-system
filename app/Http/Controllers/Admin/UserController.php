<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use App\Support\AuditLogger;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with([
                'branch',
                'latestTemporaryPermission',
                'latestTemporaryPermission.branch',
            ])
            ->latest()
            ->paginate(20)
            ->withQueryString();
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $branches = Branch::orderBy('branch_name')->get();
        return view('admin.users.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,staff,owner',
            'branch_id' => 'nullable|exists:branches,id',
            'contact_number' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'can_encode_any_branch' => 'nullable|boolean',
            'grant_temp_access' => 'nullable|boolean',
            'temp_allowed_branch_id' => 'nullable|exists:branches,id',
            'temp_expires_at' => 'nullable|date|after_or_equal:today',
        ]);

        if ($validated['role'] === 'staff' && empty($validated['branch_id'])) {
            return back()->withErrors(['branch_id' => 'Branch is required for staff accounts.'])->withInput();
        }

        $canEncodeAnyBranch = $validated['role'] === 'staff' ? $request->boolean('can_encode_any_branch') : false;
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'branch_id' => $validated['branch_id'] ?? null,
            'contact_number' => $validated['contact_number'] ?? null,
            'position' => $validated['position'] ?? null,
            'address' => $validated['address'] ?? null,
            'can_encode_any_branch' => $canEncodeAnyBranch,
            'is_active' => true,
        ]);

        $this->maybeGrantTemporaryPermission($request, $user);

        AuditLogger::log(
            action: 'user.created',
            actionType: 'create',
            entityType: 'user',
            entityId: $user->id,
            metadata: [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'branch_id' => $user->branch_id,
                'can_encode_any_branch' => $user->can_encode_any_branch,
            ],
            branchId: $user->branch_id
        );

        $returnTo = $request->input('return_to');
        if ($returnTo) {
            return redirect()->to($returnTo)->with('success', 'User created successfully.');
        }

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $user->load([
            'branch',
            'latestTemporaryPermission',
            'latestTemporaryPermission.branch',
        ]);

        $activeTempPermission = $user->temporaryPermissions()
            ->active()
            ->latest('granted_at')
            ->first();
        $latestTempPermission = $user->latestTemporaryPermission;

        $branches = Branch::orderBy('branch_name')->get();
        return view('admin.users.edit', compact('user', 'branches', 'activeTempPermission', 'latestTempPermission'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,staff,owner',
            'branch_id' => 'nullable|exists:branches,id',
            'contact_number' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'can_encode_any_branch' => 'nullable|boolean',
            'grant_temp_access' => 'nullable|boolean',
            'temp_allowed_branch_id' => 'nullable|exists:branches,id',
            'temp_expires_at' => 'nullable|date|after_or_equal:today',
        ]);

        if ($validated['role'] === 'staff' && empty($validated['branch_id'])) {
            return back()->withErrors(['branch_id' => 'Branch is required for staff accounts.'])->withInput();
        }

        $before = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'branch_id' => $user->branch_id,
            'can_encode_any_branch' => $user->can_encode_any_branch,
        ];

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'branch_id' => $validated['branch_id'] ?? null,
            'contact_number' => $validated['contact_number'] ?? null,
            'position' => $validated['position'] ?? null,
            'address' => $validated['address'] ?? null,
            'can_encode_any_branch' => $validated['role'] === 'staff'
                ? $request->boolean('can_encode_any_branch')
                : false,
        ]);

        $this->maybeGrantTemporaryPermission($request, $user);

        AuditLogger::log(
            action: 'user.updated',
            actionType: 'update',
            entityType: 'user',
            entityId: $user->id,
            metadata: [
                'before' => $before,
                'after' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'branch_id' => $user->branch_id,
                    'can_encode_any_branch' => $user->can_encode_any_branch,
                ],
            ],
            branchId: $user->branch_id
        );

        $returnTo = $request->input('return_to');
        if ($returnTo) {
            return redirect()->to($returnTo)->with('success', 'User updated successfully.');
        }

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function toggleActive(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', "You can't deactivate your own account.");
        }

        $previousStatus = $user->is_active;
        $user->is_active = !$user->is_active;
        $user->save();

        AuditLogger::log(
            action: $user->is_active ? 'user.activated' : 'user.deactivated',
            actionType: 'status_change',
            entityType: 'user',
            entityId: $user->id,
            metadata: [
                'target_user' => $user->name,
                'target_email' => $user->email,
                'from' => $previousStatus ? 'active' : 'inactive',
                'to' => $user->is_active ? 'active' : 'inactive',
            ],
            branchId: $user->branch_id
        );

        return back()->with('success', 'User status updated.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $user->password = Hash::make($validated['password']);
        $user->save();

        AuditLogger::log(
            action: 'user.password_reset',
            actionType: 'security',
            entityType: 'user',
            entityId: $user->id,
            metadata: [
                'target_user' => $user->name,
                'target_email' => $user->email,
                'reset_by' => auth()->id(),
            ],
            branchId: $user->branch_id
        );

        return back()->with('success', 'Password reset successfully.');
    }

    private function maybeGrantTemporaryPermission(Request $request, User $user): void
    {
        if ($user->role !== 'staff') {
            return;
        }

        $grant = $request->boolean('grant_temp_access');
        $allowedBranchId = $request->input('temp_allowed_branch_id');

        if (!$grant || !$allowedBranchId) {
            // If admin unchecks while an active permission exists, deactivate it.
            $user->temporaryPermissions()->active()->update(['is_active' => false]);
            return;
        }

        $branch = Branch::where('id', $allowedBranchId)
            ->where('is_active', true)
            ->first();

        if (!$branch) {
            throw ValidationException::withMessages([
                'temp_allowed_branch_id' => 'Selected branch is unavailable.',
            ]);
        }

        if (strtoupper((string) $branch->branch_code) === 'BR001') {
            throw ValidationException::withMessages([
                'temp_allowed_branch_id' => 'Temporary cross-branch access must target a non-main branch.',
            ]);
        }

        $expiresAt = $request->filled('temp_expires_at')
            ? Carbon::parse($request->input('temp_expires_at'))->endOfDay()
            : null;

        // Ensure only one active permission at a time.
        $user->temporaryPermissions()->active()->update(['is_active' => false]);

        $permission = $user->temporaryPermissions()->create([
            'allowed_branch_id' => $branch->id,
            'granted_by' => auth()->id(),
            'is_active' => true,
            'is_used' => false,
            'granted_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        AuditLogger::log(
            action: 'permission.cross_branch.granted',
            actionType: 'permission',
            entityType: 'temporary_cross_branch_permission',
            entityId: $permission->id,
            metadata: [
                'granted_to' => $user->id,
                'allowed_branch_id' => $branch->id,
                'expires_at' => $expiresAt?->toIso8601String(),
            ],
            branchId: $user->branch_id,
            targetBranchId: $branch->id
        );
    }
}
