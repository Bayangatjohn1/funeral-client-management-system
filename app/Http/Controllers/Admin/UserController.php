<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Support\AuditLogger;
use App\Support\AccountSecurity;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index(Request $request)
{
    $actor = auth()->user();

    if (! $actor) {
        abort(403, 'Unauthorized');
    }

    $validated = $request->validate([
        'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        'q' => ['nullable', 'string', 'max:100'],
        'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        'role' => ['nullable', Rule::in(['admin', 'staff'])],
        'admin_scope' => ['nullable', Rule::in(['system', 'branch'])],
        'assignment' => ['nullable', Rule::in(['unassigned'])],
        'status' => ['nullable', Rule::in(['active', 'inactive'])],
        'sort' => ['nullable', Rule::in($actor->isBranchAdmin() ? ['latest', 'oldest', 'name_asc'] : ['latest', 'name_asc', 'role_asc', 'branch_asc'])],
    ]);

    $branches = collect();
    $selectedBranchId = isset($validated['branch_id']) ? (int) $validated['branch_id'] : null;
    $assignedBranch = null;

    $query = User::where('role', '!=', 'owner')
        ->with('branch');

    if ($actor->isSystemAdmin()) {
        $branches = Branch::query()
            ->where('is_active', true)
            ->orderBy('branch_code')
            ->get(['id', 'branch_code', 'branch_name']);

        $query->whereKeyNot($actor->id);
    } elseif ($actor->role === 'admin') {
        $assignedBranch = Branch::query()
            ->whereKey($actor->branch_id)
            ->first(['id', 'branch_code', 'branch_name']);

        $query->where('role', 'staff')
            ->where('branch_id', $actor->branch_id);
    } else {
        abort(403, 'Unauthorized');
    }

    // Directory totals follow the actor's account scope, not the selected filters or page.
    $directoryStats = [
        'total' => (clone $query)->count(),
        'active' => (clone $query)->where('is_active', true)->count(),
        'inactive' => (clone $query)->where('is_active', false)->count(),
        'admins' => $actor->isSystemAdmin() ? (clone $query)->where('role', 'admin')->count() : 0,
        'staff' => $actor->isSystemAdmin() ? (clone $query)->where('role', 'staff')->count() : 0,
    ];

    $query
        ->when($validated['q'] ?? null, function ($q, string $search) use ($actor) {
            $q->where(function ($searchQuery) use ($search, $actor) {
                $searchQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
                if (! $actor->isBranchAdmin()) {
                    $searchQuery->orWhere('contact_number', 'like', "%{$search}%")
                        ->orWhereHas('branch', fn ($branchQuery) => $branchQuery->where('branch_name', 'like', "%{$search}%"));
                }
            });
        })
        ->when($actor->isSystemAdmin() ? $selectedBranchId : null, fn ($q, int $branchId) => $q->where('branch_id', $branchId))
        ->when($actor->isSystemAdmin() ? ($validated['role'] ?? null) : null, fn ($q, string $role) => $q->where('role', $role))
        ->when($actor->isSystemAdmin() ? ($validated['admin_scope'] ?? null) : null, fn ($q, string $scope) => $q->where('admin_scope', $scope))
        ->when($actor->isSystemAdmin() && ($validated['assignment'] ?? null) === 'unassigned', fn ($q) => $q->whereNull('branch_id'))
        ->when($validated['status'] ?? null, fn ($q, string $status) => $q->where('is_active', $status === 'active'));

    match ($validated['sort'] ?? 'latest') {
        'oldest' => $query->oldest()->orderBy('users.id'),
        'name_asc' => $query->orderBy('name'),
        'role_asc' => $query->orderBy('role')->orderBy('name'),
        'branch_asc' => $query
            ->leftJoin('branches', 'users.branch_id', '=', 'branches.id')
            ->select('users.*')
            ->orderBy('branches.branch_name')
            ->orderBy('users.name'),
        default => $query->latest(),
    };

    $users = $query
        ->paginate((int) ($validated['per_page'] ?? 25))
        ->withQueryString();

    return view('admin.users.index', compact(
        'users',
        'branches',
        'selectedBranchId',
        'assignedBranch',
        'directoryStats'
    ));
}
    public function create(Request $request)
{
    $actor = auth()->user();

    if (! $actor) {
        abort(403, 'Unauthorized');
    }

    $accountRole = (string) $request->query('role', 'staff');
    abort_unless(in_array($accountRole, ['staff', 'branch_admin'], true), 404);
    abort_if(! $actor->isSystemAdmin() && $accountRole !== 'staff', 403);

    if ($actor->isSystemAdmin()) {
        $branches = Branch::where('is_active', true)->orderBy('branch_name')->get();
    } elseif ($actor->role === 'admin') {
        $branches = Branch::where('id', $actor->branch_id)->get();
    } else {
        abort(403, 'Unauthorized');
    }

    $currentBranch = $actor->isSystemAdmin()
        ? Branch::find($this->resolveMainBranchId($actor))
        : $actor->branch;

    return view('admin.users.create', compact('branches', 'currentBranch', 'accountRole'));
}
    public function store(Request $request)
    {
        $privileged = $request->routeIs('admin.system-admins.store');
        if ($privileged) {
            abort_unless($request->user()->isSystemAdmin(), 403);
            $request->validate([
                'current_password' => ['required', 'current_password:web'],
                'privileged_for_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($q) => $q->where('is_active', true)->whereIn('role', ['staff', 'admin'])->where(fn ($q) => $q->whereNull('admin_scope')->orWhere('admin_scope', 'branch'))), Rule::unique('users', 'privileged_for_user_id')],
                'reason' => ['required', 'string', 'min:10', 'max:500'],
                'confirmed' => ['accepted'],
                'password' => ['required', 'string', 'min:12'],
            ]);
            $request->merge(['role' => 'system_admin', 'branch_id' => null]);
        } elseif (in_array($request->input('admin_scope'), ['main', 'all_branches'], true)) {
            throw ValidationException::withMessages(['admin_scope' => 'Legacy global administrator scopes cannot be assigned.']);
        } elseif ($request->input('role') === 'system_admin' || $request->input('admin_scope') === 'system') {
            throw ValidationException::withMessages(['role' => 'Create a separate personal System Admin account using the dedicated action.']);
        }
        $this->trimUserInput($request);
        $this->hydrateSplitNameFromLegacyName($request);
        $this->normalizeRequestedRole($request);
        $this->ensureRoleAssignmentAllowed($request);
        $this->applyUserCreationScope($request);
        
        $validated = $request->validate($this->userValidationRules($request), $this->userValidationMessages());
        $validated = $this->prepareValidatedUserAttributes($validated);

        $userAttributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'branch_id' => $validated['branch_id'] ?? null,
            'contact_number' => $validated['contact_number'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => true,
        ];

        foreach (['first_name', 'middle_name', 'last_name', 'suffix'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                $userAttributes[$column] = $validated[$column] ?? null;
            }
        }

        if (Schema::hasColumn('users', 'admin_scope')) {
            $userAttributes['admin_scope'] = $validated['role'] === 'admin'
                ? (string) $request->input('admin_scope', 'branch')
                : null;
        }
        if (Schema::hasColumn('users', 'created_by')) {
            $userAttributes['created_by'] = $request->user()->id;
        }
        $user = DB::transaction(function () use ($userAttributes, $privileged, $request) {
            if ($privileged && $request->filled('privileged_for_user_id')) {
                $regular = User::whereKey($request->integer('privileged_for_user_id'))->lockForUpdate()->firstOrFail();
                abort_unless($regular->is_active && ($regular->isStaff() || $regular->isBranchAdmin()), 403);
            }
            $user = User::create($userAttributes);
            if ($privileged) {
                $user->privileged_for_user_id = $request->filled('privileged_for_user_id') ? $request->integer('privileged_for_user_id') : null;
                $user->must_change_password = true;
                $user->save();
                AccountSecurity::audit('user.system_admin_created', $user, ['regular_account_id' => $user->privileged_for_user_id, 'reason' => $request->input('reason')]);
            }
            return $user;
        });

        AuditLogger::log(
            action: 'user.created',
            actionType: 'create',
            entityType: 'user',
            entityId: $user->id,
            metadata: [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'admin_scope' => $user->admin_scope,
                'branch_id' => $user->branch_id,
            ],
            branchId: $user->branch_id
        );

        $returnTo = $request->input('return_to');
        if ($returnTo) {
            return redirect()->to($this->safeReturnTo($returnTo))->with('success', 'User created successfully.');
        }

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function createSystemAdmin()
    {
        abort_unless(auth()->user()?->isSystemAdmin(), 403);
        $branches = collect();
        $currentBranch = null;
        $privilegedAccount = true;
        $regularAccounts = User::where('is_active', true)->whereIn('role', ['staff', 'admin'])
            ->where(fn ($q) => $q->whereNull('admin_scope')->orWhere('admin_scope', 'branch'))
            ->whereNotIn('id', User::whereNotNull('privileged_for_user_id')->select('privileged_for_user_id'))
            ->orderBy('name')->get();
        return view('admin.users.create', compact('branches', 'currentBranch', 'privilegedAccount', 'regularAccounts'));
    }

    public function editRole(User $user)
    {
        abort_unless(auth()->user()?->isSystemAdmin() && auth()->id() !== $user->id && ($user->isStaff() || $user->isBranchAdmin()), 403);
        $branches = Branch::where('is_active', true)->orderBy('branch_name')->get();
        return view('admin.users.change-role', compact('user', 'branches'));
    }

    public function changeRole(Request $request, User $user)
    {
        abort_unless($request->user()->isSystemAdmin() && $request->user()->id !== $user->id, 403);
        $validated = $request->validate([
            'role' => ['required', Rule::in(['staff', 'branch_admin'])],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('is_active', true)],
            'current_password' => ['required', 'current_password:web'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'confirmed' => ['accepted'],
            'expected_role' => ['required', Rule::in(['staff', 'branch_admin'])],
        ]);
        DB::transaction(function () use ($user, $validated) {
            $target = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_unless($target->isStaff() || $target->isBranchAdmin(), 403);
            if (! Branch::whereKey($validated['branch_id'])->lockForUpdate()->first()?->is_active) {
                throw ValidationException::withMessages(['branch_id' => 'Select an active branch.']);
            }
            $oldRole = $target->isStaff() ? 'staff' : 'branch_admin';
            if ($oldRole !== $validated['expected_role']) throw ValidationException::withMessages(['role' => 'This account changed. Reload before continuing.']);
            if ($oldRole === $validated['role']) throw ValidationException::withMessages(['role' => 'Choose a different role. Use Edit User for branch assignment changes.']);
            $before = ['role' => $target->role, 'admin_scope' => $target->admin_scope, 'branch_id' => $target->branch_id];
            $target->role = $validated['role'] === 'staff' ? 'staff' : 'admin';
            $target->admin_scope = $validated['role'] === 'staff' ? null : 'branch';
            $target->branch_id = $validated['branch_id'];
            AccountSecurity::revoke($target);
            AccountSecurity::audit('user.role_changed', $target, ['before' => $before, 'after' => ['role' => $target->role, 'admin_scope' => $target->admin_scope, 'branch_id' => $target->branch_id], 'reason' => $validated['reason']]);
        });
        return redirect()->route('admin.users.edit', $user)->with('success', 'Role changed. The user must sign in again.');
    }

    public function edit(User $user)
    {
        $this->ensureManageableUser($user);

        $actor = auth()->user();

        if (! $actor) {
            abort(403, 'Unauthorized');
        }

        $user->load([
            'branch',
        ]);

        if ($actor->isSystemAdmin()) {
            $branches = Branch::query()
                ->where('is_active', true)
                ->orWhere('id', $user->branch_id)
                ->orderBy('branch_name')
                ->get();
        } elseif ($actor->role === 'admin') {
            $branches = Branch::where('id', $actor->branch_id)->get();
        } else {
            abort(403, 'Unauthorized');
        }

        $mainBranchId = (int) $this->resolveMainBranchId($actor);

        return view('admin.users.edit', compact(
            'user',
            'branches',
            'mainBranchId'
        ));
    }

    public function update(Request $request, User $user)
    {
        return DB::transaction(fn () => $this->updateLocked($request, User::whereKey($user->id)->lockForUpdate()->firstOrFail()));
    }

    private function updateLocked(Request $request, User $user)
    {
        $this->ensureManageableUser($user);
        abort_if($request->filled('password'), 422, 'Use the separate password reset action.');
        $this->trimUserInput($request);
        $this->hydrateSplitNameFromLegacyName($request);
        $this->normalizeRequestedRole($request);
        if ($request->input('role') !== $user->role || ($user->role === 'admin' && $request->input('admin_scope') !== $user->admin_scope && ! ($user->isSystemAdmin() && $request->input('admin_scope') === 'system'))) {
            throw ValidationException::withMessages(['role' => 'Use Change Role to change account privileges. System Admin requires a separate account.']);
        }
        if ($user->isSystemAdmin() && $request->input('email') !== $user->email) {
            $request->validate(['current_password' => ['required', 'current_password:web']]);
        }
        $this->ensureRoleAssignmentAllowed($request, $user);
        /**
         * The user update scope must be applied before validation to ensure that the correct validation rules are applied based on the target user's current role and the authenticated user's role.
         */
        $this->applyUserUpdateScope($request, $user);

        $validated = $request->validate($this->userValidationRules($request, $user), $this->userValidationMessages());
        $validated = $this->prepareValidatedUserAttributes($validated);

        $before = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'admin_scope' => $user->admin_scope,
            'branch_id' => $user->branch_id,
        ];

        $isManagedSystemAdmin = $user->isSystemAdmin();

        $updateAttributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $isManagedSystemAdmin ? 'admin' : $validated['role'],
            'branch_id' => $isManagedSystemAdmin ? null : ($validated['branch_id'] ?? null),
            'contact_number' => $validated['contact_number'] ?? null,
            'address' => $validated['address'] ?? null,
        ];

        foreach (['first_name', 'middle_name', 'last_name', 'suffix'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                $updateAttributes[$column] = $validated[$column] ?? null;
            }
        }

        if (Schema::hasColumn('users', 'admin_scope')) {
            $updateAttributes['admin_scope'] = $isManagedSystemAdmin
                ? 'system'
                : ($validated['role'] === 'admin' ? (string) $request->input('admin_scope', 'branch') : null);
        }

        DB::transaction(function () use ($user, $updateAttributes) {
            $beforeFingerprint = AccountSecurity::fingerprint($user);
            if ($user->email !== $updateAttributes['email']) $user->email_verified_at = null;
            $user->update($updateAttributes);
            if ($beforeFingerprint !== AccountSecurity::fingerprint($user)) AccountSecurity::revoke($user);
        });

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
                    'admin_scope' => $user->admin_scope,
                    'branch_id' => $user->branch_id,
                ],
            ],
            branchId: $user->branch_id
        );

        $returnTo = $request->input('return_to');
        if ($returnTo) {
            return redirect()->to($this->safeReturnTo($returnTo))->with('success', 'User updated successfully.');
        }

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function toggleActive(User $user)
    {
        $this->ensureManageableUser($user);
        if (auth()->id() === $user->id) return back()->with('error', "You can't deactivate your own account.");

        DB::transaction(function () use ($user) {
            User::where('role', 'admin')->orderBy('id')->lockForUpdate()->get();
            $target = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $this->ensureManageableUser($target);
            if ($target->isSystemAdmin() && $target->is_active && User::where('is_active', true)->where('role', 'admin')->get()->filter(fn ($admin) => $admin->isSystemAdmin())->count() <= 1) {
                throw ValidationException::withMessages(['status' => 'The last active System Admin cannot be deactivated.']);
            }
            $before = $target->is_active;
            $target->is_active = ! $before;
            AccountSecurity::revoke($target);
            AccountSecurity::audit($target->is_active ? 'user.activated' : 'user.deactivated', $target, ['from' => $before ? 'active' : 'inactive', 'to' => $target->is_active ? 'active' : 'inactive']);
        });
        $user->refresh();
        return back()->with('success', sprintf('%s is now %s.', $user->name, $user->is_active ? 'active' : 'inactive'));
    }
    public function resetPassword(Request $request, User $user)
    {
        $this->ensureManageableUser($user);
        abort_unless($user->isStaff() || $user->isBranchAdmin(), 403, 'Use the designated System Admin recovery process.');

        $validated = $request->validate([
            'current_password' => ['required', 'current_password:web'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'password' => ['required', 'string', 'max:128', 'confirmed', \Illuminate\Validation\Rules\Password::min(12)->letters()->numbers()],
        ]);

        try {
        DB::transaction(function () use ($user, $validated) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $this->ensureManageableUser($user);
            abort_unless($user->isStaff() || $user->isBranchAdmin(), 403);
            if (Hash::check($validated['password'], $user->password)) throw ValidationException::withMessages(['password' => 'Choose a different temporary password.']);
            $user->password = Hash::make($validated['password']);
            $user->must_change_password = true;
            AccountSecurity::revoke($user);
            \Illuminate\Support\Facades\Password::deleteToken($user);
            AccountSecurity::audit('user.password_reset', $user, ['reason' => trim($validated['reason'])]);
        });
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception) {
            throw $exception;
        } catch (\Illuminate\Database\QueryException|\RuntimeException $exception) {
            if ($request->expectsJson()) return response()->json(['message' => 'Password reset unsuccessful. No changes were saved. Please try again or contact your System Admin.'], 500);
            return back()->withInput($request->only('reason', 'password_reset_action'))
                ->withErrors(['password_reset' => 'Password reset unsuccessful. No changes were saved. Please try again or contact your System Admin.']);
        }

        $message = 'Password reset successful. Share the temporary password privately. The user must change it after sign-in and MFA.';
        if ($request->expectsJson()) {
            $request->session()->flash('success', $message);
            return response()->json(['message' => $message]);
        }
        return back()->with('success', $message);
    }

    private function safeReturnTo(mixed $value): string
    {
        if (! is_string($value)) return route('admin.users.index');
        $base = rtrim(url('/'), '/');
        return str_starts_with($value, $base.'/admin/users') && ! str_contains($value, "\r") && ! str_contains($value, "\n")
            ? $value : route('admin.users.index');
    }

    private function userValidationRules(Request $request, ?User $user = null): array
    {
        $role = (string) $request->input('role');
        $hasSplitNames = Schema::hasColumn('users', 'first_name') && Schema::hasColumn('users', 'last_name');
        $hasMiddleName = Schema::hasColumn('users', 'middle_name');
        $hasSuffix = Schema::hasColumn('users', 'suffix');

        $rules = [
            // DNS validation is not full verification; Laravel email verification can be enabled later using email_verified_at once mail is configured.
            'email' => [
                'required',
                $this->validEmailAddressRule(),
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:6'],
            'role' => ['required', Rule::in(['admin', 'staff'])],
            'branch_id' => [(string) $request->input('admin_scope') === 'system' ? 'nullable' : 'required', 'exists:branches,id'],
            'contact_number' => ['nullable', 'string', 'max:50', $this->validPhilippineMobileRule()],
            'address' => ['nullable', 'string', 'max:255', $this->notBlankRule('Address must not be blank.')],
        ];

        if ($hasSplitNames) {
            $rules['first_name'] = ['required', 'string', 'max:100', $this->validNamePartRule('First name')];
            $rules['last_name'] = ['required', 'string', 'max:100', $this->validNamePartRule('Last name'), $this->uniqueNamePartsRule($request)];

            if ($hasMiddleName) {
                $rules['middle_name'] = ['nullable', 'string', 'max:100', $this->validNamePartRule('Middle name')];
            }

            if ($hasSuffix) {
                $rules['suffix'] = ['nullable', Rule::in($this->validSuffixes())];
            }
        } else {
            $rules['name'] = ['required', 'string', 'max:255', $this->validNamePartRule('Name')];
        }

        return $rules;
    }

    private function userValidationMessages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'name.required' => 'Name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.rfc' => 'Please enter a valid email address.',
            'email.dns' => 'The email domain appears to be invalid.',
            'email.unique' => 'This email address is already taken.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 6 characters.',
            'role.required' => 'Role is required.',
            'role.in' => 'Select a valid role.',
            'branch_id.required' => 'Branch is required for staff and branch admin accounts.',
            'branch_id.exists' => 'Select a valid branch.',
            'suffix.in' => 'Select a valid suffix.',
        ];
    }

    private function validEmailAddressRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            $email = (string) $value;

            if (Validator::make([$attribute => $email], [$attribute => 'email:rfc'])->fails()) {
                $fail('Please enter a valid email address.');
                return;
            }

            if (Validator::make([$attribute => $email], [$attribute => 'email:rfc,dns'])->fails()) {
                $fail('The email domain appears to be invalid.');
            }
        };
    }

    private function trimUserInput(Request $request): void
    {
        $trimmed = [];

        foreach (['name', 'first_name', 'middle_name', 'last_name', 'suffix', 'email', 'contact_number', 'address'] as $field) {
            if ($request->has($field)) {
                $value = $request->input($field);
                $trimmed[$field] = is_string($value) ? trim(preg_replace('/\s+/', ' ', $value)) : $value;
            }
        }

        if ($trimmed !== []) {
            $request->merge($trimmed);
        }
    }

    private function hydrateSplitNameFromLegacyName(Request $request): void
    {
        if (! Schema::hasColumn('users', 'first_name') || ! Schema::hasColumn('users', 'last_name')) {
            return;
        }

        if ($request->filled('first_name') || $request->filled('last_name') || ! $request->filled('name')) {
            return;
        }

        $parts = User::parseFullName((string) $request->input('name'));
        $payload = [
            'first_name' => $parts['first_name'] ?? null,
            'last_name' => $parts['last_name'] ?? null,
        ];

        if (Schema::hasColumn('users', 'middle_name')) {
            $payload['middle_name'] = $parts['middle_name'] ?? null;
        }

        if (Schema::hasColumn('users', 'suffix')) {
            $payload['suffix'] = $parts['suffix'] ?? null;
        }

        $request->merge($payload);
    }

    private function prepareValidatedUserAttributes(array $validated): array
    {
        if (array_key_exists('first_name', $validated) && array_key_exists('last_name', $validated)) {
            $validated['name'] = User::buildFullName(
                $validated['first_name'] ?? null,
                $validated['middle_name'] ?? null,
                $validated['last_name'] ?? null,
                $validated['suffix'] ?? null
            );
        }

        return $validated;
    }

    private function validNamePartRule(string $field): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($field): void {
            if ($value === null || $value === '') {
                return;
            }

            $value = trim((string) $value);

            if ($value === '') {
                $fail("{$field} must not be blank.");
                return;
            }

            if (preg_match('/\d/u', $value)) {
                $fail("{$field} must not contain numbers.");
                return;
            }

            if (! preg_match("/^[\pL\pM\s.'-]+$/u", $value)) {
                $fail("{$field} may only contain letters, spaces, hyphen, apostrophe, period, ñ, Ñ, and accented letters.");
            }
        };
    }

    private function uniqueNamePartsRule(Request $request): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
            $parts = [];

            foreach (['first_name', 'middle_name', 'last_name', 'suffix'] as $field) {
                $part = trim((string) $request->input($field, ''));
                if ($part !== '') {
                    $parts[] = mb_strtolower($part);
                }
            }

            if (count($parts) !== count(array_unique($parts))) {
                $fail('Name parts must not be exact duplicates.');
            }
        };
    }

    private function notBlankRule(string $message): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($message): void {
            if ($value !== null && trim((string) $value) === '') {
                $fail($message);
            }
        };
    }

    private function validPhilippineMobileRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || trim((string) $value) === '') {
                return;
            }

            $normalized = preg_replace('/[\s()\-]/', '', (string) $value);

            if (! preg_match('/^(\+639|639|09)\d{9}$/', (string) $normalized)) {
                $fail('Enter a valid Philippine mobile number.');
            }
        };
    }

    private function validSuffixes(): array
    {
        return ['Jr.', 'Sr.', 'II', 'III', 'IV', 'V'];
    }

    private function resolveMainBranchId(User $actor): ?int
    {
        if ($actor->branch_id !== null) {
            return (int) $actor->branch_id;
        }

        if (Schema::hasColumn('branches', 'branch_type')) {
            $typedMainBranchId = Branch::where('branch_type', 'main')->value('id');
            if ($typedMainBranchId !== null) {
                return (int) $typedMainBranchId;
            }
        }

        $legacyMainBranchId = Branch::where('branch_code', 'BR001')->value('id');

        return $legacyMainBranchId === null ? null : (int) $legacyMainBranchId;
    }
    private function ensureRoleAssignmentAllowed(Request $request, ?User $targetUser = null): void
    {
        if ((string) $request->input('role') === 'owner') {
            throw ValidationException::withMessages([
                'role' => 'Unauthorized to create owner accounts.',
            ]);
        }

        if (in_array((string) $request->input('admin_scope'), ['main', 'all_branches'], true)) {
            throw ValidationException::withMessages([
                'admin_scope' => 'Legacy global administrator scopes cannot be assigned.',
            ]);
        }

        if ((string) $request->input('admin_scope') === 'system'
            && (! $request->attributes->get('requested_system_admin') || ! $request->user()?->isSystemAdmin())) {
            throw ValidationException::withMessages([
                'role' => 'Only a System Admin can assign System Admin access.',
            ]);
        }

        if ($targetUser?->isSystemAdmin()) {
            if ((string) $request->input('role', 'admin') !== 'admin') {
                throw ValidationException::withMessages([
                    'role' => 'System Admin access cannot be downgraded here.',
                ]);
            }

            if ($request->filled('admin_scope') && (string) $request->input('admin_scope') !== 'system') {
                throw ValidationException::withMessages([
                    'role' => 'System Admin access cannot be reassigned here.',
                ]);
            }
        }
    }

    private function normalizeRequestedRole(Request $request): void
    {
        $requestedRole = (string) $request->input('role');

        if ($requestedRole === 'system_admin') {
            $request->attributes->set('requested_system_admin', true);
            $request->merge([
                'role' => 'admin',
                'admin_scope' => 'system',
                'branch_id' => null,
            ]);

            return;
        }

        if ($requestedRole === 'branch_admin') {
            $request->merge([
                'role' => 'admin',
                'admin_scope' => 'branch',
            ]);

            return;
        }

        if ($requestedRole === 'admin' && ! $request->filled('admin_scope')) {
            $request->merge(['admin_scope' => 'branch']);
        }

        if ($requestedRole === 'staff') {
            $request->merge(['admin_scope' => null]);
        }
    }
    /**
     * Apply the user creation scope based on the authenticated user's role.
     */
    private function applyUserCreationScope(Request $request): void
{
    /** @var \App\Models\User|null $actor */
    $actor = $request->user();

    if (! $actor) {
        abort(403, 'Unauthorized');
    }

    $targetRole = (string) $request->input('role');

    if ($actor->isSystemAdmin()) {
        if ($targetRole === 'admin' && (string) $request->input('admin_scope') === 'system') {
            $request->merge(['branch_id' => null]);

            return;
        }

        if ($targetRole === 'admin') {
            if (! $request->filled('branch_id')) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Branch is required for branch admin accounts.',
                ]);
            }

            $this->ensureBranchAdminCanUseBranch($request);

            return;
        }

        if ($targetRole === 'staff') {
            if (! $request->filled('branch_id')) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Branch is required for staff accounts.',
                ]);
            }

            $this->ensureAssignableBranchIsActive((int) $request->input('branch_id'));

            return;
        }

        throw ValidationException::withMessages([
            'role' => 'Unauthorized user role.',
        ]);
    }

    if ($actor->role === 'admin') {
        if ($targetRole !== 'staff') {
            throw ValidationException::withMessages([
                'role' => 'Branch admins can only create staff accounts.',
            ]);
        }

        $request->merge([
            'branch_id' => $actor->branch_id,
        ]);

        return;
    }

    throw ValidationException::withMessages([
        'role' => 'You are not allowed to create user accounts.',
    ]);
}

