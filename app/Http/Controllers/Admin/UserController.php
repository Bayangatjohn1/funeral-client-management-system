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
        'q' => ['nullable', 'string', 'max:100'],
        'role' => ['nullable', Rule::in(['admin', 'staff'])],
        'status' => ['nullable', Rule::in(['active', 'inactive'])],
        'sort' => ['nullable', Rule::in(['latest', 'name_asc', 'role_asc', 'branch_asc'])],
    ]);

    $query = User::where('role', '!=', 'owner')
        ->with('branch');

    if ($actor->isMainBranchAdmin()) {
        $query->where(function ($q) use ($actor) {
            $q->where(function ($adminQuery) use ($actor) {
                $adminQuery->where('role', 'admin')
                    ->where('admin_scope', 'branch')
                    ->where('branch_id', '!=', $actor->branch_id);
            })->orWhere(function ($staffQuery) use ($actor) {
                $staffQuery->where('role', 'staff')
                    ->where('branch_id', $actor->branch_id);
            });
        });
    } elseif ($actor->role === 'admin') {
        $query->where('role', 'staff')
            ->where('branch_id', $actor->branch_id);
    } else {
        abort(403, 'Unauthorized');
    }

    $query
        ->when($validated['q'] ?? null, function ($q, string $search) {
            $q->where(function ($searchQuery) use ($search) {
                $searchQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhere('contact_number', 'like', "%{$search}%")
                    ->orWhereHas('branch', fn ($branchQuery) => $branchQuery->where('branch_name', 'like', "%{$search}%"));
            });
        })
        ->when($validated['role'] ?? null, fn ($q, string $role) => $q->where('role', $role))
        ->when($validated['status'] ?? null, fn ($q, string $status) => $q->where('is_active', $status === 'active'));

    match ($validated['sort'] ?? 'latest') {
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
        ->paginate(20)
        ->withQueryString();

    return view('admin.users.index', compact('users'));
}
    public function create()
{
    $actor = auth()->user();

    if (! $actor) {
        abort(403, 'Unauthorized');
    }

    $branchesWithActiveBranchAdmin = User::where('role', 'admin')
        ->where('admin_scope', 'branch')
        ->where('is_active', true)
        ->whereNotNull('branch_id')
        ->pluck('branch_id')
        ->map(fn ($id) => (int) $id)
        ->all();

    if ($actor->isMainBranchAdmin()) {
        $branches = Branch::where('id', '!=', $actor->branch_id)
            ->orderBy('branch_name')
            ->get();
    } elseif ($actor->role === 'admin') {
        $branches = Branch::where('id', $actor->branch_id)->get();
    } else {
        abort(403, 'Unauthorized');
    }

    return view('admin.users.create', compact('branches', 'branchesWithActiveBranchAdmin'));
}
    public function store(Request $request)
    {
        $this->trimUserInput($request);
        $this->hydrateSplitNameFromLegacyName($request);
        $this->ensureRoleAssignmentAllowed($request);
        $this->applyUserCreationScope($request);
        
        $validated = $request->validate($this->userValidationRules($request), $this->userValidationMessages());
        $validated = $this->prepareValidatedUserAttributes($validated);

        $userAttributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'branch_id' => $validated['branch_id'],
            'contact_number' => $validated['contact_number'] ?? null,
            'position' => $validated['position'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => true,
        ];

        foreach (['first_name', 'middle_name', 'last_name', 'suffix'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                $userAttributes[$column] = $validated[$column] ?? null;
            }
        }

        if (Schema::hasColumn('users', 'admin_scope')) {
            $userAttributes['admin_scope'] = $validated['role'] === 'admin' ? 'branch' : null;
        }
        if (Schema::hasColumn('users', 'created_by')) {
            $userAttributes['created_by'] = $request->user()->id;
        }
        $user = User::create($userAttributes);

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
            return redirect()->to($returnTo)->with('success', 'User created successfully.');
        }

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
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

        $branchesWithActiveBranchAdmin = User::where('role', 'admin')
            ->where('admin_scope', 'branch')
            ->where('is_active', true)
            ->whereNotNull('branch_id')
            ->where('id', '!=', $user->id)
            ->pluck('branch_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($actor->isMainBranchAdmin()) {
            $branches = Branch::orderBy('branch_name')->get();
        } elseif ($actor->role === 'admin') {
            $branches = Branch::where('id', $actor->branch_id)->get();
        } else {
            abort(403, 'Unauthorized');
        }

        $mainBranchId = (int) $actor->branch_id;

        return view('admin.users.edit', compact(
            'user',
            'branches',
            'branchesWithActiveBranchAdmin',
            'mainBranchId'
        ));
    }

    public function update(Request $request, User $user)
    {
        $this->ensureManageableUser($user);
        $this->trimUserInput($request);
        $this->hydrateSplitNameFromLegacyName($request);
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

        $isManagedMainAdmin = $user->isMainBranchAdmin();

        $updateAttributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $isManagedMainAdmin ? 'admin' : $validated['role'],
            'branch_id' => $isManagedMainAdmin ? $user->branch_id : $validated['branch_id'],
            'contact_number' => $validated['contact_number'] ?? null,
            'position' => $validated['position'] ?? null,
            'address' => $validated['address'] ?? null,
        ];

        foreach (['first_name', 'middle_name', 'last_name', 'suffix'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                $updateAttributes[$column] = $validated[$column] ?? null;
            }
        }

        if (Schema::hasColumn('users', 'admin_scope')) {
            $updateAttributes['admin_scope'] = $isManagedMainAdmin ? 'main' : ($validated['role'] === 'admin' ? 'branch' : null);
        }

        $user->update($updateAttributes);

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
            return redirect()->to($returnTo)->with('success', 'User updated successfully.');
        }

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function toggleActive(User $user)
    {
        $this->ensureManageableUser($user);

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
        $this->ensureManageableUser($user);

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
            'branch_id' => ['required', 'exists:branches,id'],
            'contact_number' => ['nullable', 'string', 'max:50', $this->validPhilippineMobileRule()],
            'position' => ['nullable', 'string', 'max:100', Rule::in($this->positionOptionsForRole($role))],
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
            'position.in' => 'Select a valid position for this role.',
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

        foreach (['name', 'first_name', 'middle_name', 'last_name', 'suffix', 'email', 'contact_number', 'position', 'address'] as $field) {
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

    private function positionOptionsForRole(string $role): array
    {
        return match ($role) {
            'admin' => ['Branch Admin', 'Branch Manager', 'Office Admin'],
            'staff' => ['Staff', 'Encoder', 'Cashier', 'Branch Staff', 'Funeral Assistant'],
            default => ['Staff', 'Encoder', 'Cashier', 'Branch Staff', 'Funeral Assistant', 'Branch Admin', 'Branch Manager', 'Office Admin'],
        };
    }

    private function validSuffixes(): array
    {
        return ['Jr.', 'Sr.', 'II', 'III', 'IV', 'V'];
    }
    private function ensureRoleAssignmentAllowed(Request $request, ?User $targetUser = null): void
    {
        if ((string) $request->input('role') === 'owner') {
            throw ValidationException::withMessages([
                'role' => 'Unauthorized to create owner accounts.',
            ]);
        }

        if ((string) $request->input('admin_scope') === 'main') {
            throw ValidationException::withMessages([
                'admin_scope' => 'Unauthorized to assign main branch administrator access.',
            ]);
        }

        if ($targetUser?->isMainBranchAdmin()) {
            if ((string) $request->input('role', 'admin') !== 'admin') {
                throw ValidationException::withMessages([
                    'role' => 'Unauthorized to modify main branch administrator access.',
                ]);
            }

            if ($request->filled('admin_scope') && (string) $request->input('admin_scope') !== 'main') {
                throw ValidationException::withMessages([
                    'admin_scope' => 'Unauthorized to modify main branch administrator access.',
                ]);
            }
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

    if ($actor->isMainBranchAdmin()) {
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

    if ($actor->isMainBranchAdmin()) {
        if ($targetUser->isMainBranchAdmin()) {
            $request->merge([
                'role' => 'admin',
                'branch_id' => $targetUser->branch_id,
                'admin_scope' => 'main',
            ]);

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

    if (! $actor || ! $actor->isMainBranchAdmin()) {
        abort(403, 'Unauthorized');
    }

    $branchId = (int) $request->input('branch_id');

    if ($branchId === (int) $actor->branch_id) {
        throw ValidationException::withMessages([
            'branch_id' => 'Branch Admin cannot be assigned to the Main Branch.',
        ]);
    }

    $branchAlreadyHasAdmin = User::where('role', 'admin')
        ->where('admin_scope', 'branch')
        ->where('branch_id', $branchId)
        ->where('is_active', true)
        ->when($targetUser, function ($query) use ($targetUser) {
            $query->where('id', '!=', $targetUser->id);
        })
        ->exists();

    if ($branchAlreadyHasAdmin) {
        throw ValidationException::withMessages([
            'branch_id' => 'This branch already has an active Branch Admin.',
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

    if ($actor->isMainBranchAdmin()) {
        if ($user->isMainBranchAdmin()) {
            return;
        }

        if ($user->role === 'admin' && $user->admin_scope === 'branch') {
            return;
        }

        if ($user->role === 'staff' && (int) $user->branch_id === (int) $actor->branch_id) {
            return;
        }

        abort(403, 'Unauthorized');
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
