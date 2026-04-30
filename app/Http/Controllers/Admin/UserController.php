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
    public function index()
    {
        $users = User::where('role', '!=', 'owner')
            ->with('branch')
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
        $this->trimUserInput($request);
        $this->hydrateSplitNameFromLegacyName($request);
        $this->ensureRoleAssignmentAllowed($request);

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

        $user->load([
            'branch',
        ]);

        $branches = Branch::orderBy('branch_name')->get();
        return view('admin.users.edit', compact('user', 'branches'));
    }

    public function update(Request $request, User $user)
    {
        $this->ensureManageableUser($user);
        $this->trimUserInput($request);
        $this->hydrateSplitNameFromLegacyName($request);
        $this->ensureRoleAssignmentAllowed($request, $user);

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

    private function ensureManageableUser(User $user): void
    {
        if ($user->isOwner()) {
            abort(403, 'Unauthorized');
        }
    }
}