private function applyUserUpdateScope(Request $request, User $targetUser): void
{
    /** @var \App\Models\User|null $actor */
    $actor = $request->user();

    if (! $actor) {
        abort(403, 'Unauthorized');
    }

    $targetRole = (string) $request->input('role');

    if ($actor->isSystemAdmin()) {
        if ($targetUser->isSystemAdmin()) {
            $request->merge([
                'role' => 'admin',
                'branch_id' => null,
                'admin_scope' => 'system',
            ]);

            return;
        }

        if ($targetRole === 'admin' && (string) $request->input('admin_scope') === 'system') {
            $request->merge(['branch_id' => null]);

            return;
        }

        // IMPORTANT: this must be BEFORE the staff branch auto-assign block
        if ($targetUser->role === 'admin' && $targetUser->admin_scope === 'branch' && $targetRole !== 'admin') {
            throw ValidationException::withMessages([
                'role' => 'Branch Admin accounts cannot be changed into Staff accounts.',
            ]);
        }

        if ($targetRole === 'admin') {
            if (! $request->filled('branch_id')) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Branch is required for branch admin accounts.',
                ]);
            }

            $this->ensureBranchAdminCanUseBranch($request, $targetUser);

            return;
        }

        if ($targetRole === 'staff') {
            if (! $request->filled('branch_id')) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Branch is required for staff accounts.',
                ]);
            }

            $this->ensureAssignableBranchIsActive((int) $request->input('branch_id'), $targetUser);

            return;
        }

        throw ValidationException::withMessages([
            'role' => 'Unauthorized user role.',
        ]);
    }

    if ($actor->role === 'admin') {
        if ($targetUser->role !== 'staff') {
            throw ValidationException::withMessages([
                'role' => 'Branch admins can only manage staff accounts.',
            ]);
        }

        if ((int) $targetUser->branch_id !== (int) $actor->branch_id) {
            abort(403, 'Unauthorized');
        }

        if ($targetRole !== 'staff') {
            throw ValidationException::withMessages([
                'role' => 'Branch admins cannot change staff accounts into admin accounts.',
            ]);
        }

        $request->merge([
            'role' => 'staff',
            'branch_id' => $actor->branch_id,
        ]);

        return;
    }

    abort(403, 'Unauthorized');
}

private function ensureBranchAdminCanUseBranch(Request $request, ?User $targetUser = null): void
{
    /** @var \App\Models\User|null $actor */
    $actor = $request->user();

    if (! $actor || ! $actor->isSystemAdmin()) {
        abort(403, 'Unauthorized');
    }

    $branchId = (int) $request->input('branch_id');
    $this->ensureAssignableBranchIsActive($branchId, $targetUser);
}

private function ensureAssignableBranchIsActive(int $branchId, ?User $targetUser = null): void
{
    $isCurrentAssignment = $targetUser && (int) $targetUser->branch_id === $branchId;

    if (! $isCurrentAssignment && ! Branch::whereKey($branchId)->where('is_active', true)->exists()) {
        throw ValidationException::withMessages([
            'branch_id' => 'Select an active branch.',
        ]);
    }
}
   private function ensureManageableUser(User $user): void
{
    if ($user->isOwner()) {
        abort(403, 'Unauthorized');
    }

    $actor = auth()->user();

    if (! $actor) {
        abort(403, 'Unauthorized');
    }

    if ($actor->isSystemAdmin()) {
        return;
    }

    if ($actor->role === 'admin') {
        if ($user->role === 'staff' && (int) $user->branch_id === (int) $actor->branch_id) {
            return;
        }

        abort(403, 'Unauthorized');
    }

    abort(403, 'Unauthorized');
}
}
